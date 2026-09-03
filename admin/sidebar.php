<?php
// admin/sidebar.php
// must be included within session started and admin check performed
?>
<aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-100 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out flex flex-col">
    <div class="p-10">
        <h1 class="serif text-3xl font-semibold tracking-tighter">SZN</h1>
    </div>
    
    <nav class="flex-1 px-8 space-y-7">
        <?php 
        $current_page = basename($_SERVER['PHP_SELF']);
        $nav_items = [
            ['dashboard.php', 'Dashboard', '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="1.5" d="M4 6h16M4 12h16m-7 6h7"></path></svg>'],
            ['products.php', 'Products', '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>'],
            ['cities.php', 'Cities', '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="1.5" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 12H3m18-9h-2m0 9h-2M7 19h10M7 5h10M9 9h6m-3-6v12m0 0l-4 4m4-4l4 4"></path></svg>'],
            ['orders.php', 'Orders', '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>'],
            ['users.php', 'Users', '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>'],
            ['settings.php', 'Settings', '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>'],
        ];

        foreach ($nav_items as $item): 
            $isActive = ($current_page === $item[0]);
        ?>
            <a href="<?php echo $item[0]; ?>" class="flex items-center space-x-4 <?php echo $isActive ? 'text-black' : 'text-gray-300 hover:text-black transition-colors'; ?>">
                <?php echo $item[2]; ?>
<span class="sidebar-item uppercase <?php echo $isActive ? 'font-medium' : ''; ?>"><?php echo __($item[1]); ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="p-10">
        <a href="../includes/logout.php" class="flex items-center space-x-4 text-gray-300 hover:text-black transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            <span class="sidebar-item uppercase">Logout</span>
        </a>
    </div>
</aside>
