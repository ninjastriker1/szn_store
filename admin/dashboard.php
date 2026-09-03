<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Dashboard counters
$stmt = $pdo->query("SELECT COUNT(*) FROM products");
$nb_products = (int)$stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM orders");
$nb_orders = (int)$stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$nb_users = (int)$stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM delivery_cities");
$nb_cities = (int)$stmt->fetchColumn();

$stmt = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM orders");
$revenue = (float)$stmt->fetchColumn();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: " . base_url('index.php?page=login'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; color: #1a1a1a; background-color: #ffffff; overflow-x: hidden; }
        .serif { font-family: 'Playfair Display', serif; }
        .sidebar-item { letter-spacing: 0.1em; font-size: 0.7rem; }
        
        /* Fixed Grid Layout to match image exactly */
        .stat-grid { display: grid; border-top: 1px solid #f3f4f6; border-left: 1px solid #f3f4f6; }
        .stat-item { padding: 3rem; border-right: 1px solid #f3f4f6; border-bottom: 1px solid #f3f4f6; }
        
        @media (max-width: 768px) {
            .stat-grid { grid-template-columns: 1fr; }
        }
        @media (min-width: 769px) {
            .stat-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body class="antialiased">
    <div class="flex min-h-screen">
        
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-100 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out flex flex-col">
            <div class="p-10">
                <h1 class="serif text-3xl font-semibold tracking-tighter">SZN</h1>
            </div>
            
            <?php include 'sidebar.php'; ?>


            <div class="p-10">
                <a href="../includes/logout.php" class="flex items-center space-x-4 text-gray-300 hover:text-black transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    <span class="sidebar-item uppercase"><?php echo __('logout'); ?></span>
                </a>
            </div>
        </aside>

        <main class="flex-1 lg:ml-64 flex flex-col min-h-screen">
            <header class="h-16 flex items-center justify-between px-8 border-b border-gray-50">
                <button id="mobile-toggle" class="lg:hidden p-2 text-gray-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="1.5" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                </button>
                <div class="hidden lg:block"></div> 
                <div class="flex items-center gap-4">
                    <a href="<?php echo base_url('index.php?page=home'); ?>" class="text-[10px] uppercase tracking-[0.2em] text-gray-400 hover:text-black font-medium transition-colors"><?php echo __('view_site'); ?></a>
                    <span class="text-[10px] uppercase tracking-[0.3em] text-gray-400 font-medium"><?php echo __('admin_panel'); ?></span>
                </div>
            </header>

            <div class="flex-1 p-8 lg:p-20 w-full max-w-screen-2xl mx-auto">
                <header class="mb-16">
    <h2 class="serif text-6xl md:text-8xl mb-6 font-normal tracking-tight"><?php echo __('dashboard'); ?></h2>
                    <p class="text-gray-400 font-light text-lg"><?php echo __('overview_of_store_performance'); ?></p>
                </header>

                <div class="stat-grid mb-24 max-w-5xl">
                    <div class="stat-item flex flex-col justify-between h-56">
                        <div class="flex justify-between items-start">
                            <span class="text-[10px] uppercase tracking-[0.2em] text-gray-300 font-semibold"><?php echo __('products'); ?></span>
                            <svg class="w-4 h-4 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="1" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        </div>
                        <div class="serif text-7xl"><?php echo number_format($nb_products); ?></div>
                    </div>
                    
                    <div class="stat-item flex flex-col justify-between h-56">
                        <div class="flex justify-between items-start">
                            <span class="text-[10px] uppercase tracking-[0.2em] text-gray-300 font-semibold"><?php echo __('orders'); ?></span>
                            <svg class="w-4 h-4 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                        </div>
                        <div class="serif text-7xl"><?php echo number_format($nb_orders); ?></div>
                    </div>

                    <div class="stat-item flex flex-col justify-between h-56">
                        <div class="flex justify-between items-start">
                            <span class="text-[10px] uppercase tracking-[0.2em] text-gray-300 font-semibold">Cities</span>
                            <svg class="w-4 h-4 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="1" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 12H3m18-9h-2m0 9h-2M7 19h10M7 5h10M9 9h6m-3-6v12m0 0l-4 4m4-4l4 4"></path></svg>
                        </div>
                        <div class="serif text-7xl"><?php echo number_format($nb_cities); ?></div>
                    </div>

                    <div class="stat-item flex flex-col justify-between h-56">
                        <div class="flex justify-between items-start">
                            <span class="text-[10px] uppercase tracking-[0.2em] text-gray-300 font-semibold"><?php echo __('revenue'); ?></span>
                            <span class="text-gray-200 text-xs serif">$</span>
                        </div>
                        <div class="serif text-7xl"><?php echo number_format($revenue, 0); ?> <span class="text-3xl ml-2">MAD</span></div>
                    </div>
                </div>

                <div class="max-w-2xl">
                    <h3 class="serif text-4xl mb-6"><?php echo __('welcome_to_admin_panel'); ?></h3>
                    <p class="text-gray-400 leading-relaxed font-light text-lg">
                        <?php echo __('admin_panel_desc'); ?>
                    </p>
                </div>
            </div>
        </main>
    </div>

    <script>
        const btn = document.getElementById('mobile-toggle');
        const sidebar = document.getElementById('sidebar');
        btn.onclick = () => sidebar.classList.toggle('-translate-x-full');
    </script>
</body>
</html>