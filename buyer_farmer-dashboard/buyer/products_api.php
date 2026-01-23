<?php
require_once __DIR__ . '/../../db.php';
header('Content-Type: application/json');

$rows = [];
$res = $conn->query("SELECT name, description, price, image_path FROM products WHERE status='approved' ORDER BY id DESC LIMIT 30");
if ($res) {
  while ($r = $res->fetch_assoc()) { $rows[] = $r; }
}
echo json_encode($rows);
exit;
?>


