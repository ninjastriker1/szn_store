<?php
load_language();

$token = $_GET['token'] ?? '';
$show_form = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    $show_form = false;
    // Handled by auth.php
}
?>

<section class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-emerald-50 to-blue-50">
    <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-3xl shadow-2xl border border-gray-100">
        <div class="text-center">
            <h2 class="text-4xl md:text-5xl font-serif font-light tracking-tight text-gray-900 mb-2"><?php echo __('reset_password'); ?></h2>
            <p class="text-gray-500 text-sm md:text-base"><?php echo __('choose_new_password'); ?></p>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-sm">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl text-sm">
                <?php echo $_SESSION['success']; 
                unset($_SESSION['success']); ?>
            </div>
            <div class="text-center pt-6">
                <a href="<?php echo base_url('index.php?page=login'); ?>" class="inline-block bg-black hover:bg-gray-900 text-white font-medium py-3 px-8 rounded-2xl text-sm tracking-wide transition-all duration-200">
                    <?php echo __('go_to_login'); ?>
                </a>
            </div>
        <?php else: ?>
            <form method="POST" action="<?php echo base_url('includes/auth.php'); ?>" class="space-y-6">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div class="space-y-4">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('new_password'); ?></label>
                        <input 
                            type="password" 
                            name="password" 
                            id="password"
                            required 
                            minlength="8"
                            autocomplete="new-password"
                            class="w-full px-4 py-4 border border-gray-300 rounded-2xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 text-lg placeholder-gray-400"
                            placeholder="<?php echo __('at_least_8_chars'); ?>"
                        >
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('confirm_password'); ?></label>
                        <input 
                            type="password" 
                            name="confirm_password" 
                            id="confirm_password"
                            required 
                            autocomplete="new-password"
                            class="w-full px-4 py-4 border border-gray-300 rounded-2xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 text-lg placeholder-gray-400"
                            placeholder="<?php echo __('confirm_new_password'); ?>"
                        >
                    </div>
                </div>

                <button 
                    type="submit"
                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-4 px-6 rounded-2xl text-lg tracking-wide transition-all duration-200 transform hover:-translate-y-0.5 hover:shadow-xl"
                >
                    <?php echo __('reset_password_btn'); ?>
                </button>
            </form>

            <div class="text-center pt-4">
                <p class="text-sm text-gray-600">
                    <?php echo __('didnt_request_reset'); ?> 
                    <a href="<?php echo base_url('index.php?page=login'); ?>" class="font-medium text-emerald-600 hover:text-emerald-800 transition-colors">
                        <?php echo __('return_login'); ?>
                    </a>
                </p>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    
    function validatePasswords() {
        if (password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Passwords do not match');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
    
    password.addEventListener('input', validatePasswords);
    confirmPassword.addEventListener('input', validatePasswords);
});
</script>



