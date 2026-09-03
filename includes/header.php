<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'en'; ?>" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500&family=Inter:wght@300;400&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; color: #1a1a1a; }
        .serif { font-family: 'Playfair Display', serif; }
        .nav-transition { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
    </style>
    <title><?php echo isset($page_title) ? $page_title . ' | SZN' : 'SZN | Premium Fashion'; ?></title>
</head>
<body class="bg-white">
<?php
// Initialize session and security
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/security.php';
set_security_headers();
harden_session();
?>

    <nav id="main-nav" class="nav-transition fixed top-0 left-0 w-full z-50 flex items-center justify-between px-8 md:px-16 py-8 text-black bg-transparent">
        <div class="flex items-center gap-2 -ml-4 md:ml-0">
            <img src="assets/images/logo.png" alt="SZN" class="h-12 w-auto">
            <span class="text-2xl tracking-[0.1em] uppercase font-medium serif"></span>
        </div>
        
        <ul class="hidden md:flex gap-12 text-[13px] tracking-[0.2em] uppercase font-light">
            <li><a href="index.php?page=home" class="hover:opacity-50"><?php echo __('home'); ?></a></li>
            <li><a href="index.php?page=shop" class="hover:opacity-50"><?php echo __('shop'); ?></a></li>
            <li><a href="index.php?page=categories" class="hover:opacity-50"><?php echo __('categories'); ?></a></li>
            <li><a href="index.php?page=about" class="hover:opacity-50"><?php echo __('about'); ?></a></li>
            <li><a href="index.php?page=contact" class="hover:opacity-50"><?php echo __('contact'); ?></a></li>
        </ul>

        <div class="flex items-center gap-6">
            <div class="hidden md:flex items-center gap-1 text-[10px] tracking-[0.2em] uppercase font-light">
                <?php 
                $langParams = $_GET;
                ?>
                <a href="?<?php echo http_build_query(array_merge($langParams, ['lang' => 'en'])); ?>" class="hover:opacity-50 transition-opacity">EN</a>
                <span class="opacity-30">|</span>
                <a href="?<?php echo http_build_query(array_merge($langParams, ['lang' => 'fr'])); ?>" class="hover:opacity-50 transition-opacity">FR</a>
                <span class="opacity-30">|</span>
                <a href="?<?php echo http_build_query(array_merge($langParams, ['lang' => 'ar'])); ?>" class="hover:opacity-50 transition-opacity">AR</a>
            </div>
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="relative">
                    <button id="user-dropdown-btn" type="button" class="focus:outline-none" aria-label="<?php echo __('account'); ?>">
                        <svg class="w-5 h-5 cursor-pointer" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="1.2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </button>
                    <div id="user-dropdown" class="absolute <?php echo is_rtl() ? 'left-0' : 'right-0'; ?> mt-2 w-48 bg-white shadow-lg rounded opacity-0 invisible transition-all duration-200 z-50">
                        <div class="px-4 py-3 border-b border-gray-200">
                            <p class="text-xs font-light text-gray-500"><?php echo __('logged_in_as'); ?></p>
                            <p class="text-sm font-light"><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                        </div>
                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <a href="admin/dashboard.php" class="block px-4 py-3 text-xs uppercase tracking-widest font-light hover:bg-gray-50 transition-colors text-blue-600"><?php echo __('admin_panel'); ?></a>
                        <?php endif; ?>
                        <a href="index.php?page=profile" class="block px-4 py-3 text-xs uppercase tracking-widest font-light hover:bg-gray-50 transition-colors"><?php echo __('profile'); ?></a>
                        <a href="index.php?page=wishlist" class="block px-4 py-3 text-xs uppercase tracking-widest font-light hover:bg-gray-50 transition-colors"><?php echo __('wishlist'); ?></a>
                        <a href="index.php?page=orders" class="block px-4 py-3 text-xs uppercase tracking-widest font-light hover:bg-gray-50 transition-colors"><?php echo __('orders'); ?></a>
                        <a href="<?php echo base_url('includes/logout.php'); ?>" class="block px-4 py-3 text-xs uppercase tracking-widest font-light hover:bg-red-50 text-red-600 transition-colors border-t border-gray-200"><?php echo __('logout'); ?></a>
                    </div>
                </div>
            <?php else: ?>
                <a href="index.php?page=login" aria-label="<?php echo __('login'); ?>"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="1.2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg></a>
            <?php endif; ?>
            <div class="relative">
                <a href="index.php?page=cart">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="1.2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    <span id="cart-dot" class="absolute -top-1 <?php echo is_rtl() ? '-left-1' : '-right-1'; ?> bg-black text-white text-[8px] w-4 h-4 rounded-full flex items-center justify-center"><?php echo get_cart_count(); ?></span>
                </a>
            </div>
            
            <!-- Mobile Menu Button -->
            <button id="mobile-menu-btn" class="md:hidden z-50">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="1.2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
        </div>
    </nav>

    <!-- Mobile Menu Overlay -->
    <div id="mobile-menu" class="fixed inset-0 bg-white z-40 transform <?php echo is_rtl() ? '-translate-x-full' : 'translate-x-full'; ?> transition-transform duration-500 cubic-bezier(0.4, 0, 0.2, 1) md:hidden flex flex-col items-center justify-center">
        <ul class="text-center space-y-8 text-2xl serif">
            <li><a href="index.php?page=home" class="block hover:opacity-50"><?php echo __('home'); ?></a></li>
            <li><a href="index.php?page=shop" class="block hover:opacity-50"><?php echo __('shop'); ?></a></li>
            <li><a href="index.php?page=categories" class="block hover:opacity-50"><?php echo __('categories'); ?></a></li>
            <li><a href="index.php?page=about" class="block hover:opacity-50"><?php echo __('about'); ?></a></li>
            <li><a href="index.php?page=contact" class="block hover:opacity-50"><?php echo __('contact'); ?></a></li>
        </ul>
        
        <!-- Mobile Language Switcher -->
        <div class="flex items-center gap-4 text-sm font-light uppercase tracking-widest mt-8 mb-4">
             <?php $langParams = $_GET; ?>
             <a href="?<?php echo http_build_query(array_merge($langParams, ['lang' => 'en'])); ?>" class="<?php echo ($_SESSION['lang'] ?? 'en') == 'en' ? 'text-black' : 'text-gray-400'; ?>">EN</a>
             <span class="text-gray-300">|</span>
             <a href="?<?php echo http_build_query(array_merge($langParams, ['lang' => 'fr'])); ?>" class="<?php echo ($_SESSION['lang'] ?? 'en') == 'fr' ? 'text-black' : 'text-gray-400'; ?>">FR</a>
             <span class="text-gray-300">|</span>
             <a href="?<?php echo http_build_query(array_merge($langParams, ['lang' => 'ar'])); ?>" class="<?php echo ($_SESSION['lang'] ?? 'en') == 'ar' ? 'text-black' : 'text-gray-400'; ?>">AR</a>
        </div>
    </div>
    
    <main>
