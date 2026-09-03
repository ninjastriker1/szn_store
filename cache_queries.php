<?php
/**
 * SZN Cache Queries
 * ------------------
 * Ready-to-use cached versions of your most common DB queries.
 * Include this file (along with cache.php) wherever you need data.
 *
 * Usage:
 *   require_once 'cache.php';
 *   require_once 'cache_queries.php';
 *   $categories = szn_get_categories($pdo);
 */


// ─────────────────────────────────────────
// CATEGORIES
// ─────────────────────────────────────────

/**
 * Get all categories.
 * Cached for 24 hours — categories rarely change.
 */
function szn_get_categories(PDO $pdo): array
{
    return szn_cache_tracked('all_categories', 86400, function () use ($pdo) {
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    });
}


// ─────────────────────────────────────────
// SIZES
// ─────────────────────────────────────────

/**
 * Get all sizes.
 * Cached for 24 hours — sizes are static.
 */
function szn_get_sizes(PDO $pdo): array
{
    return szn_cache_tracked('all_sizes', 86400, function () use ($pdo) {
        $stmt = $pdo->query("SELECT * FROM sizes ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    });
}


// ─────────────────────────────────────────
// PRODUCTS
// ─────────────────────────────────────────

/**
 * Get all approved products (main shop page).
 * Cached for 30 minutes.
 */
function szn_get_all_products(PDO $pdo): array
{
    return szn_cache_tracked('all_products', 1800, function () use ($pdo) {
        $stmt = $pdo->query("
            SELECT * FROM products
            WHERE status = 'approved'
            ORDER BY created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    });
}

/**
 * Get approved products filtered by category slug.
 * Cached per category for 30 minutes.
 *
 * @param string $category_slug e.g. 'jackets', 'tops'
 */
function szn_get_products_by_category(PDO $pdo, string $category_slug): array
{
    $key = 'products_category_' . $category_slug;
    return szn_cache_tracked($key, 1800, function () use ($pdo, $category_slug) {
        $stmt = $pdo->prepare("
            SELECT * FROM products
            WHERE status = 'approved' AND category = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$category_slug]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    });
}

/**
 * Get a single product by ID (with its size/stock info).
 * Cached per product for 30 minutes.
 *
 * @param int $product_id
 */
function szn_get_product(PDO $pdo, int $product_id): ?array
{
    $key = 'product_' . $product_id;
    return szn_cache_tracked($key, 1800, function () use ($pdo, $product_id) {
        // Get product
        $stmt = $pdo->prepare("
            SELECT * FROM products WHERE id = ? AND status = 'approved'
        ");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) return null;

        // Get size stock for this product
        $stmt2 = $pdo->prepare("
            SELECT size, stock FROM product_size_stock WHERE product_id = ?
        ");
        $stmt2->execute([$product_id]);
        $product['size_stock'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        return $product;
    });
}


// ─────────────────────────────────────────
// CACHE INVALIDATION (call after DB changes)
// ─────────────────────────────────────────

/**
 * Call this after adding, approving, or deleting a product.
 * Clears all product-related cache entries.
 */
function szn_invalidate_product_cache(int $product_id = null): void
{
    // Clear full product list and category caches
    szn_cache_delete_by_prefix('all_products');
    szn_cache_delete_by_prefix('products_category_');

    // If a specific product was changed, clear its individual cache too
    if ($product_id !== null) {
        szn_cache_delete('product_' . $product_id);
    }
}

/**
 * Call this after adding or deleting a category.
 */
function szn_invalidate_category_cache(): void
{
    szn_cache_delete('all_categories');
}

/**
 * Call this after modifying sizes.
 */
function szn_invalidate_sizes_cache(): void
{
    szn_cache_delete('all_sizes');
}
