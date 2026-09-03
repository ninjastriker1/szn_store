<?php
/**
 * CSRF Token Management
 */

require_once __DIR__ . '/../config/security.php';

/**
 * Generate CSRF Token
 */
function generate_csrf_token() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        $_SESSION[CSRF_TOKEN_NAME . '_time'] = time();
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Get CSRF Token HTML Input
 */
function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars($token) . '">';
}

/**
 * Verify CSRF Token
 */
function verify_csrf_token() {
    $debug_info = [
        'has_post_token' => isset($_POST[CSRF_TOKEN_NAME]),
        'post_token_exists' => isset($_POST[CSRF_TOKEN_NAME]) ? 'yes (length: ' . strlen($_POST[CSRF_TOKEN_NAME]) . ')' : 'no',
        'has_session_token' => isset($_SESSION[CSRF_TOKEN_NAME]),
        'session_token_exists' => isset($_SESSION[CSRF_TOKEN_NAME]) ? 'yes (length: ' . strlen($_SESSION[CSRF_TOKEN_NAME]) . ')' : 'no',
        'post_token' => isset($_POST[CSRF_TOKEN_NAME]) ? substr($_POST[CSRF_TOKEN_NAME], 0, 8) . '...' : 'none',
        'session_token' => isset($_SESSION[CSRF_TOKEN_NAME]) ? substr($_SESSION[CSRF_TOKEN_NAME], 0, 8) . '...' : 'none',
        'token_time' => $_SESSION[CSRF_TOKEN_NAME . '_time'] ?? 'not set',
        'current_time' => time(),
        'age_seconds' => isset($_SESSION[CSRF_TOKEN_NAME . '_time']) ? time() - $_SESSION[CSRF_TOKEN_NAME . '_time'] : 'n/a',
        'max_age' => CSRF_TOKEN_EXPIRE
    ];
    
    if (!isset($_POST[CSRF_TOKEN_NAME])) {
        error_log('CSRF DEBUG (forgot-password): No POST token - ' . json_encode($debug_info));
        return false;
    }
    
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        error_log('CSRF DEBUG (forgot-password): No SESSION token - ' . json_encode($debug_info));
        return false;
    }
    
    // Check if token has expired
    if (time() - $_SESSION[CSRF_TOKEN_NAME . '_time'] > CSRF_TOKEN_EXPIRE) {
        error_log('CSRF DEBUG (forgot-password): Token expired - ' . json_encode($debug_info));
        unset($_SESSION[CSRF_TOKEN_NAME]);
        unset($_SESSION[CSRF_TOKEN_NAME . '_time']);
        return false;
    }
    
    // Constant-time comparison
    if (!hash_equals($_SESSION[CSRF_TOKEN_NAME], $_POST[CSRF_TOKEN_NAME])) {
        error_log('CSRF DEBUG (forgot-password): Token mismatch - ' . json_encode($debug_info));
        return false;
    }
    
    error_log('CSRF DEBUG (forgot-password): Token verified successfully - ' . json_encode($debug_info));
    return true;
}

?>
