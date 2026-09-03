<?php
load_language();
?>

<section class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-slate-50 to-slate-200">
    <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-3xl shadow-2xl border border-gray-100">
        <div class="text-center">
            <h2 class="text-4xl md:text-5xl font-serif font-light tracking-tight text-gray-900 mb-2"><?php echo __('forgot_password'); ?></h2>
            <p class="text-gray-500 text-sm md:text-base"><?php echo __('enter_email_reset'); ?></p>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-sm">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl text-sm">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo base_url('includes/auth.php'); ?>" class="space-y-6" novalidate>
            <input type="hidden" name="action" value="forgot_password">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('email_address'); ?></label>
                <input 
                    type="email" 
                    name="email" 
                    id="email"
                    required 
                    autocomplete="email"
                    class="w-full px-4 py-4 border border-gray-300 rounded-2xl focus:ring-2 focus:ring-black focus:border-transparent transition-all duration-200 text-lg placeholder-gray-400"
                    placeholder="<?php echo __('enter_email'); ?>"
                >
            </div>

            <button 
                type="submit"
                class="w-full bg-black hover:bg-gray-900 text-white font-medium py-4 px-6 rounded-2xl text-lg tracking-wide transition-all duration-200 transform hover:-translate-y-0.5 hover:shadow-xl"
            >
                <?php echo __('send_reset_link'); ?>
            </button>
        </form>

        <div class="text-center">
            <p class="text-sm text-gray-600">
                <?php echo __('remembered_password'); ?> 
                <a href="<?php echo base_url('index.php?page=login'); ?>" class="font-medium text-black hover:text-gray-900 transition-colors">
                    <?php echo __('return_login'); ?>
                </a>
            </p>
        </div>
    </div>
</section>



