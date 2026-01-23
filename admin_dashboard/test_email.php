<?php
require_once __DIR__ . '/../buyer_farmer-dashboard/email/email_functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_email = $_POST['test_email'] ?? '';
    
    if (!empty($test_email) && filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        // Test email configuration first
        $config_test = testEmailConfiguration();
        
        if ($config_test['success']) {
            $test_otp = '123456';
            $result = sendOTPEmail($test_email, 'Test User', $test_otp);
            
            if ($result['success']) {
                $message = 'Test email sent successfully! Check your inbox.';
                $message_type = 'success';
            } else {
                $message = 'Failed to send test email: ' . $result['message'];
                $message_type = 'danger';
            }
        } else {
            $message = 'Email configuration error: ' . $config_test['message'];
            $message_type = 'danger';
        }
    } else {
        $message = 'Please enter a valid email address.';
        $message_type = 'danger';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Email Configuration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-envelope"></i> Test Email Configuration</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($message)): ?>
                            <div class="alert alert-<?php echo $message_type; ?>">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle"></i> Instructions:</h6>
                            <ol>
                                <li>Make sure you've configured your SMTP settings in <code>email_config.php</code></li>
                                <li>For Gmail, use an App Password instead of your regular password</li>
                                <li>Enter a test email address below to verify the configuration</li>
                            </ol>
                        </div> -->
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="test_email" class="form-label">Test Email Address</label>
                                <input type="email" class="form-control" id="test_email" name="test_email" 
                                       placeholder="Enter your email address" required>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Send Test Email
                            </button>
                        </form>
                        
                        <hr>
                        
                        <!-- <h6>Current Configuration:</h6>
                        <div class="small text-muted">
                            <strong>SMTP Host:</strong> <?php echo SMTP_HOST; ?><br>
                            <strong>SMTP Port:</strong> <?php echo SMTP_PORT; ?><br>
                            <strong>SMTP Encryption:</strong> <?php echo SMTP_ENCRYPTION; ?><br>
                            <strong>From Email:</strong> <?php echo SMTP_FROM_EMAIL; ?><br>
                            <strong>From Name:</strong> <?php echo SMTP_FROM_NAME; ?>
                        </div> -->
                        
                        <div class="mt-3">
                            <a href="admin_dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
