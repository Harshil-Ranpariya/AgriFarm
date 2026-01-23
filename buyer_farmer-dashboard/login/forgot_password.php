<?php
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../email/email_functions.php';

$message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  
  if (empty($email)) {
    $message = 'Please enter your email address';
  } else {
    // Check if user exists in farmer_users or buyer_users
    $user = null;
    $userType = null;
    
    // Check farmer_users
    $stmt = $conn->prepare("SELECT id, username, email FROM farmer_users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
      $user = $row;
      $userType = 'farmer';
    }
    $stmt->close();
    
    // If not found, check buyer_users
    if (!$user) {
      $stmt = $conn->prepare("SELECT id, username, email FROM buyer_users WHERE email = ? LIMIT 1");
      $stmt->bind_param("s", $email);
      $stmt->execute();
      $result = $stmt->get_result();
      if ($row = $result->fetch_assoc()) {
        $user = $row;
        $userType = 'buyer';
      }
      $stmt->close();
    }
    
    if ($user) {
      // Generate OTP
      $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
      $expires = (new DateTime('+10 minutes'))->format('Y-m-d H:i:s');
      
      // Create or update password_reset table
      $conn->query("CREATE TABLE IF NOT EXISTS password_reset (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(191) NOT NULL,
        otp VARCHAR(6) NOT NULL,
        otp_expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_otp (otp)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
      
      // Delete old entries for this email
      $del = $conn->prepare("DELETE FROM password_reset WHERE email = ?");
      $del->bind_param("s", $email);
      $del->execute();
      $del->close();
      
      // Insert new OTP
      $stmt = $conn->prepare("INSERT INTO password_reset (email, otp, otp_expires_at) VALUES (?, ?, ?)");
      $stmt->bind_param("sss", $email, $otp, $expires);
      
      if ($stmt->execute()) {
        // Send OTP email
        $emailResult = sendForgotPasswordOTPEmail($email, $user['username'], $otp);
        
        if ($emailResult['success']) {
          $success_message = 'OTP has been sent to your email address. Please check your inbox.';
          // Redirect to reset password page
          header('Location: reset_password.php?email=' . urlencode($email));
          exit();
        } else {
          $message = 'Failed to send email: ' . $emailResult['message'] . ' Your OTP is: ' . $otp;
          header('Location: reset_password.php?email=' . urlencode($email) . '&otp=' . $otp);
          exit();
        }
      } else {
        $message = 'Error generating OTP. Please try again.';
      }
      $stmt->close();
    } else {
      $message = 'Email address not found in our system.';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password - AgriFarm</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="Login.css">
</head>
<body>
  <div class="container" id="login-form">
    <div class="curved-shape"></div>
    <div class="form-box Login">
      <h2><i class="fas fa-key"></i> Forgot Password</h2>
      <p class="subtitle">Enter your email to receive OTP</p>

      <?php if (!empty($message)): ?>
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>
      <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
        </div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <div class="input-box">
          <input type="email" id="email" name="email" required />
          <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
        </div>

        <div class="input-box">
          <button class="btn" type="submit">
            <i class="fas fa-paper-plane"></i> Send OTP
          </button>
        </div>
        <br><br>

        <div class="regi-link">
          <p><a href="Login.html" style="color: white;"><i class="fas fa-arrow-left"></i> Back to Login</a></p>
        </div>
      </form>
    </div>
    <div class="info-content Login">
      <h2><i class="fas fa-shield-alt"></i> Reset Password</h2>
      <p class="info-text">We'll send you a verification code to reset your password securely.</p>
    </div>
  </div>
</body>
</html>
