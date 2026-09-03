<?php
session_start();
session_destroy();
// Dynamically determine the correct path based on current request URI
$requestUri = $_SERVER['REQUEST_URI'];
$pathParts = explode('/', trim($requestUri, '/'));

// Find where 'salah' is in the path
$salahIndex = array_search('salah', $pathParts);

if ($salahIndex !== false) {
    // Build path up to and including 'salah'
    $basePath = '/' . implode('/', array_slice($pathParts, 0, $salahIndex + 1));
} else {
    $basePath = '';
}

header("Location: " . $basePath . "/index.php?page=home");
exit;
?>
