<?php
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../email/email_functions.php';

if (!isset($_SESSION['buyer_id'])) { 
  header('Location: Login.html'); 
  exit(); 
}

$buyer_id = $_SESSION['buyer_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_return'])) {
  $order_id = (int)($_POST['order_id'] ?? 0);
  $return_quantity = (float)($_POST['return_quantity'] ?? 0);
  $return_reason = trim($_POST['return_reason'] ?? '');
  
  if ($order_id <= 0 || $return_quantity <= 0 || empty($return_reason)) {
    $error = "Please fill all fields correctly.";
  } else {
    $orderCheck = $conn->prepare("SELECT o.*, p.name as product_name FROM orders o JOIN products p ON o.product_id = p.id WHERE o.id = ? AND o.buyer_id = ? AND o.payment_status = 'completed'");
    $orderCheck->bind_param("ii", $order_id, $buyer_id);
    $orderCheck->execute();
    $orderResult = $orderCheck->get_result();
    
    if ($orderResult->num_rows === 0) {
      $error = "Invalid order or order not found.";
    } else {
      $order = $orderResult->fetch_assoc();
      
      if ($return_quantity > $order['quantity']) {
        $error = "Return quantity cannot exceed purchased quantity.";
      } else {
        $existingReturn = $conn->prepare("SELECT SUM(return_quantity) as total_returned FROM product_returns WHERE order_id = ? AND return_status IN ('pending', 'approved', 'completed')");
        $existingReturn->bind_param("i", $order_id);
        $existingReturn->execute();
        $existingResult = $existingReturn->get_result();
        $totalReturned = $existingResult->fetch_assoc()['total_returned'] ?? 0;
        
        if (($totalReturned + $return_quantity) > $order['quantity']) {
          $error = "Total return quantity cannot exceed purchased quantity.";
        } else {
          $stmt = $conn->prepare("INSERT INTO product_returns (order_id, buyer_id, product_id, farmer_id, return_quantity, return_reason) VALUES (?, ?, ?, ?, ?, ?)");
          $stmt->bind_param("iiiids", $order_id, $buyer_id, $order['product_id'], $order['farmer_id'], $return_quantity, $return_reason);
          
          if ($stmt->execute()) {
            $return_id = $conn->insert_id;
            
            $farmerInfo = $conn->query("SELECT username, email FROM farmer_users WHERE id = ".(int)$order['farmer_id'])->fetch_assoc();
            $buyerInfo = $conn->query("SELECT username FROM buyer_users WHERE id = ".(int)$buyer_id)->fetch_assoc();
            
            if ($farmerInfo && $buyerInfo) {
              sendReturnRequestNotificationEmail(
                $farmerInfo['email'],
                $farmerInfo['username'],
                $buyerInfo['username'],
                $order['product_name'],
                $return_quantity,
                $return_reason,
                $order_id,
                $return_id
              );
            }
            
            $success = "Return request submitted successfully. The farmer has been notified and will review your request.";
          } else {
            $error = "Error submitting return request. Please try again.";
          }
          $stmt->close();
        }
        $existingReturn->close();
      }
    }
    $orderCheck->close();
  }
}

$buyerInfoRes = $conn->query("SELECT username, email FROM buyer_users WHERE id=".(int)$buyer_id." LIMIT 1");
$buyerInfo = $buyerInfoRes ? $buyerInfoRes->fetch_assoc() : null;
$buyerHeaderName = $buyerInfo['username'] ?? 'Buyer';

$cartCountRes = $conn->query("SELECT COUNT(*) AS cnt FROM cart WHERE buyer_id=".(int)$buyer_id);
$cartCount = $cartCountRes ? $cartCountRes->fetch_assoc()['cnt'] : 0;

$ordersQuery = $conn->query("
  SELECT o.id, o.order_date, o.quantity as order_qty, o.price_per_kg, o.total_amount, o.payment_transaction_id,
         p.name as product_name, p.image_path,
         f.username as farmer_name,
         COALESCE(SUM(CASE WHEN pr.return_status IN ('pending', 'approved', 'completed') THEN pr.return_quantity ELSE 0 END), 0) as returned_qty
  FROM orders o
  JOIN products p ON o.product_id = p.id
  JOIN farmer_users f ON o.farmer_id = f.id
  LEFT JOIN product_returns pr ON o.id = pr.order_id
  WHERE o.buyer_id = ".(int)$buyer_id." AND o.payment_status = 'completed'
  GROUP BY o.id
  HAVING returned_qty < o.quantity
  ORDER BY o.order_date DESC
");

$returnsQuery = $conn->query("
  SELECT pr.*, o.order_date, o.payment_transaction_id,
         p.name as product_name, p.image_path,
         f.username as farmer_name
  FROM product_returns pr
  JOIN orders o ON pr.order_id = o.id
  JOIN products p ON pr.product_id = p.id
  JOIN farmer_users f ON pr.farmer_id = f.id
  WHERE pr.buyer_id = ".(int)$buyer_id."
  ORDER BY pr.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Return Product - Buyer Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="buyer_home.css" />
  <link rel="stylesheet" href="../../responsive_menu.css" />
</head>
<body>
  <div class="header-bar">
    <div class="header-left">
      <div class="basket-icon">
        <i class="fas fa-shopping-basket"></i>
      </div>
      <div class="header-title">
        <h1>Return Product</h1>
        <p>Request a return for your purchased products</p>
      </div>
    </div>
    <div class="header-right">
      <a href="buyer_home.php" class="btn-bidding" style="background: #4CAF50;">
        <i class="fas fa-home"></i> Home
      </a>
      <a href="buyer_bidding.php" class="btn-bidding">
        <i class="fas fa-gavel"></i> Bidding
      </a>
      <a href="buyer_product_rating.php" class="btn-bidding" style="background: #ff9800;">
        <i class="fas fa-star"></i> Product Rating
      </a>
      <a href="buyer_website_rating.php" class="btn-bidding" style="background: #8e24aa;">
        <i class="fas fa-thumbs-up"></i> Website Rating
      </a>
      <a href="buyer_return_product.php" class="btn-bidding active" style="background: #e91e63;">
        <i class="fas fa-undo"></i> Return Product
      </a>
      <a href="cart.php" class="btn-cart">
        <i class="fas fa-shopping-cart"></i> Cart
        <?php if ($cartCount > 0): ?>
          <span class="cart-badge-count"><?php echo $cartCount; ?></span>
        <?php endif; ?>
      </a>
      <div class="dropdown profile-dropdown">
        <button type="button" class="profile-chip dropdown-toggle" id="buyerProfileDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="<?php echo esc($buyerInfo['email'] ?? ''); ?>">
          <span class="profile-avatar"><i class="fas fa-user"></i></span>
          <span class="name"><?php echo esc($buyerHeaderName); ?></span>
          <i class="fas fa-caret-down" style="font-size:0.9rem;margin-left:6px;color:rgba(255,255,255,0.9);"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="buyerProfileDropdown">
          <li>
            <button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#buyerProfileModal"><i class="fas fa-id-card me-2"></i> View Profile</button>
          </li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
        </ul>
      </div>
    </div>
  </div>

  <div class="main-content" style="padding: 30px;">
    <?php if (isset($success)): ?>
      <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> <?php echo esc($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
      <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle"></i> <?php echo esc($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <div class="row">
      <!-- Return Request Form -->
      <div class="col-md-6">
        <div class="card shadow-sm mb-4">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-undo"></i> Request Product Return</h5>
          </div>
          <div class="card-body">
            <?php if ($ordersQuery && $ordersQuery->num_rows > 0): ?>
              <form method="POST">
                <div class="mb-3">
                  <label class="form-label">Select Order</label>
                  <select class="form-select" name="order_id" id="order_id" required onchange="updateReturnForm()">
                    <option value="">-- Select Order --</option>
                    <?php 
                    $ordersQuery->data_seek(0);
                    while ($order = $ordersQuery->fetch_assoc()): 
                      $availableQty = $order['order_qty'] - $order['returned_qty'];
                    ?>
                      <option value="<?php echo $order['id']; ?>" 
                              data-qty="<?php echo $order['order_qty']; ?>"
                              data-returned="<?php echo $order['returned_qty']; ?>"
                              data-product="<?php echo esc($order['product_name']); ?>">
                        Order #<?php echo $order['id']; ?> - <?php echo esc($order['product_name']); ?> 
                        (Purchased: <?php echo $order['order_qty']; ?> kg, Available: <?php echo number_format($availableQty, 2); ?> kg)
                      </option>
                    <?php endwhile; ?>
                  </select>
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Return Quantity (kg)</label>
                  <input type="number" step="0.1" min="0.1" class="form-control" name="return_quantity" id="return_quantity" required>
                  <small class="text-muted" id="qty_hint">Select an order first</small>
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Return Reason</label>
                  <textarea class="form-control" name="return_reason" rows="4" required placeholder="Please explain why you want to return this product..."></textarea>
                </div>
                
                <button type="submit" name="submit_return" class="btn btn-primary w-100">
                  <i class="fas fa-paper-plane"></i> Submit Return Request
                </button>
              </form>
            <?php else: ?>
              <p class="text-muted">No orders available for return.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Return History -->
      <div class="col-md-6">
        <div class="card shadow-sm">
          <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-history"></i> Return History</h5>
          </div>
          <div class="card-body">
            <?php if ($returnsQuery && $returnsQuery->num_rows > 0): ?>
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead>
                    <tr>
                      <th>Order</th>
                      <th>Product</th>
                      <th>Qty</th>
                      <th>Status</th>
                      <th>Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php while ($return = $returnsQuery->fetch_assoc()): ?>
                      <tr>
                        <td>#<?php echo $return['order_id']; ?></td>
                        <td><?php echo esc($return['product_name']); ?></td>
                        <td><?php echo $return['return_quantity']; ?> kg</td>
                        <td>
                          <span class="badge bg-<?php 
                            echo $return['return_status'] === 'approved' ? 'success' : 
                                ($return['return_status'] === 'rejected' ? 'danger' : 
                                ($return['return_status'] === 'completed' ? 'info' : 'warning')); 
                          ?>">
                            <?php echo ucfirst($return['return_status']); ?>
                          </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($return['created_at'])); ?></td>
                      </tr>
                    <?php endwhile; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <p class="text-muted">No return requests yet.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!--Buyer Profile-->
  <div class="modal fade" id="buyerProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-user-circle text-success"></i> Your Profile</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label text-muted">Username</label>
            <div class="form-control"><?php echo esc($buyerHeaderName); ?></div>
          </div>
          <div class="mb-3">
            <label class="form-label text-muted">Email</label>
            <div class="form-control"><?php echo esc($buyerInfo['email'] ?? ''); ?></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../../responsive_menu.js"></script>
  <script>
    // Initialize Bootstrap dropdown properly
    document.addEventListener('DOMContentLoaded', function() {
      const dropdownButton = document.getElementById('buyerProfileDropdown');
      if (dropdownButton) {
        new bootstrap.Dropdown(dropdownButton);
      }
    });
    
    // Ensure dropdown menu is always clickable and visible
    setTimeout(function() {
      const dropdownButton = document.getElementById('buyerProfileDropdown');
      const dropdownMenu = document.querySelector('.dropdown-menu');
      
      if (dropdownButton) {
        dropdownButton.style.position = 'relative';
        dropdownButton.style.zIndex = '10002';
        dropdownButton.style.pointerEvents = 'auto';
      }
      
      if (dropdownMenu) {
        dropdownMenu.style.pointerEvents = 'auto';
        dropdownMenu.style.visibility = 'visible';
        dropdownMenu.style.zIndex = '10001';
        dropdownMenu.style.position = 'absolute';
      }
    }, 100);
    
    // Prevent responsive menu overlay from blocking dropdown
    document.addEventListener('click', function(e) {
      const dropdownButton = document.getElementById('buyerProfileDropdown');
      const dropdownMenu = document.querySelector('.dropdown-menu');
      const overlay = document.getElementById('menuOverlay');
      
      // If clicking dropdown button or items, ensure overlay doesn't block
      if (dropdownButton && (e.target === dropdownButton || dropdownButton.contains(e.target))) {
        if (overlay) overlay.style.zIndex = '9998';
        if (dropdownMenu) dropdownMenu.style.zIndex = '10001';
      }
      
      // Ensure dropdown items are clickable
      if (e.target.classList.contains('dropdown-item')) {
        e.stopPropagation();
      }
    });
  </script>
  <script>
    function updateReturnForm() {
      const orderSelect = document.getElementById('order_id');
      const qtyInput = document.getElementById('return_quantity');
      const qtyHint = document.getElementById('qty_hint');
      
      if (orderSelect.value) {
        const option = orderSelect.options[orderSelect.selectedIndex];
        const totalQty = parseFloat(option.dataset.qty);
        const returnedQty = parseFloat(option.dataset.returned);
        const availableQty = totalQty - returnedQty;
        
        qtyInput.max = availableQty;
        qtyInput.value = '';
        qtyHint.textContent = `Available for return: ${availableQty.toFixed(1)} kg (Purchased: ${totalQty} kg, Already returned: ${returnedQty} kg)`;
      } else {
        qtyInput.value = '';
        qtyHint.textContent = 'Select an order first';
      }
    }
  </script>

   <style>
html, body {
  height: 100%;
  margin: 0;
  padding: 0;
}

#footer{
  margin-top: 21%;
}

footer.footer {
  background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
  padding: 20px 0;
  text-align: center;
  width: 100%;
}
footer.footer .content {
  max-width: 1200px;
  margin: 0 auto;
}
footer.footer .bottom {
  font-size: 14px;
}
footer.footer .bottom p {
  margin: 0;
  color: #fff;
}
</style>

<div id="footer">
  <footer class="footer">
    <div class="content">
      <div class="bottom">
        <p>&copy; 2025 AgriFarm. All rights reserved.</p>
      </div>
    </div>
</div>
  </footer>
</body>
</html>

