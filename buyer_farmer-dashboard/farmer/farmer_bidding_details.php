z<?php
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/weather_config.php';
if (!isset($_SESSION['farmer_id'])) { header('Location: Login.html'); exit(); }

$farmer_id = $_SESSION['farmer_id'];
$product_id = (int)($_GET['product_id'] ?? 0);

if (!$product_id) {
    header('Location: farmer_bidding.php');
    exit();
}

// Verify product belongs to farmer
$product = $conn->query("
    SELECT p.*, f.username as farmer_name 
    FROM products p 
    JOIN farmer_users f ON p.farmer_id = f.id 
    WHERE p.id=$product_id AND p.farmer_id=$farmer_id
")->fetch_assoc();

if (!$product) {
    header('Location: farmer_bidding.php');
    exit();
}

// Handle accept/reject bid
if (isset($_POST['accept_bid'])) {
    $bid_id = (int)$_POST['bid_id'];
    // Accept this bid and reject others
    $conn->query("UPDATE bids SET status='won' WHERE id=$bid_id AND product_id=$product_id");
    $conn->query("UPDATE bids SET status='lost' WHERE product_id=$product_id AND id!=$bid_id AND status='active'");
    header('Location: farmer_bidding_details.php?product_id='.$product_id.'&success=1');
    exit();
}

if (isset($_POST['reject_bid'])) {
    $bid_id = (int)$_POST['bid_id'];
    $conn->query("UPDATE bids SET status='rejected' WHERE id=$bid_id");
    header('Location: farmer_bidding_details.php?product_id='.$product_id.'&success=2');
    exit();
}

// Ensure bids table exists
$conn->query("CREATE TABLE IF NOT EXISTS bids (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    buyer_id INT UNSIGNED NOT NULL,
    bid_amount DECIMAL(10,2) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    status ENUM('active','accepted','rejected','won','lost') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (buyer_id) REFERENCES buyer_users(id) ON DELETE CASCADE,
    INDEX idx_product_status (product_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Get all bids for this product
$bids = $conn->query("
    SELECT b.*, bu.username as buyer_name, bu.email as buyer_email
    FROM bids b
    JOIN buyer_users bu ON b.buyer_id = bu.id
    WHERE b.product_id = $product_id
    ORDER BY b.bid_amount DESC, b.created_at DESC
");

$pricePerKg = $product['price'] / 20;
$minBidPerKg = $product['minimum_bid_price'] ? $product['minimum_bid_price'] / 20 : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bidding Details - <?php echo esc($product['name']); ?></title>
  <link rel="stylesheet" href="buyer_bidding_detail.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body class="p-4">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1"><i class="fas fa-gavel text-success"></i> Bidding Details</h2>
        <small class="text-muted"><?php echo esc($product['name']); ?></small>
      </div>
      <a href="farmer_bidding.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show">
        <?php if ($_GET['success'] == 1): ?>
          Bid accepted successfully!
        <?php elseif ($_GET['success'] == 2): ?>
          Bid rejected successfully!
        <?php endif; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Product Info -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <h5 class="mb-3">Product Information</h5>
            <p><strong>Product:</strong> <?php echo esc($product['name']); ?></p>
            <p><strong>Base Price:</strong> ₹<?php echo number_format($pricePerKg, 2); ?>/kg</p>
            <p><strong>Minimum Bid:</strong> ₹<?php echo number_format($minBidPerKg, 2); ?>/kg</p>
            <p><strong>Available Quantity:</strong> <?php echo number_format($product['quantity'], 2); ?> kg</p>
          </div>
          <div class="col-md-6">
            <h5 class="mb-3">Bidding Status</h5>
            <?php 
            $endDate = new DateTime($product['bidding_end_date']);
            $now = new DateTime();
            $isActive = $endDate > $now;
            ?>
            <p>
              <strong>Status:</strong> 
              <?php if ($isActive): ?>
                <span class="badge bg-success live-update"><i class="fas fa-circle"></i> Live</span>
              <?php else: ?>
                <span class="badge bg-secondary">Ended</span>
              <?php endif; ?>
            </p>
            <p><strong>End Date:</strong> <?php echo date('M d, Y H:i', strtotime($product['bidding_end_date'])); ?></p>
            <?php if ($isActive): ?>
              <p><strong>Time Remaining:</strong> <span id="countdown"></span></p>
            <?php endif; ?>
            <p><strong>Total Bids:</strong> <?php echo $bids ? $bids->num_rows : 0; ?></p>
          </div>
        </div>
      </div>
    </div>

    <!-- Bids List -->
    <h5 class="mb-3"><i class="fas fa-list"></i> All Bids</h5>
    
    <?php if ($bids && $bids->num_rows > 0): ?>
      <div class="table-responsive">
        <table class="table table-bordered">
          <thead>
            <tr>
              <th>Rank</th>
              <th>Buyer</th>
              <th>Bid Amount (per kg)</th>
              <th>Quantity</th>
              <th>Total Value</th>
              <th>Status</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $rank = 1;
            $bids->data_seek(0);
            while ($bid = $bids->fetch_assoc()): 
              $bidPerKg = $bid['bid_amount'] / 20;
              $totalValue = $bidPerKg * $bid['quantity'];
              $statusClass = $bid['status'] === 'won' ? 'won' : ($bid['status'] === 'lost' ? 'lost' : '');
            ?>
              <tr class="bid-row <?php echo $statusClass; ?>">
                <td><strong>#<?php echo $rank++; ?></strong></td>
                <td>
                  <strong><?php echo esc($bid['buyer_name']); ?></strong><br>
                  <small class="text-muted"><?php echo esc($bid['buyer_email']); ?></small>
                </td>
                <td><strong class="text-success">₹<?php echo number_format($bidPerKg, 2); ?>/kg</strong></td>
                <td><?php echo number_format($bid['quantity'], 2); ?> kg</td>
                <td><strong>₹<?php echo number_format($totalValue, 2); ?></strong></td>
                <td>
                  <?php if ($bid['status'] === 'won'): ?>
                    <span class="badge bg-success">Winner</span>
                  <?php elseif ($bid['status'] === 'lost'): ?>
                    <span class="badge bg-danger">Lost</span>
                  <?php elseif ($bid['status'] === 'rejected'): ?>
                    <span class="badge bg-warning">Rejected</span>
                  <?php else: ?>
                    <span class="badge bg-primary">Active</span>
                  <?php endif; ?>
                </td>
                <td><?php echo date('M d, Y H:i', strtotime($bid['created_at'])); ?></td>
                <td>
                  <?php if ($bid['status'] === 'active' && $isActive): ?>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Accept this bid? Other bids will be marked as lost.');">
                      <input type="hidden" name="bid_id" value="<?php echo $bid['id']; ?>">
                      <button type="submit" name="accept_bid" class="btn btn-sm btn-success">
                        <i class="fas fa-check"></i> Accept
                      </button>
                    </form>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Reject this bid?');">
                      <input type="hidden" name="bid_id" value="<?php echo $bid['id']; ?>">
                      <button type="submit" name="reject_bid" class="btn btn-sm btn-danger">
                        <i class="fas fa-times"></i> Reject
                      </button>
                    </form>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="alert alert-info text-center">
        <i class="fas fa-info-circle"></i> No bids received yet for this product.
      </div>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <?php if ($isActive): ?>
    <script>
      const endDate = new Date('<?php echo $product['bidding_end_date']; ?>').getTime();
      function updateCountdown() {
        const now = new Date().getTime();
        const distance = endDate - now;
        
        if (distance < 0) {
          document.getElementById('countdown').innerHTML = 'Bidding Ended';
          return;
        }
        
        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        document.getElementById('countdown').innerHTML = 
          (days > 0 ? days + 'd ' : '') + 
          (hours > 0 ? hours + 'h ' : '') + 
          (minutes > 0 ? minutes + 'm ' : '') + 
          seconds + 's';
      }
      updateCountdown();
      setInterval(updateCountdown, 1000);
      
      // Auto-refresh every 5 seconds
      setInterval(() => location.reload(), 5000);
    </script>
  <?php endif; ?>
</body>
</html>

