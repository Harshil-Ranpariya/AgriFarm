<?php
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/weather_config.php';
if (!isset($_SESSION['farmer_id'])) { header('Location: Login.html'); exit(); }

$farmer_id = $_SESSION['farmer_id'];

// Ensure bidding columns exist
addColumnIfNotExists($conn, 'products', 'is_bidding', "ENUM('yes','no') DEFAULT 'no' AFTER status");
addColumnIfNotExists($conn, 'products', 'bidding_end_date', 'DATETIME NULL AFTER is_bidding');
addColumnIfNotExists($conn, 'products', 'minimum_bid_price', 'DECIMAL(10,2) NULL AFTER bidding_end_date');

// Handle enable bidding for a product
if (isset($_POST['enable_bidding'])) {
    $product_id = (int)$_POST['product_id'];
    $end_date = $_POST['bidding_end_date'];
    $min_price = (float)$_POST['minimum_bid_price'];
    
    // Verify product belongs to farmer
    $check = $conn->query("SELECT id FROM products WHERE id=$product_id AND farmer_id=$farmer_id AND status='approved'")->fetch_assoc();
    if ($check) {
        $update = $conn->prepare("UPDATE products SET is_bidding='yes', bidding_end_date=?, minimum_bid_price=? WHERE id=?");
        $update->bind_param("sdi", $end_date, $min_price, $product_id);
        $update->execute();
        header('Location: farmer_bidding.php?success=1');
        exit();
    }
}

// Handle disable bidding
if (isset($_GET['disable']) && is_numeric($_GET['disable'])) {
    $product_id = (int)$_GET['disable'];
    $check = $conn->query("SELECT id FROM products WHERE id=$product_id AND farmer_id=$farmer_id")->fetch_assoc();
    if ($check) {
        $conn->query("UPDATE products SET is_bidding='no' WHERE id=$product_id");
        header('Location: farmer_bidding.php?success=2');
        exit();
    }
}

// Handle accept/reject bid
if (isset($_POST['accept_bid'])) {
    $bid_id = (int)$_POST['bid_id'];
    $bid = $conn->query("SELECT b.*, p.farmer_id FROM bids b JOIN products p ON b.product_id=p.id WHERE b.id=$bid_id AND p.farmer_id=$farmer_id")->fetch_assoc();
    if ($bid) {
        // Accept this bid and reject others for same product
        $conn->query("UPDATE bids SET status='won' WHERE id=$bid_id");
        $conn->query("UPDATE bids SET status='lost' WHERE product_id={$bid['product_id']} AND id!=$bid_id AND status='active'");
        header('Location: farmer_bidding.php?success=3');
        exit();
    }
}

// Get products with bidding enabled
$biddingProducts = $conn->query("
    SELECT p.*, 
           (SELECT COUNT(*) FROM bids WHERE product_id=p.id AND status='active') as bid_count,
           (SELECT MAX(bid_amount) FROM bids WHERE product_id=p.id AND status='active') as highest_bid
    FROM products p 
    WHERE p.farmer_id=$farmer_id AND p.is_bidding='yes' AND p.status='approved'
    ORDER BY p.created_at DESC
");

// Get all approved products for enabling bidding
$approvedProducts = $conn->query("SELECT id, name, price, quantity FROM products WHERE farmer_id=$farmer_id AND status='approved' AND is_bidding='no' ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bidding Management - Farmer</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="farmer_bidding.css" />
</head>
<body class="p-4">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1"><i class="fas fa-gavel text-success"></i> Bidding Management</h2>
        <small class="text-muted">Manage your product bidding</small>
      </div>
      <div>
        <a href="farmer_home.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back</a>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#enableBiddingModal">
          <i class="fas fa-plus"></i> Enable Bidding
        </button>
      </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show">
        <?php if ($_GET['success'] == 1): ?>
          Bidding enabled successfully!
        <?php elseif ($_GET['success'] == 2): ?>
          Bidding disabled successfully!
        <?php elseif ($_GET['success'] == 3): ?>
          Bid accepted successfully!
        <?php endif; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <h5 class="mb-3"><i class="fas fa-list"></i> Active Bidding Products</h5>
    
    <?php if ($biddingProducts && $biddingProducts->num_rows > 0): ?>
      <div class="row g-3">
        <?php while ($product = $biddingProducts->fetch_assoc()): 
          $pricePerKg = $product['price'];
          $endDate = new DateTime($product['bidding_end_date']);
          $now = new DateTime();
          $isActive = $endDate > $now;
        ?>
          <div class="col-md-6">
            <div class="card bid-card">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <h5 class="mb-0"><?php echo esc($product['name']); ?></h5>
                  <?php if ($isActive): ?>
                    <span class="badge bg-success live-badge"><i class="fas fa-circle"></i> Live</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">Ended</span>
                  <?php endif; ?>
                </div>
                <p class="text-muted mb-2">Price: ₹<?php echo number_format($pricePerKg, 2); ?>/kg | Qty: <?php echo number_format($product['quantity'], 2); ?> kg</p>
                <p class="mb-2">
                  <strong>End Date:</strong> <?php echo date('M d, Y H:i', strtotime($product['bidding_end_date'])); ?><br>
                  <strong>Min Bid:</strong> ₹<?php echo number_format($product['minimum_bid_price'], 2); ?>/kg<br>
                  <strong>Bids Received:</strong> <?php echo (int)$product['bid_count']; ?><br>
                  <strong>Highest Bid:</strong> <?php echo $product['highest_bid'] ? '₹' . number_format($product['highest_bid'], 2) . '/kg' : 'No bids yet'; ?>
                </p>
                <div class="d-flex gap-2">
                  <a href="farmer_bidding_details.php?product_id=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-eye"></i> View Bids
                  </a>
                  <a href="?disable=<?php echo $product['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Disable bidding for this product?')">
                    <i class="fas fa-stop"></i> Disable
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="alert alert-info text-center">
        <i class="fas fa-info-circle"></i> No active bidding products. Enable bidding for your approved products.
      </div>
    <?php endif; ?>
  </div>

  <!-- Enable Bidding Modal -->
  <div class="modal fade" id="enableBiddingModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Enable Bidding for Product</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST">
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Product</label>
              <select class="form-select" name="product_id" required>
                <option value="">Select a product</option>
                <?php 
                $approvedProducts->data_seek(0);
                while ($prod = $approvedProducts->fetch_assoc()): 
                  $prodPricePerKg = $prod['price'];
                ?>
                  <option value="<?php echo $prod['id']; ?>">
                    <?php echo esc($prod['name']); ?> - ₹<?php echo number_format($prodPricePerKg, 2); ?>/kg (<?php echo number_format($prod['quantity'], 2); ?> kg)
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Bidding End Date & Time</label>
              <input type="datetime-local" class="form-control" name="bidding_end_date" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Minimum Bid Price (per 1kg)</label>
              <input type="number" step="0.01" class="form-control" name="minimum_bid_price" placeholder="e.g., 1400.00" required>
              <small class="text-muted">Minimum price buyers must bid for 1kg</small>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="enable_bidding" class="btn btn-success">Enable Bidding</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

