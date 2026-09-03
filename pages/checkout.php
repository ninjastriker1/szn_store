<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=login&redirect=checkout");
    exit;
}

// Handle form submissions
$show_review = false;
$shipping_data = [];
$payment_method = '';
$order_success = false;
$profile_error = '';
$order_id = null;

// Get cart items
$cart_items = get_cart_items($pdo);

// Calculate totals - use empty city initially for "Select City" state
        $selected_city = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['city'])) {
            $selected_city = $_POST['city'];
        }
        $totals = get_cart_totals($cart_items, $selected_city);
        $subtotal = $totals['subtotal'];
        $shipping = $totals['shipping'];
        $total = $totals['total'];
        $subtotal = $totals['subtotal'];
        $shipping = $totals['shipping'];
        $total = $totals['total'];

// Get user data
$user_id = $_SESSION['user_id'];
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $user_data = [];
}

// Get user's saved addresses
try {
    $stmt = $pdo->prepare("SELECT * FROM shipping_addresses WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $saved_addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $saved_addresses = [];
}

// POST handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'review') {
        $shipping_data = [
            'first_name' => sanitize($_POST['first_name'] ?? ''),
            'last_name' => sanitize($_POST['last_name'] ?? ''),
            'email' => sanitize($_POST['email'] ?? ''),
            'phone' => sanitize($_POST['phone'] ?? ''),
            'address1' => sanitize($_POST['address1'] ?? ''),
            'address2' => sanitize($_POST['address2'] ?? ''),
            'city' => sanitize($_POST['city'] ?? ''),
            'zip' => sanitize($_POST['zip'] ?? ''),
            'country' => sanitize($_POST['country'] ?? ''),
            'payment_method' => sanitize($_POST['payment_method'] ?? '')
        ];
        
        // Validate
        $errors = [];
        if (empty($shipping_data['first_name'])) $errors[] = 'First name is required';
        if (empty($shipping_data['last_name'])) $errors[] = 'Last name is required';
        if (empty($shipping_data['email'])) $errors[] = 'Email is required';
        if (empty($shipping_data['phone'])) $errors[] = 'Phone is required';
        if (empty($shipping_data['address1'])) $errors[] = 'Address is required';
        if (empty($shipping_data['city'])) $errors[] = 'City is required';
        if (empty($shipping_data['payment_method'])) $errors[] = 'Payment method is required';
        
        if (empty($errors)) {
            $payment_method = $shipping_data['payment_method'];
            unset($shipping_data['payment_method']);
            
            // Persist to session
            $_SESSION['checkout_shipping'] = $shipping_data;
            $_SESSION['checkout_payment'] = $payment_method;
            
            $show_review = true;
        } else {
            $profile_error = implode('<br>', $errors);
        }
    } elseif ($_POST['action'] === 'place_order') {
        $shipping_data = $_SESSION['checkout_shipping'] ?? [];
        $payment_method = $_SESSION['checkout_payment'] ?? '';
        
        if (empty($shipping_data) || empty($payment_method)) {
            $profile_error = 'Missing shipping or payment information. Please go back and re-enter your details.';
        } else {
            // Create order
            try {
                $pdo->beginTransaction();

                // Generate order ID (preferred string ID)
                $order_id = 'ORD-' . strtoupper(uniqid());

                // Try to insert using `order_id` column (modern schema)
                try {
                    $stmt = $pdo->prepare("INSERT INTO orders (order_id, user_id, subtotal, shipping, total, status, payment_method, shipping_first_name, shipping_last_name, shipping_address, shipping_city, shipping_zip, shipping_country, shipping_phone, shipping_email, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([
                        $order_id,
                        $user_id,
                        $subtotal,
                        $shipping,
                        $total,
                        'pending',
                        $payment_method,
                        $shipping_data['first_name'],
                        $shipping_data['last_name'],
                        $shipping_data['address1'] . ($shipping_data['address2'] ? ', ' . $shipping_data['address2'] : ''),
                        $shipping_data['city'],
                        $shipping_data['zip'],
                        $shipping_data['country'],
                        $shipping_data['phone'],
                        $shipping_data['email']
                    ]);
                } catch (PDOException $e) {
                    // If the `order_id` column doesn't exist, fall back to inserting without it
                    if ($e->getCode() === '42S22' || stripos($e->getMessage(), 'Unknown column') !== false) {
                        $alt = $pdo->prepare("INSERT INTO orders (user_id, subtotal, shipping, total, status, payment_method, shipping_first_name, shipping_last_name, shipping_address, shipping_city, shipping_zip, shipping_country, shipping_phone, shipping_email, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                        $alt->execute([
                            $user_id,
                            $subtotal,
                            $shipping,
                            $total,
                            'pending',
                            $payment_method,
                            $shipping_data['first_name'],
                            $shipping_data['last_name'],
                            $shipping_data['address1'] . ($shipping_data['address2'] ? ', ' . $shipping_data['address2'] : ''),
                            $shipping_data['city'],
                            $shipping_data['zip'],
                            $shipping_data['country'],
                            $shipping_data['phone'],
                            $shipping_data['email']
                        ]);

                        // Use numeric DB id as the order identifier when fallback used
                        $numeric_id = $pdo->lastInsertId();
                        $order_id = (string)$numeric_id;
                    } else {
                        throw $e;
                    }
                }

                // Insert order items and decrement stocks (total and specific size)
                $order_item_stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, size, price, quantity, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $product_stock_stmt = $pdo->prepare("UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id = ?");
                $size_stock_stmt = $pdo->prepare("UPDATE product_size_stock SET stock = GREATEST(0, stock - ?) WHERE product_id = ? AND size = ?");
                
                foreach ($cart_items as $item) {
                    $item_size = $item['size'] ?? null;
                    
                    $order_item_stmt->execute([
                        $order_id,
                        $item['id'],
                        $item['name'],
                        $item_size,
                        $item['price'],
                        $item['quantity']
                    ]);
                    
                    // Decrement total stock
                    $product_stock_stmt->execute([
                        $item['quantity'],
                        $item['id']
                    ]);
                    
                    // Decrement specific size stock if size is set
                    if ($item_size) {
                        $size_stock_stmt->execute([
                            $item['quantity'],
                            $item['id'],
                            $item_size
                        ]);
                    }
                }

                $pdo->commit();

                // Save shipping address to user profile for future use
                try {
                    $update_stmt = $pdo->prepare("UPDATE users SET phone = ?, address = ?, address2 = ?, city = ?, zip = ?, country = ? WHERE id = ?");
                    $update_stmt->execute([
                        $shipping_data['phone'],
                        $shipping_data['address1'],
                        $shipping_data['address2'],
                        $shipping_data['city'],
                        $shipping_data['zip'],
                        $shipping_data['country'],
                        $user_id
                    ]);
                } catch (PDOException $e) {
                    // Non-critical error, continue
                    error_log("Warning: Could not update user shipping info: " . $e->getMessage());
                }

                // Clear cart
                $_SESSION['cart'] = [];

                $order_success = true;
                
                // Send order confirmation email to customer
                require_once __DIR__ . '/../includes/email.php';
                $customerName = $shipping_data['first_name'] . ' ' . $shipping_data['last_name'];
                // Get user email from the users table
                $stmtEmail = $pdo->prepare("SELECT email FROM users WHERE id = ?");
                $stmtEmail->execute([$user_id]);
                $userEmailData = $stmtEmail->fetch(PDO::FETCH_ASSOC);
                $customerEmail = $userEmailData['email'] ?? $shipping_data['email'];
                
                // Prepare order data for email
                $orderEmailData = [
                    'total' => $total,
                    'shipping' => $shipping,
                    'subtotal' => $subtotal,
                    'shipping_first_name' => $shipping_data['first_name'],
                    'shipping_last_name' => $shipping_data['last_name'],
                    'shipping_address' => $shipping_data['address1'] . ($shipping_data['address2'] ? ', ' . $shipping_data['address2'] : ''),
                    'shipping_city' => $shipping_data['city'],
                    'shipping_zip' => $shipping_data['zip'],
                    'shipping_country' => $shipping_data['country'],
                    'shipping_phone' => $shipping_data['phone']
                ];
                
                // Send confirmation to customer
                send_order_confirmation($customerEmail, $customerName, $order_id, $orderEmailData, $cart_items);
                
                // Send notification to admin
                send_admin_new_order_notification($order_id, $customerName, $customerEmail, $total, count($cart_items));
            } catch (PDOException $e) {
                $pdo->rollBack();
                // Log the actual error for debugging
                error_log("Order placement failed: " . $e->getMessage());
                $profile_error = 'Failed to place order. Please try again. Error: ' . $e->getMessage();
            }
        }
    }
}

?>
<div class="py-12 md:py-20 px-8 md:px-16 mt-16">
    <div class="max-w-6xl mx-auto">
        <?php if ($order_success): ?>
            <div class="max-w-xl mx-auto bg-white p-10 rounded-2xl shadow-xl text-center">
                <div class="mx-auto w-20 h-20 flex items-center justify-center rounded-full bg-green-100 mb-6">
                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>

                <h1 class="text-3xl font-bold serif mb-3"><?php echo __('order_confirmed'); ?></h1>

                <p class="text-lg font-semibold text-gray-700 mb-4">
                    <?php echo __('your_order_id'); ?>
                    <span class="text-green-600 text-xl tracking-wider block sm:inline-block mt-1 sm:mt-0 bg-green-50 px-3 py-1 rounded-lg">
                        #<?php echo htmlspecialchars($order_id ?? 'N/A'); ?>
                    </span>
                </p>

                <p class="text-gray-600 mb-8 max-w-sm mx-auto">
                    <?php echo __('thank_you_purchase'); ?>
                </p>

                <a href="index.php?page=shop" class="inline-block bg-black text-white px-8 py-3 text-xs tracking-[0.2em] uppercase hover:bg-black/80 transition-colors">
                    <?php echo __('continue_shopping'); ?>
                </a>
            </div>
        <?php else: ?>
            <?php if (count($cart_items) === 0): ?>
                <div class="text-center py-20">
                    <h2 class="text-3xl serif mb-6"><?php echo __('empty_cart'); ?></h2>
                    <a href="index.php?page=shop" class="inline-block bg-black text-white px-8 py-3 text-xs tracking-[0.2em] uppercase hover:bg-black/80 transition-colors">
                        <?php echo __('continue_shopping'); ?>
                    </a>
                </div>
            <?php else: ?>
                <h1 class="text-4xl serif mb-10"><?php echo __('secure_checkout'); ?></h1>

                <div class="flex flex-col lg:flex-row gap-8">

                    <section class="lg:w-2/3 bg-white p-6 sm:p-8 rounded-xl shadow-lg border border-gray-100">

                        <nav class="flex justify-between mb-8 pb-4 border-b">
                            <div class="flex items-center space-x-2">
                                <span class="w-8 h-8 flex items-center justify-center rounded-full bg-black text-white font-bold">1</span>
                                <span class="font-semibold text-black"><?php echo __('shipping'); ?></span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="w-8 h-8 flex items-center justify-center rounded-full <?php echo $show_review ? 'bg-black text-white' : 'border-2 border-gray-300 text-gray-400'; ?>">2</span>
                                <span class="<?php echo $show_review ? 'text-black' : 'text-gray-400'; ?>"><?php echo __('review'); ?></span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="w-8 h-8 flex items-center justify-center rounded-full border-2 border-gray-300 text-gray-400">3</span>
                                <span class="text-gray-400"><?php echo __('confirmation'); ?></span>
                            </div>
                        </nav>

                        <h2 class="text-2xl font-bold mb-6"><?php echo $show_review ? __('order_review') : __('shipping_address'); ?></h2>

                        <?php if (!$show_review): ?>
                        
                        <?php if (!empty($saved_addresses)): ?>
                            <div class="mb-6 bg-gray-50 p-4 border rounded">
                                <label for="saved_address" class="block text-sm font-medium text-gray-700 mb-2">Select a saved address:</label>
                                <select id="saved_address" class="w-full border border-gray-300 p-3 focus:outline-none focus:border-black">
                                    <option value="">-- Choose an address or enter a new one below --</option>
                                    <?php foreach ($saved_addresses as $addr): ?>
                                        <option value="<?php echo htmlspecialchars(json_encode([
                                            'first_name' => $addr['first_name'],
                                            'last_name' => $addr['last_name'],
                                            'phone' => $addr['phone'],
                                            'address1' => $addr['address'],
                                            'city' => $addr['city'],
                                            'zip' => $addr['zip'],
                                            'country' => $addr['country']
                                        ])); ?>">
                                            <?php echo htmlspecialchars($addr['title'] . ' - ' . $addr['address'] . ', ' . $addr['city']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <form id="shipping-form" class="space-y-6" method="POST" action="index.php?page=checkout">
                            <input type="hidden" name="action" value="review">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('first_name'); ?></label>
                                    <input type="text" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($user_data['first_name'] ?? ''); ?>"
                                        class="w-full border border-gray-300 p-3 focus:outline-none focus:border-black">
                                </div>
                                <div>
                                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('last_name'); ?></label>
                                    <input type="text" id="last_name" name="last_name" required value="<?php echo htmlspecialchars($user_data['last_name'] ?? ''); ?>"
                                        class="w-full border border-gray-300 p-3 focus:outline-none focus:border-black">
                                </div>
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('email_address'); ?></label>
                                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>"
                                    class="w-full border border-gray-300 p-3 focus:outline-none focus:border-black">
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('phone_number'); ?></label>
                                <input type="tel" id="phone" name="phone" required value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>"
                                    class="w-full border border-gray-300 p-3 focus:outline-none focus:border-black">
                            </div>

                            <div>
                                <label for="address1" class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('address'); ?></label>
                                <input type="text" id="address1" name="address1" required value="<?php echo htmlspecialchars($user_data['address'] ?? ''); ?>"
                                    class="w-full border border-gray-300 p-3 focus:outline-none focus:border-black">
                            </div>

                            <div>
                                <label for="address2" class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('address_line_2'); ?></label>
                                <input type="text" id="address2" name="address2" value="<?php echo htmlspecialchars($user_data['address2'] ?? ''); ?>"
                                    class="w-full border border-gray-300 p-3 focus:outline-none focus:border-black">
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label for="city" class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('city'); ?></label>
                                    <select id="city" name="city" required class="w-full border border-gray-300 p-3 focus:outline-none focus:border-black">
                                        <option value="">Select City</option>
                                        <?php
                                        try {
                                            $stmt = $pdo->prepare("SELECT city FROM delivery_cities WHERE is_active = 1 ORDER BY city");
                                            $stmt->execute();
                                            $available_cities = $stmt->fetchAll(PDO::FETCH_COLUMN);
                                            foreach ($available_cities as $city) {
                                                $selected = ($user_data['city'] ?? '') === $city ? 'selected' : '';
                                                echo "<option value=\"" . htmlspecialchars($city) . "\" $selected>" . htmlspecialchars($city) . "</option>";
                                            }
                                        } catch (PDOException $e) {
                                            echo "<option value=\"Casablanca\">Casablanca</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="zip" class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('postal_code'); ?></label>
                                    <input type="text" id="zip" name="zip" required value="<?php echo htmlspecialchars($user_data['zip'] ?? ''); ?>"
                                        class="w-full border border-gray-300 p-3 focus:outline-none focus:border-black">
                                </div>
                                <div>
                                    <label for="country" class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('country'); ?></label>
                                    <input type="text" id="country" name="country" required value="<?php echo htmlspecialchars($user_data['country'] ?? 'Morocco'); ?>"
                                        class="w-full border border-gray-300 p-3 focus:outline-none focus:border-black">
                                </div>
                            </div>

                            <!-- Payment Method -->
                            <div class="pt-4 border-t">
                                <h3 class="text-lg font-bold mb-4"><?php echo __('payment_method'); ?></h3>
                                <div class="space-y-3">
                                    <label class="flex items-center justify-between p-4 border border-gray-200 rounded-lg cursor-pointer hover:shadow-md transition">
                                        <div class="flex items-center space-x-3">
                                            <input type="radio" name="payment_method" value="cod" required checked
                                                class="w-5 h-5 text-black border-gray-300 focus:ring-black">
                                            <span class="font-medium"><?php echo __('pay_at_arrival'); ?></span>
                                        </div>
                                        <svg class="w-6 h-6 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                    </label>
                                </div>
                            </div>

                            <?php if (!empty($profile_error)): ?>
                                <div class="p-3 text-sm text-red-800 rounded-lg bg-red-50">
                                    <?php echo $profile_error; ?>
                                </div>
                            <?php endif; ?>

                            <div class="pt-4 flex justify-between items-center">
                                <a href="index.php?page=cart" class="text-gray-500 hover:text-black transition">← <?php echo __('back_to_cart'); ?></a>
                                <button type="submit" class="px-8 py-3 bg-black text-white font-semibold text-sm tracking-widest uppercase rounded hover:bg-black/80 transition">
                                    <?php echo __('review_order'); ?>
                                </button>
                            </div>
                        </form>
                        
                        <script>
                        document.getElementById('city').addEventListener('change', function() {
                            document.getElementById('shipping-form').submit();
                        });
                        </script>
                        <?php else: ?>
                        <!-- Review Section -->
                        <div class="space-y-6">
                            <div class="p-6 border border-gray-200 rounded-xl bg-gray-50">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-xl font-bold"><?php echo __('shipping_address'); ?></h3>
                                    <button type="button" id="edit-shipping" class="text-sm font-medium text-black hover:underline"><?php echo __('change'); ?></button>
                                </div>
                                <div class="space-y-1 text-gray-600">
                                    <p class="font-semibold"><?php echo htmlspecialchars($shipping_data['first_name'] . ' ' . $shipping_data['last_name']); ?></p>
                                    <p><?php echo htmlspecialchars($shipping_data['address1']); ?></p>
                                    <p><?php echo htmlspecialchars($shipping_data['city'] . ', ' . $shipping_data['zip']); ?></p>
                                    <p><?php echo htmlspecialchars($shipping_data['country']); ?></p>
                                    <p class="pt-2"><?php echo htmlspecialchars($shipping_data['email']); ?></p>
                                    <p><?php echo htmlspecialchars($shipping_data['phone']); ?></p>
                                </div>
                            </div>

                            <div class="p-6 border border-gray-200 rounded-xl bg-gray-50">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-xl font-bold"><?php echo __('payment_method'); ?></h3>
                                </div>
                                <p class="font-semibold"><?php echo __('pay_at_arrival'); ?></p>
                                <p class="text-sm text-gray-500 mt-1"><?php echo __('payment_arrival_desc'); ?></p>
                            </div>

                            <?php if (!empty($profile_error)): ?>
                                <div class="p-3 text-sm text-red-800 rounded-lg bg-red-50">
                                    <?php echo $profile_error; ?>
                                </div>
                            <?php endif; ?>

                            <div class="pt-4 flex justify-between">
                                <button type="button" id="back-to-shipping" class="px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded hover:bg-gray-100 transition">
                                    ← <?php echo __('back'); ?>
                                </button>
                                <form method="POST" action="index.php?page=checkout" style="display:inline;">
                                    <input type="hidden" name="action" value="place_order">
                                    <button type="submit" class="px-8 py-3 bg-green-600 text-white font-semibold text-sm tracking-widest uppercase rounded hover:bg-green-700 transition">
                                        <?php echo __('place_order'); ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>

                    </section>

                    <!-- Order Summary -->
                    <aside class="lg:w-1/3">
                        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 lg:sticky lg:top-24 space-y-6">
                            <h3 class="text-2xl font-bold border-b pb-3 mb-4"><?php echo __('your_order'); ?> (<?php echo count($cart_items); ?>)</h3>

                            <div class="space-y-4 max-h-60 overflow-y-auto pr-2 border-b pb-4">
                                <?php foreach ($cart_items as $item): ?>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <img src="<?php echo htmlspecialchars($item['image1'] ?? $item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                class="w-12 h-12 object-cover rounded border border-gray-100"
                                                onerror="this.onerror=null; this.src='https://placehold.co/50x50/CCCCCC/000000?text=S';">
                                            <div class="flex flex-col">
                                                <span class="text-sm font-medium truncate max-w-[150px]"><?php echo htmlspecialchars($item['name']); ?></span>
                                                <?php if (!empty($item['size'])): ?>
                                                    <span class="text-xs text-gray-500"><?php echo __('size'); ?>: <?php echo htmlspecialchars($item['size']); ?></span>
                                                <?php endif; ?>
                                                <span class="text-xs text-gray-500"><?php echo __('quantity'); ?>: <?php echo $item['quantity']; ?></span>
                                            </div>
                                        </div>
                                        <span class="text-sm font-semibold"><?php echo number_format($item['price'] * $item['quantity'], 2); ?> MAD</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="space-y-3 text-gray-700">
        <div class="flex justify-between">
                                    <span><?php echo __('subtotal'); ?></span>
                                    <span class="font-medium"><?php echo number_format($subtotal, 2); ?> MAD</span>
                                </div>
                            <div class="flex justify-between border-b pb-3">
                                    <span><?php echo __('shipping'); ?> (<?php echo empty($selected_city) ? 'Select City' : htmlspecialchars($selected_city); ?>)</span>
                                    <span class="font-medium"><?php echo empty($selected_city) ? 'Calculate...' : ($shipping == 0 ? __('free_shipping') : number_format($shipping, 2) . ' MAD'); ?></span>
                                </div>

                                <div class="flex justify-between pt-3 text-2xl font-extrabold text-black">
                                    <span><?php echo __('order_total'); ?></span>
                                    <span><?php echo number_format($total, 2); ?> MAD</span>
                                </div>
                            </div>
                        </div>
                    </aside>

                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const editShippingBtn = document.getElementById('edit-shipping');
        const backToShippingBtn = document.getElementById('back-to-shipping');
        const savedAddressSelect = document.getElementById('saved_address');

        if (savedAddressSelect) {
            savedAddressSelect.addEventListener('change', function() {
                if (this.value) {
                    try {
                        const addr = JSON.parse(this.value);
                        document.getElementById('first_name').value = addr.first_name || '';
                        document.getElementById('last_name').value = addr.last_name || '';
                        document.getElementById('phone').value = addr.phone || '';
                        document.getElementById('address1').value = addr.address1 || '';
                        document.getElementById('city').value = addr.city || '';
                        document.getElementById('zip').value = addr.zip || '';
                        document.getElementById('country').value = addr.country || '';
                    } catch (e) {
                        console.error("Error parsing address JSON", e);
                    }
                }
            });
        }

        if (editShippingBtn) {
            editShippingBtn.addEventListener('click', () => {
                window.location.href = 'index.php?page=checkout';
            });
        }

        if (backToShippingBtn) {
            backToShippingBtn.addEventListener('click', () => {
                window.location.href = 'index.php?page=checkout';
            });
        }
    });
</script>
