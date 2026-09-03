<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// only admin allowed
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: " . base_url('index.php?page=login'));
    exit;
}

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $orderId = $_POST['order_id'];
    $newStatus = $_POST['status'];
    
    // valid statuses: pending, processing, shipped, delivered, cancelled
    $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    
    // Status progression validation
    $statusOrder = ['pending' => 0, 'processing' => 1, 'shipped' => 2, 'delivered' => 3];
    $cancelOrder = ['pending' => 0, 'processing' => 1, 'shipped' => 2, 'delivered' => 3];
    function canChangeStatus($current, $new) {
        global $statusOrder, $cancelOrder;
        if (!isset($statusOrder[$current]) || !isset($statusOrder[$new])) return false;
        if ($new === 'cancelled') {
            return isset($cancelOrder[$current]); // Can cancel any non-cancelled status
        }
        return $statusOrder[$current] < $statusOrder[$new];
    }
    
    if (in_array($newStatus, $validStatuses)) {
                // Get current status first for validation
                $stmtCurrent = $pdo->prepare("SELECT status FROM orders WHERE order_id = ?");
                $stmtCurrent->execute([$orderId]);
                $currentStatus = $stmtCurrent->fetchColumn();
                
                if (!$currentStatus || !canChangeStatus($currentStatus, $newStatus)) {
                    $_SESSION['error'] = "Invalid status change: Cannot revert order status or invalid transition.";
                    header("Location: " . base_url('admin/orders.php?view_id=' . urlencode($orderId)));
                    exit;
                }
                
                try {
            // Get current order info before update (including user email from join)
            $stmtOld = $pdo->prepare("SELECT o.*, u.email as user_email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.order_id = ?");
            $stmtOld->execute([$orderId]);
            $oldOrder = $stmtOld->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
            $stmt->execute([$newStatus, $orderId]);
            $_SESSION['success'] = "Order #{$orderId} status updated to " . ucfirst($newStatus) . ".";
            
            // Send status update email to customer if email exists
            if ($oldOrder && !empty($oldOrder['user_email'])) {
                require_once __DIR__ . '/../includes/email.php';
                $customerName = $oldOrder['shipping_first_name'] . ' ' . $oldOrder['shipping_last_name'];
                $customerEmail = $oldOrder['user_email'];
                
                // Send status update email (non-blocking)
$orderEmailData = [
                    'total' => $oldOrder['total'] ?? 0,
                    'subtotal' => $oldOrder['subtotal'] ?? 0,
                    'shipping' => $oldOrder['shipping'] ?? 0,
                    'shipping_first_name' => $oldOrder['shipping_first_name'] ?? '',
                    'shipping_last_name' => $oldOrder['shipping_last_name'] ?? '',
                    'shipping_address' => $oldOrder['shipping_address'] ?? '',
                    'shipping_city' => $oldOrder['shipping_city'] ?? '',
                    'shipping_zip' => $oldOrder['shipping_zip'] ?? '',
                    'shipping_country' => $oldOrder['shipping_country'] ?? '',
                    'shipping_phone' => $oldOrder['shipping_phone'] ?? ''
                ];
                $orderItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
                $orderItems->execute([$orderId]);
                $orderItems = $orderItems->fetchAll(PDO::FETCH_ASSOC);
                send_order_status_update($customerEmail, $customerName, $orderId, $newStatus, $oldOrder, $orderItems);
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating status: " . $e->getMessage();
        }
    }
    header("Location: " . base_url('admin/orders.php' . (isset($_GET['view_id']) ? '?view_id=' . urlencode($_GET['view_id']) : '')));
    exit;
}

// Check if viewing a specific order
$viewOrder = null;
$orderItems = [];
if (isset($_GET['view_id'])) {
    $viewId = $_GET['view_id'];
    
    // Auto-process pending orders on first view
                $stmtCheck = $pdo->prepare("SELECT o.*, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.order_id = ?");
                $stmtCheck->execute([$viewId]);
                $orderToProcess = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
    if ($orderToProcess && $orderToProcess['status'] === 'pending') {
        try {
            $pdo->prepare("UPDATE orders SET status = 'processing' WHERE order_id = ?")->execute([$viewId]);
            
            // Send auto-processing email
            if (!empty($orderToProcess['email'])) {
                require_once __DIR__ . '/../includes/email.php';
                $customerName = $orderToProcess['shipping_first_name'] . ' ' . $orderToProcess['shipping_last_name'];
$orderEmailData = [
                    'total' => $viewOrder['total'] ?? 0,
                    'subtotal' => $viewOrder['subtotal'] ?? 0,
                    'shipping' => $viewOrder['shipping'] ?? 0,
                    'shipping_first_name' => $orderToProcess['shipping_first_name'] ?? '',
                    'shipping_last_name' => $orderToProcess['shipping_last_name'] ?? '',
                    'shipping_address' => $viewOrder['shipping_address'] ?? '',
                    'shipping_city' => $viewOrder['shipping_city'] ?? '',
                    'shipping_zip' => $viewOrder['shipping_zip'] ?? '',
                    'shipping_country' => $viewOrder['shipping_country'] ?? '',
                    'shipping_phone' => $orderToProcess['shipping_phone'] ?? ''
                ];
                $orderItemsAuto = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
                $orderItemsAuto->execute([$viewId]);
                $autoItems = $orderItemsAuto->fetchAll(PDO::FETCH_ASSOC);
                send_order_status_update($orderToProcess['email'], $customerName, $viewId, 'processing', $orderToProcess, $autoItems);
                $_SESSION['success'] = "Order automatically moved to processing and customer notified.";
            }
        } catch (PDOException $e) {
            error_log("Auto-process error: " . $e->getMessage());
        }
    }
    
    $stmt = $pdo->prepare("SELECT o.*, u.first_name as u_fname, u.last_name as u_lname, u.email as u_email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.order_id = ?");
    $stmt->execute([$viewId]);
    $viewOrder = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($viewOrder) {
        $stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmtItems->execute([$viewId]);
        $orderItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $_SESSION['error'] = "Order not found.";
        header("Location: " . base_url('admin/orders.php'));
        exit;
    }
} else {
    // Get filter status
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    
    // Build query based on filter
    $whereClause = "";
    $statusColors = [
        'pending' => 'bg-yellow-500 text-white',
        'processing' => 'bg-blue-500 text-white', 
        'shipped' => 'bg-purple-500 text-white',
        'delivered' => 'bg-green-500 text-white',
        'cancelled' => 'bg-red-500 text-white'
    ];
    if ($filter !== 'all') {
        $whereClause = "WHERE o.status = '" . $filter . "'";
    }
    
    $stmt = $pdo->query("SELECT o.*, u.first_name, u.last_name FROM orders o JOIN users u ON o.user_id = u.id $whereClause ORDER BY o.created_at DESC");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // For filter tabs
    $currentFilter = $filter;
    
    // Search functionality
    $searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
    $searchWhere = '';
    $searchParams = [];
    if (!empty($searchQuery)) {
        $searchWhere = "AND (o.order_id LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR o.shipping_phone LIKE ? OR o.shipping_email LIKE ?)";
        $searchTerm = '%' . $searchQuery . '%';
        $searchParams = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
    }
    
    if ($filter !== 'all') {
        $whereClause .= ($whereClause ? ' AND ' : 'WHERE ') . "o.status = ?";
        $searchParams[] = $filter;
    }
    
    $query = "SELECT o.*, u.first_name, u.last_name FROM orders o JOIN users u ON o.user_id = u.id $whereClause $searchWhere ORDER BY o.created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute($searchParams);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
 }
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
    </style>
    <title><?php echo $viewOrder ? 'Order #' . htmlspecialchars($viewOrder['order_id']) : 'Manage Orders'; ?> | Admin SZN</title>
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
                <header class="mb-16">
                    <h2 class="serif text-6xl md:text-8xl mb-6 font-normal tracking-tight">
                        <?php if ($viewOrder): ?>
                            #<?php echo htmlspecialchars($viewOrder['order_id']); ?>
                        <?php else: ?>
                            Orders
                        <?php endif; ?>
                    </h2>
                    <p class="text-gray-400 font-light text-lg">
                        <?php if ($viewOrder): ?>
                            <a href="orders.php" class="text-gray-400 hover:text-black transition-colors">&larr; Back to all orders</a>
                        <?php else: ?>
                            Track and manage customer orders and fulfillment.
                        <?php endif; ?>
                    </p>
                </header>
                
                <?php if (!$viewOrder): ?>
                <!-- Filter Tabs & Search -->
                <div class="mb-6 space-y-4">
                    <div class="flex space-x-2">
                        <a href="?filter=all<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="px-4 py-2 rounded-full text-sm font-medium <?php echo $currentFilter === 'all' ? 'bg-black text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">All Orders</a>
                        <a href="?filter=pending<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="px-4 py-2 rounded-full text-sm font-medium <?php echo $currentFilter === 'pending' ? 'bg-yellow-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">Pending</a>
                        <a href="?filter=processing<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="px-4 py-2 rounded-full text-sm font-medium <?php echo $currentFilter === 'processing' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">Processing</a>
                        <a href="?filter=shipped<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="px-4 py-2 rounded-full text-sm font-medium <?php echo $currentFilter === 'shipped' ? 'bg-purple-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">Shipped</a>
                        <a href="?filter=delivered<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="px-4 py-2 rounded-full text-sm font-medium <?php echo $currentFilter === 'delivered' ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">Delivered</a>
                        <a href="?filter=cancelled<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="px-4 py-2 rounded-full text-sm font-medium <?php echo $currentFilter === 'cancelled' ? 'bg-red-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">Cancelled</a>
                    </div>
                    <div class="flex gap-2">
                        <form method="GET" class="flex-1">
                            <input type="hidden" name="filter" value="<?php echo htmlspecialchars($currentFilter); ?>">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Search by Order ID, Customer, Phone, Email..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-black">
                        </form>
                        <?php if (!empty($searchQuery)): ?>
                            <a href="?filter=<?php echo htmlspecialchars($currentFilter); ?>" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 whitespace-nowrap">Clear</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                 <?php if (isset($_SESSION['error'])): ?>
                    <div class="mb-6 p-4 bg-red-50 text-red-700 border border-red-200 rounded text-sm"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="mb-6 p-4 bg-green-50 text-green-700 border border-green-200 rounded text-sm"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <?php if ($viewOrder): ?>
                    <!-- Order Details View -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="col-span-2 space-y-6">
                            <div class="bg-white shadow rounded-lg p-6">
                                <h3 class="text-lg serif font-medium mb-4">Items Ordered</h3>
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-gray-50 border-b border-gray-200">
                                            <th class="p-3 text-xs uppercase tracking-wider text-gray-500 font-medium">Product</th>
                                            <th class="p-3 text-xs uppercase tracking-wider text-gray-500 font-medium text-center">Qty</th>
                                            <th class="p-3 text-xs uppercase tracking-wider text-gray-500 font-medium text-right">Price</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <?php foreach ($orderItems as $item): ?>
                                            <tr>
                                                <td class="p-3">
                                                    <div class="font-medium"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                                    <?php if(!empty($item['size'])): ?>
                                                        <div class="text-xs text-gray-500">Size: <?php echo htmlspecialchars($item['size']); ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="p-3 text-center"><?php echo $item['quantity']; ?></td>
                                                <td class="p-3 text-right"><?php echo number_format($item['price'] * $item['quantity'], 2); ?> MAD</td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr>
                                            <td colspan="2" class="p-3 text-right font-medium">Subtotal</td>
                                            <td class="p-3 text-right"><?php echo number_format($viewOrder['subtotal'], 2); ?> MAD</td>
                                        </tr>
                                        <tr>
                                            <td colspan="4" class="p-3 text-right font-medium text-gray-500">Shipping</td>
                                            <td class="p-3 text-right"><?php echo number_format($viewOrder['shipping'], 2); ?> MAD</td>
                                        </tr>
                                        <tr class="bg-gray-50">
                                            <td colspan="4" class="p-3 text-right font-bold text-gray-900">Total</td>
                                            <td class="p-3 text-right font-bold text-gray-900"><?php echo number_format($viewOrder['total'], 2); ?> MAD</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="space-y-6">
                            <div class="bg-white shadow rounded-lg p-6">
                                <h3 class="text-lg serif font-medium mb-4">Order Status</h3>
                                <form method="POST" class="flex flex-col space-y-3">
                                    <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($viewOrder['order_id']); ?>">
                                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-black" id="statusSelect">
                                        <?php 
                                        $current = $viewOrder['status'];
                                        $allowed = ['cancelled']; // Always allow cancel
                                        if ($current === 'pending') { $allowed[] = 'processing'; }
                                        elseif ($current === 'processing') { $allowed[] = 'shipped'; }
                                        elseif ($current === 'shipped') { $allowed[] = 'delivered'; }
                                        elseif ($current === 'delivered') { /* only cancel */ }
                                        elseif ($current === 'cancelled') { $allowed = []; }
                                        foreach (['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $s) {
                                            $disabled = !in_array($s, $allowed) && $s !== $current;
                                            $selected = $s === $current;
                                            echo '<option value="' . $s . '" ' . ($selected ? 'selected' : '') . ($disabled ? ' disabled' : '') . '>' . ucfirst($s) . '</option>';
                                        } ?>
                                    </select>
                                    <button type="submit" class="bg-black text-white px-4 py-2 rounded text-sm uppercase tracking-widest font-light hover:bg-gray-800 transition-colors">Update Status</button>
                                </form>
                            </div>
                            
                            <div class="bg-white shadow rounded-lg p-6">
                                <h3 class="text-lg serif font-medium mb-4">Customer Details</h3>
                                <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($viewOrder['u_fname'] . ' ' . $viewOrder['u_lname']); ?></p>
                                <p class="mb-1"><strong>Email:</strong> <a href="mailto:<?php echo htmlspecialchars($viewOrder['u_email']); ?>" class="text-blue-600 hover:underline"><?php echo htmlspecialchars($viewOrder['u_email']); ?></a></p>
                                <hr class="my-4">
                                <h4 class="font-medium text-gray-700 mb-2">Shipping Address</h4>
                                <p class="text-sm text-gray-600">
                                    <?php echo htmlspecialchars($viewOrder['shipping_first_name'] . ' ' . $viewOrder['shipping_last_name']); ?><br>
                                    <?php echo htmlspecialchars($viewOrder['shipping_address']); ?><br>
                                    <?php echo htmlspecialchars($viewOrder['shipping_city'] . ', ' . $viewOrder['shipping_zip']); ?><br>
                                    <?php echo htmlspecialchars($viewOrder['shipping_country']); ?><br>
                                    <?php echo htmlspecialchars($viewOrder['shipping_phone']); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Orders List View -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="p-4 text-xs uppercase tracking-wider text-gray-500 font-medium">Order ID</th>
                                    <th class="p-4 text-xs uppercase tracking-wider text-gray-500 font-medium">Date</th>
                                    <th class="p-4 text-xs uppercase tracking-wider text-gray-500 font-medium">Customer</th>
                                    <th class="p-4 text-xs uppercase tracking-wider text-gray-500 font-medium">Total</th>
                                    <th class="p-4 text-xs uppercase tracking-wider text-gray-500 font-medium">Status</th>
                                    <th class="p-4 text-xs uppercase tracking-wider text-gray-500 font-medium">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($orders as $o): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="p-4 font-medium text-gray-900">
                                            #<?php echo htmlspecialchars($o['order_id']); ?>
                                        </td>
                                        <td class="p-4 text-sm text-gray-600">
                                            <?php echo date('M j, Y g:i A', strtotime($o['created_at'])); ?>
                                        </td>
                                        <td class="p-4 text-sm text-gray-600">
                                            <?php echo htmlspecialchars($o['first_name'] . ' ' . $o['last_name']); ?>
                                        </td>
                                        <td class="p-4 font-medium text-gray-900">
                                            <?php echo number_format($o['total'], 2); ?> MAD
                                        </td>
                                        <td class="p-4">
                                            <?php
                                                $statusColors = [
                                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                                    'processing' => 'bg-blue-100 text-blue-800',
                                                    'shipped' => 'bg-purple-100 text-purple-800',
                                                    'delivered' => 'bg-green-100 text-green-800',
                                                    'cancelled' => 'bg-red-100 text-red-800'
                                                ];
                                                $colorClass = $statusColors[$o['status']] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $colorClass; ?>">
                                                <?php echo ucfirst($o['status']); ?>
                                            </span>
                                        </td>
                                        <td class="p-4 text-sm">
                                            <a href="?view_id=<?php echo urlencode($o['order_id']); ?>" class="text-indigo-600 hover:text-indigo-900 font-medium">View Details</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if(empty($orders)): ?>
                                    <tr>
                                        <td colspan="6" class="p-8 text-center text-gray-500">No orders found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        const btn = document.getElementById('mobile-toggle');
        const sidebar = document.getElementById('sidebar');
        btn.onclick = () => sidebar.classList.toggle('-translate-x-full');
    </script>
</body>
</html>
