<?php
require_once __DIR__ . '/../../db.php';
header('Content-Type: application/json');

if (!isset($_GET['product_id']) || !is_numeric($_GET['product_id'])) {
  echo json_encode(['error' => 'Product ID is required']);
  exit;
}

$product_id = (int)$_GET['product_id'];

// Ensure ratings table exists
$conn->query("CREATE TABLE IF NOT EXISTS product_ratings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  buyer_id INT UNSIGNED NOT NULL,
  rating TINYINT UNSIGNED NOT NULL CHECK (rating >= 1 AND rating <= 5),
  review TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (buyer_id) REFERENCES buyer_users(id) ON DELETE CASCADE,
  UNIQUE KEY unique_rating (product_id, buyer_id),
  INDEX idx_product_rating (product_id, rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Get recent reviews
$reviews = $conn->query("
  SELECT r.rating, r.review, r.created_at, b.username as buyer_name
  FROM product_ratings r
  JOIN buyer_users b ON r.buyer_id = b.id
  WHERE r.product_id = $product_id
  ORDER BY r.created_at DESC
  LIMIT 10
");

$reviewList = [];
while ($row = $reviews->fetch_assoc()) {
  $reviewList[] = [
    'buyer_name' => $row['buyer_name'],
    'rating' => $row['rating'],
    'review' => $row['review'],
    'date' => date('M d, Y', strtotime($row['created_at']))
  ];
}

echo json_encode(['reviews' => $reviewList]);
?>

