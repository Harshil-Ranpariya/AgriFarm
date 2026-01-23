<?php
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../email/email_functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $username = trim($_POST['username'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $mobile_number = trim($_POST['mobile_number'] ?? '');
  $password = $_POST['password'] ?? '';
  $confirm_password = $_POST['confirm_password'] ?? '';
  $role = $_POST['role'] ?? '';

  // Validation
  if (!preg_match("/^[a-zA-Z_ ]+$/", $username)) {
    echo "<script>alert('Username can only contain letters, spaces, and underscore'); window.history.back();</script>";
    exit();
  }

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "<script>alert('Invalid email format'); window.history.back();</script>";
    exit();
  }

  // Validate mobile number (required and format check)
  if (empty($mobile_number)) {
    echo "<script>alert('Mobile number is required. Please enter your mobile number.'); window.history.back();</script>";
    exit();
  }
  
  // Remove spaces and + for validation
  $mobile_clean = preg_replace('/[\s+]/', '', $mobile_number);
  if (!preg_match('/^[0-9]{10,15}$/', $mobile_clean)) {
    echo "<script>alert('Invalid mobile number format. Please enter 10-15 digits (e.g., +91 9876543210 or 9876543210)'); window.history.back();</script>";
    exit();
  }

  if ($password !== $confirm_password) {
    echo "<script>alert('Passwords do not match'); window.history.back();</script>";
    exit();
  }

  if (empty($role) || !in_array($role, ['farmer','buyer'], true)) {
    echo "<script>alert('Please select a valid role'); window.history.back();</script>";
    exit();
  }

  // Check if email already exists
  $exists = false;
  $tables = ['users','temp_users','farmer_users','buyer_users'];
  foreach ($tables as $t) {
    $stmt = $conn->prepare("SELECT id FROM $t WHERE email = ? LIMIT 1");
    if ($stmt) {
      $stmt->bind_param("s", $email);
      $stmt->execute();
      $stmt->store_result();
      if ($stmt->num_rows > 0) { $exists = true; }
      $stmt->close();
      if ($exists) break;
    }
  }
  if ($exists) {
    echo "<script>alert('Email already registered or pending verification'); window.location='../email/otp_verify.php?email=" . urlencode($email) . "';</script>";
    exit();
  }

  // Check if mobile number already exists
  $mobile_exists = false;
  $mobile_tables = ['farmer_users','buyer_users'];
  foreach ($mobile_tables as $t) {
    // Ensure mobile_number column exists
    $colRes = $conn->query("SHOW COLUMNS FROM $t LIKE 'mobile_number'");
    if ($colRes && $colRes->num_rows === 0) {
      $conn->query("ALTER TABLE $t ADD COLUMN mobile_number VARCHAR(20) NULL AFTER email");
    }
    
    $stmt = $conn->prepare("SELECT id FROM $t WHERE mobile_number = ? LIMIT 1");
    if ($stmt) {
      $stmt->bind_param("s", $mobile_number);
      $stmt->execute();
      $stmt->store_result();
      if ($stmt->num_rows > 0) { $mobile_exists = true; }
      $stmt->close();
      if ($mobile_exists) break;
    }
  }
  if ($mobile_exists) {
    echo "<script>alert('Mobile number already registered. Please use a different mobile number.'); window.history.back();</script>";
    exit();
  }

  // Ensure temp_users has mobile_number column
  $colRes = $conn->query("SHOW COLUMNS FROM temp_users LIKE 'mobile_number'");
  if ($colRes && $colRes->num_rows === 0) {
    $conn->query("ALTER TABLE temp_users ADD COLUMN mobile_number VARCHAR(20) NULL AFTER email");
  }

  $hashed_password = password_hash($password, PASSWORD_DEFAULT);
  $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
  $expires = (new DateTime('+10 minutes'))->format('Y-m-d H:i:s');

  $stmt = $conn->prepare("INSERT INTO temp_users (username, email, mobile_number, password, role, otp, otp_expires_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
  if (!$stmt) {
    echo "<script>alert('Server error. Please try again later.'); window.history.back();</script>";
    exit();
  }
  $stmt->bind_param("sssssss", $username, $email, $mobile_number, $hashed_password, $role, $otp, $expires);

  if ($stmt->execute()) {
    $emailResult = sendOTPEmail($email, $username, $otp);
    
    if ($emailResult['success']) {
      echo "<script>alert('Registration successful! Please check your email for the verification code.'); window.location='../email/otp_verify.php?email=" . urlencode($email) . "';</script>";
    } else {
      echo "<script>alert('Registration successful! Email sending failed: " . addslashes($emailResult['message']) . "\\n\\nYour OTP is: $otp'); window.location='../email/otp_verify.php?email=" . urlencode($email) . "';</script>";
    }
    $stmt->close();
    exit();
  } else {
    echo "<script>alert('Error: " . addslashes($stmt->error) . "'); window.history.back();</script>";
  }
  $stmt->close();
}

$conn->close();
?>
