<?php
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/email_functions.php';

$email = $_GET['email'] ?? $_POST['email'] ?? '';
$message = '';
$success_message = '';

if (isset($_POST['resend_otp'])) {
  $email = trim($_POST['email'] ?? '');
  
  if (!empty($email)) {
    $stmt = $conn->prepare("SELECT id, username, email FROM temp_users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if ($row) {
      $new_otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
      $expires = (new DateTime('+10 minutes'))->format('Y-m-d H:i:s');

      $update_stmt = $conn->prepare("UPDATE temp_users SET otp = ?, otp_expires_at = ? WHERE email = ?");
      $update_stmt->bind_param("sss", $new_otp, $expires, $email);
      
      if ($update_stmt->execute()) {
        $emailResult = sendOTPEmail($email, $row['username'], $new_otp);
        
        if ($emailResult['success']) {
          $success_message = 'New OTP sent to your email address!';
        } else {
          $message = 'Failed to send email: ' . $emailResult['message'] . ' Your new OTP is: ' . $new_otp;
        }
      } else {
        $message = 'Failed to update OTP. Please try again.';
      }
      $update_stmt->close();
    } else {
      $message = 'No pending registration found for this email.';
    }
  } else {
    $message = 'Email address is required.';
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['resend_otp'])) {
  $otp = trim($_POST['otp'] ?? '');
  $email = trim($_POST['email'] ?? '');

  $stmt = $conn->prepare("SELECT id, username, email, mobile_number, password, role, otp, otp_expires_at FROM temp_users WHERE email = ? LIMIT 1");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  $stmt->close();

  if ($row) {
    if ($row['otp'] !== $otp) {
      $message = 'Invalid OTP';
    } else if (new DateTime() > new DateTime($row['otp_expires_at'])) {
      $message = 'OTP expired';
    } else {
      // Ensure mobile_number column exists
      if ($row['role'] === 'farmer') {
        $colRes = $conn->query("SHOW COLUMNS FROM farmer_users LIKE 'mobile_number'");
        if ($colRes && $colRes->num_rows === 0) {
          $conn->query("ALTER TABLE farmer_users ADD COLUMN mobile_number VARCHAR(20) NULL AFTER email");
        }
        $ins = $conn->prepare("INSERT INTO farmer_users (username, email, mobile_number, password) VALUES (?, ?, ?, ?)");
      } else {
        $colRes = $conn->query("SHOW COLUMNS FROM buyer_users LIKE 'mobile_number'");
        if ($colRes && $colRes->num_rows === 0) {
          $conn->query("ALTER TABLE buyer_users ADD COLUMN mobile_number VARCHAR(20) NULL AFTER email");
        }
        $ins = $conn->prepare("INSERT INTO buyer_users (username, email, mobile_number, password) VALUES (?, ?, ?, ?)");
      }
      $ins->bind_param("ssss", $row['username'], $row['email'], $row['mobile_number'], $row['password']);
      if ($ins->execute()) {
        $ins->close();
        $del = $conn->prepare("DELETE FROM temp_users WHERE id = ?");
        $del->bind_param("i", $row['id']);
        $del->execute();
        $del->close();
        
        // Simple alert on success then redirect to home page
        echo '<script>(function(){ if (alert("OTP verification successful. Welcome to AgriFarm!")) {} window.location.href = "../../home_pages/index.html"; })();</script>';
        exit();
      } else {
        $message = 'Server error while finalizing account';
      }
    }
  } else {
    $message = 'No pending registration found for this email';
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify OTP</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="admin_styles.css">
</head>
<body class="p-4">
  <div class="container" style="max-width:480px;">
    <h3 class="mb-3">Verify OTP</h3>
    <?php if (!empty($message)): ?>
      <div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div>
      <script>(function(){
        var msg = <?php echo json_encode($message); ?>;
        alert(msg === 'OTP expired' ? 'OTP expired. Please resend a new OTP.' : 'Invalid OTP. Please try again.');
      })();</script>
    <?php endif; ?>
    <?php if (!empty($success_message)): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    <form method="post">
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input class="form-control" type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" readonly>
      </div>
      <div class="mb-3">
        <label class="form-label">Enter 6-digit OTP</label>
        <input class="form-control" type="text" name="otp" maxlength="6" required>
      </div>
      <button class="btn btn-success w-100 mb-2" type="submit">Verify OTP</button>
    </form>
    <br><br>
    
    <form method="post" class="mt-3">
      <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
      <button class="btn btn-outline-primary w-100" type="submit" name="resend_otp">
        <i class="fas fa-redo"></i> Resend OTP
      </button>
    </form>
    
    <div class="text-center mt-3">
      <small class="text-muted">
        Didn't receive the email? Check your spam folder or 
        <a href="#" onclick="document.querySelector('form[method=post]:last-of-type').submit(); return false;">resend OTP</a>
      </small>
    </div>
  </div>
</body>
</html>


