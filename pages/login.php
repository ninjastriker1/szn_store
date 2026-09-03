<section class="h-screen w-full flex items-center justify-center bg-gray-50 px-6">
    <div class="w-full max-w-md bg-white p-12 shadow-sm">
        <div class="text-center mb-10">
            <h2 class="text-3xl serif mb-4"><?php echo __('login'); ?></h2>
            <p class="text-xs font-light text-gray-500 uppercase tracking-widest"><?php echo __('welcome_back'); ?></p>
        </div>

        <!-- Display error messages -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 text-sm rounded">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Display success messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 text-sm rounded">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo base_url('includes/auth.php'); ?>?redirect=<?php echo $_GET['redirect'] ?? ''; ?>" method="POST" class="space-y-8">
            <?php require_once __DIR__ . '/../includes/csrf.php'; echo csrf_field(); ?>
            <div class="space-y-2">
                <label class="text-xs uppercase tracking-widest font-light"><?php echo __('email'); ?></label>
                <input type="email" name="email" required class="w-full border border-gray-200 p-3 focus:outline-none focus:border-black transition-colors font-light text-sm">
            </div>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <label class="text-xs uppercase tracking-widest font-light"><?php echo __('password'); ?></label>
<a href="<?php echo base_url('index.php?page=forgot-password'); ?>" class="text-[10px] uppercase tracking-wider text-gray-400 hover:text-black"><?php echo __('forgot_password'); ?></a>
                </div>
                <div class="relative">
                    <input type="password" name="password" required class="w-full border border-gray-200 p-3 pr-12 focus:outline-none focus:border-black transition-colors font-light text-sm">
                    <button type="button" class="toggle-password absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors" data-target="password">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" name="login" class="w-full bg-black text-white py-4 text-xs uppercase tracking-[0.2em] hover:opacity-90 transition-opacity">
                <?php echo __('sign_in'); ?>
            </button>

            <div class="text-center pt-4">
                <a href="<?php echo base_url('index.php?page=signup'); ?>" class="text-xs font-light text-gray-500 border-b border-transparent hover:border-gray-500 transition-all">
                    <?php echo __('create_an_account'); ?>
                </a>
            </div>
        </form>
    </div>
</section>
