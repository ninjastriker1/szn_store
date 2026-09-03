<section class="py-32 px-8 md:px-16 bg-white min-h-screen">
    <?php
    global $pdo;

    // Fetch Filter Data
    $category = isset($_GET['category']) ? $_GET['category'] : '';
    // Price Slider Data
    $min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
    $max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 1000;

    $has_price_filter = isset($_GET['max_price']);

    if ($has_price_filter) {
        // Dynamic price filter — skip caching, run fresh query
        $query = "SELECT * FROM products WHERE 1=1 AND (status = 'approved' OR status IS NULL)";
        $params = [];

        if ($category) {
            $query .= " AND category = :category";
            $params[':category'] = $category;
        }

        $query .= " AND price <= :max_price ORDER BY created_at DESC";
        $params[':max_price'] = $max_price;

        try {
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $products = [];
        }
    } elseif ($category) {
        // Category filter only — use per-category cache (30 min)
        try {
            $products = szn_get_products_by_category($pdo, $category);
        } catch (PDOException $e) {
            $products = [];
        }
    } else {
        // No filters — use full approved-products cache (30 min)
        try {
            $products = szn_get_all_products($pdo);
        } catch (PDOException $e) {
            $products = [];
        }
    }
    ?>

    <!-- Title: shown inline on desktop (inside flex), stacked on mobile -->
    <div class="text-center mb-20 md:hidden">
        <h2 class="text-5xl md:text-6xl serif mb-6"><?php echo __('shop'); ?></h2>
        <p class="max-w-2xl mx-auto text-sm font-light opacity-60">
            <?php echo __('shop_description'); ?>
        </p>
    </div>

    <div class="flex flex-col md:flex-row gap-12 md:items-start">

        <!-- Filter Sidebar — pulled up on desktop with negative margin -->
        <div class="w-full md:w-64 flex-shrink-0 md:-mt-20">
            <form action="index.php" method="GET" class="sticky top-24 space-y-12">
                <input type="hidden" name="page" value="shop">

                <!-- Mobile filter toggle header -->
                <div class="flex justify-between items-center md:hidden border-b border-gray-100 pb-4 mb-8">
                    <span class="uppercase tracking-widest text-xs"><?php echo __('filters'); ?></span>
                    <button type="button" onclick="toggleMobileFilters()" class="text-xs underline flex items-center space-x-1 group">
                        <span><?php echo __('toggle'); ?></span>
                        <svg id="toggle-icon" class="w-3 h-3 transform transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                </div>

                <div id="mobile-filters" class="space-y-12 overflow-hidden transition-all duration-500 ease-in-out max-h-0 opacity-0 md:max-h-none md:opacity-100 md:overflow-visible">

                    <!-- Categories -->
                    <div>
                        <h3 class="text-xs uppercase tracking-widest font-bold mb-6 border-b border-black pb-2"><?php echo __('categories'); ?></h3>
                        <div class="space-y-3">
                            <label class="block cursor-pointer group">
                                <input type="radio" name="category" value="" <?php echo $category == '' ? 'checked' : ''; ?> class="peer hidden" onchange="this.form.submit()">
                                <span class="text-sm font-light text-gray-500 peer-checked:text-black peer-checked:font-medium transition-colors group-hover:text-black"><?php echo __('view_all'); ?></span>
                            </label>
                            <label class="block cursor-pointer group">
                                <input type="radio" name="category" value="tops" <?php echo $category == 'tops' ? 'checked' : ''; ?> class="peer hidden" onchange="this.form.submit()">
                                <span class="text-sm font-light text-gray-500 peer-checked:text-black peer-checked:font-medium transition-colors group-hover:text-black"><?php echo __('tops'); ?></span>
                            </label>
                            <label class="block cursor-pointer group">
                                <input type="radio" name="category" value="bottoms" <?php echo $category == 'bottoms' ? 'checked' : ''; ?> class="peer hidden" onchange="this.form.submit()">
                                <span class="text-sm font-light text-gray-500 peer-checked:text-black peer-checked:font-medium transition-colors group-hover:text-black"><?php echo __('bottoms'); ?></span>
                            </label>
                            <label class="block cursor-pointer group">
                                <input type="radio" name="category" value="jackets" <?php echo $category == 'jackets' ? 'checked' : ''; ?> class="peer hidden" onchange="this.form.submit()">
                                <span class="text-sm font-light text-gray-500 peer-checked:text-black peer-checked:font-medium transition-colors group-hover:text-black"><?php echo __('jackets'); ?></span>
                            </label>
                            <label class="block cursor-pointer group">
                                <input type="radio" name="category" value="football_jerseys" <?php echo $category == 'football_jerseys' ? 'checked' : ''; ?> class="peer hidden" onchange="this.form.submit()">
                                <span class="text-sm font-light text-gray-500 peer-checked:text-black peer-checked:font-medium transition-colors group-hover:text-black"><?php echo __('football_jerseys'); ?></span>
                            </label>
                            <label class="block cursor-pointer group">
                                <input type="radio" name="category" value="perfumes" <?php echo $category == 'perfumes' ? 'checked' : ''; ?> class="peer hidden" onchange="this.form.submit()">
                                <span class="text-sm font-light text-gray-500 peer-checked:text-black peer-checked:font-medium transition-colors group-hover:text-black"><?php echo __('perfumes'); ?></span>
                            </label>
                            <label class="block cursor-pointer group">
                                <input type="radio" name="category" value="hats" <?php echo $category == 'hats' ? 'checked' : ''; ?> class="peer hidden" onchange="this.form.submit()">
                                <span class="text-sm font-light text-gray-500 peer-checked:text-black peer-checked:font-medium transition-colors group-hover:text-black"><?php echo __('hats'); ?></span>
                            </label>
                            <label class="block cursor-pointer group">
                                <input type="radio" name="category" value="shoes" <?php echo $category == 'shoes' ? 'checked' : ''; ?> class="peer hidden" onchange="this.form.submit()">
                                <span class="text-sm font-light text-gray-500 peer-checked:text-black peer-checked:font-medium transition-colors group-hover:text-black"><?php echo __('shoes'); ?></span>
                            </label>
                            <label class="block cursor-pointer group">
                                <input type="radio" name="category" value="accessories" <?php echo $category == 'accessories' ? 'checked' : ''; ?> class="peer hidden" onchange="this.form.submit()">
                                <span class="text-sm font-light text-gray-500 peer-checked:text-black peer-checked:font-medium transition-colors group-hover:text-black"><?php echo __('accessories'); ?></span>
                            </label>
                        </div>
                    </div>

                    <!-- Price Slider -->
                    <div>
                        <h3 class="text-xs uppercase tracking-widest font-bold mb-6 border-b border-black pb-2"><?php echo __('price'); ?></h3>
                        <div class="space-y-6">
                            <div class="flex justify-between text-sm font-medium">
                                <span class="text-gray-400">0 MAD</span>
                                <span><span id="max-display"><?php echo $max_price; ?></span> MAD</span>
                            </div>
                            <div class="relative h-1 bg-gray-200 rounded-full cursor-pointer group">
                                <div class="absolute h-full bg-black rounded-full transition-all duration-100 ease-out" id="slider-track" style="left: 0%; right: 0%;"></div>
                                <input type="range" name="max_price" id="max-price" min="0" max="1000" value="<?php echo $max_price; ?>" class="absolute w-full h-full opacity-0 cursor-pointer z-10">
                                <div class="absolute top-1/2 -mt-3 w-6 h-6 bg-white border-2 border-black rounded-full shadow-lg pointer-events-none transform -translate-x-1/2 transition-transform duration-100 ease-out group-hover:scale-110" id="max-thumb" style="left: 100%;"></div>
                            </div>
                        </div>
                        <script>
                            const maxPrice = document.getElementById('max-price');
                            const maxDisplay = document.getElementById('max-display');
                            const maxThumb = document.getElementById('max-thumb');
                            const track = document.getElementById('slider-track');

                            function updateSlider() {
                                let max = parseInt(maxPrice.value);
                                const percentMax = (max / 1000) * 100;
                                maxThumb.style.left = percentMax + '%';
                                track.style.right = (100 - percentMax) + '%';
                                maxDisplay.textContent = max;
                            }

                            maxPrice.addEventListener('input', updateSlider);
                            updateSlider();
                        </script>
                    </div>

                    <div class="text-center pt-2">
                        <a href="index.php?page=shop" class="text-xs font-light text-gray-500 border-b border-transparent hover:border-gray-500 hover:text-black transition-all"><?php echo __('clear_all'); ?></a>
                    </div>
                    <button type="submit" class="w-full bg-black text-white py-3 text-xs uppercase tracking-[0.2em] transform hover:-translate-y-1 transition-transform duration-300">
                        <?php echo __('apply'); ?>
                    </button>

                </div> <!-- End mobile-filters wrapper -->
            </form>

            <script>
                function toggleMobileFilters() {
                    const filters = document.getElementById('mobile-filters');
                    const icon = document.getElementById('toggle-icon');

                    if (filters.classList.contains('max-h-0')) {
                        filters.classList.remove('max-h-0', 'opacity-0');
                        filters.classList.add('max-h-[2000px]', 'opacity-100');
                        icon.classList.add('rotate-180');
                    } else {
                        filters.classList.remove('max-h-[2000px]', 'opacity-100');
                        filters.classList.add('max-h-0', 'opacity-0');
                        icon.classList.remove('rotate-180');
                    }
                }
            </script>
        </div>

        <!-- Product Grid (includes desktop title above it) -->
        <div class="flex-1">

            <!-- Desktop-only title above product grid -->
            <div class="hidden md:block text-center mb-20">
                <h2 class="text-5xl md:text-6xl serif mb-6"><?php echo __('shop'); ?></h2>
                <p class="max-w-2xl mx-auto text-sm font-light opacity-60">
                    <?php echo __('shop_description'); ?>
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $product): ?>
                        <div class="group block cursor-pointer">
                            <div class="aspect-[4/5] overflow-hidden mb-6 bg-[#f9f9f9] relative">
                                <a href="index.php?page=product&id=<?php echo $product['id']; ?>">
                                    <img src="<?php echo htmlspecialchars($product['image1'] ?: $product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-1000">
                                </a>

                                <div class="absolute inset-0 bg-black/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

                                <!-- Action Buttons -->
                                <div class="absolute bottom-4 right-4 flex flex-col gap-2">
                                    <button type="button" onclick="addToWishlist(<?php echo $product['id']; ?>)" class="bg-white text-black w-10 h-10 flex items-center justify-center opacity-0 transform translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300 hover:bg-black hover:text-white ring-1 ring-transparent hover:ring-gray-300" title="<?php echo __('add_wishlist'); ?>">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-width="1.2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                        </svg>
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
                                    <p class="text-xs text-gray-500 font-light"><?php echo __($product['category']); ?></p>
                                </div>
                                <p class="text-sm font-light"><?php echo number_format($product['price'], 2); ?> MAD</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full py-20 text-center">
                        <p class="text-gray-500 font-light"><?php echo __('no_products_found'); ?></p>
                        <a href="index.php?page=shop" class="inline-block mt-4 text-xs uppercase tracking-widest border-b border-black pb-1"><?php echo __('clear_filters'); ?></a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <div class="mt-20 flex justify-center space-x-4">
                <button class="w-10 h-10 flex items-center justify-center border border-black text-black">1</button>
                <button class="w-10 h-10 flex items-center justify-center border border-transparent text-gray-400 hover:text-black">2</button>
                <button class="w-10 h-10 flex items-center justify-center border border-transparent text-gray-400 hover:text-black">3</button>
                <button class="w-10 h-10 flex items-center justify-center border border-transparent text-gray-400 hover:text-black">→</button>
            </div>
        </div>
    </div>
</section>

<script>
    function addToWishlist(productId) {
        if (!<?php echo isLoggedIn() ? 'true' : 'false'; ?>) {
            alert('<?php echo __('please_login_wishlist'); ?>');
            return;
        }

        fetch('index.php?ajax=wishlist', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=add&product_id=' + productId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelector('[onclick="addToWishlist(' + productId + ')"]').classList.add('text-red-500');
                }
                showToast(data.message || '<?php echo __('added_to_wishlist'); ?>');
            })
            .catch(error => {
                showToast('Error adding to wishlist');
            });
    }

    function showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-8 left-1/2 transform -translate-x-1/2 bg-black text-white px-6 py-3 text-sm font-light opacity-0 transition-opacity duration-300 z-50 rounded shadow-2xl';
        toast.textContent = message;
        document.body.appendChild(toast);
        toast.classList.remove('opacity-0');
        setTimeout(() => {
            toast.classList.add('opacity-0');
            setTimeout(() => document.body.removeChild(toast), 300);
        }, 3000);
    }
</script>