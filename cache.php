<?php
/**
 * SZN File-Based Caching System
 * --------------------------------
 * Usage: include this file wherever you need caching.
 * Make sure the /cache folder exists and is writable.
 */

define('CACHE_DIR', __DIR__ . '/cache/');
define('CACHE_EXT', '.cache');

// Create the cache folder if it doesn't exist
if (!is_dir(CACHE_DIR)) {
    mkdir(CACHE_DIR, 0755, true);
}

/**
 * Get data from cache, or run the query and store it.
 *
 * @param string   $key      A unique name for this cache (e.g. 'all_categories')
 * @param int      $ttl      Time to live in seconds (e.g. 3600 = 1 hour)
 * @param callable $callback A function that returns the fresh data from DB
 * @return mixed             The cached or freshly fetched data
 */
function szn_cache(string $key, int $ttl, callable $callback): mixed
{
    $file = CACHE_DIR . md5($key) . CACHE_EXT;

    // If cache file exists and is still fresh, return it
    if (file_exists($file) && (time() - filemtime($file)) < $ttl) {
        $data = file_get_contents($file);
        return unserialize($data);
    }

    // Otherwise, run the callback (your DB query), store, and return
    $data = $callback();
    file_put_contents($file, serialize($data));
    return $data;
}

/**
 * Delete a specific cache entry by key.
 * Call this when you update/add/delete data in the DB.
 *
 * @param string $key The same key you used in szn_cache()
 */
function szn_cache_delete(string $key): void
{
    $file = CACHE_DIR . md5($key) . CACHE_EXT;
    if (file_exists($file)) {
        unlink($file);
    }
}

/**
 * Delete ALL cache files at once.
 * Useful for a full refresh after major changes.
 */
function szn_cache_clear_all(): void
{
    $files = glob(CACHE_DIR . '*' . CACHE_EXT);
    if ($files) {
        foreach ($files as $file) {
            unlink($file);
        }
    }
}

/**
 * Delete all cache entries whose key starts with a prefix.
 * Useful for clearing only product cache without touching categories, etc.
 *
 * @param string $prefix e.g. 'product_' will clear product_1, product_2, etc.
 */
function szn_cache_delete_by_prefix(string $prefix): void
{
    // We store a map of key => filename in a small index file
    $index_file = CACHE_DIR . 'cache_index.json';
    if (!file_exists($index_file)) return;

    $index = json_decode(file_get_contents($index_file), true) ?? [];

    foreach ($index as $key => $filename) {
        if (str_starts_with($key, $prefix)) {
            $file = CACHE_DIR . $filename;
            if (file_exists($file)) unlink($file);
            unset($index[$key]);
        }
    }

    file_put_contents($index_file, json_encode($index));
}

/**
 * Extended version of szn_cache() that also tracks keys in an index.
 * Use this instead of szn_cache() when you need prefix-based deletion.
 *
 * @param string   $key
 * @param int      $ttl
 * @param callable $callback
 * @return mixed
 */
function szn_cache_tracked(string $key, int $ttl, callable $callback): mixed
{
    $filename = md5($key) . CACHE_EXT;
    $file     = CACHE_DIR . $filename;

    // Save key -> filename in index
    $index_file = CACHE_DIR . 'cache_index.json';
    $index = file_exists($index_file)
        ? (json_decode(file_get_contents($index_file), true) ?? [])
        : [];

    if (!isset($index[$key])) {
        $index[$key] = $filename;
        file_put_contents($index_file, json_encode($index));
    }

    // Check if cache is still fresh
    if (file_exists($file) && (time() - filemtime($file)) < $ttl) {
        return unserialize(file_get_contents($file));
    }

    // Run callback, store result
    $data = $callback();
    file_put_contents($file, serialize($data));
    return $data;
}
