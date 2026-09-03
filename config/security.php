<?php
/**
 * Security Configuration File
 * Move sensitive data here and load via environment variables
 */

// Load from environment or use defaults - Local XAMPP
define('DB_HOST', 'sql312.infinityfree.com');
define('DB_NAME', 'if0_41400800_salah_store');
define('DB_USER', 'if0_41400800');
define('DB_PASS', '7lIC2xHs71');

// Email Configuration (SMTP)
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', getenv('SMTP_PORT') ?: '587');
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: 'vipdiscouverytours@gmail.com');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: 'xtvv bqxu jejz ymaj');
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: 'vipdiscouverytours@gmail.com');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'SZN Store');
define('SMTP_DEBUG', getenv('SMTP_DEBUG') === 'true' ? true : false);

// Admin email for notifications
define('ADMIN_EMAIL', getenv('ADMIN_EMAIL') ?: 'vipdiscouverytours@gmail.com');

// Security Settings
define('ENABLE_HTTPS', getenv('ENABLE_HTTPS') === 'true' ? true : false);
define('SESSION_TIMEOUT', 86400); // 24 hours (was 30 minutes)
define('LOGIN_ATTEMPTS_MAX', 5);
define('LOGIN_ATTEMPTS_TIMEOUT', 900); // 15 minutes

// CSRF Token settings
define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_EXPIRE', 3600); // 1 hour

// Logging
define('LOG_DIR', __DIR__ . '/../logs/');
define('LOG_ERRORS', true);

// Ensure logs directory exists
if (!is_dir(LOG_DIR)) {
    mkdir(LOG_DIR, 0755, true);
}

/**
 * Log errors securely (not to user)
 */
function log_error($message, $context = []) {
    if (!LOG_ERRORS) return;
    
    $log_file = LOG_DIR . 'error_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $context_str = json_encode($context);
    $log_entry = "[$timestamp] $message | Context: $context_str\n";
    
    @file_put_contents($log_file, $log_entry, FILE_APPEND);
}

/**
 * Enforce HTTPS in production
 */
function enforce_https() {
    if (ENABLE_HTTPS && empty($_SERVER['HTTPS'])) {
        header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        exit;
    }
}

/**
 * Set security headers
 */
function set_security_headers() {
    // Prevent clickjacking
    header("X-Frame-Options: SAMEORIGIN");
    
    // Prevent MIME type sniffing
    header("X-Content-Type-Options: nosniff");
    
    // Enable XSS protection
    header("X-XSS-Protection: 1; mode=block");
    
    // Referrer policy
    header("Referrer-Policy: strict-origin-when-cross-origin");
    
    // Feature policy (Permissions-Policy)
    header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
    
    // Basic CSP (Content Security Policy)
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' https:; frame-ancestors 'self';");
}

/**
 * Session hardening
 */
function harden_session() {
    // Only set session ini if no session active (avoid warnings)
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.use_strict_mode', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', ENABLE_HTTPS ? 1 : 0);
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
            session_destroy();
            header("Location: index.php?page=login&reason=timeout");
            exit;
        }
    }
    $_SESSION['last_activity'] = time();
}

?>