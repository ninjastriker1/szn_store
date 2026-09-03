<?php
session_start();

// Define base URL for the application
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base === '.' || $base === '\\' || $base === '/') {
    $base = '';
}
define('BASE_URL', $base);

require_once 'config/database.php';
require_once 'config/security.php';
require_once 'includes/functions.php';
require_once 'includes/csrf.php';
require_once 'cache.php';
require_once 'cache_queries.php';

// Handle cart actions via AJAX (JSON response) - must be before any output
if (isset($_POST['action']) && in_array($_POST['action'], ['update_quantity', 'remove_item'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    
    if ($action === 'update_quantity' && isset($_POST['key']) && isset($_POST['quantity'])) {
        $key = $_POST['key'];
        $quantity = (int)$_POST['quantity'];
        
        if ($quantity <= 0) {
            remove_from_cart($key);
        } else {
            update_cart_quantity($key, $quantity);
        }
    } elseif ($action === 'remove_item' && isset($_POST['key'])) {
        remove_from_cart($_POST['key']);
    }
    
    // Return JSON response
    $cart_items = get_cart_items($pdo);
    $totals = get_cart_totals($cart_items);
    echo json_encode([
        'success' => true,
        'cart_count' => get_cart_count(),
        'cart_items' => $cart_items,
        'totals' => $totals
    ]);
    exit;
}

// Handle regular add to cart (with redirect)
if (isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    if (isset($_POST['product_id'])) {
        $size = isset($_POST['size']) ? $_POST['size'] : null;
        add_to_cart((int)$_POST['product_id'], 1, $size);
        // Redirect to cart page to see the added product
        header('Location: index.php?page=cart');
        exit;
    }
}

// Handle wishlist AJAX requests
if (isset($_GET['ajax']) && $_GET['ajax'] === 'wishlist' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Please login to add to wishlist']);
        exit;
    }
    
    $action = isset($_POST['action']) ? $_POST['action'] : 'add';
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    
    if ($product_id === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product']);
        exit;
    }
    
    try {
        if ($action === 'add') {
            // Check if already in wishlist
            $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$_SESSION['user_id'], $product_id]);
            
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Already in wishlist!']);
            } else {
                $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
                $stmt->execute([$_SESSION['user_id'], $product_id]);
                echo json_encode(['success' => true, 'message' => 'Added to wishlist!']);
            }
        } elseif ($action === 'remove') {
            $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$_SESSION['user_id'], $product_id]);
            echo json_encode(['success' => true, 'message' => 'Removed from wishlist!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle profile updates (must be before header output)
if (isset($_POST['update_profile'])) {
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $email = sanitize($_POST['email']);
    
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $_SESSION['error'] = "First name, last name, and email are required";
    } elseif (strlen($first_name) < 2 || strlen($last_name) < 2) {
        $_SESSION['error'] = "First and last name must be at least 2 characters each";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Please enter a valid email address";
    } else {
        try {
            // Check if email is already taken by another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                $_SESSION['error'] = "This email is already in use by another account";
            } else {
                $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
                $stmt->execute([$first_name, $last_name, $email, $_SESSION['user_id']]);
                
                $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                $_SESSION['user_email'] = $email;
                $_SESSION['success'] = "Profile updated successfully!";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Failed to update profile: " . $e->getMessage();
        }
    }
    
    header("Location: " . base_url('index.php?page=profile'));
    exit;
}

// Handle address updates (must be before header output)
if (isset($_POST['update_address'])) {
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $address2 = sanitize($_POST['address2'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $zip = sanitize($_POST['zip'] ?? '');
    $country = sanitize($_POST['country'] ?? '');
    
    if (empty($phone) || empty($address) || empty($city) || empty($zip) || empty($country)) {
        $_SESSION['error'] = "Please fill in all required address fields";
    } else {
        try {
            // Debug: log the values
            error_log("Saving address - phone: $phone, address: $address, city: $city, zip: $zip, country: $country");
            
            $stmt = $pdo->prepare("UPDATE users SET phone = ?, address = ?, address2 = ?, city = ?, zip = ?, country = ? WHERE id = ?");
            $result = $stmt->execute([
                $phone,
                $address,
                $address2,
                $city,
                $zip,
                $country,
                $_SESSION['user_id']
            ]);
            
            if ($result) {
                $_SESSION['success'] = "Address saved successfully!";
                error_log("Address update successful for user " . $_SESSION['user_id']);
            } else {
                $_SESSION['error'] = "Failed to save address (update returned false)";
                error_log("Address update failed for user " . $_SESSION['user_id']);
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Failed to save address: " . $e->getMessage();
            error_log("Address save error: " . $e->getMessage());
        }
    }
    
    header("Location: " . base_url('index.php?page=profile'));
    exit;
}
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$page_file = "pages/{$page}.php";

// Set Page Titles
$page_titles = [
    'home' => 'Home',
    'shop' => 'Shop',
    'categories' => 'Categories',
    'cart' => 'My Cart',
    'checkout' => 'Checkout',
    'login' => 'Login',
    'register' => 'Create Account',
    'profile' => 'My Profile',
    'wishlist' => 'My Wishlist',
    'orders' => 'Order History',
    'about' => 'About Us',
    'contact' => 'Contact Us',
    'submit_product' => 'Submit Product'
];

$page_title = $page_titles[$page] ?? ucfirst($page);

// Special case: Product page title
if ($page === 'product' && isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $prod_name = $stmt->fetchColumn();
        if ($prod_name) {
            $page_title = $prod_name;
        }
    } catch (PDOException $e) {
        // Fallback to "Product" if query fails
    }
}

// Redirect to login if accessing profile without being logged in
if ($page === 'profile' && !isset($_SESSION['user_id'])) {
    header('Location: ' . base_url('index.php?page=login'));
    exit;
}

// Redirect admins away from the user profile page
if ($page === 'profile' && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header('Location: ' . base_url('admin/dashboard.php'));
    exit;
}

// Basic security check to prevent directory traversal
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $page)) {
    $page = 'home';
    $page_file = 'pages/home.php';
}

if (!file_exists($page_file)) {
    $page_file = 'pages/404.php';
}

include 'includes/header.php';

if (file_exists($page_file)) {
    include $page_file;
} else {
    echo "<div class='py-32 text-center text-xl'>404 - Page Not Found</div>";
}

include 'includes/footer.php';
?>
