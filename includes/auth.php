<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if session already active before starting
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base URL for the application (calculate from script location)
$script_dir = dirname($_SERVER['SCRIPT_NAME']);
if (basename($script_dir) === 'includes') {
    $base = dirname($script_dir);
} else {
    $base = $script_dir;
}
if ($base === '.' || $base === '\\' || $base === '/') {
    $base = '';
}
define('BASE_URL', $base);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/rate_limit.php';

// Set security headers
set_security_headers();

// Harden session
harden_session();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    error_log("AUTH DEBUG: POST action='$action' from IP $ip");
    error_log("AUTH DEBUG: CSRF_TOKEN_NAME=" . (defined('CSRF_TOKEN_NAME') ? CSRF_TOKEN_NAME : 'NOT_DEFINED'));
    error_log("AUTH DEBUG: POST keys: " . json_encode(array_keys($_POST)));
    
    // Verify CSRF token (skip for AJAX forgot/reset if needed, but keep for forms)
    if (!verify_csrf_token()) {
        error_log("AUTH DEBUG (forgot-password): CSRF verification FAILED for action='$action'");
        $_SESSION['error'] = "Security validation failed. Please try again.";
        header("Location: " . base_url('index.php?page=login'));
        exit;
    }
    error_log("AUTH DEBUG (forgot-password): CSRF verification PASSED for action='$action'");
    // LOGIN
    if (isset($_POST['login'])) {
        // Check rate limit
        $ip = $_SERVER['REMOTE_ADDR'];
        $rate_check = check_rate_limit($ip);
        
        if ($rate_check['limited']) {
            $_SESSION['error'] = $rate_check['message'];
            header("Location: " . base_url('index.php?page=login'));
            exit;
        }
        
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            $_SESSION['error'] = "Email and password are required";
            header("Location: " . base_url('index.php?page=login'));
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                if (isset($user['status']) && $user['status'] === 'banned') {
                    $_SESSION['error'] = "Your account has been banned. Please contact support.";
                    header("Location: " . base_url('index.php?page=login'));
                    exit;
                }
                
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);
                
                // normalize role value
                $role = strtolower(trim($user['role'] ?? 'user'));
                if ($role !== 'admin') {
                    $role = 'user';
                }

                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $role;
                $_SESSION['login_time'] = time();
                $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
                $_SESSION['success'] = "Login successful! Welcome back, " . htmlspecialchars($user['first_name']);
                
                // Clear CSRF token after successful login
                unset($_SESSION[CSRF_TOKEN_NAME]);
                
                // Redirect based on role using helper that respects the application base path
                // Check for redirect parameter
                $redirect = $_POST['redirect'] ?? $_GET['redirect'] ?? '';

                if ($redirect === 'checkout') {
                    header("Location: " . base_url('pages/checkout.php'));
                } elseif ($role === 'admin') {
                    header("Location: " . base_url('admin/dashboard.php'));
                } else {
                    header("Location: " . base_url('index.php?page=profile'));
                }
                exit;
            } else {
                // Record failed attempt for rate limiting
                record_login_attempt($ip);
                $_SESSION['error'] = "Invalid email or password";
                header("Location: " . base_url('index.php?page=login'));
                exit;
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
            header("Location: " . base_url('index.php?page=login'));
            exit;
        }
    }

    // SIGNUP
    if (isset($_POST['signup'])) {
        $first_name = sanitize($_POST['first_name']);
        $last_name = sanitize($_POST['last_name']);
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Validation
        if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($confirm_password)) {
            $_SESSION['error'] = "All fields are required";
            header("Location: " . base_url('index.php?page=signup'));
            exit;
        }

        if (strlen($first_name) < 2 || strlen($last_name) < 2) {
            $_SESSION['error'] = "First and last name must be at least 2 characters each";
            header("Location: " . base_url('index.php?page=signup'));
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Please enter a valid email";
            header("Location: " . base_url('index.php?page=signup'));
            exit;
        }

        if (strlen($password) < 6) {
            $_SESSION['error'] = "Password must be at least 6 characters";
            header("Location: " . base_url('index.php?page=signup'));
            exit;
        }

        if ($password !== $confirm_password) {
            $_SESSION['error'] = "Passwords do not match";
            header("Location: " . base_url('index.php?page=signup'));
            exit;
        }

        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            if ($stmt->fetch()) {
                $_SESSION['error'] = "Email already registered";
                header("Location: " . base_url('index.php?page=signup'));
                exit;
            }

            // Insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES (:first_name, :last_name, :email, :password, 'user')");
            $stmt->execute([
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'password' => $hashed_password
            ]); // role default enforced at query level

            $_SESSION['success'] = "Account created successfully! Please login.";
            header("Location: " . base_url('index.php?page=login'));
            exit;
        } catch (PDOException $e) {
            $_SESSION['error'] = "Registration failed: " . $e->getMessage();
            header("Location: " . base_url('index.php?page=signup'));
            exit;
        }
    }

    // FORGOT PASSWORD REQUEST
    if ($action === 'forgot_password') {
        $email = sanitize($_POST['email']);
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = __('invalid_email');
            header("Location: " . base_url('index.php?page=forgot-password'));
            exit;
        }
        
        try {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id, first_name, email FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                // Don't reveal if email exists (security)
                $_SESSION['success'] = __('reset_link_sent');
                header("Location: " . base_url('index.php?page=forgot-password'));
                exit;
            }
            
            // Generate secure token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            
            // Update user with token
            $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $stmt->execute([$token, $expires, $user['id']]);
            
// Send reset email
            require_once __DIR__ . '/email.php';
            if (function_exists('send_reset_email') && send_reset_email($email, $user['first_name'], $token)) {
                $_SESSION['success'] = __('reset_link_sent');
            } else {
                $_SESSION['error'] = __('email_send_failed');
            }
            
        } catch (PDOException $e) {
            $_SESSION['error'] = __('system_error');
            error_log("Forgot password DB error: " . $e->getMessage());
        }
        
        header("Location: " . base_url('index.php?page=forgot-password'));
        exit;
    }
    
    // PASSWORD RESET
    if ($action === 'reset_password') {
        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($token) || empty($password) || $password !== $confirm_password || strlen($password) < 8) {
            $_SESSION['error'] = __('invalid_reset_data');
            header("Location: " . base_url('index.php?page=reset-password&token=' . urlencode($token)));
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
            $stmt->execute([$token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $_SESSION['error'] = __('invalid_expired_token');
                header("Location: " . base_url('index.php?page=reset-password&token=' . urlencode($token)));
                exit;
            }
            
            // Update password and clear token
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
            $stmt->execute([$hashed_password, $user['id']]);
            
            $_SESSION['success'] = __('password_reset_success');
            header("Location: " . base_url('index.php?page=reset-password&token=' . urlencode($token)));
            exit;
            
        } catch (PDOException $e) {
            $_SESSION['error'] = __('system_error');
            error_log("Reset password DB error: " . $e->getMessage());
        }
        
        header("Location: " . base_url('index.php?page=reset-password&token=' . urlencode($token)));
        exit;
    }

    // Invalid POST request
    header("Location: " . base_url('index.php?page=login'));
    exit;
}
?>

