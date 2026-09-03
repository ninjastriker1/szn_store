<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// only admin allowed
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: " . base_url('index.php?page=login'));
    exit;
}

// handle role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) && isset($_POST['new_role'])) {
    $userId = (int)$_POST['user_id'];
    $newRole = $_POST['new_role'] === 'admin' ? 'admin' : 'user';

    // don't let admin demote themselves
    if ($userId === $_SESSION['user_id']) {
        $_SESSION['error'] = "You cannot change your own role.";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET role = :role WHERE id = :id");
        $stmt->execute(['role' => $newRole, 'id' => $userId]);
        $_SESSION['success'] = "Role updated successfully.";
    }
    header("Location: " . base_url('admin/users.php'));
    exit;
}

// handle status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) && isset($_POST['toggle_status'])) {
    $userId = (int)$_POST['user_id'];
    
    // don't let admin ban themselves
    if ($userId === $_SESSION['user_id']) {
        $_SESSION['error'] = "You cannot ban your own account.";
    } else {
        $stmt = $pdo->prepare("SELECT status FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $newStatus = $user['status'] === 'active' ? 'banned' : 'active';
            $update = $pdo->prepare("UPDATE users SET status = :status WHERE id = :id");
            $update->execute(['status' => $newStatus, 'id' => $userId]);
            $_SESSION['success'] = "User status updated to " . $newStatus . ".";
        }
    }
    header("Location: " . base_url('admin/users.php'));
    exit;
}

// fetch all users
$stmt = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) as name, email, role, status, created_at FROM users ORDER BY id ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
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
    <title>User Management | Admin SZN</title>
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
                    <h2 class="serif text-6xl md:text-8xl mb-6 font-normal tracking-tight">Users</h2>
                    <p class="text-gray-400 font-light text-lg">Manage user roles and account statuses.</p>
                </header>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="mb-4 p-3 bg-red-100 text-red-700 rounded"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>
                <div class="mb-4 p-3 bg-green-100 text-green-700 rounded"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            <table class="w-full table-auto bg-white shadow rounded">
                <thead>
                    <tr class="bg-gray-200 text-left">
                        <th class="p-2">#</th>
                        <th class="p-2">Name</th>
                        <th class="p-2">Email</th>
                        <th class="p-2">Role</th>
                        <th class="p-2">Status</th>
                        <th class="p-2">Created</th>
                        <th class="p-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr class="border-t">
                            <td class="p-2"><?php echo $u['id']; ?></td>
                            <td class="p-2"><?php echo htmlspecialchars($u['name']); ?></td>
                            <td class="p-2"><?php echo htmlspecialchars($u['email']); ?></td>
                            <td class="p-2"><?php echo htmlspecialchars($u['role']); ?></td>
                            <td class="p-2">
                                <?php if(isset($u['status'])): ?>
                                    <span class="px-2 py-1 text-xs rounded <?php echo $u['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo ucfirst($u['status']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-800">Active</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-2"><?php echo $u['created_at']; ?></td>
                            <td class="p-2 flex space-x-2 items-center">
                                <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <select name="new_role" class="border p-1 text-sm">
                                        <option value="user" <?php echo $u['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                        <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                    <button class="ml-1 bg-blue-500 text-white px-2 py-1 text-xs rounded">Update Role</button>
                                </form>
                                <form method="POST" class="inline ml-2" onsubmit="return confirm('Toggle active/banned status for this user?');">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <input type="hidden" name="toggle_status" value="1">
                                    <?php if(isset($u['status']) && $u['status'] === 'banned'): ?>
                                        <button class="bg-green-600 text-white px-3 py-1 text-xs rounded uppercase tracking-wider">Unban</button>
                                    <?php else: ?>
                                        <button class="bg-red-600 text-white px-3 py-1 text-xs rounded uppercase tracking-wider">Ban</button>
                                    <?php endif; ?>
                                </form>
                                <?php else: ?>
                                    <span class="text-gray-400">&mdash;</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>

    <script>
        const btn = document.getElementById('mobile-toggle');
        const sidebar = document.getElementById('sidebar');
        btn.onclick = () => sidebar.classList.toggle('-translate-x-full');
    </script>
</body>
</html>
