<?php
// Get user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Debug logging
    if (!$user) {
        error_log("WARNING: User not found for id: " . $_SESSION['user_id']);
    } else {
        error_log("User loaded - phone: " . ($user['phone'] ?? 'NULL') . ", address: " . ($user['address'] ?? 'NULL'));
    }
} catch (PDOException $e) {
    $user = null;
    error_log("Error fetching user: " . $e->getMessage());
}

// Handle Add Address
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_address'])) {
    $title = trim($_POST['title']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $zip = trim($_POST['zip']);
    $country = trim($_POST['country']);
    $phone = trim($_POST['phone']);

    if (empty($first_name) || empty($last_name) || empty($address) || empty($city) || empty($zip) || empty($country) || empty($phone)) {
        $_SESSION['error'] = "All fields except Title are required for shipping address.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO shipping_addresses (user_id, title, first_name, last_name, address, city, zip, country, phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $title ?: 'Home', $first_name, $last_name, $address, $city, $zip, $country, $phone]);
            $_SESSION['success'] = "Address added successfully.";
            header("Location: index.php?page=profile#addresses");
            exit;
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error adding address: " . $e->getMessage();
        }
    }
}

// Handle Delete Address
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_address'])) {
    $address_id = (int)$_POST['address_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM shipping_addresses WHERE id = ? AND user_id = ?");
        $stmt->execute([$address_id, $_SESSION['user_id']]);
        $_SESSION['success'] = "Address deleted successfully.";
        header("Location: index.php?page=profile#addresses");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting address.";
    }
}

// Fetch all addresses for this user
try {
    $stmt = $pdo->prepare("SELECT * FROM shipping_addresses WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $addresses = [];
}
?>

<section class="py-32 px-8 md:px-16 bg-white min-h-screen">
    <div class="max-w-4xl mx-auto">
        <h2 class="text-4xl serif mb-8"><?php echo __('profile'); ?></h2>
        
        <!-- Debug Info (remove later) -->
        <pre style="background: #f0f0f0; padding: 10px; margin-bottom: 20px; display: none;">
User ID: <?php echo $_SESSION['user_id'] ?? 'NOT SET'; ?>
User Data: <?php echo json_encode($user, JSON_PRETTY_PRINT); ?>
        </pre>
        
        <!-- Messages -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 text-sm rounded">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 text-sm rounded">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Sidebar Menu -->
            <div class="md:col-span-1">
                <div class="bg-gray-50 p-6 rounded-sm">
                    <div class="text-center mb-6 pb-6 border-b border-gray-200">
                        <div class="w-20 h-20 bg-black text-white rounded-full flex items-center justify-center text-3xl serif mx-auto mb-4">
                            <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                        </div>
                        <h3 class="text-lg font-medium"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h3>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                    </div>
                    
                    <nav class="space-y-2">
                        <a href="#profile-info" class="block px-4 py-3 text-xs uppercase tracking-widest font-light bg-black text-white rounded transition-colors">
                            <?php echo __('edit_profile'); ?>
                        </a>
                        <!--<a href="<?php echo base_url('pages/orders.php'); ?>" class="block px-4 py-3 text-xs uppercase tracking-widest font-light hover:bg-gray-100 rounded transition-colors">
                            <?php echo __('my_orders'); ?>
                        </a>-->
                        <a href="#addresses" class="block px-4 py-3 text-xs uppercase tracking-widest font-light hover:bg-gray-100 rounded transition-colors">
                            <?php echo __('my_addresses'); ?>
                        </a>
                        <a href="#password" class="block px-4 py-3 text-xs uppercase tracking-widest font-light hover:bg-gray-100 rounded transition-colors">
                            <?php echo __('change_password'); ?>
                        </a>
                        <hr class="my-4 border-gray-200">
                        <a href="<?php echo base_url('includes/logout.php'); ?>" class="block px-4 py-3 text-xs uppercase tracking-widest font-light text-red-500 hover:bg-red-50 rounded transition-colors">
                            <?php echo __('logout'); ?>
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="md:col-span-2 space-y-8">
                <!-- Profile Information -->
                <div id="profile-info" class="bg-gray-50 p-8 rounded-sm">
                    <h3 class="text-xl serif mb-6"><?php echo __('edit_profile'); ?></h3>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="update_profile" value="1">
                        
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs uppercase tracking-widest font-light mb-2"><?php echo __('first_name'); ?></label>
                                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required 
                                           class="w-full border border-gray-200 p-3 focus:outline-none focus:border-black transition-colors font-light text-sm">
                                </div>
                                
                                <div>
                                    <label class="block text-xs uppercase tracking-widest font-light mb-2"><?php echo __('last_name'); ?></label>
                                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required 
                                           class="w-full border border-gray-200 p-3 focus:outline-none focus:border-black transition-colors font-light text-sm">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-xs uppercase tracking-widest font-light mb-2"><?php echo __('email'); ?></label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required 
                                       class="w-full border border-gray-200 p-3 focus:outline-none focus:border-black transition-colors font-light text-sm">
                            </div>
                            
                            <div>
                                <button type="submit" class="bg-black text-white px-8 py-3 text-xs uppercase tracking-[0.2em] hover:opacity-90 transition-opacity">
                                    <?php echo __('save_changes'); ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Addresses -->
                <div id="addresses" class="bg-gray-50 p-8 rounded-sm">
                    <h3 class="text-xl serif mb-6"><?php echo __('my_addresses'); ?></h3>
                    
                    <?php if (count($addresses) > 0): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                            <?php foreach ($addresses as $addr): ?>
                                <div class="bg-white p-4 border border-gray-200 shadow-sm relative rounded">
                                    <h4 class="font-bold text-sm uppercase tracking-wider mb-2"><?php echo htmlspecialchars($addr['title']); ?></h4>
                                    <p class="text-sm font-light text-gray-700 leading-relaxed mb-4">
                                        <?php echo htmlspecialchars($addr['first_name'] . ' ' . $addr['last_name']); ?><br>
                                        <?php echo htmlspecialchars($addr['address']); ?><br>
                                        <?php echo htmlspecialchars($addr['city'] . ', ' . $addr['zip']); ?><br>
                                        <?php echo htmlspecialchars($addr['country']); ?><br>
                                        Phone: <?php echo htmlspecialchars($addr['phone']); ?>
                                    </p>
                                    <form method="POST" action="index.php?page=profile#addresses" class="absolute top-4 right-4" onsubmit="return confirm('Are you sure you want to delete this address?');">
                                        <input type="hidden" name="address_id" value="<?php echo $addr['id']; ?>">
                                        <input type="hidden" name="delete_address" value="1">
                                        <button type="submit" class="text-red-500 hover:text-red-700 text-xs uppercase tracking-widest font-bold">Delete</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-sm font-light text-gray-500 mb-8">You have no saved addresses.</p>
                    <?php endif; ?>

                    <h4 class="text-lg serif mb-4">Add New Address</h4>
                    <form method="POST" action="index.php?page=profile#addresses">
                        <input type="hidden" name="add_address" value="1">
                        
                        <div class="space-y-6">
                            <div>
                                <label class="block text-xs uppercase tracking-widest font-light mb-2">Address Title (e.g. Home, Office)</label>
                                <input type="text" name="title" placeholder="Home" class="w-full border border-gray-200 p-3 focus:outline-none focus:border-black transition-colors font-light text-sm">
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs uppercase tracking-widest font-light mb-2"><?php echo __('first_name'); ?> *</label>
                                    <input type="text" name="first_name" required class="w-full border border-gray-200 p-3 focus:outline-none focus:border-black transition-colors font-light text-sm">
                                </div>
                                
                                <div>
                                    <label class="block text-xs uppercase tracking-widest font-light mb-2"><?php echo __('last_name'); ?> *</label>
                                    <input type="text" name="last_name" required class="w-full border border-gray-200 p-3 focus:outline-none focus:border-black transition-colors font-light text-sm">
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs uppercase tracking-widest font-light mb-2"><?php echo __('phone_number'); ?> *</label>
                                    <input type="tel" name="phone" required class="w-full border border-gray-200 p-3 focus:outline-none focus:border-black transition-colors font-light text-sm">
                                </div>
                                
                                <div>
                                    <label class="block text-xs uppercase tracking-widest font-light mb-2"><?php echo __('country'); ?> *</label>
                                    <input type="text" name="country" required class="w-full border border-gray-200 p-3 focus:outline-none focus:border-black transition-colors font-light text-sm">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-xs uppercase tracking-widest font-light mb-2"><?php echo __('address'); ?> *</label>
                                <input type="text" name="address" required class="w-full border border-gray-200 p-3 focus:outline-none focus:border-black transition-colors font-light text-sm">
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs uppercase tracking-widest font-light mb-2"><?php echo __('city'); ?> *</label>
                                    <input type="text" name="city" required class="w-full border border-gray-200 p-3 focus:outline-none focus:border-black transition-colors font-light text-sm">
                                </div>
                                
                                <div>
                                    <label class="block text-xs uppercase tracking-widest font-light mb-2"><?php echo __('postal_code'); ?> *</label>
                                    <input type="text" name="zip" required class="w-full border border-gray-200 p-3 focus:outline-none focus:border-black transition-colors font-light text-sm">
                                </div>
                            </div>
                            
                            <div>
                                <button type="submit" class="bg-black text-white px-8 py-3 text-xs uppercase tracking-[0.2em] hover:opacity-90 transition-opacity">
                                    <?php echo __('save_address'); ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Change Password -->
                <div id="password" class="bg-gray-50 p-8 rounded-sm">
                    <h3 class="text-xl serif mb-6"><?php echo __('change_password'); ?></h3>
                    
                    <form method="POST" action="">
                        <div class="space-y-6">
                            <div>
                                <label class="block text-xs uppercase tracking-widest font-light mb-2"><?php echo __('current_password'); ?></label>
                                <div class="relative">
                                    <input type="password" id="current_password" name="current_password" required 
                                           class="w-full border border-gray-200 p-3 pr-12 focus:outline-none focus:border-black transition-colors font-light text-sm">
                                    <button type="button" class="toggle-password absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors" data-target="current_password">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-xs uppercase tracking-widest font-light mb-2"><?php echo __('new_password'); ?></label>
                                <div class="relative">
                                    <input type="password" id="new_password" name="new_password" required 
                                           class="w-full border border-gray-200 p-3 pr-12 focus:outline-none focus:border-black transition-colors font-light text-sm">
                                    <button type="button" class="toggle-password absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors" data-target="new_password">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-xs uppercase tracking-widest font-light mb-2"><?php echo __('confirm_new_password'); ?></label>
                                <div class="relative">
                                    <input type="password" id="confirm_password" name="confirm_password" required 
                                           class="w-full border border-gray-200 p-3 pr-12 focus:outline-none focus:border-black transition-colors font-light text-sm">
                                    <button type="button" class="toggle-password absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors" data-target="confirm_password">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <div>
                                <button type="submit" name="change_password" class="bg-black text-white px-8 py-3 text-xs uppercase tracking-[0.2em] hover:opacity-90 transition-opacity">
                                    <?php echo __('update_password'); ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get all navigation links in the sidebar
    const navLinks = document.querySelectorAll('.bg-gray-50 nav a[href^="#"]');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Get the target section
            const targetId = this.getAttribute('href');
            const targetSection = document.querySelector(targetId);
            
            if (targetSection) {
                // Smooth scroll to the target section
                targetSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                
                // Update active state
                navLinks.forEach(navLink => navLink.classList.remove('bg-black', 'text-white'));
                navLinks.forEach(navLink => navLink.classList.add('hover:bg-gray-100'));
                this.classList.remove('hover:bg-gray-100');
                this.classList.add('bg-black', 'text-white');
            }
        });
    });
    
    // Highlight active section on scroll
    const sections = document.querySelectorAll('div[id]');
    const observerOptions = {
        root: null,
        rootMargin: '-50% 0px -50% 0px',
        threshold: 0
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const id = entry.target.getAttribute('id');
                navLinks.forEach(navLink => {
                    navLink.classList.remove('bg-black', 'text-white');
                    navLink.classList.add('hover:bg-gray-100');
                });
                
                const activeLink = document.querySelector(`.bg-gray-50 nav a[href="#${id}"]`);
                if (activeLink) {
                    activeLink.classList.remove('hover:bg-gray-100');
                    activeLink.classList.add('bg-black', 'text-white');
                }
            }
        });
    }, observerOptions);
    
    sections.forEach(section => {
        observer.observe(section);
    });
});
</script>

