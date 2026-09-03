<?php
// Get user data for auto-fill if logged in
$user_name = '';
$user_email = '';

if (isLoggedIn() && isset($pdo)) {
    $user_name = $_SESSION['user_name'] ?? '';
    $user_email = $_SESSION['user_email'] ?? '';
    
    // Try to get more details from database if needed
    if (empty($user_name) && isset($_SESSION['user_id'])) {
        try {
            $stmt = $pdo->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $user_name = $user['first_name'] . ' ' . $user['last_name'];
                $user_email = $user['email'];
            }
        } catch (PDOException $e) {
            // Use session data as fallback
        }
    }
}
?>

<section class="py-32 px-8 md:px-16 bg-white min-h-screen">
    <div class="max-w-2xl mx-auto">
        <div class="text-center mb-16">
            <h2 class="text-5xl serif mb-6"><?php echo __('contact_us'); ?></h2>
            <p class="text-sm font-light opacity-60"><?php echo __('we_would_love_to_hear_from_you'); ?></p>
        </div>

        <form class="space-y-8" method="POST" action="<?php echo base_url('includes/contact.php'); ?>">
            <?php echo csrf_field(); ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-2">
                    <label class="text-xs uppercase tracking-widest font-light"><?php echo __('name'); ?></label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($user_name); ?>" class="w-full border-b border-gray-300 py-2 focus:outline-none focus:border-black transition-colors bg-transparent">
                </div>
                <div class="space-y-2">
                    <label class="text-xs uppercase tracking-widest font-light"><?php echo __('email'); ?></label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user_email); ?>" class="w-full border-b border-gray-300 py-2 focus:outline-none focus:border-black transition-colors bg-transparent">
                </div>
            </div>
            
            <div class="space-y-2">
                <label class="text-xs uppercase tracking-widest font-light"><?php echo __('subject'); ?></label>
                <input type="text" name="subject" class="w-full border-b border-gray-300 py-2 focus:outline-none focus:border-black transition-colors bg-transparent">
            </div>

            <div class="space-y-2">
                <label class="text-xs uppercase tracking-widest font-light"><?php echo __('message'); ?></label>
                <textarea rows="4" name="message" class="w-full border-b border-gray-300 py-2 focus:outline-none focus:border-black transition-colors bg-transparent resize-none"></textarea>
            </div>

            <button type="submit" name="send_message" class="w-full bg-black text-white py-4 text-xs uppercase tracking-[0.2em] hover:bg-black/80 transition-colors">
                <?php echo __('send_message'); ?>
            </button>
        </form>
    </div>
</section>

