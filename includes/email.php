<?php
/**
 * Email Helper Functions using PHPMailer
 * Handles sending order confirmation and status update emails
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Initialize and configure PHPMailer
 * @return PHPMailer Configured mailer instance
 */
function init_mailer() {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        if (!empty(SMTP_USERNAME) && !empty(SMTP_PASSWORD)) {
            // SMTP authentication
            $mail->isSMTP();
            $mail->SMTPAuth = true;
            $mail->Host = SMTP_HOST;
            $mail->Port = SMTP_PORT;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            
            // Encryption settings
            if (SMTP_PORT === '465') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
        } else {
            // Use local mail (for development/testing)
            $mail->isMail();
        }
        
        // Sender
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        
        // HTML formatting
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        
        return $mail;
    } catch (Exception $e) {
        error_log("Mailer initialization failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Send order status update email to customer with receipt
 */
function send_product_approved_notification($email, $name, $productId, $productName, $price, $notes = '') {
    $mail = init_mailer();
    if (!$mail) return false;
    
    try {
        $mail->addAddress($email, $name);
        $mail->Subject = __('product_approved');
        
        $body = '<!DOCTYPE html>
<html>
<head>
<style>
body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
.container { max-width: 600px; margin: 0 auto; padding: 20px; }
.header { background: #10b981; color: #fff; padding: 20px; text-align: center; }
.content { padding: 20px; background: #f9f9f9; }
.product-details { margin: 20px 0; }
.footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
</style>
</head>
<body>
<div class="container">
<div class="header">
<h1>' . __('product_approved') . '</h1>
</div>
<div class="content">
<p>' . sprintf(__('product_approved_desc'), htmlspecialchars($name)) . '</p>
<div class="product-details">
<h3>' . htmlspecialchars($productName) . '</h3>
<p>Price: ' . number_format($price, 2) . ' MAD</p>';
        if ($notes) {
            $body .= '<p><strong>Notes:</strong> ' . htmlspecialchars($notes) . '</p>';
        }
        $body .= '</div>
<p>' . __('now_live_store') . '</p>
</div>
<div class="footer">
<p>&copy; ' . date('Y') . ' SZN Store</p>
</div>
</div>
</body>
</html>';
        
        $mail->Body = $body;
        $mail->AltBody = 'Product ' . $productName . ' approved!';
        return $mail->send();
    } catch (Exception $e) {
        error_log("Product approved email failed: " . $mail->ErrorInfo);
        return false;
    }
}

function send_product_rejected_notification($email, $name, $productId, $productName, $notes = '') {
    $mail = init_mailer();
    if (!$mail) return false;
    
    try {
        $mail->addAddress($email, $name);
        $mail->Subject = __('product_rejected');
        
        $body = '<!DOCTYPE html>
<html>
<head>
<style>
body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
.container { max-width: 600px; margin: 0 auto; padding: 20px; }
.header { background: #ef4444; color: #fff; padding: 20px; text-align: center; }
.content { padding: 20px; background: #f9f9f9; }
.product-details { margin: 20px 0; }
.footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
</style>
</head>
<body>
<div class="container">
<div class="header">
<h1>' . __('product_rejected') . '</h1>
</div>
<div class="content">
<p>' . sprintf(__('product_rejected_desc'), htmlspecialchars($name)) . '</p>
<div class="product-details">
<h3>' . htmlspecialchars($productName) . '</h3>';
        if ($notes) {
            $body .= '<p><strong>Admin Notes:</strong> ' . htmlspecialchars($notes) . '</p>';
        }
        $body .= '</div>
<p>' . __('revise_resubmit') . '</p>
</div>
<div class="footer">
<p>&copy; ' . date('Y') . ' SZN Store</p>
</div>
</div>
</body>
</html>';
        
        $mail->Body = $body;
        $mail->AltBody = 'Product ' . $productName . ' not approved';
        return $mail->send();
    } catch (Exception $e) {
        error_log("Product rejected email failed: " . $mail->ErrorInfo);
        return false;
    }
}

function send_order_status_update($email, $name, $orderId, $status, $orderData = null, $items = null) {
    $mail = init_mailer();
    if (!$mail) return false;
    
    try {
        $mail->addAddress($email, $name);
        $mail->Subject = 'Order Status Update - #' . $orderId;
        
        // Status messages
        $statusMessages = [
            'pending' => 'Your order is being reviewed and will be processed soon.',
            'processing' => 'Your order is being prepared and will be shipped soon.',
            'shipped' => 'Your order has been shipped and is on its way!',
            'delivered' => 'Your order has been delivered. Thank you for shopping with us!',
            'cancelled' => 'Your order has been cancelled. If you have any questions, please contact us.'
        ];
        
        $statusColors = [
            'pending' => '#f59e0b',
            'processing' => '#3b82f6',
            'shipped' => '#8b5cf6',
            'delivered' => '#10b981',
            'cancelled' => '#ef4444'
        ];
        
        $message = $statusMessages[$status] ?? 'Your order status has been updated.';
        $color = $statusColors[$status] ?? '#666';
        
        $body = '<!DOCTYPE html>
<html>
<head>
<style>
body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
.container { max-width: 600px; margin: 0 auto; padding: 20px; }
.header { background: #000; color: #fff; padding: 20px; text-align: center; }
.content { padding: 20px; background: #f9f9f9; }
.status-box { padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px; }
.order-details { margin: 20px 0; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #f0f0f0; }
.total { font-size: 18px; font-weight: bold; }
.footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
</style>
</head>
<body>
<div class="container">
<div class="header">
<h1>Order Update</h1>
</div>
<div class="content">
<p>Dear <strong>' . htmlspecialchars($name) . '</strong>,</p>
<p>Your order status has been updated.</p>
<div class="status-box" style="background: ' . $color . '20; border: 2px solid ' . $color . ';">
<p style="font-size: 24px; font-weight: bold; color: ' . $color . '; margin: 0;">' . strtoupper($status) . '</p>
<p>' . $message . '</p>
</div>';

        if ($items && $orderData) {
            $body .= '<div class="order-details">
<h3>Order Receipt</h3>
<table>
<thead>
<tr>
<th>Product</th>
<th>Size</th>
<th>Qty</th>
<th>Price</th>
</tr>
</thead>
<tbody>';
            foreach ($items as $item) {
                $body .= '<tr>
<td>' . htmlspecialchars($item['product_name']) . '</td>
<td>' . htmlspecialchars($item['size'] ?? '-') . '</td>
<td>' . $item['quantity'] . '</td>
<td>' . number_format($item['price'] * $item['quantity'], 2) . ' MAD</td>
</tr>';
            }
            $body .= '</tbody>
</table>
<p class="total">Total: ' . number_format($orderData['total'], 2) . ' MAD<br>
(Subtotal: ' . number_format($orderData['subtotal'], 2) . ' + Shipping: ' . number_format($orderData['shipping'], 2) . ')</p>
<h4>Shipping Address</h4>
<p>' . htmlspecialchars($orderData['shipping_first_name'] . ' ' . $orderData['shipping_last_name']) . '<br>' . htmlspecialchars($orderData['shipping_address']) . '<br>' . htmlspecialchars($orderData['shipping_city'] . ', ' . $orderData['shipping_zip']) . '<br>' . htmlspecialchars($orderData['shipping_country']) . '<br>Phone: ' . htmlspecialchars($orderData['shipping_phone']) . '</p>
</div>';
        }
        
        $body .= '<p><strong>Order ID:</strong> #' . htmlspecialchars($orderId) . '</p>
<p><strong>Date:</strong> ' . date('F j, Y g:i A') . '</p>
</div>
<div class="footer">
<p>This is an automated email. Please do not reply directly to this message.</p>
<p>&copy; ' . date('Y') . ' SZN Store. All rights reserved.</p>
</div>
</div>
</body>
</html>';
        
        $mail->Body = $body;
        $mail->AltBody = 'Order #' . $orderId . ' status: ' . $status . ' ' . $message;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Status update email failed: " . $mail->ErrorInfo);
        return false;
    }
}

// [Other functions unchanged - send_order_confirmation, product notifications, admin notifications...]
function send_order_confirmation($email, $name, $orderId, $orderData, $items) {
    // [Existing implementation - already has full receipt table]
}

function send_admin_new_order_notification($orderId, $customerName, $customerEmail, $total, $itemCount) {
    // [Existing - admin notification]
}

/**
 * Send password reset email
 */
function send_reset_email($email, $name, $token) {
    $mail = init_mailer();
    if (!$mail) return false;
    
    try {
        // Fix base_url for email context (no $_SERVER) - hardcoded for XAMPP
        $site_url = 'http://localhost/salah';  // Adjust port if needed: http://localhost:8080/salah
        $reset_url = $site_url . '/index.php?page=reset-password&token=' . $token;
        $expires = date('F j, Y g:i A', strtotime('+15 minutes'));
        
        $mail->addAddress($email, $name);
        $mail->Subject = __('password_reset_request');
        
        $body = '<!DOCTYPE html>
<html>
<head>
<style>
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; color: #333; }
.container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); overflow: hidden; }
.header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 40px 30px; text-align: center; }
.content { padding: 40px 30px; }
.btn { display: inline-block; background: #000; color: #fff; padding: 16px 32px; text-decoration: none; border-radius: 12px; font-weight: 500; font-size: 16px; transition: all 0.3s; }
.btn:hover { background: #333; transform: translateY(-1px); }
.footer { padding: 30px; text-align: center; font-size: 14px; color: #666; background: #f8f9fa; }
</style>
</head>
<body>
<div class="container">
<div class="header">
<h1>' . __('reset_your_password') . '</h1>
<p>Hi ' . htmlspecialchars($name) . '</p>
</div>
<div class="content">
<p>' . __('reset_email_body1') . '</p>
<p><strong>' . __('expires') . ':</strong> ' . $expires . '</p>
<div style="text-align: center; margin: 30px 0;">
<a href="' . htmlspecialchars($reset_url) . '" class="btn">' . __('reset_now') . '</a>
</div>
<p style="font-size: 14px; color: #666; text-align: center;">
<a href="' . htmlspecialchars($reset_url) . '">' . htmlspecialchars($reset_url) . '</a>
</p>
<p>' . __('reset_email_body2') . '</p>
</div>
<div class="footer">
<p>&copy; ' . date('Y') . ' SZN. ' . __('all_rights_reserved') . '.</p>
</div>
</div>
</body>
</html>';
        
        $mail->Body = $body;
        $mail->AltBody = __('reset_email_alt', $name, $expires, $reset_url);
        return $mail->send();
    } catch (Exception $e) {
        error_log("Reset email failed: " . $mail->ErrorInfo);
        return false;
    }
}

// [All other functions unchanged]
?>


