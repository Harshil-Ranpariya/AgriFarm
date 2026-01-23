<?php
require_once __DIR__ . '/../../db.php';

if (!isset($_SESSION['buyer_id'])) { 
  header('Location: Login.html'); 
  exit(); 
}

$buyer_id = $_SESSION['buyer_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating'])) {
  $product_id = (int)($_POST['product_id'] ?? 0);
  $rating = (int)($_POST['rating'] ?? 0);
  $review = trim($_POST['review'] ?? '');
  
  if ($product_id <= 0 || $rating < 1 || $rating > 5) {
    $error = "Please provide a valid rating (1-5 stars).";
  } else {
    $purchaseCheck = $conn->query("SELECT id FROM orders WHERE buyer_id = ".(int)$buyer_id." AND product_id = ".$product_id." AND payment_status = 'completed' LIMIT 1");
    
    if (!$purchaseCheck || $purchaseCheck->num_rows == 0) {
      $error = "You can only rate products you have purchased.";
    } else {
      $stmt = $conn->prepare("INSERT INTO product_ratings (product_id, buyer_id, rating, review) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE rating = ?, review = ?");
      $stmt->bind_param("iiisss", $product_id, $buyer_id, $rating, $review, $rating, $review);
      
      if ($stmt->execute()) {
        $success = "Rating submitted successfully!";
      } else {
        $error = "Error submitting rating. Please try again.";
      }
      $stmt->close();
    }
  }
}

$buyerInfoRes = $conn->query("SELECT username, email FROM buyer_users WHERE id=".(int)$buyer_id." LIMIT 1");
$buyerInfo = $buyerInfoRes ? $buyerInfoRes->fetch_assoc() : null;
$buyerHeaderName = $buyerInfo['username'] ?? 'Buyer';

$cartCountRes = $conn->query("SELECT COUNT(*) AS cnt FROM cart WHERE buyer_id=".(int)$buyer_id);
$cartCount = $cartCountRes ? $cartCountRes->fetch_assoc()['cnt'] : 0;

$purchasedProducts = $conn->query("
  SELECT DISTINCT o.product_id, p.name, p.image_path, p.price,
         pr.rating as existing_rating, pr.review as existing_review,
         f.username as farmer_name
  FROM orders o
  JOIN products p ON o.product_id = p.id
  JOIN farmer_users f ON p.farmer_id = f.id
  LEFT JOIN product_ratings pr ON o.product_id = pr.product_id AND pr.buyer_id = ".(int)$buyer_id."
  WHERE o.buyer_id = ".(int)$buyer_id." AND o.payment_status = 'completed'
  ORDER BY o.order_date DESC
");

$myRatings = $conn->query("
  SELECT pr.*, p.name as product_name, p.image_path, f.username as farmer_name
  FROM product_ratings pr
  JOIN products p ON pr.product_id = p.id
  JOIN farmer_users f ON p.farmer_id = f.id
  WHERE pr.buyer_id = ".(int)$buyer_id."
  ORDER BY pr.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Product Rating - Buyer Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="buyer_home.css" />
  <link rel="stylesheet" href="../../responsive_menu.css" />
  <link rel="stylesheet" href="buyer_product_rating.css" />
</head>
<body>
  <!-- Header Bar -->
  <div class="header-bar">
    <div class="header-left">
      <div class="basket-icon">
        <i class="fas fa-shopping-basket"></i>
      </div>
      <div class="header-title">
        <h1>Product Rating</h1>
        <p>Rate and review your purchased products</p>
      </div>
    </div>
    <div class="header-right">
      <a href="buyer_home.php" class="btn-bidding" style="background: #4CAF50;">
        <i class="fas fa-home"></i> Home
      </a>
      <a href="buyer_bidding.php" class="btn-bidding">
        <i class="fas fa-gavel"></i> Bidding
      </a>
      <a href="buyer_product_rating.php" class="btn-bidding active" style="background: #ff9800;">
        <i class="fas fa-star"></i> Product Rating
      </a>
      <a href="buyer_website_rating.php" class="btn-bidding" style="background: #8e24aa;">
        <i class="fas fa-thumbs-up"></i> Website Rating
      </a>
      <a href="buyer_return_product.php" class="btn-bidding" style="background: #e91e63;">
        <i class="fas fa-undo"></i> Return Product
      </a>
      <a href="cart.php" class="btn-cart">
        <i class="fas fa-shopping-cart"></i> Cart
        <?php if ($cartCount > 0): ?>
          <span class="cart-badge-count"><?php echo $cartCount; ?></span>
        <?php endif; ?>
      </a>
      <div class="dropdown profile-dropdown">
        <button type="button" class="profile-chip dropdown-toggle" id="buyerProfileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
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
      <!-- Rate Products -->
      <div class="col-md-6">
        <div class="card shadow-sm mb-4">
          <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="fas fa-star"></i> Rate Your Purchased Products</h5>
          </div>
          <div class="card-body">
            <?php if ($purchasedProducts && $purchasedProducts->num_rows > 0): ?>
              <?php 
              $purchasedProducts->data_seek(0);
              while ($product = $purchasedProducts->fetch_assoc()): 
              ?>
                <div class="card mb-3">
                  <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                      <?php if (!empty($product['image_path'])): ?>
                        <img src="<?php echo esc($product['image_path']); ?>" alt="" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; margin-right: 15px;">
                      <?php endif; ?>
                      <div>
                        <h6 class="mb-0"><?php echo esc($product['name']); ?></h6>
                        <small class="text-muted">Farmer: <?php echo esc($product['farmer_name']); ?></small>
                      </div>
                    </div>
                    
                    <form method="POST" class="mt-3">
                      <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                      
                      <div class="mb-3">
                        <label class="form-label">Rating</label>
                        <div class="rating-input">
                          <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" name="rating" id="rating_<?php echo $product['product_id']; ?>_<?php echo $i; ?>" value="<?php echo $i; ?>" 
                                   <?php echo ($product['existing_rating'] == $i) ? 'checked' : ''; ?> required>
                            <label for="rating_<?php echo $product['product_id']; ?>_<?php echo $i; ?>" class="star-label">
                              <i class="fas fa-star"></i>
                            </label>
                          <?php endfor; ?>
                        </div>
                      </div>
                      
                      <div class="mb-3">
                        <label class="form-label">Review (Optional)</label>
                        <textarea class="form-control" name="review" rows="3" placeholder="Share your experience..."><?php echo esc($product['existing_review'] ?? ''); ?></textarea>
                      </div>
                      
                      <button type="submit" name="submit_rating" class="btn btn-warning btn-sm">
                        <i class="fas fa-paper-plane"></i> <?php echo $product['existing_rating'] ? 'Update Rating' : 'Submit Rating'; ?>
                      </button>
                    </form>
                  </div>
                </div>
              <?php endwhile; ?>
            <?php else: ?>
              <p class="text-muted">No purchased products available for rating yet.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- My Ratings -->
      <div class="col-md-6">
        <div class="card shadow-sm">
          <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-history"></i> My Ratings</h5>
          </div>
          <div class="card-body">
            <?php if ($myRatings && $myRatings->num_rows > 0): ?>
              <?php while ($rating = $myRatings->fetch_assoc()): ?>
                <div class="card mb-3">
                  <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                      <?php if (!empty($rating['image_path'])): ?>
                        <img src="<?php echo esc($rating['image_path']); ?>" alt="" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px; margin-right: 10px;">
                      <?php endif; ?>
                      <div>
                        <h6 class="mb-0"><?php echo esc($rating['product_name']); ?></h6>
                        <small class="text-muted"><?php echo esc($rating['farmer_name']); ?></small>
                      </div>
                    </div>
                    <div class="mb-2">
                      <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star <?php echo $i <= $rating['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                      <?php endfor; ?>
                      <span class="ms-2"><?php echo $rating['rating']; ?>/5</span>
                    </div>
                    <?php if (!empty($rating['review'])): ?>
                      <p class="mb-0"><small><?php echo esc($rating['review']); ?></small></p>
                    <?php endif; ?>
                    <small class="text-muted"><?php echo date('M d, Y', strtotime($rating['created_at'])); ?></small>
                  </div>
                </div>
              <?php endwhile; ?>
            <?php else: ?>
              <p class="text-muted">You haven't rated any products yet.</p>
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

  <style>
    .footer{
      margin-top: 22.25%;
    }
  </style>

  <footer class="footer">
    <div class="content">
      <div class="bottom">
        <p>&copy; 2025 AgriFarm. All rights reserved.</p>
      </div>
    </div>
  </footer>
</body>
</html>

