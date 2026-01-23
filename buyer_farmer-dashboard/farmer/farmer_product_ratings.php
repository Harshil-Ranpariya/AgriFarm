<?php
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/weather_config.php';
if (!isset($_SESSION['farmer_id'])) { header('Location: Login.html'); exit(); }

$fid = $_SESSION['farmer_id'];
$farmerInfo = $conn->query("SELECT * FROM farmer_users WHERE id=".(int)$fid)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Product Ratings - Farmer Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="farmer_home.css" />
  <link rel="stylesheet" href="../../responsive_menu.css" />
</head>
<body>
  <!-- Dark Green Header -->
  <div class="dashboard-header">
    <div class="header-left">
      <i class="fas fa-seedling"></i>
      <div class="header-title">
        <h2>Farmer Dashboard</h2>
        <small>Manage your farm products and harvest.</small>
      </div>
    </div>
    <div class="header-right">
      <a href="farmer_add_product.php" class="btn-bidding" style="background: #4CAF50;"><i class="fas fa-plus-circle"></i> Add Product</a>
      <a href="farmer_product_ratings.php" class="btn-bidding active" style="background: #ff9800;"><i class="fas fa-star"></i> Product Rating</a>
      <a href="farmer_feedback.php" class="btn-bidding" style="background: #8e24aa;"><i class="fas fa-thumbs-up"></i> Website Rating</a>
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
    <!-- Product Ratings Section -->
    <div class="section-card" id="ratings">
      <div class="section-header" style="background: #bbdefb; color: #1565c0;">
        <i class="fas fa-star"></i> Product Ratings & Reviews
      </div>
      <div class="section-body">
        <?php
        // Get all products with ratings for this farmer
        $conn->query("CREATE TABLE IF NOT EXISTS product_ratings (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          product_id INT UNSIGNED NOT NULL,
          buyer_id INT UNSIGNED NOT NULL,
          rating TINYINT UNSIGNED NOT NULL CHECK (rating >= 1 AND rating <= 5),
          review TEXT,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
          FOREIGN KEY (buyer_id) REFERENCES buyer_users(id) ON DELETE CASCADE,
          UNIQUE KEY unique_rating (product_id, buyer_id),
          INDEX idx_product_rating (product_id, rating)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $ratingsQuery = $conn->query("
          SELECT pr.*, p.name as product_name, b.username as buyer_name
          FROM product_ratings pr
          JOIN products p ON pr.product_id = p.id
          JOIN buyer_users b ON pr.buyer_id = b.id
          WHERE p.farmer_id = ".(int)$fid."
          ORDER BY pr.created_at DESC
        ");
        
        if ($ratingsQuery && $ratingsQuery->num_rows > 0): ?>
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Buyer</th>
                  <th>Rating</th>
                  <th>Review</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($rating = $ratingsQuery->fetch_assoc()): ?>
                  <tr>
                    <td><strong><?php echo esc($rating['product_name']); ?></strong></td>
                    <td><?php echo esc($rating['buyer_name']); ?></td>
                    <td>
                      <span class="rating-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                          <?php if ($i <= $rating['rating']): ?>
                            <i class="fas fa-star" style="color: #ffc107;"></i>
                          <?php else: ?>
                            <i class="far fa-star" style="color: #ddd;"></i>
                          <?php endif; ?>
                        <?php endfor; ?>
                      </span>
                      (<?php echo $rating['rating']; ?>/5)
                    </td>
                    <td><?php echo esc($rating['review'] ?: 'No review'); ?></td>
                    <td><?php echo date('M d, Y', strtotime($rating['created_at'])); ?></td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p class="text-muted text-center py-4">No ratings yet for your products.</p>
        <?php endif; ?>
      </div>
      <!-- <div class="section-footer text-center text-muted">
        Need more insights? Return to <a href="farmer_home.php#ratings">your dashboard</a>.
      </div> -->
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
  
  <style>
/* --- Sticky Footer Fix --- */
html, body {
  height: 100%;
  margin: 0;
  padding: 0;
}

.footer{
  margin-top: 19%;
}

.main-container {
  flex: 1;
}

/* --- Your existing footer styling --- */
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

