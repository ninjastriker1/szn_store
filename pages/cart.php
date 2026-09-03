<?php
// Error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Get cart items
$cart_items = get_cart_items($pdo);

// Calculate totals
$totals = get_cart_totals($cart_items);
$subtotal = $totals['subtotal'];
$shipping = $totals['shipping'];
$total = $totals['total'];
?>

<section class="py-32 px-8 md:px-16 bg-gray-50 min-h-screen">
    <div class="max-w-6xl mx-auto">
        <h2 class="text-4xl serif mb-10"><?php echo __('your_cart'); ?> (<span id="cart-item-count"><?php echo count($cart_items); ?></span> <?php echo __('items'); ?>)</h2>
        
        <div class="flex flex-col lg:flex-row gap-8">
            
            <!-- Cart Items List (Left Side) -->
            <section class="lg:w-2/3 space-y-6" id="cart-items-list">
                <?php if (count($cart_items) > 0): ?>
                    <?php foreach ($cart_items as $item): 
                        $item_subtotal = $item['price'] * $item['quantity'];
                    ?>
                        <!-- Cart Item Card -->
                        <div class="cart-item bg-white p-4 sm:p-6 rounded-xl shadow-lg flex flex-col sm:flex-row items-center sm:items-start space-y-4 sm:space-y-0 sm:space-x-6 transition duration-300 hover:shadow-xl" data-key="<?php echo $item['key']; ?>">
                            
                            <!-- Image -->
                            <div class="flex-shrink-0">
                                <a href="index.php?page=product&id=<?php echo $item['id']; ?>">
                                    <img src="<?php echo htmlspecialchars($item['image1'] ?? $item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>"
                                        class="w-24 h-24 object-cover rounded-lg border border-gray-100"
                                        onerror="this.onerror=null; this.src='https://placehold.co/100x100/CCCCCC/000000?text=Product';"
                                    >
                                </a>
                            </div>

                            <!-- Details -->
                            <div class="flex-grow text-center sm:text-left">
                                <h2 class="text-xl font-bold text-gray-900 mb-1 hover:text-gray-600 transition">
                                    <a href="index.php?page=product&id=<?php echo $item['id']; ?>">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </a>
                                </h2>
                                <?php if (!empty($item['size'])): ?>
                                    <p class="text-sm text-gray-600 mb-2"><?php echo __('size'); ?>: <span class="font-medium"><?php echo htmlspecialchars($item['size']); ?></span></p>
                                <?php endif; ?>
                                <p class="text-lg font-semibold text-gray-700 mt-2"><?php echo __('price'); ?>: <span class="item-price"><?php echo number_format($item['price'], 2); ?></span> MAD</p>
                            </div>

                            <!-- Quantity and Actions -->
                            <div class="flex flex-col items-center sm:items-end space-y-3">
                                <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden">
                                    <button type="button" class="update-quantity p-2 bg-gray-100 hover:bg-gray-200 text-gray-700 w-8 h-8 flex items-center justify-center" data-key="<?php echo $item['key']; ?>" data-action="decrease">-</button>
                                    <input type="text" value="<?php echo $item['quantity']; ?>" class="item-quantity w-10 text-center border-x border-gray-300 focus:outline-none text-gray-900 font-medium" readonly data-key="<?php echo $item['key']; ?>">
                                    <button type="button" class="update-quantity p-2 bg-gray-100 hover:bg-gray-200 text-gray-700 w-8 h-8 flex items-center justify-center" data-key="<?php echo $item['key']; ?>" data-action="increase">+</button>
                                </div>
                                <p class="text-xl font-bold text-gray-900"><?php echo __('total'); ?>: <span class="item-subtotal"><?php echo number_format($item_subtotal, 2); ?></span> MAD</p>
                                <button type="button" class="remove-item text-sm text-gray-500 hover:text-red-600 transition" data-key="<?php echo $item['key']; ?>">
                                    <?php echo __('remove'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Empty Cart Message -->
                    <div class="text-center p-10 bg-white rounded-xl shadow-lg" id="empty-cart-message">
                        <svg class="w-20 h-20 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                        <p class="text-xl font-semibold text-gray-700 mb-4"><?php echo __('empty_cart'); ?></p>
                        <a href="index.php?page=shop" class="inline-block bg-black text-white px-8 py-3 text-[10px] tracking-[0.2em] uppercase hover:bg-black/80 transition-colors">
                            <?php echo __('continue_shopping'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Order Summary (Right Side) - Always Visible -->
            <aside class="lg:w-1/3">
                <div class="bg-white p-6 rounded-xl shadow-xl lg:sticky lg:top-24 space-y-6">
                    <h3 class="text-2xl font-bold text-gray-900 border-b pb-3 mb-4"><?php echo __('order_total'); ?></h3>
                    
<div class="space-y-3 text-gray-700">
                        <!-- Subtotal -->
                        <div class="flex justify-between">
                            <span><?php echo __('subtotal'); ?></span>
                            <span class="font-medium" id="cart-subtotal"><span><?php echo number_format($subtotal, 2); ?></span> MAD</span>
                        </div>
                        
                        <!-- Shipping -->
                        <div class="flex justify-between border-b pb-3">
                            <span><?php echo __('shipping'); ?></span>
                            <span class="font-medium" id="cart-shipping"><?php echo $shipping == 0 ? __('free_shipping') : '<span>' . number_format($shipping, 2) . '</span> MAD'; ?></span>
                        </div>
                        
                        <!-- Total -->
                        <div class="flex justify-between pt-3 text-2xl font-extrabold text-gray-900">
                            <span><?php echo __('order_total'); ?></span>
                            <span id="cart-total"><span><?php echo number_format($total, 2); ?></span> MAD</span>
                        </div>
                    </div>
                    
<!-- Checkout Button -->
                    <a href="index.php?page=checkout" class="w-full py-3 bg-black text-white font-semibold text-sm tracking-widest uppercase rounded hover:bg-black/80 transition duration-150 text-center block <?php echo count($cart_items) == 0 ? 'opacity-50 pointer-events-none' : ''; ?>">
                        <?php echo __('proceed_to_checkout'); ?>
                    </a>

                    <!-- Continue Shopping Link -->
                    <p class="text-center text-sm">
                        <a href="index.php?page=shop" class="text-gray-500 hover:text-black transition">
                            <?php echo __('continue_shopping'); ?>
                        </a>
                    </p>
                </div>
            </aside>
        </div>
    </div>
</section>

<script>
// Update cart count in header
function updateHeaderCartCount(count) {
    const cartDot = document.getElementById('cart-dot');
    if (cartDot) {
        cartDot.textContent = count;
    }
}

// Format number as price
function formatPrice(price) {
    return number_format(price, 2);
}

function number_format(number, decimals) {
    return parseFloat(number).toFixed(decimals);
}

// Handle quantity update
document.querySelectorAll('.update-quantity').forEach(button => {
    button.addEventListener('click', function() {
        const key = this.dataset.key;
        const action = this.dataset.action;
        const quantityInput = document.querySelector(`.item-quantity[data-key="${key}"]`);
        let quantity = parseInt(quantityInput.value);
        
        if (action === 'increase') {
            quantity++;
        } else if (action === 'decrease') {
            quantity--;
        }
        
        // If quantity becomes 0, remove the item
        if (quantity < 0) quantity = 0;
        
        updateCartQuantity(key, quantity);
    });
});

// Handle remove item
document.querySelectorAll('.remove-item').forEach(button => {
    button.addEventListener('click', function() {
        const key = this.dataset.key;
        removeCartItem(key);
    });
});

function updateCartQuantity(key, quantity) {
    fetch('index.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=update_quantity&key=' + encodeURIComponent(key) + '&quantity=' + quantity
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (quantity === 0) {
                // Remove the item from DOM
                const itemElement = document.querySelector(`.cart-item[data-key="${key}"]`);
                if (itemElement) {
                    itemElement.remove();
                }
            } else {
                // Update quantity in DOM
                const quantityInput = document.querySelector(`.item-quantity[data-key="${key}"]`);
                if (quantityInput) {
                    quantityInput.value = quantity;
                }
                
                // Update item subtotal
                const itemElement = document.querySelector(`.cart-item[data-key="${key}"]`);
                if (itemElement) {
                    const priceElement = itemElement.querySelector('.item-price');
                    const subtotalElement = itemElement.querySelector('.item-subtotal');
                    if (priceElement && subtotalElement) {
                        const price = parseFloat(priceElement.textContent);
                        subtotalElement.textContent = formatPrice(price * quantity);
                    }
                }
            }
            
            // Update totals
            updateCartTotals(data.totals);
            
            // Update cart count
            updateHeaderCartCount(data.cart_count);
            document.getElementById('cart-item-count').textContent = data.cart_items.length;
            
            // Check if cart is empty
            if (data.cart_items.length === 0) {
                showEmptyCartMessage();
            }
        }
    })
    .catch(error => console.error('Error updating quantity:', error));
}

function removeCartItem(key) {
    fetch('index.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=remove_item&key=' + encodeURIComponent(key)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the item from DOM
            const itemElement = document.querySelector(`.cart-item[data-key="${key}"]`);
            if (itemElement) {
                itemElement.remove();
            }
            
            // Update totals
            updateCartTotals(data.totals);
            
            // Update cart count
            updateHeaderCartCount(data.cart_count);
            document.getElementById('cart-item-count').textContent = data.cart_items.length;
            
            // Check if cart is empty
            if (data.cart_items.length === 0) {
                showEmptyCartMessage();
            }
        }
    })
    .catch(error => console.error('Error removing item:', error));
}

function updateCartTotals(totals) {
    document.getElementById('cart-subtotal').innerHTML = '<span>' + formatPrice(totals.subtotal) + '</span> MAD';
    document.getElementById('cart-shipping').textContent = totals.shipping == 0 ? '<?php echo __('free_shipping'); ?>' : formatPrice(totals.shipping) + ' MAD';
    document.getElementById('cart-total').innerHTML = '<span>' + formatPrice(totals.total) + '</span> MAD';
}

function showEmptyCartMessage() {
    const cartItemsList = document.getElementById('cart-items-list');
    // Add empty cart message
    const emptyMessage = document.createElement('div');
    emptyMessage.id = 'empty-cart-message';
    emptyMessage.className = 'text-center p-10 bg-white rounded-xl shadow-lg';
    emptyMessage.innerHTML = `
        <svg class="w-20 h-20 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
        </svg>
        <p class="text-xl font-semibold text-gray-700 mb-4"><?php echo __('empty_cart'); ?></p>
        <a href="index.php?page=shop" class="inline-block bg-black text-white px-8 py-3 text-[10px] tracking-[0.2em] uppercase hover:bg-black/80 transition-colors">
            <?php echo __('continue_shopping'); ?>
        </a>
    `;
    cartItemsList.appendChild(emptyMessage);
}
</script>
