<?php
// Display latest products from database (cached 30 minutes)
global $pdo;
try {
    $latest_products = szn_cache_tracked('home_latest_products', 1800, function () use ($pdo) {
        $stmt_home = $pdo->prepare("SELECT * FROM products WHERE status = 'approved' ORDER BY created_at DESC LIMIT 4");
        $stmt_home->execute();
        return $stmt_home->fetchAll(PDO::FETCH_ASSOC);
    });
} catch (PDOException $e) {
    $latest_products = [];
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'en'; ?>" dir="<?php echo get_text_direction(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Shop - Home</title>
</head>
<body>
    <section class="relative h-screen w-full overflow-hidden bg-[#F2EDE7] flex flex-col items-center justify-center text-center">
        <img src="assets/images/hero-section.png" 
             alt="Model Hero" class="absolute inset-0 w-full h-full object-cover object-top">
        
        <div class="relative z-10 px-6 mt-20">
<h1 class="relative z-10 <?php echo ($_SESSION['lang'] ?? 'en') === 'fr' ? 'text-5xl md:text-[120px]' : 'text-7xl md:text-[120px]'; ?> leading-tight serif mb-8">
    <?php echo __('timeless_elegance'); ?>
</h1>

            <p class="max-w-2xl mx-auto text-sm md:text-base font-light leading-relaxed mb-12 opacity-80 text-black">
                <?php echo __('hero_desc'); ?>
            </p>
            <div class="flex flex-col md:flex-row justify-center gap-6">
<a href="index.php?page=shop" class="border border-black text-black px-12 py-5 text-[10px] tracking-[0.3em] uppercase hover:bg-white hover:text-black transition-all duration-500">
                    <?php echo __('shop_collection'); ?>
                </a>
                <a href="index.php?page=shop" class="border border-black text-black px-12 py-5 text-[10px] tracking-[0.3em] uppercase hover:bg-white hover:text-black transition-all duration-500">
                    <?php echo __('explore_lookbook'); ?>
                </a>
            </div>
        </div>
    </section>

    <section class="py-32 px-8 md:px-16 bg-white">
        <div class="text-center mb-20">
            <p class="text-[10px] tracking-[0.4em] uppercase mb-4 opacity-40"><?php echo __('explore'); ?></p>
            <h2 class="text-5xl md:text-6xl serif"><?php echo __('shop_by_category'); ?></h2>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="index.php?page=shop&category=tops" class="group relative cursor-pointer overflow-hidden block">
                <div class="aspect-[3/4] bg-gray-100">
                    <img src="assets/images/top.png" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-1000">
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-80 transition-opacity duration-500"></div>
                <h3 class="absolute bottom-8 <?php echo is_rtl() ? 'right-8' : 'left-8'; ?> text-3xl serif text-white group-hover:<?php echo is_rtl() ? '-translate-x' : 'translate-x'; ?>-2 transition-transform"><?php echo __('tops'); ?></h3>
            </a>
            <a href="index.php?page=shop&category=bottoms" class="group relative cursor-pointer overflow-hidden block">
                <div class="aspect-[3/4] bg-gray-100">
                    <img src="assets/images/bottom.png" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-1000">
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-80 transition-opacity duration-500"></div>
                <h3 class="absolute bottom-8 <?php echo is_rtl() ? 'right-8' : 'left-8'; ?> text-3xl serif text-white group-hover:<?php echo is_rtl() ? '-translate-x' : 'translate-x'; ?>-2 transition-transform"><?php echo __('bottoms'); ?></h3>
            </a>
            <a href="index.php?page=shop&category=shoes" class="group relative cursor-pointer overflow-hidden block">
                <div class="aspect-[3/4] bg-gray-100">
                    <img src="assets/images/shoes.jpeg" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-1000">
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-80 transition-opacity duration-500"></div>
                <h3 class="absolute bottom-8 <?php echo is_rtl() ? 'right-8' : 'left-8'; ?> text-3xl serif text-white group-hover:<?php echo is_rtl() ? '-translate-x' : 'translate-x'; ?>-2 transition-transform"><?php echo __('shoes'); ?></h3>
            </a>
            <a href="index.php?page=shop&category=accessories" class="group relative cursor-pointer overflow-hidden block">
                <div class="aspect-[3/4] bg-gray-100 p-12 flex items-center justify-center">
                    <img src="assets/images/accessories.png" class="w-full h-full object-cover shadow-sm group-hover:scale-105 transition-transform duration-1000">
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-80 transition-opacity duration-500"></div>
                <h3 class="absolute bottom-8 <?php echo is_rtl() ? 'right-8' : 'left-8'; ?> text-3xl serif text-white group-hover:<?php echo is_rtl() ? '-translate-x' : 'translate-x'; ?>-2 transition-transform"><?php echo __('accessories'); ?></h3>
            </a>
        </div>
    </section>

    <section class="pb-32 px-8 md:px-16 bg-white">
        <div class="flex justify-between items-end mb-16 border-b border-gray-100 pb-8">
            <div>
                <p class="text-[10px] tracking-[0.4em] uppercase mb-4 opacity-40"><?php echo __('curated_selection'); ?></p>
                <h2 class="text-5xl serif"><?php echo __('new_arrivals'); ?></h2>
            </div>
            <a href="index.php?page=shop" class="text-[10px] tracking-[0.3em] uppercase group flex items-center <?php echo is_rtl() ? 'flex-row-reverse' : ''; ?>">
                <?php echo __('view_all'); ?> <span class="<?php echo is_rtl() ? 'mr-2 group-hover:-translate-x-1' : 'ml-2 group-hover:translate-x-1'; ?> transition-transform">→</span>
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-12">
            <?php if (count($latest_products) > 0): ?>
                <?php foreach ($latest_products as $product): ?>
                <div class="group block cursor-pointer">
                    <div class="aspect-[4/5] overflow-hidden mb-6 bg-[#f9f9f9] relative">
<img src="<?php echo htmlspecialchars($product['image1'] ?? $product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full h-full object-cover mix-blend-multiply group-hover:scale-105 transition-transform duration-1000">
                        
                        <div class="absolute inset-0 bg-black/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                        
                        <!-- View Product Button -->
                        <a href="index.php?page=product&id=<?php echo $product['id']; ?>" class="absolute bottom-4 left-4 bg-white text-black px-4 py-2 text-xs uppercase tracking-widest opacity-0 transform translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300 hover:bg-black hover:text-white">
                            <?php echo __('view_product'); ?>
                        </a>
                    </div>
                    <h4 class="text-sm font-light mb-2"><?php echo htmlspecialchars($product['name']); ?></h4>
                    <p class="text-xs text-gray-500 font-light"><?php echo ucfirst($product['category']); ?></p>
                    <p class="text-sm opacity-50 font-light"><?php echo number_format($product['price'], 2); ?> MAD</p>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full text-center py-10 opacity-50">No new arrivals yet.</div>
            <?php endif; ?>
        </div>
    </section>
