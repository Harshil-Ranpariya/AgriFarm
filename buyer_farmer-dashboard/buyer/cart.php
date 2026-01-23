<?php
require_once __DIR__ . '/../../db.php';
if (!isset($_SESSION['buyer_id'])) { header('Location: Login.html'); exit(); }

$buyer_id = $_SESSION['buyer_id'];

// Handle remove from cart
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
  $stmt = $conn->prepare("DELETE FROM cart WHERE buyer_id = ? AND product_id = ?");
  $stmt->bind_param("ii", $buyer_id, $_GET['remove']);
  $stmt->execute();
  header('Location: cart.php');
  exit();
}

// Handle checkout - redirect to payment page
if (isset($_POST['checkout'])) {
  // Validate cart items before redirecting to payment
  $cartItems = $conn->query("
    SELECT c.product_id, c.quantity as cart_qty, p.quantity as available_qty, p.name, p.price
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.buyer_id = ".$buyer_id."
  ");
  
  $errors = [];
  $hasItems = false;
  while ($item = $cartItems->fetch_assoc()) {
    $hasItems = true;
    $maxAllowed = max(0.1, floor($item['available_qty'] * 0.4 * 10) / 10);
    if ($item['cart_qty'] > $item['available_qty']) {
      $errors[] = "Insufficient stock for " . $item['name'];
    } elseif ($item['cart_qty'] - $maxAllowed > 1e-6) {
      $errors[] = "You can purchase only up to 40% (".number_format($maxAllowed,1)." kg) of ".$item['name'].".";
    }
  }
  
  if (!empty($errors)) {
    $errorMsg = '&error=' . urlencode(implode(', ', $errors));
    header('Location: cart.php?error=insufficient_stock' . $errorMsg);
    exit();
  }
  
  if (!$hasItems) {
    header('Location: cart.php?error=empty_cart');
    exit();
  }
  
  // Redirect to payment page
  header('Location: payment.php');
  exit();
}

// Get cart items
$cartItems = $conn->query("
  SELECT c.id, c.product_id, c.quantity, p.name, p.price, p.image_path, p.quantity as available_qty
  FROM cart c
  JOIN products p ON c.product_id = p.id
  WHERE c.buyer_id = ".$buyer_id."
");

$total = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Cart</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    body {
      background: linear-gradient(135deg, #2e7d32 0%, #4CAF50 100%);
      min-height: 100vh;
    }
    .container {
      background: white;
      border-radius: 15px;
      box-shadow: 0 10px 40px rgba(0,0,0,0.1);
      padding: 30px;
      margin-top: 20px;
      margin-bottom: 20px;
    }
    .quantity-controls {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .qty-btn {
      width: 35px;
      height: 35px;
      border: 1px solid #ddd;
      background: white;
      border-radius: 5px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
    }
    .qty-btn:hover {
      background: #f0f0f0;
    }
    .qty-input {
      width: 100px;
      text-align: center;
      border: 1px solid #ddd;
      border-radius: 5px;
      padding: 5px;
    }
    .btn-success {
      background: #2e7d32;
      border-color: #2e7d32;
    }
    .btn-success:hover {
      background: #1b5e20;
      border-color: #1b5e20;
    }
  </style>
</head>
<body class="p-4">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2><i class="fas fa-shopping-cart"></i> My Cart</h2>
      <a href="buyer_home.php" class="btn btn-success">Continue Shopping</a>
    </div>

    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> Order placed successfully! Product quantities have been reduced automatically.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> <?php echo esc($_GET['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    
    <div class="row">
      <div class="col-md-8">
        <div class="card">
          <div class="card-body">
            <?php if ($cartItems && $cartItems->num_rows > 0): ?>
              <table class="table">
                <thead>
                  <tr>
                    <th>Product</th>
                    <th>Price (per kg)</th>
                    <th>Quantity (kg)</th>
                    <th>Total</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($item = $cartItems->fetch_assoc()): 
                    $pricePerKg = (float)$item['price'];
                    $itemTotal = $pricePerKg * $item['quantity'];
                    $total += $itemTotal;
                    $maxAllowed = max(0.1, floor($item['available_qty'] * 0.4 * 10) / 10);
                  ?>
                    <tr>
                      <td>
                        <div class="d-flex align-items-center">
                          <?php if (!empty($item['image_path'])): ?>
                            <img src="<?php echo esc($item['image_path']); ?>" style="width:50px;height:50px;object-fit:cover;border-radius:6px;margin-right:10px;">
                          <?php endif; ?>
                          <span><?php echo esc($item['name']); ?></span>
                        </div>
                      </td>
                      <td>₹ <?php echo number_format($pricePerKg, 2); ?>/kg</td>
                      <td>
                        <div class="d-flex align-items-center">
                          <strong><?php echo number_format($item['quantity'], 1); ?></strong>
                        </div>
                      </td>
                      <td>₹ <?php echo number_format($itemTotal, 2); ?></td>
                      <td>
                        <a href="?remove=<?php echo $item['product_id']; ?>" class="btn btn-sm btn-danger">
                          <i class="fas fa-trash"></i>
                        </a>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            <?php else: ?>
              <div class="text-center py-5">
                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                <p class="text-muted">Your cart is empty</p>
                <a href="buyer_home.php" class="btn btn-success">Start Shopping</a>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card">
          <div class="card-body">
            <h5>Cart Summary</h5>
            <hr>
            <div class="d-flex justify-content-between mb-3">
              <strong>Total:</strong>
              <strong class="text-success">₹ <?php echo number_format($total, 2); ?></strong>
            </div>
            <?php if ($cartItems && $cartItems->num_rows > 0): ?>
              <form method="POST">
                <button type="submit" name="checkout" class="btn btn-success w-100">
                  <i class="fas fa-shopping-bag"></i> Checkout & Purchase
                </button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

