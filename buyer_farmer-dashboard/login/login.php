<?php
require_once __DIR__ . '/../../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Support new email field, fallback to old 'username' if form not updated yet
  $email = trim($_POST['email'] ?? ($_POST['username'] ?? ''));
  $password = $_POST['password'] ?? '';

  if (empty($email) || empty($password)) {
    echo "<script>alert('Please enter both email and password'); window.history.back();</script>";
    exit();
  }

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "<script>alert('Please enter a valid email address'); window.history.back();</script>";
    exit();
  }

  

  // Farmer login strictly by email (case-insensitive)
  $stmt = $conn->prepare("SELECT id, username, email, password FROM farmer_users WHERE LOWER(email) = LOWER(?) LIMIT 1");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($row = $res->fetch_assoc()) {
    if (password_verify($password, $row['password'])) {
      $_SESSION['farmer_id'] = $row['id'];
      header('Location: ../farmer/farmer_home.php');
      exit();
    }
  }
  $stmt->close();

  // Buyer login strictly by email (case-insensitive)
  $stmt = $conn->prepare("SELECT id, username, email, password FROM buyer_users WHERE LOWER(email) = LOWER(?) LIMIT 1");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($row = $res->fetch_assoc()) {
    if (password_verify($password, $row['password'])) {
      $_SESSION['buyer_id'] = $row['id'];
      header('Location: ../buyer/buyer_home.php');
      exit();
    }
  }
  $stmt->close();

  // If we reach here, login failed
  echo "<script>alert('Invalid email or password. Please check your credentials and try again.'); window.history.back();</script>";
  exit();
}

header('Location: Login.html');
?>


