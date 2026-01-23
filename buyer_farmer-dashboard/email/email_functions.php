<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendOTPEmail($email, $username, $otp) {
    $config = getEmailConfig();
    
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->Hostname = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = $config['encryption'];
        $mail->Port = $config['port'];
        
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($email, $username);
        
        $mail->isHTML(true);
        $mail->Subject = OTP_SUBJECT;
        $mail->Body = generateOTPEmailTemplate($username, $otp);
        $mail->AltBody = generateOTPEmailText($username, $otp);
        
        $mail->send();
        return ['success' => true, 'message' => 'OTP sent successfully'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Failed to send OTP: ' . $mail->ErrorInfo];
    }
}

function generateOTPEmailTemplate($username, $otp) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Email Verification</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #4CAF50, #45a049); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .otp-box { background: white; border: 2px dashed #4CAF50; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px; }
            .otp-code { font-size: 32px; font-weight: bold; color: #4CAF50; letter-spacing: 5px; margin: 10px 0; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 14px; }
            .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🌾 AgriFarm</h1>
                <p>Email Verification</p>
            </div>
            <div class='content'>
                <h2>Hello " . htmlspecialchars($username) . "!</h2>
                <p>Welcome to AgriFarm! To complete your registration, please verify your email address using the OTP code below:</p>
                
                <div class='otp-box'>
                    <p><strong>Your Verification Code:</strong></p>
                    <div class='otp-code'>" . $otp . "</div>
                    <p><small>This code will expire in " . OTP_EXPIRY_MINUTES . " minutes</small></p>
                </div>
                
                <div class='warning'>
                    <strong>⚠️ Important:</strong>
                    <ul>
                        <li>This code is valid for " . OTP_EXPIRY_MINUTES . " minutes only</li>
                        <li>Do not share this code with anyone</li>
                        <li>If you didn't request this code, please ignore this email</li>
                    </ul>
                </div>
                
                <p>If you have any questions, feel free to contact our support team.</p>
                <a href='mailto:agrifarm.helpdesk@gmail.com'>agrifarm.helpdesk@gmail.com</a>
                <p>Thank You! 🌱</p>
            </div>
            <div class='footer'>
                <p>© " . date('Y') . " AgriFarm. All rights reserved.</p>
                <p>This is an automated message, please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>";
}


function generateOTPEmailText($username, $otp) {
    return "
AgriFarm - Email Verification

Hello " . $username . "!

Welcome to AgriFarm! To complete your registration, please verify your email address using the OTP code below:

Your Verification Code: " . $otp . "

This code will expire in " . OTP_EXPIRY_MINUTES . " minutes.

Important:
- This code is valid for " . OTP_EXPIRY_MINUTES . " minutes only
- Do not share this code with anyone
- If you didn't request this code, please ignore this email

If you have any questions, feel free to contact our support team.

Happy farming!

© " . date('Y') . " AgriFarm. All rights reserved.
This is an automated message, please do not reply to this email.
";
}

// Forgot Password Functions
function sendForgotPasswordOTPEmail($email, $username, $otp) {
    $config = getEmailConfig();
    
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->Hostname = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = $config['encryption'];
        $mail->Port = $config['port'];
        
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($email, $username);
        
        $mail->isHTML(true);
        $mail->Subject = 'AgriFarm - Password Reset OTP';
        $mail->Body = generateForgotPasswordEmailTemplate($username, $otp);
        $mail->AltBody = "AgriFarm - Password Reset\n\nHello $username,\n\nYour password reset OTP is: $otp\n\nThis code will expire in 10 minutes.";
        
        $mail->send();
        return ['success' => true, 'message' => 'OTP sent successfully'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Failed to send OTP: ' . $mail->ErrorInfo];
    }
}

function generateForgotPasswordEmailTemplate($username, $otp) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #2e7d32 0%, #4CAF50 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .otp-box { background: white; border: 2px solid #4CAF50; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0; }
            .otp-code { font-size: 32px; font-weight: bold; color: #2e7d32; letter-spacing: 5px; }
            .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🌾 AgriFarm</h1>
                <p>Password Reset Request</p>
            </div>
            <div class='content'>
                <h2>Hello " . htmlspecialchars($username) . "!</h2>
                <p>You have requested to reset your password. Use the OTP code below to proceed:</p>
                
                <div class='otp-box'>
                    <p><strong>Your Password Reset Code:</strong></p>
                    <div class='otp-code'>" . $otp . "</div>
                    <p><small>This code will expire in 10 minutes</small></p>
                </div>
                
                <div class='warning'>
                    <strong>⚠️ Important:</strong>
                    <ul>
                        <li>This code is valid for 10 minutes only</li>
                        <li>Do not share this code with anyone</li>
                        <li>If you didn't request this, please ignore this email</li>
                    </ul>
                </div>
                
                <p>Thank You! 🌱</p>
            </div>
        </div>
    </body>
    </html>";
}

function testEmailConfiguration() {
    $config = getEmailConfig();
    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = $config['encryption'];
        $mail->Port = $config['port'];
        
        // Test connection
        $mail->smtpConnect();
        $mail->smtpClose();
        
        return ['success' => true, 'message' => 'Email configuration is working correctly'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Email configuration error: ' . $e->getMessage()];
    }
}

// Purchase notification to farmer
function sendPurchaseNotificationEmail($farmerEmail, $farmerName, $buyerName, $productName, $quantityPurchased, $remainingQuantity, $totalAmount, $pricePerKg) {
    $config = getEmailConfig();
    
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->Hostname = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = $config['encryption'];
        $mail->Port = $config['port'];
        
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($farmerEmail, $farmerName);
        
        $mail->isHTML(true);
        $mail->Subject = 'AgriFarm - Product Purchase Notification';
        $mail->Body = generatePurchaseNotificationTemplate($farmerName, $buyerName, $productName, $quantityPurchased, $remainingQuantity, $totalAmount, $pricePerKg);
        $mail->AltBody = "Hello $farmerName,\n\nYour product '$productName' has been purchased by $buyerName.\n\nQuantity Purchased: $quantityPurchased kg\nRemaining Quantity: $remainingQuantity kg\nTotal Amount: ₹$totalAmount\nPrice per kg: ₹$pricePerKg";
        
        $mail->send();
        return ['success' => true, 'message' => 'Purchase notification sent successfully'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Failed to send notification: ' . $mail->ErrorInfo];
    }
}

function generatePurchaseNotificationTemplate($farmerName, $buyerName, $productName, $quantityPurchased, $remainingQuantity, $totalAmount, $pricePerKg) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Product Purchase Notification</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #4CAF50, #45a049); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .info-box { background: white; border: 2px solid #4CAF50; border-radius: 8px; padding: 20px; margin: 20px 0; }
            .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
            .info-row:last-child { border-bottom: none; }
            .info-label { font-weight: bold; color: #666; }
            .info-value { color: #2e7d32; font-weight: bold; }
            .success-badge { background: #4CAF50; color: white; padding: 10px 20px; border-radius: 5px; display: inline-block; margin: 20px 0; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🌾 AgriFarm</h1>
                <p>Product Purchase Notification</p>
            </div>
            <div class='content'>
                <h2>Hello " . htmlspecialchars($farmerName) . "!</h2>
                <div class='success-badge'>
                    ✓ Your product has been purchased!
                </div>
                
                <p>Great news! Your product has been purchased on AgriFarm platform. Here are the details:</p>
                
                <div class='info-box'>
                    <div class='info-row'>
                        <span class='info-label'>Product Name:</span>
                        <span class='info-value'>" . htmlspecialchars($productName) . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Buyer:</span>
                        <span class='info-value'>" . htmlspecialchars($buyerName) . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Quantity Purchased:</span>
                        <span class='info-value'>" . number_format($quantityPurchased, 2) . " kg</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Remaining Quantity:</span>
                        <span class='info-value'>" . number_format($remainingQuantity, 2) . " kg</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Price per kg:</span>
                        <span class='info-value'>₹ " . number_format($pricePerKg, 2) . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Total Amount:</span>
                        <span class='info-value' style='color: #2e7d32; font-size: 18px;'>₹ " . number_format($totalAmount, 2) . "</span>
                    </div>
                </div>
                
                <p>Please prepare the product for delivery. If you have any questions, feel free to contact our support team.</p>
                <p>Thank you for being a part of AgriFarm! 🌱</p>
            </div>
            <div class='footer'>
                <p>© " . date('Y') . " AgriFarm. All rights reserved.</p>
                <p>This is an automated message, please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>";
}

// Zero quantity notification to farmer
function sendZeroQuantityNotificationEmail($farmerEmail, $farmerName, $productName, $productId) {
    $config = getEmailConfig();
    
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->Hostname = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = $config['encryption'];
        $mail->Port = $config['port'];
        
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($farmerEmail, $farmerName);
        
        $mail->isHTML(true);
        $mail->Subject = 'AgriFarm - Product Quantity Alert: Out of Stock';
        $mail->Body = generateZeroQuantityEmailTemplate($farmerName, $productName, $productId);
        $mail->AltBody = "Hello $farmerName,\n\nYour product '$productName' quantity has reached 0. Please delete this product from your dashboard.\n\nProduct ID: $productId\n\nThank you!";
        
        $mail->send();
        return ['success' => true, 'message' => 'Zero quantity notification sent successfully'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Failed to send notification: ' . $mail->ErrorInfo];
    }
}

function generateZeroQuantityEmailTemplate($farmerName, $productName, $productId) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Product Quantity Alert</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #ff6b6b, #ee5a6f); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .alert-box { background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px; padding: 20px; margin: 20px 0; }
            .info-box { background: white; border: 2px solid #ff6b6b; border-radius: 8px; padding: 20px; margin: 20px 0; }
            .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
            .info-row:last-child { border-bottom: none; }
            .info-label { font-weight: bold; color: #666; }
            .info-value { color: #d32f2f; font-weight: bold; }
            .action-box { background: #e3f2fd; border-left: 4px solid #2196F3; padding: 15px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>⚠️ AgriFarm</h1>
                <p>Product Quantity Alert</p>
            </div>
            <div class='content'>
                <h2>Hello " . htmlspecialchars($farmerName) . "!</h2>
                
                <div class='alert-box'>
                    <h3 style='margin-top: 0; color: #856404;'>⚠️ Important Notice</h3>
                    <p style='margin-bottom: 0;'><strong>Your product quantity has reached 0!</strong></p>
                </div>
                
                <p>We wanted to inform you that one of your products has run out of stock:</p>
                
                <div class='info-box'>
                    <div class='info-row'>
                        <span class='info-label'>Product Name:</span>
                        <span class='info-value'>" . htmlspecialchars($productName) . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Product ID:</span>
                        <span class='info-value'>#" . htmlspecialchars($productId) . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Remaining Quantity:</span>
                        <span class='info-value' style='color: #d32f2f; font-size: 18px;'>0 kg</span>
                    </div>
                </div>
                
                <div class='action-box'>
                    <h4 style='margin-top: 0; color: #1976D2;'>📋 Action Required</h4>
                    <p><strong>Please delete this product from your dashboard</strong> as it is no longer available for purchase.</p>
                    <p>You can manage your products by logging into your farmer dashboard.</p>
                </div>
                
                <p>If you have more stock available, you can add a new product listing with the updated quantity.</p>
                <p>Thank you for being a part of AgriFarm! 🌱</p>
            </div>
            <div class='footer'>
                <p>© " . date('Y') . " AgriFarm. All rights reserved.</p>
                <p>This is an automated message, please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>";
}

// Order confirmation email to buyer
function sendOrderConfirmationEmail($buyerEmail, $buyerName, $transactionId, $orderItems, $totalAmount, $paymentMethod) {
    $config = getEmailConfig();
    
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->Hostname = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = $config['encryption'];
        $mail->Port = $config['port'];
        
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($buyerEmail, $buyerName);
        
        $mail->isHTML(true);
        $mail->Subject = 'AgriFarm - Order Confirmation';
        $mail->Body = generateOrderConfirmationTemplate($buyerName, $transactionId, $orderItems, $totalAmount, $paymentMethod);
        $mail->AltBody = "Hello $buyerName,\n\nYour order has been confirmed!\n\nTransaction ID: $transactionId\nTotal Amount: ₹$totalAmount\n\nThank you for your purchase!";
        
        $mail->send();
        return ['success' => true, 'message' => 'Order confirmation email sent successfully'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Failed to send confirmation email: ' . $mail->ErrorInfo];
    }
}

function generateOrderConfirmationTemplate($buyerName, $transactionId, $orderItems, $totalAmount, $paymentMethod) {
    $itemsHtml = '';
    foreach ($orderItems as $item) {
        $itemsHtml .= "
        <div style='background: white; border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; margin: 10px 0;'>
            <div style='display: flex; justify-content: space-between; margin-bottom: 10px;'>
                <strong style='color: #2e7d32; font-size: 16px;'>" . htmlspecialchars($item['product_name']) . "</strong>
                <span style='color: #4CAF50; font-weight: bold;'>₹" . number_format($item['total_amount'], 2) . "</span>
            </div>
            <div style='color: #666; font-size: 14px;'>
                <div>Farmer: " . htmlspecialchars($item['farmer_name']) . "</div>
                <div>Quantity: " . number_format($item['quantity'], 2) . " kg × ₹" . number_format($item['price_per_kg'], 2) . "/kg</div>
            </div>
        </div>";
    }
    
    $paymentMethodText = ucfirst($paymentMethod);
    if ($paymentMethod === 'card') $paymentMethodText = 'Credit/Debit Card';
    elseif ($paymentMethod === 'upi') $paymentMethodText = 'UPI';
    elseif ($paymentMethod === 'cod') $paymentMethodText = 'Cash on Delivery';
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Order Confirmation</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #4CAF50, #45a049); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .success-badge { background: #4CAF50; color: white; padding: 15px 25px; border-radius: 8px; display: inline-block; margin: 20px 0; font-size: 18px; font-weight: bold; }
            .info-box { background: white; border: 2px solid #4CAF50; border-radius: 8px; padding: 20px; margin: 20px 0; }
            .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
            .info-row:last-child { border-bottom: none; }
            .info-label { font-weight: bold; color: #666; }
            .info-value { color: #2e7d32; font-weight: bold; }
            .transaction-id { background: #e3f2fd; color: #1976d2; padding: 10px 15px; border-radius: 8px; font-family: monospace; font-weight: bold; text-align: center; margin: 20px 0; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🌾 AgriFarm</h1>
                <p>Order Confirmation</p>
            </div>
            <div class='content'>
                <h2>Hello " . htmlspecialchars($buyerName) . "!</h2>
                <div class='success-badge'>
                    ✓ Your Order is Confirmed!
                </div>
                
                <p>Thank you for your purchase! Your order has been successfully placed and confirmed. Here are your order details:</p>
                
                <div class='transaction-id'>
                    Transaction ID: " . htmlspecialchars($transactionId) . "
                </div>
                
                <div class='info-box'>
                    <div class='info-row'>
                        <span class='info-label'>Payment Method:</span>
                        <span class='info-value'>" . htmlspecialchars($paymentMethodText) . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Payment Status:</span>
                        <span class='info-value' style='color: #4CAF50;'>✓ Completed</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Total Amount:</span>
                        <span class='info-value' style='color: #2e7d32; font-size: 20px;'>₹ " . number_format($totalAmount, 2) . "</span>
                    </div>
                </div>
                
                <h3 style='color: #2e7d32; margin-top: 30px;'>Ordered Products:</h3>
                " . $itemsHtml . "
                
                <p style='margin-top: 30px;'><strong>Your products will be prepared and delivered to you soon.</strong></p>
                <p>If you have any questions about your order, feel free to contact our support team.</p><br>
                <p>agrifarm.helpdesk@gmail.com</p>
                <p>Thank you for shopping with AgriFarm! 🌱</p>
            </div>
            <div class='footer'>
                <p>© " . date('Y') . " AgriFarm. All rights reserved.</p>
                <p>This is an automated message, please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>";
}
?>
