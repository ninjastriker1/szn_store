<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/email.php';
require_once __DIR__ . '/../cache.php';
require_once __DIR__ . '/../cache_queries.php';

// only admin allowed
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: " . base_url('index.php?page=login'));
    exit;
}

// Handle product status update (approve/reject)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $productId = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if (in_array($action, ['approve', 'reject'])) {
        $newStatus = $action === 'approve' ? 'approved' : 'rejected';
        $adminNotes = trim($_POST['admin_notes'] ?? '');
        
        try {
            // Get product info before update
            $stmt = $pdo->prepare("SELECT p.*, u.first_name, u.last_name, u.email FROM products p LEFT JOIN users u ON p.submitted_by = u.id WHERE p.id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                // Update status
                $updateStmt = $pdo->prepare("UPDATE products SET status = ?, admin_notes = ? WHERE id = ?");
                $updateStmt->execute([$newStatus, $adminNotes, $productId]);
                
                // Send email notification
                if ($product['submitted_by'] && $product['email']) {
                    $userName = $product['first_name'] . ' ' . $product['last_name'];
                    
                    if ($action === 'approve') {
                        send_product_approved_notification(
                            $product['email'], 
                            $userName, 
                            $productId, 
                            $product['name'], 
                            $product['price'],
                            $adminNotes
                        );
                        $_SESSION['success'] = "Product approved! User has been notified via email.";
                    } else {
                        send_product_rejected_notification(
                            $product['email'], 
                            $userName, 
                            $productId, 
                            $product['name'], 
                            $adminNotes
                        );
                        $_SESSION['success'] = "Product rejected! User has been notified via email.";
                    }
                } else {
                    $_SESSION['success'] = "Product status updated successfully.";
                }

                // Invalidate product cache so front-end shows updated status
                szn_invalidate_product_cache($productId);
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating product: " . $e->getMessage();
        }
    }
    
    header("Location: " . base_url('admin/products.php'));
    exit;
}

// Handle Delete Product
if (isset($_GET['delete_id'])) {
    $deleteId = (int)$_GET['delete_id'];
    try {
        // Check if in orders
        $checkOrder = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
        $checkOrder->execute([$deleteId]);
        if ($checkOrder->fetchColumn() > 0) {
             $_SESSION['error'] = "Cannot delete product. It is part of existing orders.";
        } else {
             $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
             $stmt->execute([$deleteId]);
             $_SESSION['success'] = "Product deleted successfully.";
             // Invalidate cache so front-end no longer shows deleted product
             szn_invalidate_product_cache($deleteId);
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting product: " . $e->getMessage();
    }
    header("Location: " . base_url('admin/products.php'));
    exit;
}

// Fetch categories for the form
$stmtCat = $pdo->query("SELECT slug, name FROM categories ORDER BY name ASC");
$categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

// Fetch sizes for the form
$stmtSize = $pdo->query("SELECT name FROM sizes ORDER BY id ASC");
$available_sizes = $stmtSize->fetchAll(PDO::FETCH_ASSOC);

// Handle Add/Edit Product (via POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    
    // Handle sizes and per-size stock
    $sizes_array = $_POST['sizes'] ?? [];
    $size_stocks_input = $_POST['size_stock'] ?? [];
    
    if (!is_array($sizes_array)) {
        $sizes_array = [];
    }
    
    $total_stock = 0;
    $final_size_stocks = [];
    
    foreach ($sizes_array as $sz) {
        $qty = (int)($size_stocks_input[$sz] ?? 0);
        $total_stock += $qty;
        $final_size_stocks[$sz] = $qty;
    }
    
    // Sanitize and implode size names for the products table
    $sizes = implode(',', array_map('trim', $sizes_array));
    $stock = $total_stock; // Calculated total stock
    
// Multi-image Handling - Convert to WebP for storage optimization
    $imagePaths = [];
    $imageFields = ['image1', 'image2', 'image3', 'image4'];
    foreach ($imageFields as $field) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/';
            $webpPath = convert_to_webp($_FILES[$field], $uploadDir, 80);
            if ($webpPath !== false) {
                $imagePaths[$field] = $webpPath;
            }
        }
    }
    $imagePath = $imagePaths['image1'] ?? ''; // Legacy for 'image' field

    if (empty($name) || empty($price) || empty($category)) {
        $_SESSION['error'] = "Name, price, and category are required.";
    } else {
        if ($_POST['action'] === 'add') {
             try {
                 $pdo->beginTransaction();
                 // For admin-added products, status is approved by default
                 $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock, category, image1, image2, image3, image4, sizes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved')");
                 $stmt->execute([$name, $description, $price, $stock, $category, $imagePaths['image1'] ?? null, $imagePaths['image2'] ?? null, $imagePaths['image3'] ?? null, $imagePaths['image4'] ?? null, $sizes]);
                 $productId = $pdo->lastInsertId();
                 
                 // Save per-size stock
                 $stmtStock = $pdo->prepare("INSERT INTO product_size_stock (product_id, size, stock) VALUES (?, ?, ?)");
                 foreach ($final_size_stocks as $sz => $qty) {
                     $stmtStock->execute([$productId, $sz, $qty]);
                 }
                 
                 $pdo->commit();
                 $_SESSION['success'] = "Product added successfully.";
                 // Invalidate all-products and category caches
                 szn_invalidate_product_cache();
             } catch (PDOException $e) {
                 $pdo->rollBack();
                 $_SESSION['error'] = "Error adding product: " . $e->getMessage();
             }
        } elseif ($_POST['action'] === 'edit') {
             $productId = (int)$_POST['product_id'];
             try {
                 $pdo->beginTransaction();
                 
                 // Get current product info for email
                 $oldStmt = $pdo->prepare("SELECT p.*, u.first_name, u.last_name, u.email FROM products p LEFT JOIN users u ON p.submitted_by = u.id WHERE p.id = ?");
                 $oldStmt->execute([$productId]);
                 $oldProduct = $oldStmt->fetch(PDO::FETCH_ASSOC);
                 
 if (!empty($imagePaths)) {
                      $setClause = "UPDATE products SET name=?, description=?, price=?, stock=?, category=?, sizes=?";
                      $params = [$name, $description, $price, $stock, $category, $sizes, $productId];
                      foreach ($imageFields as $field) {
                          $setClause .= ", $field = ?";
                          $params[] = $imagePaths[$field] ?? null;
                      }
                      $setClause .= " WHERE id=?";
                      $stmt = $pdo->prepare($setClause);
                      $stmt->execute($params);
                  } else {
                      $stmt = $pdo->prepare("UPDATE products SET name=?, description=?, price=?, stock=?, category=?, sizes=? WHERE id=?");
                      $stmt->execute([$name, $description, $price, $stock, $category, $sizes, $productId]);
                  }
                 
                 // Update per-size stock: delete and re-insert
                 $pdo->prepare("DELETE FROM product_size_stock WHERE product_id = ?")->execute([$productId]);
                 $stmtStock = $pdo->prepare("INSERT INTO product_size_stock (product_id, size, stock) VALUES (?, ?, ?)");
                 foreach ($final_size_stocks as $sz => $qty) {
                     $stmtStock->execute([$productId, $sz, $qty]);
                 }
                 
                 // If product was pending and now being edited by admin, auto-approve and send email
                 if ($oldProduct && $oldProduct['status'] === 'pending') {
                     $pdo->prepare("UPDATE products SET status = 'approved' WHERE id = ?")->execute([$productId]);
                     
                     // Send approval email
                     if ($oldProduct['submitted_by'] && $oldProduct['email']) {
                         $userName = $oldProduct['first_name'] . ' ' . $oldProduct['last_name'];
                         send_product_approved_notification(
                             $oldProduct['email'], 
                             $userName, 
                             $productId, 
                             $name, 
                             $price,
                             ''
                         );
                         $_SESSION['success'] = "Product updated and approved! User has been notified via email.";
                     } else {
                         $_SESSION['success'] = "Product updated successfully.";
                     }
                 } else {
                     $_SESSION['success'] = "Product updated successfully.";
                 }

                 // Invalidate cache for this product and listing caches
                 szn_invalidate_product_cache($productId);
                 
                 $pdo->commit();
             } catch (PDOException $e) {
                 $pdo->rollBack();
                 $_SESSION['error'] = "Error updating product: " . $e->getMessage();
             }
        }
    }
    header("Location: " . base_url('admin/products.php'));
    exit;
}

// Show all products (no filter)
$whereClause = "";

// Fetch all products with per-size stock info
    $sql = "SELECT p.*, c.name as category_name, 
        (SELECT GROUP_CONCAT(CONCAT(size, ':', stock)) FROM product_size_stock WHERE product_id = p.id) as size_stocks 
        FROM products p 
        LEFT JOIN categories c ON p.category = c.slug 
        $whereClause
        ORDER BY p.id DESC";
    $stmt = $pdo->query($sql);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process size_stocks into an array for easier JS use
foreach ($products as &$p) {
    if ($p['size_stocks']) {
        $stockMap = [];
        $pairs = explode(',', $p['size_stocks']);
        foreach ($pairs as $pair) {
            $parts = explode(':', $pair);
            if (count($parts) === 2) {
                $stockMap[$parts[0]] = (int)$parts[1];
            }
        }
        $p['size_stocks_map'] = $stockMap;
    } else {
        $p['size_stocks_map'] = new stdClass(); // Empty object
    }
}
unset($p);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; color: #1a1a1a; background-color: #ffffff; overflow-x: hidden; }
        .serif { font-family: 'Playfair Display', serif; }
        .sidebar-item { letter-spacing: 0.1em; font-size: 0.7rem; }
        .modal { display: none; }
        .modal.active { display: flex; }
    </style>
    <title>Manage Products | Admin SZN</title>
</head>
<body class="antialiased">
    <div class="flex min-h-screen">
        <?php include 'sidebar.php'; ?>
        
        <main class="flex-1 lg:ml-64 flex flex-col min-h-screen">
            <header class="h-16 flex items-center justify-between px-8 border-b border-gray-50 bg-white">
                <button id="mobile-toggle" class="lg:hidden p-2 text-gray-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="1.5" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                </button>
                <div class="hidden lg:block"></div> <div class="text-[10px] uppercase tracking-[0.3em] text-gray-400 font-medium">Admin Panel</div>
            </header>

            <div class="flex-1 p-8 lg:p-20 w-full max-w-screen-2xl mx-auto">
                <header class="mb-16 flex justify-between items-end">
                    <div>
                        <h2 class="serif text-6xl md:text-8xl mb-6 font-normal tracking-tight">Products</h2>
                        <p class="text-gray-400 font-light text-lg">Manage your inventory, sizes, and pricing.</p>
                    </div>
                    <button onclick="openAddModal()" class="bg-black text-white px-8 py-3 rounded text-[10px] uppercase tracking-[0.2em] font-medium hover:bg-gray-800 transition-all duration-300">Add New Product</button>
                </header>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="mb-6 p-4 bg-red-50 text-red-700 border border-red-200 rounded text-sm"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="mb-6 p-4 bg-green-50 text-green-700 border border-green-200 rounded text-sm"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <!-- Filters removed per request -->

                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="p-4 text-xs uppercase tracking-wider text-gray-500 font-medium">Image</th>
                                <th class="p-4 text-xs uppercase tracking-wider text-gray-500 font-medium">Name</th>
                                <th class="p-4 text-xs uppercase tracking-wider text-gray-500 font-medium">Category</th>
                                <th class="p-4 text-xs uppercase tracking-wider text-gray-500 font-medium">Price</th>
                                <th class="p-4 text-xs uppercase tracking-wider text-gray-500 font-medium">Stock</th>
                                <th class="p-4 text-xs uppercase tracking-wider text-gray-500 font-medium">Status</th>
                                <th class="p-4 text-xs uppercase tracking-wider text-gray-500 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($products as $p): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="p-4">
                                        <?php if (!empty($p['image1'])): ?>
                                            <img src="<?php echo htmlspecialchars(filter_var($p['image1'], FILTER_VALIDATE_URL) ? $p['image1'] : base_url($p['image1'])); ?>" alt="img" class="h-16 w-16 object-cover rounded shadow-sm">
                                        <?php elseif (!empty($p['image'])): ?>
                                            <img src="<?php echo htmlspecialchars(filter_var($p['image'], FILTER_VALIDATE_URL) ? $p['image'] : base_url($p['image'])); ?>" alt="img" class="h-16 w-16 object-cover rounded shadow-sm">
                                        <?php else: ?>
                                            <div class="h-16 w-16 bg-gray-200 rounded shadow-sm flex items-center justify-center text-gray-400 text-xs">No Img</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4">
                                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($p['name']); ?></div>
                                        <?php if (!empty($p['sizes'])): ?>
                                            <div class="text-xs text-gray-500 mt-1">Sizes: <?php echo htmlspecialchars($p['sizes']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4 text-sm text-gray-600">
                                        <?php echo htmlspecialchars($p['category_name'] ?: $p['category']); ?>
                                    </td>
                                    <td class="p-4 font-medium text-gray-900">
                                        <?php echo number_format($p['price'], 2); ?> MAD
                                    </td>
                                    <td class="p-4 font-medium <?php echo $p['stock'] <= 0 ? 'text-red-600' : 'text-green-600'; ?>">
                                        <?php echo $p['stock']; ?>
                                    </td>
                                    <td class="p-4">
                                        <?php 
                                        $status = $p['status'] ?? 'approved';
                                        $statusClass = [
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'approved' => 'bg-green-100 text-green-800',
                                            'rejected' => 'bg-red-100 text-red-800'
                                        ];
                                        $statusLabel = [
                                            'pending' => 'Pending',
                                            'approved' => 'Approved',
                                            'rejected' => 'Rejected'
                                        ];
                                        ?>
                                        <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $statusClass[$status] ?? 'bg-gray-100 text-gray-800'; ?>">
                                            <?php echo $statusLabel[$status] ?? $status; ?>
                                        </span>
                                        <?php if($status === 'pending'): ?>
                                            <div class="mt-2">
                                                <a href="?action=approve&id=<?php echo $p['id']; ?>" class="text-green-600 hover:text-green-800 text-xs mr-2" onclick="return confirm('Approve this product and notify the user?');">Approve</a>
                                                <a href="?action=reject&id=<?php echo $p['id']; ?>" class="text-red-600 hover:text-red-800 text-xs" onclick="return confirm('Reject this product and notify the user?');">Reject</a>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4 text-sm">
                                        <button onclick='openEditModal(<?php echo json_encode($p); ?>)' class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                                        <a href="?delete_id=<?php echo $p['id']; ?>" onclick="return confirm('Are you sure you want to delete this product?');" class="text-red-600 hover:text-red-900">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if(empty($products)): ?>
                                <tr>
                                    <td colspan="5" class="p-8 text-center text-gray-500">No products found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Add/Edit Product Modal -->
    <div id="productModal" class="modal fixed inset-0 bg-black bg-opacity-50 z-50 items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                <h3 id="modalTitle" class="text-xl serif font-medium">Add Product</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>
            <form id="productForm" method="POST" enctype="multipart/form-data" class="p-6">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="product_id" id="productId" value="">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                        <input type="text" name="name" id="productName" required class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-black">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Price (MAD) *</label>
                            <input type="number" step="0.01" name="price" id="productPrice" required class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                            <select name="category" id="productCategory" required class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-black bg-white">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['slug']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Available Sizes & Stock</label>
                        <div class="border border-gray-200 p-4 rounded bg-gray-50 h-64 overflow-y-auto" id="sizes-container">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <?php foreach ($available_sizes as $size): ?>
                                    <div class="flex items-center justify-between p-2 bg-white rounded border border-gray-100 size-label" data-size="<?php echo htmlspecialchars($size['name']); ?>">
                                        <label class="inline-flex items-center space-x-2 cursor-pointer">
                                            <input type="checkbox" name="sizes[]" value="<?php echo htmlspecialchars($size['name']); ?>" class="form-checkbox h-4 w-4 text-black focus:ring-black border-gray-300 rounded size-checkbox" onchange="toggleSizeStock(this)">
                                            <span class="text-sm text-gray-700 select-none font-medium"><?php echo htmlspecialchars($size['name']); ?></span>
                                        </label>
                                        <div class="flex items-center space-x-2 stock-input-wrapper" style="display: none;">
                                            <span class="text-xs text-gray-500">Qty:</span>
                                            <input type="number" name="size_stock[<?php echo htmlspecialchars($size['name']); ?>]" min="0" value="0" class="w-16 px-2 py-1 text-xs border border-gray-300 rounded size-stock-input">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Select available sizes and enter the stock quantity for each.</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" id="productDescription" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-black"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Image</label>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Main Image</label>
                                <input type="file" name="image1" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-gray-50 file:text-gray-700 hover:file:bg-gray-100">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Image 2</label>
                                <input type="file" name="image2" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-gray-50 file:text-gray-700 hover:file:bg-gray-100">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Image 3</label>
                                <input type="file" name="image3" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-gray-50 file:text-gray-700 hover:file:bg-gray-100">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Image 4</label>
                                <input type="file" name="image4" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-gray-50 file:text-gray-700 hover:file:bg-gray-100">
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Upload up to 4 images for different angles/poses. Leave empty to keep existing.</p>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50 font-light tracking-wide">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-black text-white rounded hover:bg-gray-800 font-light tracking-wide uppercase text-sm">Save Product</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('productModal');
        const form = document.getElementById('productForm');
        const categorySelect = document.getElementById('productCategory');
        const sizeLabels = document.querySelectorAll('.size-label');

        function toggleSizeStock(checkbox) {
            const wrapper = checkbox.closest('.size-label').querySelector('.stock-input-wrapper');
            const input = checkbox.closest('.size-label').querySelector('.size-stock-input');
            if (checkbox.checked) {
                wrapper.style.display = 'flex';
                if (input.value == "") input.value = 0;
            } else {
                wrapper.style.display = 'none';
                input.value = 0;
            }
        }

        function updateAvailableSizes() {
            const category = categorySelect.value;
            const clothesCategories = ['tops', 'bottoms', 'jackets', 'football_jerseys'];
            const shoeCategories = ['shoes'];
            const miscCategories = ['hats', 'perfumes', 'accessories'];
            
            let allowedPattern = /.*/; 
            
            if (clothesCategories.includes(category)) {
                allowedPattern = /^(XS|S|M|L|XL|XXL)$/i;
            } else if (shoeCategories.includes(category)) {
                allowedPattern = /^\d{2}$/;
            } else if (miscCategories.includes(category)) {
                allowedPattern = /^(One Size|OneSize)$/i;
            }
            
            sizeLabels.forEach(label => {
                const sizeValue = label.getAttribute('data-size');
                
                if (!category) {
                    label.style.display = 'inline-flex';
                    return;
                }
                
                if (allowedPattern.test(sizeValue)) {
                    label.style.display = 'flex';
                } else {
                    label.style.display = 'none';
                    const cb = label.querySelector('.size-checkbox');
                    if (cb) {
                        cb.checked = false;
                        toggleSizeStock(cb);
                    }
                }
            });
        }
        
        categorySelect.addEventListener('change', updateAvailableSizes);
        
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Product';
            document.getElementById('formAction').value = 'add';
            document.getElementById('productId').value = '';
            form.reset();
            
            // Reset stock inputs
            document.querySelectorAll('.size-checkbox').forEach(cb => {
                cb.checked = false;
                toggleSizeStock(cb);
            });
            
            updateAvailableSizes();
            modal.classList.add('active');
        }
        
        function openEditModal(product) {
            document.getElementById('modalTitle').textContent = 'Edit Product';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('productId').value = product.id;
            
            document.getElementById('productName').value = product.name;
            document.getElementById('productPrice').value = product.price;
            document.getElementById('productCategory').value = product.category;
            document.getElementById('productDescription').value = product.description;
            
            // Handle checkboxes and per-size stock
            document.querySelectorAll('.size-checkbox').forEach(cb => {
                cb.checked = false;
                const input = cb.closest('.size-label').querySelector('.size-stock-input');
                input.value = 0;
                toggleSizeStock(cb);
            });
            
            updateAvailableSizes(); // Filter visible boxes immediately
            
            if (product.sizes) {
                const selectedSizes = product.sizes.split(',').map(s => s.trim());
                const stockMap = product.size_stocks_map || {};
                
                document.querySelectorAll('.size-checkbox').forEach(cb => {
                    if (selectedSizes.includes(cb.value)) {
                        cb.checked = true;
                        const input = cb.closest('.size-label').querySelector('.size-stock-input');
                        input.value = stockMap[cb.value] || 0;
                        toggleSizeStock(cb);
                    }
                });
            }
            
            modal.classList.add('active');
        }
        
        function closeModal() {
            modal.classList.remove('active');
        }
        
        // Close modal on outside click
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    </script>
    <script>
        const btn = document.getElementById('mobile-toggle');
        const sidebar = document.getElementById('sidebar');
        btn.onclick = () => sidebar.classList.toggle('-translate-x-full');
    </script>
</body>
</html>
