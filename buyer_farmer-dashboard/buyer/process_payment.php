<?php
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../email/email_functions.php';

if (!isset($_SESSION['buyer_id'])) { 
  header('Location: Login.html'); 
  exit(); 
}

$buyer_id = $_SESSION['buyer_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: cart.php');
  exit();
}

$payment_method = $_POST['payment_method'] ?? '';

// Validate payment method
if (!in_array($payment_method, ['card', 'upi', 'cod'])) {
  header('Location: payment.php?error=invalid_method');
  exit();
}

// Validate payment details based on method
if ($payment_method === 'card') {
  $card_number = str_replace(' ', '', $_POST['card_number'] ?? '');
  $card_name = trim($_POST['card_name'] ?? '');
  $card_expiry = trim($_POST['card_expiry'] ?? '');
  $card_cvv = trim($_POST['card_cvv'] ?? '');
  
  if (strlen($card_number) < 16 || empty($card_name) || empty($card_expiry) || empty($card_cvv)) {
    header('Location: payment.php?error=invalid_card_details');
    exit();
  }
} elseif ($payment_method === 'upi') {
  $upi_id = trim($_POST['upi_id'] ?? '');
  if (empty($upi_id) || !strpos($upi_id, '@')) {
    header('Location: payment.php?error=invalid_upi');
    exit();
  }
}

// Ensure orders table exists with payment_status column
$conn->query("CREATE TABLE IF NOT EXISTS orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  buyer_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  farmer_id INT UNSIGNED NOT NULL,
  quantity DECIMAL(10,2) NOT NULL,
  price_per_kg DECIMAL(10,2) NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  remaining_quantity DECIMAL(10,2) NOT NULL,
  payment_method VARCHAR(50) DEFAULT NULL,
  payment_status ENUM('pending','completed','failed') DEFAULT 'pending',
  payment_transaction_id VARCHAR(100) DEFAULT NULL,
  order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (buyer_id) REFERENCES buyer_users(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (farmer_id) REFERENCES farmer_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Add payment columns if they don't exist
addColumnIfNotExists($conn, 'orders', 'payment_method', 'VARCHAR(50) DEFAULT NULL AFTER remaining_quantity');
addColumnIfNotExists($conn, 'orders', 'payment_status', "ENUM('pending','completed','failed') DEFAULT 'pending' AFTER payment_method");
addColumnIfNotExists($conn, 'orders', 'payment_transaction_id', 'VARCHAR(100) DEFAULT NULL AFTER payment_status');

// Get all cart items with farmer info
$cartItems = $conn->query("
  SELECT c.product_id, c.quantity as cart_qty, p.quantity as available_qty, p.name, p.price, p.farmer_id,
         f.username as farmer_name, f.email as farmer_email, b.username as buyer_name
  FROM cart c
  JOIN products p ON c.product_id = p.id
  JOIN farmer_users f ON p.farmer_id = f.id
  JOIN buyer_users b ON c.buyer_id = b.id
  WHERE c.buyer_id = ".$buyer_id."
");

$errors = [];
$items = [];
while ($item = $cartItems->fetch_assoc()) {
  if ($item['cart_qty'] > $item['available_qty']) {
    $errors[] = "Insufficient stock for " . $item['name'];
  } else {
    $items[] = $item;
  }
}

if (!empty($errors)) {
  $errorMsg = '&error=' . urlencode(implode(', ', $errors));
  header('Location: cart.php?error=insufficient_stock' . $errorMsg);
  exit();
}

if (empty($items)) {
  header('Location: cart.php?error=empty_cart');
  exit();
}

// Generate mock transaction ID
$transaction_id = 'TXN' . time() . rand(1000, 9999);

// Process payment (mock - always succeeds)
$payment_status = 'completed';

// Create order and reduce quantity
$conn->begin_transaction();
$totalAmount = 0;
try {
  foreach ($items as $item) {
    $pricePerKg = (float)$item['price']; // Price as entered by farmer (per kg)
    $itemTotal = $pricePerKg * $item['cart_qty'];
    $totalAmount += $itemTotal;
    $remainingQty = $item['available_qty'] - $item['cart_qty'];
    
    // Create order record with payment info
    $insertOrder = $conn->prepare("INSERT INTO orders (buyer_id, product_id, farmer_id, quantity, price_per_kg, total_amount, remaining_quantity, payment_method, payment_status, payment_transaction_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $insertOrder->bind_param("iiiddddsss", $buyer_id, $item['product_id'], $item['farmer_id'], $item['cart_qty'], $pricePerKg, $itemTotal, $remainingQty, $payment_method, $payment_status, $transaction_id);
    $insertOrder->execute();
    
    // Reduce product quantity automatically
    $updateQty = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
    $updateQty->bind_param("di", $item['cart_qty'], $item['product_id']);
    $updateQty->execute();
    
    // Send email notification to farmer
    sendPurchaseNotificationEmail(
      $item['farmer_email'],
      $item['farmer_name'],
      $item['buyer_name'],
      $item['name'],
      $item['cart_qty'],
      $remainingQty,
      $totalAmount,
      $pricePerKg
    );
    
    // Check if quantity reached 0 and send zero quantity notification
    if ($remainingQty <= 0) {
      sendZeroQuantityNotificationEmail(
        $item['farmer_email'],
        $item['farmer_name'],
        $item['name'],
        $item['product_id']
      );
    }
  }
  
  // Clear cart
  $conn->query("DELETE FROM cart WHERE buyer_id = ".$buyer_id);
  
  $conn->commit();
  
  // Get order details for email
  $orderDetails = $conn->query("
    SELECT o.*, p.name as product_name, f.username as farmer_name
    FROM orders o
    JOIN products p ON o.product_id = p.id
    JOIN farmer_users f ON o.farmer_id = f.id
    WHERE o.buyer_id = ".$buyer_id." AND o.payment_transaction_id = '".$conn->real_escape_string($transaction_id)."'
  ");
  
  $orderItems = [];
  while ($order = $orderDetails->fetch_assoc()) {
    $orderItems[] = $order;
  }
  
  // Get buyer info
  $buyerInfo = $conn->query("SELECT username, email FROM buyer_users WHERE id = ".(int)$buyer_id)->fetch_assoc();
  
  // Send order confirmation email to buyer
  if ($buyerInfo && !empty($orderItems)) {
    sendOrderConfirmationEmail(
      $buyerInfo['email'],
      $buyerInfo['username'],
      $transaction_id,
      $orderItems,
      $totalAmount,
      $payment_method
    );
  }
  
  // Redirect to success page with transaction ID
  header('Location: payment_success.php?txn=' . urlencode($transaction_id) . '&method=' . urlencode($payment_method));
  exit();
  
} catch (Exception $e) {
  $conn->rollback();
  header('Location: payment.php?error=payment_failed');
  exit();
}
?>

