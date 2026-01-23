<?php
require_once __DIR__ . '/../../db.php';

if (!isset($_SESSION['buyer_id'])) { 
  header('Location: Login.html'); 
  exit(); 
}

$buyer_id = $_SESSION['buyer_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $subject = trim($_POST['subject'] ?? '');
  $message = trim($_POST['message'] ?? '');
  $rating = isset($_POST['feedback_rating']) ? (int)$_POST['feedback_rating'] : null;
  
  if (empty($name) || empty($email) || empty($subject) || empty($message)) {
    $error = "Please fill all required fields.";
  } else {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $error = "Please enter a valid email address.";
    } else {
      $colCheck = $conn->query("SHOW COLUMNS FROM website_feedback LIKE 'farmer_id'");
      if (!$colCheck || $colCheck->num_rows == 0) {
        $conn->query("ALTER TABLE website_feedback ADD COLUMN farmer_id INT UNSIGNED NULL AFTER buyer_id");
      }
      
      $stmt = $conn->prepare("INSERT INTO website_feedback (buyer_id, name, email, subject, message, rating) VALUES (?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("issssi", $buyer_id, $name, $email, $subject, $message, $rating);
      
      if ($stmt->execute()) {
        $success = "Thank you for your feedback! We appreciate your input.";
      } else {
        $error = "Error submitting feedback. Please try again.";
      }
      $stmt->close();
    }
  }
}

$buyerInfoRes = $conn->query("SELECT username, email FROM buyer_users WHERE id=".(int)$buyer_id." LIMIT 1");
$buyerInfo = $buyerInfoRes ? $buyerInfoRes->fetch_assoc() : null;
$buyerHeaderName = $buyerInfo['username'] ?? 'Buyer';
$buyerEmail = $buyerInfo['email'] ?? '';

$cartCountRes = $conn->query("SELECT COUNT(*) AS cnt FROM cart WHERE buyer_id=".(int)$buyer_id);
$cartCount = $cartCountRes ? $cartCountRes->fetch_assoc()['cnt'] : 0;

$myFeedback = $conn->query("
  SELECT * FROM website_feedback 
  WHERE buyer_id = ".(int)$buyer_id."
  ORDER BY created_at DESC
  LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Website Rating - Buyer Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="buyer_website_rating.css" />
  <link rel="stylesheet" href="../../responsive_menu.css" />
  <link rel="stylesheet" href="buyer_home.css" />
</head>
<body>
  <div class="header-bar">
    <div class="header-left">
      <div class="basket-icon">
        <i class="fas fa-shopping-basket"></i>
      </div>
      <div class="header-title">
        <h1>Website Rating</h1>
        <p>Share your feedback about our platform</p>
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
      <a href="buyer_website_rating.php" class="btn-bidding active" style="background: #8e24aa;">
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
      <!-- Feedback Form -->
      <div class="col-md-6">
        <div class="card shadow-sm mb-4">
          <div class="card-header bg-purple text-white" style="background: #8e24aa;">
            <h5 class="mb-0"><i class="fas fa-comment-dots"></i> Share Your Feedback</h5>
          </div>
          <div class="card-body">
            <form method="POST">
              <div class="mb-3">
                <label class="form-label">Your Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="name" value="<?php echo esc($buyerHeaderName); ?>" readonly required>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Your Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" name="email" value="<?php echo esc($buyerEmail); ?>" readonly required>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Subject <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="subject" required placeholder="e.g., Great platform, Suggestion for improvement, etc.">
              </div>
              
              <div class="mb-3">
                <label class="form-label">Overall Rating</label>
                <div class="rating-input">
                  <?php for ($i = 5; $i >= 1; $i--): ?>
                    <input type="radio" name="feedback_rating" id="feedback_rating_<?php echo $i; ?>" value="<?php echo $i; ?>">
                    <label for="feedback_rating_<?php echo $i; ?>" class="star-label">
                      <i class="fas fa-star"></i>
                    </label>
                  <?php endfor; ?>
                </div>
                <small class="text-muted">Optional: Rate your overall experience</small>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Your Message <span class="text-danger">*</span></label>
                <textarea class="form-control" name="message" rows="5" required placeholder="Tell us about your experience, suggestions, or any issues you encountered..."></textarea>
              </div>
              
              <button type="submit" name="submit_feedback" class="btn btn-purple w-100" style="background: #8e24aa; color: white;">
                <i class="fas fa-paper-plane"></i> Submit Feedback
              </button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="card shadow-sm">
          <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-history"></i> My Feedback History</h5>
          </div>
          <div class="card-body">
            <?php if ($myFeedback && $myFeedback->num_rows > 0): ?>
              <?php while ($feedback = $myFeedback->fetch_assoc()): ?>
                <div class="card mb-3">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                      <div>
                        <h6 class="mb-0"><?php echo esc($feedback['subject']); ?></h6>
                        <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($feedback['created_at'])); ?></small>
                      </div>
                      <span class="badge bg-<?php 
                        echo $feedback['status'] === 'replied' ? 'success' : 
                            ($feedback['status'] === 'read' ? 'warning' : 'secondary'); 
                      ?>">
                        <?php echo ucfirst($feedback['status']); ?>
                      </span>
                    </div>
                    
                    <?php if ($feedback['rating']): ?>
                      <div class="mb-2">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                          <i class="fas fa-star <?php echo $i <= $feedback['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                        <?php endfor; ?>
                        <span class="ms-2"><?php echo $feedback['rating']; ?>/5</span>
                      </div>
                    <?php endif; ?>
                    
                    <p class="mb-0"><small><?php echo esc($feedback['message']); ?></small></p>
                  </div>
                </div>
              <?php endwhile; ?>
            <?php else: ?>
              <p class="text-muted">You haven't submitted any feedback yet.</p>
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

  <!-- Footer -->
  <footer class="footer">
    <div class="content">
      <div class="bottom">
        <p>&copy; 2025 AgriFarm. All rights reserved.</p>
      </div>
    </div>
  </footer>
</body>
</html>