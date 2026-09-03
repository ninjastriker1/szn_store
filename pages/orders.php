<?php
// Get user orders
try {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM orders 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get order items for each order
    foreach ($orders as &$order) {
        $items_stmt = $pdo->prepare("
            SELECT * FROM order_items 
            WHERE order_id = ?
        ");
        $items_stmt->execute([$order['order_id']]);
        $order['items'] = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $orders = [];
}
?>

<section class="py-32 px-8 md:px-16 bg-white min-h-screen">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-20">
            <h2 class="text-5xl md:text-6xl serif mb-6"><?php echo __('order_history'); ?></h2>
            <p class="max-w-2xl mx-auto text-sm font-light opacity-60">
                <?php echo __('view_track_orders'); ?>
            </p>
        </div>
        
        <?php if (count($orders) > 0): ?>
            <div class="space-y-8">
                <?php foreach ($orders as $order): ?>
                    <div class="border border-gray-200 rounded-sm overflow-hidden">
                        <!-- Order Header -->
                        <div class="bg-gray-50 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <p class="text-xs uppercase tracking-widest text-gray-500 mb-1">Order ID</p>
                                <p class="text-sm font-light"><?php echo htmlspecialchars($order['order_id']); ?></p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-widest text-gray-500 mb-1">Date</p>
                                <p class="text-sm font-light"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-widest text-gray-500 mb-1">Status</p>
                                <span class="inline-block px-3 py-1 text-xs uppercase tracking-widest 
                                    <?php 
                                    switch($order['status']) {
                                        case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                        case 'processing': echo 'bg-blue-100 text-blue-800'; break;
                                        case 'shipped': echo 'bg-purple-100 text-purple-800'; break;
                                        case 'delivered': echo 'bg-green-100 text-green-800'; break;
                                        case 'cancelled': echo 'bg-red-100 text-red-800'; break;
                                        default: echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-widest text-gray-500 mb-1">Total</p>
                                <p class="text-sm font-light"><?php echo number_format($order['total'], 2); ?> MAD</p>
                            </div>
                        </div>
                        
                        <!-- Order Items -->
                        <div class="p-6">
                            <h4 class="text-xs uppercase tracking-widest text-gray-500 mb-4">Items</h4>
                            <div class="space-y-4">
                                <?php foreach ($order['items'] as $item): ?>
                                    <div class="flex items-center gap-4">
                                        <?php if (!empty($item['product_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($item['product_image']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="w-16 h-16 object-cover rounded">
                                        <?php else: ?>
                                            <div class="w-16 h-16 bg-gray-100 flex items-center justify-center">
                                                <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                                </svg>
                                            </div>
                                        <?php endif; ?>
                                        <div class="flex-1">
                                            <p class="text-sm font-light"><?php echo htmlspecialchars($item['product_name']); ?></p>
                                            <?php if (!empty($item['size'])): ?>
                                                <p class="text-xs text-gray-500">Size: <?php echo htmlspecialchars($item['size']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-light"><?php echo number_format($item['price'], 2); ?> MAD</p>
                                            <p class="text-xs text-gray-500">Qty: <?php echo $item['quantity']; ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Shipping Info -->
                        <div class="border-t border-gray-200 p-6">
                            <h4 class="text-xs uppercase tracking-widest text-gray-500 mb-4">Shipping Address</h4>
                            <p class="text-sm font-light">
                                <?php echo htmlspecialchars($order['shipping_first_name'] . ' ' . $order['shipping_last_name']); ?><br>
                                <?php echo htmlspecialchars($order['shipping_address']); ?><br>
                                <?php echo htmlspecialchars($order['shipping_city'] . ', ' . $order['shipping_zip']); ?><br>
                                <?php echo htmlspecialchars($order['shipping_country']); ?><br>
                                <?php echo htmlspecialchars($order['shipping_phone']); ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-20">
                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <p class="text-gray-500 font-light text-lg mb-6"><?php echo __('no_orders'); ?></p>
                <a href="index.php?page=shop" class="inline-block bg-black text-white px-8 py-3 text-sm uppercase tracking-widest hover:bg-gray-800 transition-colors"><?php echo __('shop'); ?></a>
            </div>
        <?php endif; ?>
    </div>
</section>

