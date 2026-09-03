<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in (must be logged in to access dashboard)
if (!isset($_SESSION['user_id'])) {
    header("Location: " . base_url('index.php?page=login'));
    exit;
}

// Only non-admin users can access this dashboard
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header("Location: " . base_url('admin/dashboard.php'));
    exit;
}
?>
<section class="py-32 px-8 md:px-16 bg-white min-h-screen">
    <div class="max-w-4xl mx-auto">
        <h2 class="text-4xl serif mb-8"><?php echo __('account'); ?></h2>
        <div class="bg-gray-50 p-8 rounded-sm">
            <h3 class="text-xl font-medium mb-4">Welcome back!</h3>
            <p class="text-gray-600 mb-6">This is your dashboard. You can view your recent orders and manage your account details here.</p>
            <div class="border-t border-gray-200 pt-6">
                <a href="<?php echo base_url('includes/logout.php'); ?>" class="text-red-500 hover:text-red-700 underline text-sm decoration-1 underline-offset-4">Logout</a>
            </div>
        </div>
    </div>
</section>
