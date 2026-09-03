<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// only admin allowed
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: " . base_url('index.php?page=login'));
    exit;
}

$settingsFile = __DIR__ . '/../config/settings.json';

// Default settings
$settings = [
    'store_name' => 'SZN Store',
    'contact_email' => 'support@szn.com',
    'currency' => 'USD',
    'shipping_fee' => '15.00'
];

// Load existing settings if file exists
if (file_exists($settingsFile)) {
    $fileContents = file_get_contents($settingsFile);
    $loadedSettings = json_decode($fileContents, true);
    if (is_array($loadedSettings)) {
        $settings = array_merge($settings, $loadedSettings);
    }
}

// Handle Save Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    $settings['store_name'] = trim($_POST['store_name'] ?? '');
    $settings['contact_email'] = trim($_POST['contact_email'] ?? '');
    $settings['currency'] = trim($_POST['currency'] ?? 'USD');
    $settings['shipping_fee'] = number_format((float)($_POST['shipping_fee'] ?? 0), 2, '.', '');
    
    // Validate
    if (empty($settings['store_name']) || empty($settings['contact_email'])) {
        $_SESSION['error'] = "Store Name and Contact Email are required.";
    } else {
        // Save to file
        $configDir = dirname($settingsFile);
        if (!is_dir($configDir)) {
            mkdir($configDir, 0755, true);
        }
        
        if (file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT))) {
            $_SESSION['success'] = "Settings saved successfully.";
        } else {
            $_SESSION['error'] = "Failed to save settings. Please check directory permissions.";
        }
    }
    
    header("Location: " . base_url('admin/settings.php'));
    exit;
}

// Handle Social Links Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'update_social':
            $id = (int)($_POST['id'] ?? 0);
            $url = trim(filter_var($_POST['url'] ?? '', FILTER_SANITIZE_URL));
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            if ($id > 0 && filter_var($url, FILTER_VALIDATE_URL)) {
                $stmt = $pdo->prepare("UPDATE social_links SET url = ?, is_active = ? WHERE id = ?");
                if ($stmt->execute([$url, $is_active, $id])) {
                    $_SESSION['success'] = "Social link updated successfully.";
                } else {
                    $_SESSION['error'] = "Failed to update social link.";
                }
            } else {
                $_SESSION['error'] = "Invalid URL or ID.";
            }
            break;
            
        case 'add_social':
            $platform = trim(sanitize($_POST['platform'] ?? ''));
            $url = trim(filter_var($_POST['url'] ?? '', FILTER_SANITIZE_URL));
            
            if ($platform && filter_var($url, FILTER_VALIDATE_URL)) {
                $stmt = $pdo->prepare("INSERT INTO social_links (platform, url, order_num) VALUES (?, ?, (SELECT COALESCE(MAX(order_num), 0) + 1 FROM social_links))");
                if ($stmt->execute([$platform, $url])) {
                    $_SESSION['success'] = "Social link added successfully.";
                } else {
                    $_SESSION['error'] = "Failed to add social link. Platform may already exist.";
                }
            } else {
                $_SESSION['error'] = "Invalid platform or URL.";
            }
            break;
            
        case 'delete_social':
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $pdo->prepare("DELETE FROM social_links WHERE id = ?");
                if ($stmt->execute([$id])) {
                    $_SESSION['success'] = "Social link deleted successfully.";
                } else {
                    $_SESSION['error'] = "Failed to delete social link.";
                }
            }
            break;
    }
    header("Location: " . base_url('admin/settings.php#social-section'));
    exit;
}

// Fetch social links
$social_links = [];
try {
    $stmt = $pdo->query("SELECT * FROM social_links ORDER BY order_num ASC, id ASC");
    $social_links = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching social links: " . $e->getMessage());
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
    </style>
    <title>Settings | Admin SZN</title>
</head>
<body class="antialiased">
    <div class="flex min-h-screen">
        <?php include 'sidebar.php'; ?>
        
        <main class="flex-1 lg:ml-64 flex flex-col min-h-screen">
            <header class="h-16 flex items-center justify-between px-8 border-b border-gray-50 bg-white">
                <button id="mobile-toggle" class="lg:hidden p-2 text-gray-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="1.5" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                </button>
                <div class="hidden lg:block"></div> <div class="text-[10px] uppercase tracking-[0.3em] text-gray-400 font-medium">Admin Panel</div>
            </header>

            <div class="flex-1 p-8 lg:p-20 w-full max-w-screen-2xl mx-auto">
                <header class="mb-16">
                    <h2 class="serif text-6xl md:text-8xl mb-6 font-normal tracking-tight">Settings</h2>
                    <p class="text-gray-400 font-light text-lg">Configure your store's general information and preferences.</p>
                </header>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="mb-6 p-4 bg-red-50 text-red-700 border border-red-200 rounded text-sm"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="mb-6 p-4 bg-green-50 text-green-700 border border-green-200 rounded text-sm"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <div class="bg-white shadow rounded-lg p-8">
                    <h3 class="text-xl serif font-medium mb-6">General Store Configuration</h3>
                    
                    <form method="POST" action="settings.php">
                        <input type="hidden" name="action" value="save_settings">
                        
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Store Name *</label>
                                    <input type="text" name="store_name" value="<?php echo htmlspecialchars($settings['store_name']); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-black">
                                    <p class="text-xs text-gray-500 mt-1">The name displayed on the frontend header.</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Email *</label>
                                    <input type="email" name="contact_email" value="<?php echo htmlspecialchars($settings['contact_email']); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-black">
                                    <p class="text-xs text-gray-500 mt-1">Used for customer support inquiries.</p>
                                </div>
                            </div>
                            
                            <hr class="border-gray-100">
                            
                            <h3 class="text-lg serif font-medium mb-4 text-gray-800">Checkout Preferences</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Currency Code</label>
                                    <select name="currency" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-black">
                                        <option value="MAD" selected>MAD (Moroccan Dirham)</option>
                                    </select>
                                    <p class="text-xs text-gray-500 mt-1">Default currency for all products.</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Standard Shipping Fee</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 sm:text-xs pr-1">MAD</span>
                                        </div>
                                        <input type="number" step="0.01" name="shipping_fee" value="<?php echo htmlspecialchars($settings['shipping_fee']); ?>" class="w-full pl-[50px] px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-black">
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">Flat rate added during checkout.</p>
                                </div>
                            </div>

                        </div>
                        
                        <div class="mt-8 flex justify-end">
                            <button type="submit" class="bg-black text-white px-6 py-2 rounded uppercase text-sm font-light tracking-widest hover:bg-gray-800 transition-colors">Save Changes</button>
                        </div>
                    </form>

                    <!-- Social Media Links Section -->
                    <div id="social-section" class="mt-12 pt-12 border-t border-gray-100">
                        <h3 class="text-xl serif font-medium mb-8">Social Media Links</h3>
                        
                        <div class="overflow-x-auto mb-8">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="border-b border-gray-200">
                                        <th class="text-left py-4 px-6 text-sm font-medium text-gray-700 w-32">Platform</th>
                                        <th class="text-left py-4 px-6 text-sm font-medium text-gray-700">URL</th>
                                        <th class="text-center py-4 px-6 text-sm font-medium text-gray-700 w-24">Active</th>
                                        <th class="text-right py-4 px-6 text-sm font-medium text-gray-700 w-48">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($social_links as $link): ?>
                                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                                        <td class="py-4 px-6 text-sm font-medium"><?php echo htmlspecialchars($link['platform']); ?></td>
                                        <td class="py-4 px-6 text-sm font-mono text-gray-600 truncate max-w-xs" title="<?php echo htmlspecialchars($link['url']); ?>"><?php echo htmlspecialchars($link['url']); ?></td>
                                        <td class="py-4 px-6 text-center">
                                            <span class="inline-block w-20 px-2 py-1 text-xs <?php echo $link['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?> rounded-full">
                                                <?php echo $link['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 text-right space-x-2">
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="update_social">
                                                <input type="hidden" name="id" value="<?php echo $link['id']; ?>">
                                                <input type="url" name="url" value="<?php echo htmlspecialchars($link['url']); ?>" class="w-32 px-2 py-1 text-xs border rounded focus:outline-none focus:ring-1 focus:ring-black mr-1" required>
                                                <label class="text-xs mr-2">
                                                    <input type="checkbox" name="is_active" value="1" <?php echo $link['is_active'] ? 'checked' : ''; ?> class="mr-1">
                                                    Active
                                                </label>
                                                <button type="submit" class="text-xs bg-black text-white px-3 py-1 rounded hover:bg-gray-800 transition-colors">Update</button>
                                            </form>
                                            <form method="POST" class="inline" onsubmit="return confirm('Delete <?php echo htmlspecialchars($link['platform']); ?>?')">
                                                <input type="hidden" name="action" value="delete_social">
                                                <input type="hidden" name="id" value="<?php echo $link['id']; ?>">
                                                <button type="submit" class="text-xs text-red-600 hover:text-red-800 hover:underline">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($social_links)): ?>
                                    <tr>
                                        <td colspan="4" class="py-12 text-center text-gray-500">No social links found. Add your first one below.</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Add New Social Link -->
                        <form method="POST" class="bg-gray-50 p-6 rounded-lg">
                            <input type="hidden" name="action" value="add_social">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Platform Name</label>
                                    <input type="text" name="platform" placeholder="e.g. TikTok, Snapchat" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-black" required maxlength="50">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">URL</label>
                                    <input type="url" name="url" placeholder="https://instagram.com/yourhandle" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-black" required>
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end">
                                <button type="submit" class="bg-black text-white px-6 py-2 rounded uppercase text-sm font-light tracking-widest hover:bg-gray-800 transition-colors">Add Social Link</button>
                            </div>
                        </form>
                    </div>
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
