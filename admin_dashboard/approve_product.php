<?php
require_once '../db.php';
if (!isset($_SESSION['admin_id'])) { header('Location: admin_login.php'); exit(); }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = $_GET['action'] ?? '';
if ($id <= 0 || !in_array($action, ['approve','reject'], true)) {
  header('Location: admin_dashboard.php'); exit();
}

$status = $action === 'approve' ? 'approved' : 'rejected';
$stmt = $conn->prepare("UPDATE products SET status=? WHERE id=?");
$stmt->bind_param("si", $status, $id);
$stmt->execute();
$stmt->close();

header('Location: admin_dashboard.php');
exit();
?>