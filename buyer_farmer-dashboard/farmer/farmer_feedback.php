<?php
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/weather_config.php';
if (!isset($_SESSION['farmer_id'])) { header('Location: Login.html'); exit(); }

$fid = $_SESSION['farmer_id'];
$farmerInfo = $conn->query("SELECT * FROM farmer_users WHERE id=".(int)$fid)->fetch_assoc();
$feedbackSuccess = false;
$feedbackError = '';

if (isset($_POST['submit_feedback'])) {
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $subject = trim($_POST['subject'] ?? '');
  $message = trim($_POST['message'] ?? '');
  $rating = isset($_POST['feedback_rating']) ? (int)$_POST['feedback_rating'] : null;

  if ($name && $email && $subject && $message) {

    $colCheck = $conn->query("SHOW COLUMNS FROM website_feedback LIKE 'farmer_id'");
    if (!$colCheck || $colCheck->num_rows == 0) {
      $conn->query("ALTER TABLE website_feedback ADD COLUMN farmer_id INT UNSIGNED NULL AFTER buyer_id");
      $conn->query("ALTER TABLE website_feedback ADD FOREIGN KEY (farmer_id) REFERENCES farmer_users(id) ON DELETE SET NULL");
    }

    $stmt = $conn->prepare("INSERT INTO website_feedback (farmer_id, name, email, subject, message, rating) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssi", $fid, $name, $email, $subject, $message, $rating);
    if ($stmt->execute()) {
      $feedbackSuccess = true;
    } else {
      $feedbackError = 'Unable to submit feedback right now. Please try again.';
    }
    $stmt->close();
  } else {
    $feedbackError = 'All required fields must be filled.';
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Website Feedback - Farmer Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="farmer_home.css" />
  <link rel="stylesheet" href="../../responsive_menu.css" />
</head>
<body>
  <div class="dashboard-header">
    <div class="header-left">
      <i class="fas fa-seedling"></i>
      <div class="header-title">
        <h2>Farmer Dashboard</h2>
        <small>Share feedback about your experience.</small>
      </div>
    </div>
    <div class="header-right">
      <a href="farmer_add_product.php" class="btn-bidding" style="background: #4CAF50;"><i class="fas fa-plus-circle"></i> Add Product</a>
      <a href="farmer_product_ratings.php" class="btn-bidding" style="background: #ff9800;"><i class="fas fa-star"></i> Product Rating</a>
      <a href="farmer_feedback.php" class="btn-bidding active" style="background: #8e24aa;"><i class="fas fa-thumbs-up"></i> Website Rating</a>
      <a href="farmer_weather.php" class="btn-bidding" style="background: #2196F3;"><i class="fas fa-cloud-sun"></i> Weather</a>
      <a href="farmer_bidding.php" class="btn-bidding"><i class="fas fa-gavel"></i> Bidding</a>
      <a href="farmer_return_products.php" class="btn-bidding" style="background: #e91e63;"><i class="fas fa-undo"></i> Return Products</a>
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
    <div class="section-card">
      <div class="section-header" style="background: #ffe0b2; color: #e65100;">
        <i class="fas fa-comment-dots"></i> Website Feedback
      </div>
      <div class="section-body">
        <?php if ($feedbackSuccess): ?>
          <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> Thank you for your feedback! We appreciate your time.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php elseif (!empty($feedbackError)): ?>
          <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> <?php echo esc($feedbackError); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <p class="text-muted mb-4">Use this form to share suggestions, report issues, or let us know how we can improve the farmer portal.</p>

        <form method="POST" action="">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label"><strong>Your Name</strong></label>
              <input type="text" class="form-control" name="name" value="<?php echo esc($farmerInfo['username'] ?? ''); ?>" readonly required>
            </div>
            <div class="col-md-6">
              <label class="form-label"><strong>Your Email</strong></label>
              <input type="email" class="form-control" name="email" value="<?php echo esc($farmerInfo['email'] ?? ''); ?>" readonly required>
            </div>
            <div class="col-md-12">
              <label class="form-label"><strong>Subject</strong></label>
              <input type="text" class="form-control" name="subject" placeholder="Suggestion, Bug Report, Feature Request" required>
            </div>
            <div class="col-md-12">
              <label class="form-label"><strong>Your Feedback</strong></label>
              <textarea class="form-control" name="message" rows="5" placeholder="Share your thoughts, suggestions, or report any issues..." required></textarea>
            </div>
            <div class="col-md-12">
              <label class="form-label"><strong>Rate Your Experience (Optional)</strong></label>
              <div class="rating-input">
                <input type="radio" name="feedback_rating" value="5" id="frate5"><label for="frate5">★</label>
                <input type="radio" name="feedback_rating" value="4" id="frate4"><label for="frate4">★</label>
                <input type="radio" name="feedback_rating" value="3" id="frate3"><label for="frate3">★</label>
                <input type="radio" name="feedback_rating" value="2" id="frate2"><label for="frate2">★</label>
                <input type="radio" name="feedback_rating" value="1" id="frate1"><label for="frate1">★</label>
              </div>
            </div>
            <div class="col-12">
              <button type="submit" name="submit_feedback" class="btn-submit">
                <i class="fas fa-paper-plane"></i> Submit Feedback
              </button>
            </div>
          </div>
        </form>
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

  <footer class="dashboard-footer">
    <div class="footer-content">
      <div class="footer-bottom">
        <p>&copy; 2025 AgriFarm. All rights reserved.</p>
      </div>
    </div>
  </footer>
</body>
</html>

