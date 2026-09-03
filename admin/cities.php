<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

if (!isAdmin()) {
    header("Location: ../index.php?page=login");
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_city']) || (isset($_POST['edit_id']) && !empty($_POST['edit_id']) && !isset($_POST['delete_id']) && !isset($_POST['toggle_id']))) {
        $city = trim(sanitize($_POST['city']));
        $price = (float)$_POST['price'];
        $edit_id = (int)($_POST['edit_id'] ?? 0);
        
        if (empty($city) || $price < 0) {
            $_SESSION['error'] = 'City name and valid price required.';
        } else {
            try {
                if ($edit_id > 0) {
                    $stmt = $pdo->prepare("UPDATE delivery_cities SET city = ?, price = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                    $stmt->execute([$city, $price, $edit_id]);
                    $_SESSION['success'] = 'City updated successfully.';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO delivery_cities (city, price) VALUES (?, ?) ON DUPLICATE KEY UPDATE price = VALUES(price), updated_at = CURRENT_TIMESTAMP");
                    $stmt->execute([$city, $price]);
                    $_SESSION['success'] = 'City added successfully.';
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Database error: ' . $e->getMessage();
            }
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } elseif (isset($_POST['delete_id'])) {
        $id = (int)$_POST['delete_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM delivery_cities WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = 'City deleted successfully.';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Delete failed.';
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } elseif (isset($_POST['toggle_id'])) {
        $id = (int)$_POST['toggle_id'];
        try {
            $stmt = $pdo->prepare("UPDATE delivery_cities SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = 'Status updated successfully.';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Update failed.';
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Fetch cities
try {
    $stmt = $pdo->query("SELECT * FROM delivery_cities ORDER BY city ASC");
    $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $cities = [];
    $_SESSION['error'] = 'Failed to load cities.';
}

// Flash messages
$message = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Cities | Admin SZN</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; color: #1a1a1a; background-color: #ffffff; overflow-x: hidden; }
        .serif { font-family: 'Playfair Display', serif; }
        .sidebar-item { letter-spacing: 0.1em; font-size: 0.7rem; }
    </style>
</head>
<body class="antialiased">
    <div class="flex min-h-screen">
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <main class="flex-1 lg:ml-64 flex flex-col min-h-screen">
            <header class="h-16 flex items-center justify-between px-8 border-b border-gray-50 bg-white">
                <button id="mobile-toggle" class="lg:hidden p-2 text-gray-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="1.5" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                </button>
                <div class="hidden lg:block"></div> 
                <div class="text-[10px] uppercase tracking-[0.3em] text-gray-400 font-medium">Cities Management</div>
            </header>

            <div class="flex-1 p-8 lg:p-20 w-full max-w-screen-2xl mx-auto">
                <header class="mb-16">
                    <h2 class="serif text-6xl md:text-8xl mb-6 font-normal tracking-tight">Delivery Cities</h2>
                    <p class="text-gray-400 font-light text-lg">Manage shipping prices per city.</p>
                </header>

                <?php if ($message): ?>
                    <div class="bg-green-50 border border-green-200 text-green-800 px-6 py-4 rounded-lg mb-8 shadow-sm">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-800 px-6 py-4 rounded-lg mb-8 shadow-sm">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Add/Edit City Form -->
                <div class="bg-white p-10 rounded-2xl shadow-xl border border-gray-100 mb-12">
                    <h3 id="formTitle" class="text-2xl font-bold mb-8">Add New City</h3>
                    <form method="POST" class="max-w-md">
                        <?php echo csrf_field(); ?>
                        <div class="space-y-6">
                            <div>
                                <label class="block text-xs uppercase tracking-widest font-semibold text-gray-400 mb-3">City Name</label>
                                <input type="text" name="city" id="cityInput" required placeholder="e.g. Casablanca" class="w-full px-5 py-4 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent transition-all">
                            </div>
                            <div>
                                <label class="block text-xs uppercase tracking-widest font-semibold text-gray-400 mb-3">Shipping Price (MAD)</label>
                                <input type="number" name="price" id="priceInput" step="0.01" min="0" required placeholder="0.00" class="w-full px-5 py-4 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent transition-all">
                            </div>
                            <input type="hidden" name="edit_id" id="editId">
                            <div class="flex space-x-4 pt-4">
                                <button type="submit" name="add_city" id="formButton" class="flex-1 bg-black text-white py-4 px-8 rounded-xl font-bold text-xs tracking-[0.2em] uppercase hover:bg-gray-800 transition-all shadow-lg hover:shadow-xl active:scale-[0.98]">
                                    Add City
                                </button>
                                <button type="button" id="cancelEdit" class="hidden px-8 py-4 border border-gray-200 rounded-xl text-xs tracking-[0.1em] uppercase font-medium hover:bg-gray-50 transition-all" onclick="resetForm()">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Cities Table -->
                <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-gray-50/50 border-b border-gray-100">
                                    <th class="px-8 py-5 text-xs font-bold text-gray-400 uppercase tracking-[0.2em]">City</th>
                                    <th class="px-8 py-5 text-xs font-bold text-gray-400 uppercase tracking-[0.2em]">Shipping</th>
                                    <th class="px-8 py-5 text-xs font-bold text-gray-400 uppercase tracking-[0.2em]">Status</th>
                                    <th class="px-8 py-5 text-xs font-bold text-gray-400 uppercase tracking-[0.2em] text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php if (empty($cities)): ?>
                                    <tr>
                                        <td colspan="4" class="px-8 py-16 text-center text-gray-400 font-light italic">
                                            No delivery cities configured yet.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($cities as $city): ?>
                                        <tr class="hover:bg-gray-50/50 transition-colors">
                                            <td class="px-8 py-6">
                                                <span class="font-medium text-gray-900 text-lg"><?php echo htmlspecialchars($city['city']); ?></span>
                                            </td>
                                            <td class="px-8 py-6">
                                                <span class="font-bold text-black"><?php echo number_format($city['price'], 2); ?></span>
                                                <span class="text-[10px] text-gray-400 ml-1 font-medium italic">MAD</span>
                                            </td>
                                            <td class="px-8 py-6">
                                                <?php if ($city['is_active']): ?>
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-green-50 text-green-700 border border-green-100">
                                                        Active
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-red-50 text-red-700 border border-red-100">
                                                        Inactive
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-8 py-6 text-right space-x-3">
                                                <button onclick="editCity(<?php echo $city['id']; ?>, '<?php echo addslashes($city['city']); ?>', <?php echo $city['price']; ?>)" class="text-black hover:text-gray-600 font-bold text-xs uppercase tracking-widest transition-colors">
                                                    Edit
                                                </button>
                                                <form method="POST" class="inline" onsubmit="return confirm('Change status?')">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="toggle_id" value="<?php echo $city['id']; ?>">
                                                    <button type="submit" class="text-blue-600 hover:text-blue-900 font-bold text-xs uppercase tracking-widest transition-colors">
                                                        <?php echo $city['is_active'] ? 'Disable' : 'Enable'; ?>
                                                    </button>
                                                </form>
                                                <form method="POST" class="inline" onsubmit="return confirm('Permanently delete this city?')">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="delete_id" value="<?php echo $city['id']; ?>">
                                                    <button type="submit" class="text-red-500 hover:text-red-700 font-bold text-xs uppercase tracking-widest transition-colors">
                                                        Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    function editCity(id, city, price) {
        document.getElementById('cityInput').value = city;
        document.getElementById('priceInput').value = price;
        document.getElementById('editId').value = id;
        document.getElementById('formButton').textContent = 'Update City';
        document.getElementById('formTitle').textContent = 'Edit City: ' + city;
        document.getElementById('cancelEdit').classList.remove('hidden');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function resetForm() {
        document.getElementById('cityInput').value = '';
        document.getElementById('priceInput').value = '';
        document.getElementById('editId').value = '';
        document.getElementById('formButton').textContent = 'Add City';
        document.getElementById('formTitle').textContent = 'Add New City';
        document.getElementById('cancelEdit').classList.add('hidden');
    }

    document.addEventListener('DOMContentLoaded', () => {
        const btn = document.getElementById('mobile-toggle');
        const sidebar = document.getElementById('sidebar');
        if (btn && sidebar) {
            btn.onclick = () => sidebar.classList.toggle('-translate-x-full');
        }
    });
    </script>
</body>
</html>
