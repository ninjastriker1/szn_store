<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/csrf.php';

// Set security headers
set_security_headers();

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Verify CSRF token
    if (!verify_csrf_token()) {
        $_SESSION['error'] = "Security validation failed. Please try again.";
        header("Location: " . base_url('index.php?page=contact'));
        exit;
    }
    
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');
    
    // Validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: " . base_url('index.php?page=contact'));
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Please enter a valid email address.";
        header("Location: " . base_url('index.php?page=contact'));
        exit;
    }
    
    try {
        // Insert contact message into database
        $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message, created_at) VALUES (:name, :email, :subject, :message, NOW())");
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => $message
        ]);
        
        $_SESSION['success'] = "Your message has been sent successfully! We'll get back to you soon.";
        header("Location: " . base_url('index.php?page=contact'));
        exit;
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "Failed to send message. Please try again later.";
        header("Location: " . base_url('index.php?page=contact'));
        exit;
    }
}

// Invalid request
header("Location: " . base_url('index.php?page=contact'));
exit;
?>

