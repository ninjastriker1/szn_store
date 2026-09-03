<?php
/**
 * Rate Limiting - Prevent brute force attacks
 */

require_once __DIR__ . '/../config/security.php';

/**
 * Check login rate limit
 */
function check_rate_limit($ip_address) {
    $rate_limit_key = 'login_attempts_' . $ip_address;
    
    if (!isset($_SESSION[$rate_limit_key])) {
        $_SESSION[$rate_limit_key] = [
            'attempts' => 0,
            'first_attempt' => time()
        ];
    }
    
    $limit_data = &$_SESSION[$rate_limit_key];
    $time_since_first = time() - $limit_data['first_attempt'];
    
    // Reset if timeout exceeded
    if ($time_since_first > LOGIN_ATTEMPTS_TIMEOUT) {
        $limit_data = [
            'attempts' => 0,
            'first_attempt' => time()
        ];
    }
    
    // Check if exceeded limit
    if ($limit_data['attempts'] >= LOGIN_ATTEMPTS_MAX) {
        $remaining_time = LOGIN_ATTEMPTS_TIMEOUT - $time_since_first;
        return [
            'limited' => true,
            'remaining_time' => $remaining_time,
            'message' => "Too many login attempts. Please try again in " . ceil($remaining_time / 60) . " minutes."
        ];
    }
    
    return ['limited' => false];
}

/**
 * Record login attempt
 */
function record_login_attempt($ip_address) {
    $rate_limit_key = 'login_attempts_' . $ip_address;
    
    if (isset($_SESSION[$rate_limit_key])) {
        $_SESSION[$rate_limit_key]['attempts']++;
    } else {
        $_SESSION[$rate_limit_key] = [
            'attempts' => 1,
            'first_attempt' => time()
        ];
    }
}

?>
