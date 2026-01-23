<?php
require_once __DIR__ . '/../../db.php';
header('Content-Type: application/json');

$count = 0;
if (isset($_SESSION['buyer_id'])) {
  $buyer_id = (int)$_SESSION['buyer_id'];
  $res = $conn->query("SELECT COUNT(*) AS cnt FROM cart WHERE buyer_id={$buyer_id}");
  if ($res) {
    $row = $res->fetch_assoc();
    $count = (int)($row['cnt'] ?? 0);
  }
}

echo json_encode(['count' => $count]);
exit();

?>
