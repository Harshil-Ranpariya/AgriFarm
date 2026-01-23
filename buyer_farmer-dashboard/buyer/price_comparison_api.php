<?php
require_once __DIR__ . '/../../db.php';
header('Content-Type: application/json');

$productName = trim($_GET['product'] ?? '');
if ($productName === '') {
  echo json_encode(['error' => 'Product name is required']);
  exit;
}

$stmt = $conn->prepare("
  SELECT p.id, p.name, p.price, p.quantity, f.username AS farmer_name
  FROM products p
  JOIN farmer_users f ON p.farmer_id = f.id
  WHERE p.status = 'approved' AND p.name LIKE CONCAT('%', ?, '%')
  ORDER BY p.price ASC, p.id ASC
");

if (!$stmt) {
  echo json_encode(['error' => 'Unable to prepare comparison query.']);
  exit;
}

$stmt->bind_param('s', $productName);
$stmt->execute();
$result = $stmt->get_result();

$productList = [];
$prices = [];

while ($row = $result->fetch_assoc()) {
  $pricePerKg = (float)$row['price'];
  $quantity = (float)$row['quantity'];

  $prices[] = $pricePerKg;
  $productList[] = [
    'id' => (int)$row['id'],
    'name' => $row['name'],
    'farmer_name' => $row['farmer_name'],
    'price_value' => $pricePerKg,
    'price_per_kg' => number_format($pricePerKg, 2),
    'quantity_value' => $quantity,
    'quantity' => number_format($quantity, 2),
    'is_lowest' => false,
    'is_highest' => false,
    'price_diff' => '0.00',
    'price_diff_percent' => '0.00',
    'in_stock' => $quantity > 0
  ];
}
$stmt->close();

if (empty($productList)) {
  echo json_encode(['error' => 'No approved products found for the provided search.']);
  exit;
}

$lowestPrice = min($prices);
$highestPrice = max($prices);
$marketAvg = array_sum($prices) / count($prices);

foreach ($productList as &$product) {
  if (abs($product['price_value'] - $lowestPrice) < 0.001) {
    $product['is_lowest'] = true;
  }
  if (abs($product['price_value'] - $highestPrice) < 0.001) {
    $product['is_highest'] = true;
  }
  $diff = $product['price_value'] - $lowestPrice;
  $product['price_diff'] = number_format($diff, 2);
  $product['price_diff_percent'] = $lowestPrice > 0
    ? number_format(($diff / $lowestPrice) * 100, 2)
    : '0.00';
}
unset($product);

echo json_encode([
  'products' => $productList,
  'market_avg' => number_format($marketAvg, 2),
  'lowest' => number_format($lowestPrice, 2),
  'highest' => number_format($highestPrice, 2),
  'total_offers' => count($productList),
  'search_term' => $productName
]);
?>
