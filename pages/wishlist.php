<section class="py-32 px-8 md:px-16 bg-white min-h-screen">
    <?php
    global $pdo;
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo '<div class="text-center py-20">
            <h2 class="text-3xl serif mb-6">' . __('login_to_view_wishlist') . '</h2>
            <a href="index.php?page=login" class="inline-block bg-black text-white px-8 py-3 text-sm uppercase tracking-widest hover:bg-gray-800 transition-colors">' . __('sign_in') . '</a>
        </div>';
        return;
    }
    
    // Fetch wishlist products
    try {
        $stmt = $pdo->prepare("
            SELECT p.* FROM products p 
            INNER JOIN wishlist w ON p.id = w.product_id 
            WHERE w.user_id = ? 
            ORDER BY w.created_at DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $wishlist_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $wishlist_products = [];
    }
    ?>
    
    <div class="text-center mb-20">
        <h2 class="text-5xl md:text-6xl serif mb-6"><?php echo __('your_wishlist'); ?></h2>
        <p class="max-w-2xl mx-auto text-sm font-light opacity-60">
            <?php echo __('shop_description'); ?>
        </p>
    </div>
    
    <div class="max-w-7xl mx-auto">
        <?php if (count($wishlist_products) > 0): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                <?php foreach ($wishlist_products as $product): ?>
                    <div class="group block cursor-pointer">
                        <div class="aspect-[4/5] overflow-hidden mb-6 bg-[#f9f9f9] relative">
                            <a href="index.php?page=product&id=<?php echo $product['id']; ?>">
<img src="<?php echo htmlspecialchars($product['image1'] ?? $product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-1000">
                            </a>
                            
                            <div class="absolute inset-0 bg-black/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                            
                            <!-- Action Buttons -->
                            <div class="absolute bottom-4 right-4 flex flex-col gap-2">
                                <!-- Remove from Wishlist Button -->
                                <button type="button" onclick="removeFromWishlist(<?php echo $product['id']; ?>)" class="bg-white text-black w-10 h-10 flex items-center justify-center opacity-0 transform translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300 hover:bg-red-500 hover:text-white" title="<?php echo __('remove_from_wishlist'); ?>">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="1.2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                            
                            <!-- View Product Button -->
                            <a href="index.php?page=product&id=<?php echo $product['id']; ?>" class="absolute bottom-4 left-4 bg-white text-black px-4 py-2 text-xs uppercase tracking-widest opacity-0 transform translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300 hover:bg-black hover:text-white">
                                <?php echo __('view_product'); ?>
                            </a>
                        </div>
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="text-sm font-light mb-1"><?php echo htmlspecialchars($product['name']); ?></h4>
                                <p class="text-xs text-gray-500 font-light"><?php echo ucfirst($product['category']); ?></p>
                            </div>
                            <p class="text-sm font-light"><?php echo number_format($product['price'], 2); ?> MAD</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-20">
                <p class="text-gray-500 font-light text-lg mb-6"><?php echo __('empty_wishlist'); ?></p>
                <a href="index.php?page=shop" class="inline-block bg-black text-white px-8 py-3 text-sm uppercase tracking-widest hover:bg-gray-800 transition-colors"><?php echo __('shop'); ?></a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Wishlist Toast Notification -->
<div id="wishlist-toast" class="fixed bottom-8 left-1/2 transform -translate-x-1/2 bg-black text-white px-6 py-3 text-sm font-light opacity-0 transition-opacity duration-300 z-50">
    <span id="wishlist-message"><?php echo __('added_to_wishlist'); ?></span>
</div>

<script>
function removeFromWishlist(productId) {
    fetch('index.php?ajax=wishlist', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId + '&action=remove'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload the page to update the wishlist
            location.reload();
        } else {
            showToast(data.message || 'Error removing from wishlist');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error removing from wishlist');
    });
}

function showToast(message) {
    const toast = document.getElementById('wishlist-toast');
    const messageEl = document.getElementById('wishlist-message');
    messageEl.textContent = message;
    toast.classList.remove('opacity-0');
    toast.classList.add('opacity-100');
    setTimeout(() => {
        toast.classList.remove('opacity-100');
        toast.classList.add('opacity-0');
    }, 3000);
}
</script>