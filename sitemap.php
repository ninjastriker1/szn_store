<?php
ob_start();
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/database.php';
ob_clean();

$site_url = 'https://szn.wuaze.com';

header('Content-Type: application/xml; charset=utf-8');

// Fetch all approved products
$products = $pdo->query("
    SELECT id, name, updated_at FROM products WHERE status = 'approved'
")->fetchAll();

// Fetch all categories
$categories = $pdo->query("
    SELECT slug FROM categories
")->fetchAll();

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">

    <!-- Static pages -->
    <url>
        <loc><?= $site_url ?>/</loc>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
        <!-- Multilingual alternates -->
        <xhtml:link rel="alternate" hreflang="en" href="<?= $site_url ?>/?lang=en"/>
        <xhtml:link rel="alternate" hreflang="fr" href="<?= $site_url ?>/?lang=fr"/>
        <xhtml:link rel="alternate" hreflang="ar" href="<?= $site_url ?>/?lang=ar"/>

    </url>

    <url>
        <loc><?= $site_url ?>/shop.php</loc>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
        <xhtml:link rel="alternate" hreflang="en" href="<?= $site_url ?>/shop.php?lang=en"/>
        <xhtml:link rel="alternate" hreflang="fr" href="<?= $site_url ?>/shop.php?lang=fr"/>
        <xhtml:link rel="alternate" hreflang="ar" href="<?= $site_url ?>/shop.php?lang=ar"/>
    </url>

    <url>
        <loc><?= $site_url ?>/contact.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <!-- Category pages -->
    <?php foreach ($categories as $cat): ?>
    <url>
        <loc><?= $site_url ?>/shop.php?category=<?= urlencode($cat['slug']) ?></loc>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
        <xhtml:link rel="alternate" hreflang="en" href="<?= $site_url ?>/shop.php?category=<?= urlencode($cat['slug']) ?>&amp;lang=en"/>
        <xhtml:link rel="alternate" hreflang="fr" href="<?= $site_url ?>/shop.php?category=<?= urlencode($cat['slug']) ?>&amp;lang=fr"/>
        <xhtml:link rel="alternate" hreflang="ar" href="<?= $site_url ?>/shop.php?category=<?= urlencode($cat['slug']) ?>&amp;lang=ar"/>
    </url>
    <?php endforeach; ?>

    <!-- Product pages -->
    <?php foreach ($products as $product): ?>
    <url>
        <loc><?= $site_url ?>/product.php?id=<?= $product['id'] ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($product['updated_at'])) ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
        <xhtml:link rel="alternate" hreflang="en" href="<?= $site_url ?>/product.php?id=<?= $product['id'] ?>&amp;lang=en"/>
        <xhtml:link rel="alternate" hreflang="fr" href="<?= $site_url ?>/product.php?id=<?= $product['id'] ?>&amp;lang=fr"/>
        <xhtml:link rel="alternate" hreflang="ar" href="<?= $site_url ?>/product.php?id=<?= $product['id'] ?>&amp;lang=ar"/>
    </url>
    <?php endforeach; ?>

</urlset>
