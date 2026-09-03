<?php
global $pdo;
try {
    // Cached for 24 hours
    $categories = szn_get_categories($pdo);
} catch (PDOException $e) {
    $categories = [];
}
?>
<section class="py-32 px-8 md:px-16 bg-white min-h-screen">
    <div class="text-center mb-20">
        <h2 class="text-5xl md:text-6xl serif mb-6"><?php echo __('categories'); ?></h2>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-6xl mx-auto">
        <?php if (count($categories) > 0): ?>
            <?php foreach ($categories as $cat): ?>
        <a href="index.php?page=shop&category=<?php echo $cat['slug']; ?>" class="group relative block overflow-hidden aspect-[3/4] md:aspect-[4/3] bg-gray-100">
<img src="<?php echo base_url($cat['image']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-1000 grayscale group-hover:grayscale-0">
            <div class="absolute inset-0 bg-black/30 group-hover:bg-black/10 transition-colors duration-500"></div>
            <div class="absolute inset-0 flex items-center justify-center">
                <h3 class="text-4xl md:text-5xl serif text-white tracking-widest uppercase text-center"><?php echo __($cat['slug']); ?></h3>
            </div>
            <div class="absolute bottom-8 left-0 w-full text-center opacity-0 group-hover:opacity-100 transition-opacity duration-500 transform translate-y-4 group-hover:translate-y-0">
                <span class="text-white text-xs tracking-[0.3em] uppercase border-b border-white pb-1"><?php echo __('view_products'); ?></span>
            </div>
        </a>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-full text-center py-10 opacity-50"><?php echo __('no_categories_found'); ?></div>
        <?php endif; ?>
    </div>
</section>

