<?php
// helpers and common functions

if (!defined('BASE_URL')) {
    $script_dir = dirname($_SERVER['SCRIPT_NAME']);
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http';
    $base_path = rtrim($script_dir, '/\\');
    
    // Remove common subdirs to get project root
    $base_path = preg_replace('#/(admin|includes)/?$#', '', $base_path);
    
    define('BASE_URL', $protocol . '://' . $_SERVER['SERVER_NAME'] . $base_path);
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// return application base URL (script directory) optionally appending a path
function base_url($path = '') {
    if ($path !== '') {
        // ensure single slash between base and path
        return BASE_URL . '/' . ltrim($path, '/\\');
    }
    return BASE_URL;
}

function redirect($page) {
    header("Location: " . base_url('index.php?page=' . $page));
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'admin';
}

// ==================== CART FUNCTIONS ====================

// Initialize cart in session
function init_cart() {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

// Add item to cart
function add_to_cart($product_id, $quantity = 1, $size = null, $pdo_connection = null) {
    global $pdo;
    $db = $pdo_connection ?: $pdo ?? null;
    
    init_cart();
    
    $product_id = (int)$product_id;
    $quantity = (int)$quantity;
    
    if ($product_id <= 0) {
        return false;
    }
    
    // Get product stock per size
    $stock = 0;
    if ($db) {
        try {
            if ($size) {
                $stmt = $db->prepare("SELECT stock FROM product_size_stock WHERE product_id = ? AND size = ?");
                $stmt->execute([$product_id, $size]);
                $sizeStock = $stmt->fetchColumn();
                
                if ($sizeStock !== false) {
                    $stock = (int)$sizeStock;
                } else {
                    // Fallback to global stock if size not found in stock table
                    $stmt = $db->prepare("SELECT stock FROM products WHERE id = ?");
                    $stmt->execute([$product_id]);
                    $stock = (int)$stmt->fetchColumn();
                }
            } else {
                $stmt = $db->prepare("SELECT stock FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $stock = (int)$stmt->fetchColumn();
            }
        } catch (PDOException $e) {
            $stock = 0;
        }
    }
    
    // Generate unique key for cart item (include size in key for different sizes)
    $key = 'item_' . $product_id . '_' . ($size ?: 'default');
    
    $current_qty = isset($_SESSION['cart'][$key]) ? $_SESSION['cart'][$key]['quantity'] : 0;
    $new_qty = $current_qty + $quantity;
    
    if ($new_qty > $stock) {
        $quantity = $stock - $current_qty;
        if ($quantity <= 0) return false;
    }
    
    if (isset($_SESSION['cart'][$key])) {
        $_SESSION['cart'][$key]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$key] = [
            'product_id' => $product_id,
            'quantity' => $quantity,
            'size' => $size
        ];
    }
    
    return true;
}

// Remove item from cart
function remove_from_cart($key) {
    init_cart();
    
    if (isset($_SESSION['cart'][$key])) {
        unset($_SESSION['cart'][$key]);
        return true;
    }
    return false;
}

// Update cart item quantity
function update_cart_quantity($key, $quantity) {
    global $pdo;
    if (!isset($pdo)) return false;

    init_cart();
    
    $quantity = (int)$quantity;
    
    if ($quantity <= 0) {
        return remove_from_cart($key);
    }
    
    // Extract product_id from the key to check stock
    $parts = explode('_', $key);
    $product_id = isset($parts[1]) ? (int)$parts[1] : 0;
    
    $item_size = isset($_SESSION['cart'][$key]['size']) ? $_SESSION['cart'][$key]['size'] : null;
    
    if ($pdo && $product_id > 0) {
        try {
            $stock = 0;
            if ($item_size) {
                $stmt = $pdo->prepare("SELECT stock FROM product_size_stock WHERE product_id = ? AND size = ?");
                $stmt->execute([$product_id, $item_size]);
                $sizeStock = $stmt->fetchColumn();
                
                if ($sizeStock !== false) {
                    $stock = (int)$sizeStock;
                } else {
                    $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
                    $stmt->execute([$product_id]);
                    $stock = (int)$stmt->fetchColumn();
                }
            } else {
                $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $stock = (int)$stmt->fetchColumn();
            }
            
            if ($quantity > $stock) {
                $quantity = $stock;
            }
        } catch (PDOException $e) {
            // Proceed without capping if DB query fails
        }
    }
    
    if (isset($_SESSION['cart'][$key])) {
        $_SESSION['cart'][$key]['quantity'] = $quantity;
        return true;
    }
    return false;
}

// Get cart items with product details
function get_cart_items($pdo_connection = null) {
    init_cart();
    
    $items = [];
    
    if (empty($_SESSION['cart'])) {
        return $items;
    }
    
    // Use passed PDO or try to get global
    global $pdo;
    $db = $pdo_connection ?: $pdo;
    
    if ($db) {
        try {
            // Extract actual product IDs from cart session
            $product_ids = [];
            foreach ($_SESSION['cart'] as $cart_item) {
                if (isset($cart_item['product_id'])) {
                    $product_ids[] = (int)$cart_item['product_id'];
                }
            }
            
            if (empty($product_ids)) {
                return $items;
            }
            
            $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
            
            $stmt = $db->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
            $stmt->execute($product_ids);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $products_by_id = [];
            foreach ($products as $product) {
                $products_by_id[$product['id']] = $product;
            }
            
            foreach ($_SESSION['cart'] as $key => $cart_item) {
                if (isset($cart_item['product_id']) && isset($products_by_id[$cart_item['product_id']])) {
                    $product = $products_by_id[$cart_item['product_id']];
                    $items[] = [
                        'id' => $product['id'],
                        'key' => $key,
                        'name' => $product['name'],
                        'price' => $product['price'],
                        'image' => $product['image'] ?? '',
                        'image1' => $product['image1'] ?? $product['image'] ?? '',
                        'quantity' => $cart_item['quantity'],
                        'size' => $cart_item['size'] ?? null
                    ];
                }
            }
        } catch (PDOException $e) {
            error_log("Error getting cart items: " . $e->getMessage());
        }
    }
    
    return $items;
}

// Get cart totals
function get_cart_totals($cart_items, $city = null) {
    $subtotal = 0;
    
    foreach ($cart_items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    
    global $pdo;
    
    $shipping = 0;
    if (count($cart_items) > 0) {
        if (empty($city)) {
            $shipping = 0; // Wait for city selection
        } elseif ($pdo) {
            try {
                $stmt = $pdo->prepare("SELECT price FROM delivery_cities WHERE city = ? LIMIT 1");
                $stmt->execute([$city]);
                $city_price = $stmt->fetchColumn();
                $shipping = $city_price !== false ? (float)$city_price : 150;
            } catch (PDOException $e) {
                $shipping = 150;
            }
        } else {
            $shipping = 150;
        }
        
        if ($subtotal > 5000) {
            $shipping = 0;
        }
    }
    
    $total = $subtotal + $shipping;
    
    return [
        'subtotal' => $subtotal,
        'shipping' => $shipping,
        'total' => $total
    ];
}

// Get cart count
function get_cart_count() {
    init_cart();
    
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return 0;
    }
    
    $count = 0;
    foreach ($_SESSION['cart'] as $item) {
        if (isset($item['quantity'])) {
            $count += (int)$item['quantity'];
        }
    }
    return $count;
}

// Get wishlist count
function get_wishlist_count() {
    global $pdo;
    if (!isset($pdo)) return 0;
    
    if (!isset($_SESSION['user_id'])) {
        return 0;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    } catch (PDOException $e) {
        return 0;
    }
}

// Clear cart
function clear_cart() {
    $_SESSION['cart'] = [];
}

// ==================== LANGUAGE HANDLING ====================

function load_language() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_GET['lang'])) {
        $allowed_langs = ['en', 'fr', 'ar'];
        if (in_array($_GET['lang'], $allowed_langs)) {
            $_SESSION['lang'] = $_GET['lang'];
        }
    }

    if (!isset($_SESSION['lang'])) {
        $_SESSION['lang'] = 'en';
    }

    $lang_file = __DIR__ . '/../lang/' . $_SESSION['lang'] . '.php';
    if (file_exists($lang_file)) {
        return include $lang_file;
    } else {
        return include __DIR__ . '/../lang/en.php';
    }
}

function __($key) {
    static $translations = null;
    if ($translations === null) {
        $translations = load_language();
    }
    return $translations[$key] ?? $key;
}

// Get text direction based on language
function get_text_direction() {
    $rtl_languages = ['ar'];
    return in_array($_SESSION['lang'] ?? 'en', $rtl_languages) ? 'rtl' : 'ltr';
}

// ==================== SOCIAL LINKS FUNCTIONS ====================

/**
 * Get active social links ordered by display order
 * @param PDO|null $pdo_connection - PDO connection (uses global $pdo if null)
 * @return array - Array of social links [platform, url]
 */
function get_social_links($pdo_connection = null) {
    global $pdo;
    $db = $pdo_connection ?: $pdo;
    
    if (!$db) {
        return [];
    }
    
    try {
        $stmt = $db->query("SELECT platform, url FROM social_links WHERE is_active = 1 ORDER BY order_num ASC, id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching social links: " . $e->getMessage());
        return [];
    }
}


// Get direction class for Tailwind
function get_direction_class() {
    return get_text_direction() === 'rtl' ? 'rtl' : '';
}

// Check if current language is RTL
function is_rtl() {
    return get_text_direction() === 'rtl';
}

// ==================== IMAGE HANDLING ====================

/**
 * Check if WebP conversion is available via GD or ImageMagick
 * @return array - Information about available WebP support
 */
function check_webp_support() {
    $support = [
        'gd' => false,
        'cwebp' => false
    ];
    
    // Check GD WebP support
    if (function_exists('gd_info')) {
        $gdInfo = gd_info();
        $support['gd'] = isset($gdInfo['WebP Support']) && $gdInfo['WebP Support'];
    }
    
    // No cwebp support on shared hosting - disable
    $support['cwebp'] = false;
     
    return $support;
}

/**
 * Convert image to WebP using available methods
 * @param resource|GdImage $image - GD image resource
 * @param string $targetFile - Target file path
 * @param int $quality - WebP quality (0-100)
 * @return bool - Success status
 */
function save_as_webp($image, $targetFile, $quality = 80) {
    // Try GD imagewebp first
    if (function_exists('imagewebp')) {
        return imagewebp($image, $targetFile, $quality);
    }
    
    return false;
}

/**
 * Convert uploaded image to WebP format for storage optimization
 * @param array $file - $_FILES array element
 * @param string $uploadDir - Directory to save the image
 * @param int $quality - WebP quality (0-100), default 80
 * @return string|false - Returns the saved file path or false on failure
 */
function convert_to_webp($file, $uploadDir, $quality = 80) {
    // Check for upload errors
    if (!isset($file['error']) || is_array($file['error'])) {
        return false;
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Get file info
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    
    // Max file size (10MB)
    if ($fileSize > 10 * 1024 * 1024) {
        return false;
    }
    
    // Get extension
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // Allowed extensions
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($fileExtension, $allowedExtensions)) {
        return false;
    }
    
    // If already webp, just move it
    if ($fileExtension === 'webp') {
        // Ensure upload directory exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $newFileName = time() . '_' . bin2hex(random_bytes(4)) . '.webp';
        $targetFile = $uploadDir . $newFileName;
        
        if (move_uploaded_file($fileTmpName, $targetFile)) {
            return 'uploads/' . $newFileName;
        }
        return false;
    }
    
    // Check WebP support
    $webpSupport = check_webp_support();
     
    // If no GD WebP support available (cwebp disabled on shared hosting), fallback
    if (!$webpSupport['gd']) {
        error_log("WebP conversion not available. No GD WebP support found.");
        return false;
    }
    
    // Create image resource based on extension
    $image = null;
    
    switch ($fileExtension) {
        case 'jpeg':
        case 'jpg':
            $image = imagecreatefromjpeg($fileTmpName);
            break;
        case 'png':
            $image = imagecreatefrompng($fileTmpName);
            break;
        case 'gif':
            $image = imagecreatefromgif($fileTmpName);
            break;
        default:
            return false;
    }
    
    if ($image === false) {
        return false;
    }
    
    // Ensure upload directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Generate unique filename
    $newFileName = time() . '_' . bin2hex(random_bytes(4)) . '.webp';
    $targetFile = $uploadDir . $newFileName;
    
    // Save as WebP using the helper function
    $result = save_as_webp($image, $targetFile, $quality);
    imagedestroy($image);
    
    if ($result) {
        return 'uploads/' . $newFileName;
    }
    
    return false;
}

/**
 * Generate thumbnail in WebP format
 * @param string $sourceImage - Source image path
 * @param string $uploadDir - Directory to save thumbnail
 * @param int $maxWidth - Maximum thumbnail width
 * @param int $maxHeight - Maximum thumbnail height
 * @param int $quality - WebP quality
 * @return string|false - Returns the saved file path or false on failure
 */
function create_webp_thumbnail($sourceImage, $uploadDir, $maxWidth = 300, $maxHeight = 300, $quality = 75) {
    // Get source image info
    $imageInfo = getimagesize($sourceImage);
    
    if ($imageInfo === false) {
        return false;
    }
    
    $originalWidth = $imageInfo[0];
    $originalHeight = $imageInfo[1];
    $mimeType = $imageInfo['mime'];
    
    // Calculate new dimensions
    $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
    
    if ($ratio >= 1) {
        // No need to resize, just convert
        return convert_to_webp(['tmp_name' => $sourceImage, 'name' => basename($sourceImage), 'error' => 0, 'size' => filesize($sourceImage)], $uploadDir, $quality);
    }
    
    $newWidth = (int)($originalWidth * $ratio);
    $newHeight = (int)($originalHeight * $ratio);
    
    // Create new image resource
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Handle transparency for PNG and GIF
    if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Create source image resource
    switch ($mimeType) {
        case 'image/jpeg':
        case 'image/jpg':
            $source = imagecreatefromjpeg($sourceImage);
            break;
        case 'image/png':
            $source = imagecreatefrompng($sourceImage);
            break;
        case 'image/gif':
            $source = imagecreatefromgif($sourceImage);
            break;
        default:
            imagedestroy($newImage);
            return false;
    }
    
    // Resize
    imagecopyresampled($newImage, $source, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
    imagedestroy($source);
    
    // Ensure upload directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Generate unique filename
    $newFileName = 'thumb_' . time() . '_' . bin2hex(random_bytes(4)) . '.webp';
    $targetFile = $uploadDir . $newFileName;
    
    // Save as WebP using the helper function
    $result = save_as_webp($newImage, $targetFile, $quality);
    imagedestroy($newImage);
    
    if ($result) {
        return 'uploads/' . $newFileName;
    }
    
    return false;
}
?>

