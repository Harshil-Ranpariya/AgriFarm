<?php
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../email/email_functions.php';

if (!function_exists('is_strong_password')) {
  /**
   * Validate password strength (min 8 chars, upper, lower, digit, special).
   */
  function is_strong_password(string $password): bool {
    return (bool)preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password);
  }
}

$email = $_GET['email'] ?? $_POST['email'] ?? '';
$otp = $_GET['otp'] ?? $_POST['otp'] ?? '';
$message = '';
$showResetForm = false;

// Verify OTP first
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
  $otp = trim($_POST['otp'] ?? '');
  $email = trim($_POST['email'] ?? '');
  
  $stmt = $conn->prepare("SELECT id, email, otp, otp_expires_at FROM password_reset WHERE email = ? AND otp = ? LIMIT 1");
  $stmt->bind_param("ss", $email, $otp);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  $stmt->close();
  
  if ($row) {
    if (new DateTime() > new DateTime($row['otp_expires_at'])) {
      $message = 'OTP has expired. Please request a new one.';
    } else {
      $showResetForm = true;
      $_SESSION['reset_email'] = $email;
      $_SESSION['reset_otp'] = $otp;
    }
  } else {
    $message = 'Invalid OTP. Please try again.';
  }
}

// Reset password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
  $email = $_SESSION['reset_email'] ?? '';
  $otp = $_SESSION['reset_otp'] ?? '';
  $new_password = $_POST['new_password'] ?? '';
  $confirm_password = $_POST['confirm_password'] ?? '';
  
  if (empty($email) || empty($otp)) {
    $message = 'Session expired. Please start over.';
    unset($_SESSION['reset_email'], $_SESSION['reset_otp']);
  } elseif ($new_password !== $confirm_password) {
    $message = 'Passwords do not match.';
  } elseif (!is_strong_password($new_password)) {
    $message = 'Password must be at least 8 characters long and include uppercase, lowercase, numeric, and special characters.';
  } else {
    // Verify OTP again
    $stmt = $conn->prepare("SELECT id FROM password_reset WHERE email = ? AND otp = ? LIMIT 1");
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $result = $stmt->get_result();
    $resetRow = $result->fetch_assoc();
    $stmt->close();
    
    if ($resetRow) {
      $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
      
      // Update password in farmer_users or buyer_users
      $updated = false;
      
      // Try farmer_users
      $stmt = $conn->prepare("UPDATE farmer_users SET password = ? WHERE email = ?");
      $stmt->bind_param("ss", $hashed_password, $email);
      if ($stmt->execute() && $stmt->affected_rows > 0) {
        $updated = true;
      }
      $stmt->close();
      
      // If not updated, try buyer_users
      if (!$updated) {
        $stmt = $conn->prepare("UPDATE buyer_users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $email);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
          $updated = true;
        }
        $stmt->close();
      }
      
      if ($updated) {
        // Delete OTP record
        $del = $conn->prepare("DELETE FROM password_reset WHERE email = ?");
        $del->bind_param("s", $email);
        $del->execute();
        $del->close();
        
        unset($_SESSION['reset_email'], $_SESSION['reset_otp']);
        echo "<script>alert('Password reset successful! Please login with your new password.'); window.location.href='Login.html';</script>";
        exit();
      } else {
        $message = 'Error updating password. Please try again.';
      }
    } else {
      $message = 'Invalid or expired OTP. Please start over.';
      unset($_SESSION['reset_email'], $_SESSION['reset_otp']);
    }
  }
}

// If OTP is provided in URL, verify it
if (!empty($email) && !empty($otp) && !$showResetForm && $_SERVER['REQUEST_METHOD'] !== 'POST') {
  $stmt = $conn->prepare("SELECT id FROM password_reset WHERE email = ? AND otp = ? LIMIT 1");
  $stmt->bind_param("ss", $email, $otp);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  $stmt->close();
  
  if ($row) {
    $showResetForm = true;
    $_SESSION['reset_email'] = $email;
    $_SESSION['reset_otp'] = $otp;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password - AgriFarm</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="Login.css">
  <style>
    .password-box { position: relative; }
    .toggle-password {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: #ff0000;
      font-weight: 600;
      cursor: pointer;
      padding: 4px 6px;
    }
    /* Keep label floated when input has text */
    .input-box input.has-content ~ label {
      top: -20px;
      font-size: 12px;
      color: #4CAF50;
      background-color: rgba(34, 139, 34, 0.95);
      padding: 5px 15px;
      border-radius: 20px;
      transform: translateY(0);
    }
  </style>
</head>
<body>
  <div class="container" id="login-form">
    <div class="curved-shape"></div>
    <div class="form-box Login">
      <?php if ($showResetForm): ?>
        <h2><i class="fas fa-lock"></i> Reset Password</h2>
        <p class="subtitle">Enter your new password</p>
      <?php else: ?>
        <h2><i class="fas fa-shield-alt"></i> Verify OTP</h2>
        <p class="subtitle">Enter the OTP sent to your email</p>
      <?php endif; ?>

      <?php if (!empty($message)): ?>
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>

      <?php if ($showResetForm): ?>
        <form method="POST" novalidate>
          <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
          <input type="hidden" name="otp" value="<?php echo htmlspecialchars($otp); ?>">
          
          <div class="input-box password-box">
            <input type="password" id="new_password" name="new_password" required minlength="8" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}" title="At least 8 characters with uppercase, lowercase, number, and special character." />
            <label for="new_password"><i class="fas fa-lock"></i> New Password</label>
            <button type="button" class="toggle-password" data-target="new_password" aria-label="Show password">
              <i class="fas fa-eye"></i>
            </button>
          </div>

          <div class="input-box password-box">
            <input type="password" id="confirm_password" name="confirm_password" required minlength="8" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}" title="Must match the new password and meet the strength rules." />
            <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password</label>
            <button type="button" class="toggle-password" data-target="confirm_password" aria-label="Show password">
              <i class="fas fa-eye"></i>
            </button>
          </div>

          <div class="input-box">
            <button class="btn" type="submit" name="reset_password">
              <i class="fas fa-check"></i> Reset Password
            </button>
          </div>
          <br><br>

          <div class="regi-link">
            <p><a href="Login.html" style="color: #ffffff;"><i class="fas fa-arrow-left"></i> Back to Login</a></p>
          </div>
        </form>
      <?php else: ?>
        <form method="POST" novalidate>
          <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
          
          <div class="input-box">
            <input type="text" id="otp" name="otp" maxlength="6" required />
            <label for="otp"><i class="fas fa-shield-alt"></i> Enter 6-digit OTP</label>
          </div>

          <div class="input-box">
            <button class="btn" type="submit" name="verify_otp">
              <i class="fas fa-check"></i> Verify OTP
            </button>
          </div>

          <br><br>
          <div class="regi-link">
            <p style="color: #ffffff;"><a href="forgot_password.php" style="color: #ffffff;"><i class="fas fa-redo"></i> Resend OTP</a> | <a href="Login.html" style="color: #ffffff;"><i class="fas fa-arrow-left" style="color: #ffffff;"></i> Back to Login</a></p>
          </div>
        </form>
      <?php endif; ?>
    </div>
    <div class="info-content Login">
      <?php if ($showResetForm): ?>
        <h2><i class="fas fa-key"></i> Create New Password</h2>
        <p class="info-text">Make sure your password is strong and secure.</p>
      <?php else: ?>
        <h2><i class="fas fa-envelope"></i> Check Your Email</h2>
        <p class="info-text">We've sent you a 6-digit verification code.</p>
      <?php endif; ?>
    </div>
  </div>
  <script>
    // Toggle password visibility for reset password fields
    document.addEventListener('DOMContentLoaded', function () {
      const inputs = [document.getElementById('new_password'), document.getElementById('confirm_password')];

      function updateLabelState(inputEl) {
        if (!inputEl) return;
        inputEl.classList.toggle('has-content', inputEl.value.length > 0);
      }

      inputs.forEach((el) => {
        if (!el) return;
        updateLabelState(el);
        el.addEventListener('input', () => updateLabelState(el));
        el.addEventListener('blur', () => updateLabelState(el));
      });

      document.querySelectorAll('.toggle-password').forEach(function (btn) {
        const target = document.getElementById(btn.dataset.target);
        const icon = btn.querySelector('i');
        if (!target || !icon) return;

        btn.addEventListener('click', function () {
          const isVisible = target.type === 'text';
          target.type = isVisible ? 'password' : 'text';
          target.focus();

          icon.classList.toggle('fa-eye', !isVisible);
          icon.classList.toggle('fa-eye-slash', isVisible);

          btn.style.color = isVisible ? '#ff0000' : '#28a745';

          btn.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
        });
      });
    });
  </script>
</body>
</html>
