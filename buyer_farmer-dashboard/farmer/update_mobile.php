<?php
require_once __DIR__ . '/../../db.php';
if (!isset($_SESSION['farmer_id'])) { header('Location: Login.html'); exit(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $mobile = trim($_POST['mobile_number'] ?? '');
  $fid = $_SESSION['farmer_id'];
  
  // Check if mobile_number column exists
  $colRes = $conn->query("SHOW COLUMNS FROM farmer_users LIKE 'mobile_number'");
  if ($colRes && $colRes->num_rows === 0) {
    $conn->query("ALTER TABLE farmer_users ADD COLUMN mobile_number VARCHAR(20) NULL AFTER email");
  }
  
  $stmt = $conn->prepare("UPDATE farmer_users SET mobile_number = ? WHERE id = ?");
  $stmt->bind_param("si", $mobile, $fid);
  if ($stmt->execute()) {
    header('Location: farmer_home.php?msg=mobile_updated');
  } else {
    header('Location: farmer_home.php?msg=error');
  }
  exit();
}
?>
