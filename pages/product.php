<?php
global $pdo;

// Get product ID safely
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id > 0) {
    try {
        // Cached for 30 minutes (includes per-size stock inside $product['size_stock'])
        $product = szn_get_product($pdo, $product_id);

        if (!$product) {
            // Product not found
            echo "<div class='py-32 text-center'>Product not found. <a href='index.php?page=shop' class='underline'>Return to shop</a></div>";
            return;
        }

        // Build $sizeStocks as key=>value map from the cached size_stock array
        $sizeStocks = [];
        foreach ($product['size_stock'] as $row) {
            $sizeStocks[$row['size']] = $row['stock'];
        }
    } catch (PDOException $e) {
        // Log error and show generic message
        echo "<div class='py-32 text-center'>An error occurred. Please try again.</div>";
        return;
    }
} else {
    echo "<div class='py-32 text-center'>Invalid product. <a href='index.php?page=shop' class='underline'>Return to shop</a></div>";
    return;
}
?>

<section class="py-20 px-8 md:px-16 bg-white min-h-screen">
    <div class="max-w-7xl mx-auto">
        <!-- Breadcrumb -->
        <nav class="text-xs uppercase tracking-widest text-gray-500 mb-12 flex items-center flex-wrap gap-2">
            <a href="index.php?page=home" class="hover:text-black transition-colors"><?php echo __('home'); ?></a>
            <span class="text-gray-300">/</span>
            <a href="index.php?page=shop" class="hover:text-black transition-colors"><?php echo __('shop'); ?></a>
            <?php if (!empty($product['category'])): ?>
                <span class="text-gray-300">/</span>
                <a href="index.php?page=shop&category=<?php echo htmlspecialchars($product['category']); ?>" class="hover:text-black transition-colors"><?php echo __($product['category']); ?></a>
            <?php endif; ?>
            <span class="text-gray-300">/</span>
            <span class="text-black font-medium border-b border-black pb-0.5"><?php echo htmlspecialchars($product['name']); ?></span>
        </nav>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-16 lg:gap-24">
            <!-- Product Image Gallery -->
            <div class="space-y-4">
                <div class="aspect-[3/4] bg-gray-50 rounded-lg overflow-hidden relative group">
                    <img id="main-image" src="<?php echo htmlspecialchars($product['image1'] ?: $product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full h-full object-cover transition-opacity duration-300">
                    <?php if (isset($product['images']) && count($product['images']) > 1): ?>
                        <button id="next-image" class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/80 hover:bg-white p-2 rounded-full shadow-lg opacity-0 group-hover:opacity-100 transition-all duration-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </button>
                    <?php endif; ?>
                </div>
                
                <!-- Thumbnail switcher -->
                <?php 
                $allImages = [$product['image1'], $product['image2'], $product['image3'], $product['image4']];
                $thumbs = array_filter($allImages);
                if (!empty($thumbs)): 
                ?>
                    <div class="flex gap-2">
                        <?php foreach ($thumbs as $i => $thumb): ?>
                            <div class="relative flex-shrink-0">
                                <img src="<?php echo htmlspecialchars($thumb); ?>" alt="Image <?php echo $i+1; ?>" class="w-20 h-20 object-cover rounded-lg cursor-pointer border-2 <?php echo $i==0 ? 'border-black ring-2 ring-black ring-offset-2' : 'border-transparent hover:border-gray-300'; ?> transition-all duration-200" onclick="switchMainImage('<?php echo htmlspecialchars($thumb); ?>')">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Product Details -->
            <div class="flex flex-col justify-center">
                <h1 class="text-4xl md:text-5xl serif mb-4 leading-tight"><?php echo htmlspecialchars($product['name']); ?></h1>
                <div class="flex items-center space-x-4 mb-8">
                    <p class="text-xl font-light"><?php echo number_format($product['price'], 2); ?> MAD</p>
                    <?php if ($product['stock'] > 0): ?>
                        <span class="px-2 py-1 bg-gray-100 text-xs text-gray-600 rounded">
                            <?php echo $product['stock'] . ' ' . __('in_stock', 'in stock'); ?>
                        </span>
                    <?php else: ?>
                        <span class="px-2 py-1 bg-red-50 text-xs text-red-600 rounded">
                            Out of Stock
                        </span>
                    <?php endif; ?>
                </div>

                <div class="prose prose-sm font-light text-gray-600 mb-12 leading-relaxed">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>

                <?php 
                $hasSizes = !empty($product['sizes']);
                if ($hasSizes && $product['stock'] > 0): 
                    $sizes = array_map('trim', explode(',', $product['sizes']));
                ?>
                <!-- Size Selector -->
                <div class="mb-10" id="size-selector-container">
                    <h3 class="text-xs uppercase tracking-widest font-bold mb-4"><?php echo __('size'); ?></h3>
                    <div class="flex flex-wrap gap-4">
                        <?php 
                        foreach ($sizes as $size): 
                            $sizeStock = isset($sizeStocks[$size]) ? $sizeStocks[$size] : 0;
                            $isOutOfStock = ($sizeStock <= 0);
                        ?>
                            <label class="relative <?php echo $isOutOfStock ? 'cursor-not-allowed opacity-40' : 'cursor-pointer group'; ?>">
                                <input type="radio" name="size" value="<?php echo htmlspecialchars($size); ?>" 
                                    class="peer hidden" <?php echo $isOutOfStock ? 'disabled' : ''; ?>>
                                <span class="px-4 h-12 flex items-center justify-center border border-gray-200 text-sm font-light uppercase transition-all 
                                    <?php echo !$isOutOfStock ? 'peer-checked:border-black peer-checked:bg-black peer-checked:text-white group-hover:border-black' : ''; ?> 
                                    min-w-[3rem] relative overflow-hidden">
                                    <?php echo htmlspecialchars($size); ?>
                                    <?php if ($isOutOfStock): ?>
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <div class="w-full h-[1px] bg-gray-400 rotate-45 transform"></div>
                                        </div>
                                    <?php endif; ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Wishlist Button -->
                <?php if (isLoggedIn()): ?>
                    <button type="button" onclick="addToWishlist(<?php echo $product['id']; ?>)" class="flex items-center justify-center w-full border-2 border-black text-black py-3 text-sm uppercase tracking-[0.2em] hover:bg-black hover:text-white transition-all mb-4">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                        <?php echo __('add_wishlist'); ?>
                    </button>
                <?php endif; ?>
                
                <!-- Add to Cart -->
                <form id="add-to-cart-form" method="POST" action="index.php">
                    <input type="hidden" name="action" value="add_to_cart">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <input type="hidden" name="size" id="selected-size" value="">
                    
                    <?php if ($product['stock'] > 0): ?>
                        <button type="submit" id="add-to-cart-btn" class="w-full bg-black text-white py-4 text-xs uppercase tracking-[0.2em] transform active:scale-[0.98] transition-all hover:bg-gray-900 mb-6">
                            <?php echo __('add_to_cart'); ?>
                        </button>
                    <?php else: ?>
                        <button type="button" disabled class="w-full bg-gray-300 text-gray-500 py-4 text-xs uppercase tracking-[0.2em] cursor-not-allowed mb-6">
                            Out of Stock
                        </button>
                    <?php endif; ?>
                </form>
                
                <!-- Cart Message -->
                <div id="cart-message" class="hidden text-center p-3 mb-4 text-sm"></div>
                
                <p class="text-xs text-gray-400 font-light text-center uppercase tracking-widest">
                </p>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('add-to-cart-form');
    const sizeInputs = document.querySelectorAll('input[name="size"]');
    const selectedSizeInput = document.getElementById('selected-size');
    const messageDiv = document.getElementById('cart-message');
    const addToCartBtn = document.getElementById('add-to-cart-btn');
    
    // Image switcher
    window.switchMainImage = function(imgSrc) {
        const mainImage = document.getElementById('main-image');
        mainImage.src = imgSrc;
        mainImage.classList.add('opacity-0');
        setTimeout(() => mainImage.classList.remove('opacity-0'), 150);
        
        // Update thumbnail active state
        document.querySelectorAll('img[onclick*=\"switchMainImage\"]').forEach((thumb, index) => {
            thumb.parentElement.querySelector('img').classList.toggle('border-black', thumb.src === imgSrc);
            thumb.parentElement.querySelector('img').classList.toggle('ring-2', thumb.src === imgSrc);
            thumb.parentElement.querySelector('img').classList.toggle('ring-offset-2', thumb.src === imgSrc);
            thumb.parentElement.querySelector('img').classList.toggle('ring-black', thumb.src === imgSrc);
            thumb.parentElement.querySelector('img').classList.toggle('border-transparent', thumb.src !== imgSrc);
            thumb.parentElement.querySelector('img').classList.toggle('hover:border-gray-300', thumb.src !== imgSrc);
        });
    };
    
window.addToWishlist = function(productId) {
        if (!<?php echo isLoggedIn() ? 'true' : 'false'; ?>) {
            alert('<?php echo __('please_login_wishlist'); ?>');
            return;
        }
        fetch('index.php?ajax=wishlist', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=add&product_id=' + productId
        }).then(response => response.json()).then(data => {
            showToast(data.success ? 'Added to wishlist!' : data.message);
        }).catch(() => showToast('Error'));
    };
    
    function showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-8 right-8 bg-black text-white px-6 py-3 text-sm rounded shadow-2xl opacity-0 transition-all z-50';
        toast.textContent = message;
        document.body.appendChild(toast);
        toast.classList.remove('opacity-0');
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => document.body.removeChild(toast), 300);
        }, 2500);
    };
    
    // Handle size selection
    sizeInputs.forEach(input => {
        input.addEventListener('change', function() {
            selectedSizeInput.value = this.value;
            // Remove any error message
            messageDiv.classList.add('hidden');
            messageDiv.classList.remove('bg-red-100', 'text-red-700');
        });
    });
    
    // Handle form submission
    form.addEventListener('submit', function(e) {
        // Check if size is selected (only if size container exists)
        const sizeContainer = document.getElementById('size-selector-container');
        if (sizeContainer && !selectedSizeInput.value) {
            e.preventDefault();
            messageDiv.textContent = '<?php echo __('please_select_size'); ?>';
            messageDiv.classList.remove('hidden');
            messageDiv.classList.add('bg-red-100', 'text-red-700');
            return false;
        }
        
        // Allow form to submit normally
        return true;
    });
});
</script>
