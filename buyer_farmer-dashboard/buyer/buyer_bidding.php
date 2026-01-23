<?php
require_once __DIR__ . '/../../db.php';
if (!isset($_SESSION['buyer_id'])) { header('Location: Login.html'); exit(); }

$buyer_id = $_SESSION['buyer_id'];

// Ensure bidding columns and table exist
addColumnIfNotExists($conn, 'products', 'is_bidding', "ENUM('yes','no') DEFAULT 'no' AFTER status");
addColumnIfNotExists($conn, 'products', 'bidding_end_date', 'DATETIME NULL AFTER is_bidding');
addColumnIfNotExists($conn, 'products', 'minimum_bid_price', 'DECIMAL(10,2) NULL AFTER bidding_end_date');


if (isset($_POST['place_bid'])) {
    $product_id = (int)$_POST['product_id'];
    $bid_amount_per_20kg = (float)$_POST['bid_amount'];
    $quantity = (float)$_POST['quantity'];
    
    // Get product info
    $product = $conn->query("
        SELECT p.*, p.minimum_bid_price as min_bid_20kg
        FROM products p 
        WHERE p.id=$product_id AND p.status='approved' AND p.is_bidding='yes'
    ")->fetch_assoc();
    
    if ($product) {
        // Check if bidding is still active
        $endDate = new DateTime($product['bidding_end_date']);
        $now = new DateTime();
        if ($endDate <= $now) {
            header('Location: buyer_bidding.php?error=expired');
            exit();
        }
        
        // Check minimum bid
        if ($bid_amount_per_20kg < $product['min_bid_20kg']) {
            header('Location: buyer_bidding.php?error=min_bid&product_id='.$product_id);
            exit();
        }
        
        // Check quantity
        if ($quantity > $product['quantity']) {
            header('Location: buyer_bidding.php?error=quantity&product_id='.$product_id);
            exit();
        }
        
        // Get highest bid for this product
        $highestBid = $conn->query("SELECT MAX(bid_amount) as max_bid FROM bids WHERE product_id=$product_id AND status='active'")->fetch_assoc();
        if ($highestBid['max_bid'] && $bid_amount_per_20kg <= $highestBid['max_bid']) {
            header('Location: buyer_bidding.php?error=low_bid&product_id='.$product_id);
            exit();
        }
        
        // Insert bid
        $insert = $conn->prepare("INSERT INTO bids (product_id, buyer_id, bid_amount, quantity) VALUES (?, ?, ?, ?)");
        $insert->bind_param("iidd", $product_id, $buyer_id, $bid_amount_per_20kg, $quantity);
        $insert->execute();
        
        header('Location: buyer_bidding.php?success=1');
        exit();
    }
}

// Get all active bidding products with price comparison
$biddingProducts = $conn->query("
    SELECT p.*, f.username as farmer_name,
           (SELECT MAX(bid_amount) FROM bids WHERE product_id=p.id AND status='active') as highest_bid,
           (SELECT COUNT(*) FROM bids WHERE product_id=p.id AND status='active') as bid_count,
           (SELECT AVG(price) FROM products WHERE name LIKE CONCAT('%', SUBSTRING_INDEX(p.name, ' ', 1), '%') AND status='approved' AND is_bidding='no') as market_avg_price
    FROM products p 
    JOIN farmer_users f ON p.farmer_id = f.id 
    WHERE p.status='approved' AND p.is_bidding='yes'
    ORDER BY p.bidding_end_date ASC
");

// Get user's bids
$userBids = $conn->query("
    SELECT b.*, p.name as product_name, p.bidding_end_date
    FROM bids b
    JOIN products p ON b.product_id = p.id
    WHERE b.buyer_id = $buyer_id
    ORDER BY b.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bidding Marketplace - Buyer</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="buyer_bidding.css">
</head>
<body class="p-4">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1"><i class="fas fa-gavel text-success"></i> Bidding Marketplace</h2>
        <small class="text-muted">Place bids on products</small>
      </div>
      <div>
        <a href="buyer_home.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back</a>
        <a href="#myBids" class="btn btn-primary"><i class="fas fa-list"></i> My Bids</a>
      </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> Bid placed successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle"></i> 
        <?php 
        if ($_GET['error'] == 'expired') echo 'Bidding has ended for this product!';
        elseif ($_GET['error'] == 'min_bid') echo 'Your bid is below the minimum bid price!';
        elseif ($_GET['error'] == 'quantity') echo 'Quantity exceeds available stock!';
        elseif ($_GET['error'] == 'low_bid') echo 'Your bid must be higher than the current highest bid!';
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Active Bidding Products -->
    <h5 class="mb-3"><i class="fas fa-fire"></i> Live Bidding Products</h5>
    
    <?php if ($biddingProducts && $biddingProducts->num_rows > 0): ?>
      <div class="row g-3 mb-4">
        <?php while ($product = $biddingProducts->fetch_assoc()): 
          $pricePerKg = $product['price'];
          $minBidPerKg = $product['minimum_bid_price'] ? $product['minimum_bid_price'] : 0;
          $highestBidPerKg = $product['highest_bid'] ? $product['highest_bid'] : 0;
          $marketAvgPerKg = $product['market_avg_price'] ? $product['market_avg_price'] : 0;
          
          $endDate = new DateTime($product['bidding_end_date']);
          $now = new DateTime();
          $isActive = $endDate > $now;
          
          $timeRemaining = '';
          if ($isActive) {
            $diff = $now->diff($endDate);
            $timeRemaining = $diff->d . 'd ' . $diff->h . 'h ' . $diff->i . 'm';
          }
        ?>
          <div class="col-md-6">
            <div class="card bid-card h-100">
              <?php if ($isActive): ?>
                <span class="badge bg-success live-badge"><i class="fas fa-circle"></i> LIVE</span>
              <?php else: ?>
                <span class="badge bg-secondary" style="position: absolute; top: 10px; right: 10px;">ENDED</span>
              <?php endif; ?>
              <div class="card-body">
                <h5 class="card-title"><?php echo esc($product['name']); ?></h5>
                <p class="text-muted mb-2">by <?php echo esc($product['farmer_name']); ?></p>
                <p class="mb-2"><strong>Available:</strong> <?php echo number_format($product['quantity'], 2); ?> kg</p>
                
                <!-- Price Comparison -->
                <div class="price-comparison">
                  <small class="text-muted d-block">Price Comparison (per kg)</small>
                  <div class="row mt-2">
                    <div class="col-6">
                      <small>Base Price:</small><br>
                      <strong>₹<?php echo number_format($pricePerKg, 2); ?></strong>
                    </div>
                    <div class="col-6">
                      <small>Min Bid:</small><br>
                      <strong class="text-warning">₹<?php echo number_format($minBidPerKg, 2); ?></strong>
                    </div>
                  </div>
                  <?php if ($highestBidPerKg > 0): ?>
                    <div class="row mt-2">
                      <div class="col-6">
                        <small>Highest Bid:</small><br>
                        <strong class="text-danger">₹<?php echo number_format($highestBidPerKg, 2); ?></strong>
                      </div>
                    </div>
                  <?php endif; ?>
                </div>
                
                <p class="mb-2">
                  <strong>Bids:</strong> <?php echo (int)$product['bid_count']; ?> | 
                  <strong>Ends:</strong> <?php echo date('M d, H:i', strtotime($product['bidding_end_date'])); ?>
                  <?php if ($isActive): ?>
                    <br><small class="text-muted">Time Remaining: <?php echo $timeRemaining; ?></small>
                  <?php endif; ?>
                </p>
                
                <?php if ($isActive): ?>
                  <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#bidModal<?php echo $product['id']; ?>">
                    <i class="fas fa-hand-pointer"></i> Place Bid
                  </button>
                <?php else: ?>
                  <button class="btn btn-secondary w-100" disabled>Bidding Ended</button>
                <?php endif; ?>
              </div>
            </div>

            <!-- Bid Modal -->
            <div class="modal fade" id="bidModal<?php echo $product['id']; ?>" tabindex="-1">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">Place Bid - <?php echo esc($product['name']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <form method="POST">
                    <div class="modal-body">
                      <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                      
                      <div class="mb-3">
                        <label class="form-label">Bid Amount</label>
                        <input type="number" step="1" class="form-control" name="bid_amount" 
                               value="<?php echo max($product['minimum_bid_price'], $highestBidPerKg + 1); ?>" 
                               min="<?php echo $product['minimum_bid_price']; ?>" required>
                        <small class="text-muted">
                          Minimum: ₹<?php echo number_format($product['minimum_bid_price'], 2); ?> | 
                          <?php if ($highestBidPerKg > 0): ?>
                            Current Highest: ₹<?php echo number_format($highestBidPerKg, 2); ?>
                          <?php else: ?>
                            No bids yet
                          <?php endif; ?>
                        </small>
                        <div class="mt-2">
                          <small><strong>Per kg:</strong> ₹<span id="bidPerKg<?php echo $product['id']; ?>"><?php echo number_format($minBidPerKg, 2); ?></span></small>
                        </div>
                      </div>
                      
                      <div class="mb-3">
                        <label class="form-label">Quantity (kg)</label>
                        <input type="number" step="0.5" class="form-control" name="quantity" 
                               value="1" min="0.5" max="<?php echo $product['quantity']; ?>" required>
                        <small class="text-muted">Max: <?php echo number_format($product['quantity'], 2); ?> kg</small>
                      </div>
                      
                      <div class="alert alert-info">
                        <strong>Your Total Bid Value:</strong> ₹<span id="totalValue<?php echo $product['id']; ?>">0.00</span>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" name="place_bid" class="btn btn-success">Place Bid</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>

            <script>
              document.getElementById('bidModal<?php echo $product['id']; ?>').addEventListener('shown.bs.modal', function() {
                const bidInput = this.querySelector('input[name="bid_amount"]');
                const qtyInput = this.querySelector('input[name="quantity"]');
                const bidPerKgSpan = document.getElementById('bidPerKg<?php echo $product['id']; ?>');
                const totalValueSpan = document.getElementById('totalValue<?php echo $product['id']; ?>');
                
                function updateValues() {
                  const bidAmount = parseFloat(bidInput.value) || 0;
                  const quantity = parseFloat(qtyInput.value) || 0;
                  const bidPerKg = bidAmount ;
                  const totalValue = bidPerKg * quantity;
                  
                  bidPerKgSpan.textContent = bidPerKg.toFixed(2);
                  totalValueSpan.textContent = totalValue.toFixed(2);
                }
                
                bidInput.addEventListener('input', updateValues);
                qtyInput.addEventListener('input', updateValues);
                updateValues();
              });
            </script>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="alert alert-info text-center">
        <i class="fas fa-info-circle"></i> No active bidding products available.
      </div>
    <?php endif; ?>

    <!-- My Bids Section -->
    <div id="myBids">
      <h5 class="mb-3 mt-4"><i class="fas fa-list"></i> My Bids</h5>
      
      <?php if ($userBids && $userBids->num_rows > 0): ?>
        <div class="table-responsive">
          <table class="table table-bordered">
            <thead>
              <tr>
                <th>Product</th>
                <th>My Bid (per kg)</th>
                <th>Quantity</th>
                <th>Total Value</th>
                <th>Status</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($bid = $userBids->fetch_assoc()): 
                $bidPerKg = $bid['bid_amount'];
                $totalValue = $bidPerKg * $bid['quantity'];
              ?>
                <tr class="<?php echo $bid['status'] === 'won' ? 'table-success' : ($bid['status'] === 'lost' ? 'table-danger' : 'my-bid'); ?>">
                  <td><?php echo esc($bid['product_name']); ?></td>
                  <td><strong>₹<?php echo number_format($bidPerKg, 2); ?>/kg</strong></td>
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
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="alert alert-info text-center">
          <i class="fas fa-info-circle"></i> You haven't placed any bids yet.
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

