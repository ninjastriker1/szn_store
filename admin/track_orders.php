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

$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$orders = [];
if (!empty($searchQuery)) {
    $searchTerm = '%' . $searchQuery . '%';
    $stmt = $pdo->prepare("
        SELECT o.*, u.first_name, u.last_name, u.email, 
        (SELECT GROUP_CONCAT(CONCAT(product_name, ' (', quantity, ')')) FROM order_items WHERE order_id = o.order_id) as items_summary
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.order_id LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR o.shipping_phone LIKE ? OR o.shipping_email LIKE ?
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->query("
        SELECT o.*, u.first_name, u.last_name, u.email,
        (SELECT GROUP_CONCAT(CONCAT(product_name, ' (', quantity, ')')) FROM order_items WHERE order_id = o.order_id) as items_summary
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC LIMIT 50
    ");
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
    <title>Track Orders | Admin SZN</title>
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
                    <h2 class="serif text-6xl md:text-8xl mb-6 font-normal tracking-tight">Track Orders</h2>
                    <p class="text-gray-400 font-light text-lg">Monitor delivery status and customer updates in real-time.</p>
                </header>

                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="p-6 border-b border-gray-200">
                        <form method="GET" class="flex gap-2 max-w-md">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Search Order ID, Customer, Phone, Email..." 
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-black">
                            <button type="submit" class="px-6 py-2 bg-black text-white rounded-lg hover:bg-gray-800 whitespace-nowrap font-medium tracking-wide uppercase text-xs">
                                Search
                            </button>
                            <?php if (!empty($searchQuery)): ?>
                                <a href="track_orders.php" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 whitespace-nowrap font-medium tracking-wide uppercase text-xs">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="p-4 text-xs uppercase tracking-wider text-gray-500 font-medium">Order ID</th>
                                <th class="p-4 text-xs uppercase tracking-wider text-gray-500 font-medium">Customer</th>
                                <th class="p-4 text-xs uppercase tracking-wider text-gray-500 font-medium">Items</th>
                                <th class="p-4 text-xs uppercase tracking-wider text-gray-500 font-medium text-right">Total</th>
                                <th class="p-4 text-xs uppercase tracking-wider text-gray-500 font-medium">Status</th>
                                <th class="p-4 text-xs uppercase tracking-wider text-gray-500 font-medium">Date</th>
                                <th class="p-4 text-xs uppercase tracking-wider text-gray-500 font-medium">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($orders as $order): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="p-4 font-medium">#<?php echo htmlspecialchars($order['order_id']); ?></td>
                                    <td class="p-4"><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                    <td class="p-4 text-sm max-w-xs truncate"><?php echo htmlspecialchars($order['items_summary']); ?></td>
                                    <td class="p-4 font-medium text-right"><?php echo number_format($order['total'], 2); ?> MAD</td>
                                    <td class="p-4">
                                        <?php 
                                        $statusClass = [
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'processing' => 'bg-blue-100 text-blue-800',
                                            'shipped' => 'bg-purple-100 text-purple-800',
                                            'delivered' => 'bg-green-100 text-green-800',
                                            'cancelled' => 'bg-red-100 text-red-800'
                                        ];
                                        ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $statusClass[$order['status']] ?? 'bg-gray-100 text-gray-800'; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td class="p-4 text-sm text-gray-500"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                    <td class="p-4">
                                        <a href="?view_id=<?php echo urlencode($order['order_id']); ?>" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="7" class="p-12 text-center text-gray-500">
                                        <div class="inline-block p-8 border-2 border-dashed border-gray-300 rounded-lg">
                                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                            </svg>
                                            <h3 class="text-lg font-medium text-gray-900 mb-2">No orders found</h3>
                                            <p class="text-sm">Try adjusting your search criteria.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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
<?php
?>
