<?php
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/weather_config.php';
if (!isset($_SESSION['farmer_id'])) { 
  header('Location: Login.html'); 
  exit(); 
}

$farmer_id = $_SESSION['farmer_id'];
$farmerInfo = $conn->query("SELECT * FROM farmer_users WHERE id=".(int)$farmer_id)->fetch_assoc();

// Handle return status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
  $return_id = (int)($_POST['return_id'] ?? 0);
  $new_status = trim($_POST['new_status'] ?? '');
  
  if ($return_id > 0 && in_array($new_status, ['approved', 'rejected'])) {
    // Verify return belongs to this farmer
    $checkReturn = $conn->prepare("SELECT id FROM product_returns WHERE id = ? AND farmer_id = ?");
    $checkReturn->bind_param("ii", $return_id, $farmer_id);
    $checkReturn->execute();
    $checkResult = $checkReturn->get_result();
    
    if ($checkResult->num_rows > 0) {
      $stmt = $conn->prepare("UPDATE product_returns SET return_status = ? WHERE id = ? AND farmer_id = ?");
      $stmt->bind_param("sii", $new_status, $return_id, $farmer_id);
      
      if ($stmt->execute()) {
        $success = "Return status updated successfully.";
        
        // If approved, restore product quantity
        if ($new_status === 'approved') {
          $returnInfo = $conn->query("SELECT product_id, return_quantity FROM product_returns WHERE id = $return_id")->fetch_assoc();
          $conn->query("UPDATE products SET quantity = quantity + {$returnInfo['return_quantity']} WHERE id = {$returnInfo['product_id']}");
        }
      } else {
        $error = "Error updating return status.";
      }
      $stmt->close();
    } else {
      $error = "Invalid return request.";
    }
    $checkReturn->close();
  }
}

// Get return requests for this farmer
$filter = $_GET['filter'] ?? 'all';
$statusFilter = $filter !== 'all' ? "AND pr.return_status = '$filter'" : '';

$returnsQuery = $conn->query("
  SELECT pr.*, 
         o.order_date, o.payment_transaction_id, o.quantity as order_qty, o.price_per_kg, o.total_amount,
         p.name as product_name, p.image_path,
         b.username as buyer_name, b.email as buyer_email
  FROM product_returns pr
  JOIN orders o ON pr.order_id = o.id
  JOIN products p ON pr.product_id = p.id
  JOIN buyer_users b ON pr.buyer_id = b.id
  WHERE pr.farmer_id = ".(int)$farmer_id." $statusFilter
  ORDER BY pr.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Return Products - Farmer Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="farmer_home.css" />
  <link rel="stylesheet" href="../../responsive_menu.css" />
</head>
<body>
  <!-- Header -->
  <div class="dashboard-header">
    <div class="header-left">
      <i class="fas fa-seedling"></i>
      <div class="header-title">
        <h2>Return Products</h2>
        <small>Manage product return requests</small>
      </div>
    </div>
    <div class="header-right">
      <!-- <a href="farmer_home.php" class="btn-bidding" style="background: #4CAF50;"><i class="fas fa-home"></i> Home</a> -->
      <a href="farmer_add_product.php" class="btn-bidding" style="background: #4CAF50;"><i class="fas fa-plus-circle"></i> Add Product</a>
      <a href="farmer_product_ratings.php" class="btn-bidding" style="background: #ff9800;"><i class="fas fa-star"></i> Product Rating</a>
      <a href="farmer_feedback.php" class="btn-bidding" style="background: #8e24aa;"><i class="fas fa-thumbs-up"></i> Website Rating</a>
      <a href="farmer_weather.php" class="btn-bidding" style="background: #2196F3;"><i class="fas fa-cloud-sun"></i> Weather</a>
      <a href="farmer_bidding.php" class="btn-bidding"><i class="fas fa-gavel"></i> Bidding</a>
      <a href="farmer_return_products.php" class="btn-bidding active" style="background: #e91e63;"><i class="fas fa-undo"></i> Return Products</a>
      <div class="dropdown">
        <button type="button" class="btn btn-light dropdown-toggle" style="background:#ffffff;color:#2e7d32;border:none;border-radius:999px;font-weight:600;display:flex;align-items:center;gap:8px;" id="farmerProfileDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="<?php echo esc($farmerInfo['email'] ?? ''); ?>">
          <i class="fas fa-user-circle"></i>
          <span><?php echo esc($farmerInfo['username'] ?? 'Farmer'); ?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="farmerProfileDropdown">
          <li>
            <button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#farmerProfileModal">
              <i class="fas fa-id-card me-2"></i> View Profile
            </button>
          </li>
          <li><hr class="dropdown-divider"></li>
          <li>
            <a class="dropdown-item text-danger" href="logout.php">
              <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
          </li>
        </ul>
      </div>
    </div>
  </div>

  <div class="main-container">
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

    <!-- Returns Table -->
    <div class="section-card">
      <div class="section-header">
        <i class="fas fa-list"></i> Return Requests
      </div>
      <div class="section-body">
        <?php if ($returnsQuery && $returnsQuery->num_rows > 0): ?>
          <div class="table-responsive">
            <table class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Order</th>
                  <th>Product</th>
                  <th>Buyer</th>
                  <th>Quantity</th>
                  <th>Reason</th>
                  <th>Status</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($return = $returnsQuery->fetch_assoc()): ?>
                  <tr>
                    <td><strong>#<?php echo $return['id']; ?></strong></td>
                    <td>
                      Order #<?php echo $return['order_id']; ?><br>
                      <small class="text-muted"><?php echo date('M d, Y', strtotime($return['order_date'])); ?></small>
                    </td>
                    <td>
                      <div class="d-flex align-items-center">
                        <?php if (!empty($return['image_path'])): ?>
                          <img src="<?php echo esc($return['image_path']); ?>" alt="" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px; margin-right: 10px;">
                        <?php endif; ?>
                        <div>
                          <strong><?php echo esc($return['product_name']); ?></strong><br>
                          <small class="text-muted">₹<?php echo number_format($return['price_per_kg'], 2); ?>/kg</small>
                        </div>
                      </div>
                    </td>
                    <td>
                      <strong><?php echo esc($return['buyer_name']); ?></strong><br>
                      <small class="text-muted"><?php echo esc($return['buyer_email']); ?></small>
                    </td>
                    <td>
                      <strong><?php echo number_format($return['return_quantity'], 2); ?> kg</strong><br>
                      <small class="text-muted">of <?php echo number_format($return['order_qty'], 2); ?> kg</small>
                    </td>
                    <td>
                      <small><?php echo esc($return['return_reason']); ?></small>
                    </td>
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
                    <td>
                      <?php if ($return['return_status'] === 'pending'): ?>
                        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#updateModal<?php echo $return['id']; ?>">
                          <i class="fas fa-edit"></i> Review
                        </button>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>
                  </tr>

                  <!-- Update Modal -->
                  <div class="modal fade" id="updateModal<?php echo $return['id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title">Review Return Request</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST">
                          <div class="modal-body">
                            <input type="hidden" name="return_id" value="<?php echo $return['id']; ?>">
                            <div class="mb-3">
                              <label class="form-label"><strong>Product:</strong> <?php echo esc($return['product_name']); ?></label>
                            </div>
                            <div class="mb-3">
                              <label class="form-label"><strong>Buyer:</strong> <?php echo esc($return['buyer_name']); ?></label>
                            </div>
                            <div class="mb-3">
                              <label class="form-label"><strong>Return Quantity:</strong> <?php echo number_format($return['return_quantity'], 2); ?> kg</label>
                            </div>
                            <div class="mb-3">
                              <label class="form-label"><strong>Reason:</strong></label>
                              <p class="form-control-plaintext"><?php echo esc($return['return_reason']); ?></p>
                            </div>
                            <div class="mb-3">
                              <label class="form-label">Action</label>
                              <select class="form-select" name="new_status" required>
                                <option value="approved">Approve Return</option>
                                <option value="rejected">Reject Return</option>
                              </select>
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="update_status" class="btn btn-primary">Submit</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="text-center py-5">
            <i class="fas fa-inbox text-muted" style="font-size: 3rem;"></i>
            <h4 class="mt-3 text-muted">No return requests found</h4>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>


  <style>
  html, body {
  height: 100%;
  margin: 0;
  padding: 0;
}

  .footer{
    margin: 30px -80px -30px -90px;
    margin-top: 13.5%;
  }

footer.footer {
  background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
  padding: 30px 20px 20px 20px;
  text-align: center;
  width: 106.30%;
}

footer.footer .bottom {
  font-size: 14px;
}
footer.footer .bottom p {
  margin: 0;
  color: #fff;
}
</style>

  <footer class="footer">
    <div class="content">
      <div class="bottom">
        <p>&copy; 2025 AgriFarm. All rights reserved.</p>
      </div>
    </div>
  </footer>

  <!-- Farmer Profile Modal -->
  <div class="modal fade" id="farmerProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-user-circle text-success"></i> Your Profile</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label text-muted">Username</label>
            <div class="form-control"><?php echo esc($farmerInfo['username'] ?? ''); ?></div>
          </div>
          <div class="mb-3">
            <label class="form-label text-muted">Email</label>
            <div class="form-control"><?php echo esc($farmerInfo['email'] ?? ''); ?></div>
          </div>
          <div class="mb-3">
            <label class="form-label text-muted">Mobile Number</label>
            <div class="form-control"><?php echo esc($farmerInfo['mobile_number'] ?? 'Not set'); ?></div>
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
      const dropdownButton = document.getElementById('farmerProfileDropdown');
      if (dropdownButton) {
        new bootstrap.Dropdown(dropdownButton);
      }
    });
    
    // Ensure dropdown menu is always clickable and visible
    setTimeout(function() {
      const dropdownButton = document.getElementById('farmerProfileDropdown');
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
      const dropdownButton = document.getElementById('farmerProfileDropdown');
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
</body>
</html>

