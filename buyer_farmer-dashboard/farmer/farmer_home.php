<?php
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/weather_config.php';
if (!isset($_SESSION['farmer_id'])) { header('Location: ../login/Login.html'); exit(); }

$fid = $_SESSION['farmer_id'];

$farmerInfo = $conn->query("SELECT * FROM farmer_users WHERE id=".(int)$fid)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Farmer Home</title>
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
      <a href="farmer_product_ratings.php" class="btn-bidding" style="background: #ff9800;"><i class="fas fa-star"></i> Product Rating</a>
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

    <?php
      $statTotal = $conn->query("SELECT COUNT(*) AS c FROM products WHERE farmer_id=".(int)$fid)->fetch_assoc()['c'] ?? 0;
      $statApproved = $conn->query("SELECT COUNT(*) AS c FROM products WHERE farmer_id=".(int)$fid." AND status='approved'")->fetch_assoc()['c'] ?? 0;
      $statPending = $conn->query("SELECT COUNT(*) AS c FROM products WHERE farmer_id=".(int)$fid." AND status='pending'")->fetch_assoc()['c'] ?? 0;
      $statRejected = $conn->query("SELECT COUNT(*) AS c FROM products WHERE farmer_id=".(int)$fid." AND status='rejected'")->fetch_assoc()['c'] ?? 0;
      
      // Check for zero quantity products
      $zeroQtyProducts = $conn->query("SELECT id, name FROM products WHERE farmer_id=".(int)$fid." AND quantity <= 0");
    ?>
    
    <?php if (isset($_GET['deleted'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> Product deleted successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['updated'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> Product updated successfully! It will be reviewed by admin.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    
    <?php if ($zeroQtyProducts && $zeroQtyProducts->num_rows > 0): ?>
      <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle"></i> <strong>Alert:</strong> You have <?php echo $zeroQtyProducts->num_rows; ?> product(s) with zero quantity. Please delete them from your dashboard.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="stats-grid">
      <div class="stat-card total">
        <div class="stat-info">
          <div class="stat-label">TOTAL PRODUCTS</div>
          <div class="stat-value"><?php echo (int)$statTotal; ?></div>
        </div>
        <div class="stat-icon"><i class="fas fa-box"></i></div>
      </div>
      <div class="stat-card approved">
        <div class="stat-info">
          <div class="stat-label">APPROVED</div>
          <div class="stat-value"><?php echo (int)$statApproved; ?></div>
        </div>
        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
      </div>
      <div class="stat-card pending">
        <div class="stat-info">
          <div class="stat-label">PENDING</div>
          <div class="stat-value"><?php echo (int)$statPending; ?></div>
        </div>
        <div class="stat-icon"><i class="fas fa-clock"></i></div>
      </div>
      <div class="stat-card rejected">
        <div class="stat-info">
          <div class="stat-label">REJECTED</div>
          <div class="stat-value"><?php echo (int)$statRejected; ?></div>
        </div>
        <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
      </div>
    </div>

    <!-- Add New Product Section (Primary on load) -->
    <div class="section-card" id="add-product">
      <div class="section-header product-header">
        <i class="fas fa-plus-circle"></i> Add New Product
      </div>
      <div class="section-body">
        <form action="add_product.php" method="post" class="row g-3" enctype="multipart/form-data">
          <div class="col-md-4">
            <label class="form-label">Category</label>
            <select class="form-select" name="category" id="category" onchange="updateSubcategories()" required>
              <option value="">-- Select Category --</option>
              <option value="Fruits">Fruits</option>
              <option value="Vegetables">Vegetables</option>
              <option value="Grains&Crops">Grains & Crops</option>
              <option value="Spices">Spices</option>
              <option value="Pulses">Pulses</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Sub Category</label>
            <select class="form-select" name="subcategory" id="subcategory" onchange="updateProductName()">
              <option value="">-- Select Sub Category --</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Product Name</label>
            <input class="form-control" type="text" name="name" id="product_name" placeholder="Enter product name or select subcategory" required oninput="checkProductNameMatch()">
            <small class="text-muted" id="product_name_hint">Enter product name manually or select subcategory to auto-fill</small>
          </div>
          <div class="col-md-4">
            <label class="form-label">Price per 1kg (₹)</label>
            <input class="form-control" type="number" step="1" name="price" placeholder="Enter price for 1kg" min="0" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Quantity (kg)</label>
            <input class="form-control" type="number" step="0.1" name="quantity" placeholder="Enter available quantity in kg" min="0" required>
          </div>
          <div class="col-12">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description" rows="3" placeholder="Enter product description"></textarea>
          </div>
          <div class="col-md-4">
            <label class="form-label">Product Image</label>
            <input class="form-control" type="file" name="image" accept="image/*">
            <small class="text-muted">Upload product image (JPG, PNG, JPEG)</small>
          </div>
          <div class="col-12">
            <button class="btn-submit" type="submit"><i class="fas fa-paper-plane"></i> Submit for Approval</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Additional Dashboard Insights -->

    <!-- Profile Information Section -->
    <div class="section-card" id="farmerProfileCard">
      <div class="section-header profile-header">
        <i class="fas fa-user"></i> Profile Information
      </div>
      <div class="section-body">
        <div class="profile-info">
          <div class="profile-field">
            <label>Name</label>
            <div class="field-value">
              <i class="fas fa-user-circle"></i>
              <span><?php echo esc($farmerInfo['username'] ?? 'N/A'); ?></span>
            </div>
          </div>
          <div class="profile-field">
            <label>Email</label>
            <div class="field-value">
              <i class="fas fa-envelope"></i>
              <span><?php echo esc($farmerInfo['email'] ?? 'N/A'); ?></span>
            </div>
          </div>
          <div class="profile-field">
            <label>Mobile Number</label>
            <div class="field-value">
              <i class="fas fa-phone"></i>
              <span><?php echo esc($farmerInfo['mobile_number'] ?? 'Not set'); ?></span>
            </div>
          </div>
        </div>
        <button class="btn-update-mobile" data-bs-toggle="modal" data-bs-target="#updateMobileModal">
          <i class="fas fa-edit"></i> Update Mobile
        </button>
      </div>
    </div>

    <!-- Your Products Section -->
    <div class="section-card" id="products">
      <div class="section-header products-header">
        <i class="fas fa-list"></i> Your Products
      </div>
      <div class="section-body">
    <div class="table-responsive">
      <table class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>Image</th>
            <th>Name</th>
            <th>Price (per kg)</th>
            <th>Quantity (kg)</th>
            <th>Status</th>
            <th>Created</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $res = $conn->query("SELECT id, name, image_path, price, quantity, status, created_at FROM products WHERE farmer_id=".(int)$fid." ORDER BY id DESC");
          if ($res && $res->num_rows > 0):
            while ($row = $res->fetch_assoc()): 
              $pricePerKg = $row['price']; 
            ?>
              <tr>
                <td><?php if (!empty($row['image_path'])): ?><img src="<?php echo esc($row['image_path']); ?>" alt="" style="width:60px;height:45px;object-fit:cover;border-radius:6px;" /><?php else: ?><span class="text-muted">No image</span><?php endif; ?></td>
                <td><?php echo esc($row['name']); ?></td>
                <td>₹ <?php echo esc(number_format($pricePerKg, 2)); ?>/kg</td>
                <td>
                  <?php echo esc($row['quantity'] ?? 0); ?> kg
                  <?php if (($row['quantity'] ?? 0) == 0): ?>
                    <span class="badge bg-danger ms-1">Out of Stock</span>
                  <?php endif; ?>
                </td>
                <td><span class="badge bg-<?php echo $row['status']==='approved'?'success':($row['status']==='rejected'?'danger':'warning'); ?>"><?php echo esc($row['status']); ?></span></td>
                <td><?php echo esc($row['created_at']); ?></td>
                <td>
                  <button class="btn-edit" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $row['id']; ?>">
                    <i class="fas fa-edit"></i> Edit
                  </button>
                  <button class="btn-delete" onclick="deleteProduct(<?php echo $row['id']; ?>, '<?php echo esc(addslashes($row['name'])); ?>')">
                    <i class="fas fa-trash"></i> Delete
                  </button>
                </td>
              </tr>

              <div class="modal fade" id="editModal<?php echo $row['id']; ?>" tabindex="-1">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">Edit Product</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="update_product.php" enctype="multipart/form-data">
                      <div class="modal-body">
                        <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                        <div class="mb-3">
                          <label class="form-label">Category</label>
                          <select class="form-select" name="category" id="edit_category_<?php echo $row['id']; ?>" onchange="updateEditSubcategories(<?php echo $row['id']; ?>)" required>
                            <option value="">-- Select Category --</option>
                            <option value="Fruits" <?php echo ($row['category'] ?? '') == 'Fruits' ? 'selected' : ''; ?>>Fruits</option>
                            <option value="Vegetables" <?php echo ($row['category'] ?? '') == 'Vegetables' ? 'selected' : ''; ?>>Vegetables</option>
                            <option value="Grains&Crops" <?php echo ($row['category'] ?? '') == 'Grains&Crops' ? 'selected' : ''; ?>>Grains & Crops</option>
                            <option value="Spices" <?php echo ($row['category'] ?? '') == 'Spices' ? 'selected' : ''; ?>>Spices</option>
                            <option value="Pulses" <?php echo ($row['category'] ?? '') == 'Pulses' ? 'selected' : ''; ?>>Pulses</option>
                            <option value="Other" <?php echo ($row['category'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                          </select>
                        </div>
                        <div class="mb-3">
                          <label class="form-label">Sub Category</label>
                          <select class="form-select" name="subcategory" id="edit_subcategory_<?php echo $row['id']; ?>" onchange="updateEditProductName(<?php echo $row['id']; ?>)">
                            <option value="">-- Select Sub Category --</option>
                          </select>
                        </div>
                        <div class="mb-3">
                          <label class="form-label">Product Name</label>
                          <input type="text" class="form-control" name="name" id="edit_product_name_<?php echo $row['id']; ?>" value="<?php echo esc($row['name']); ?>" placeholder="Enter product name or select subcategory" required oninput="checkEditProductNameMatch(<?php echo $row['id']; ?>)">
                          <small class="text-muted" id="edit_product_name_hint_<?php echo $row['id']; ?>">Enter product name manually or select subcategory to auto-fill</small>
                        </div>
                        <div class="mb-3">
                          <label class="form-label">Price per 1kg (₹)</label>
                          <input type="number" step="1" class="form-control" name="price" value="<?php echo esc($row['price']); ?>" placeholder="Enter price for 1kg" required>
                        </div>
                        <div class="mb-3">
                          <label class="form-label">Quantity (kg)</label>
                          <input type="number" step="0.1" class="form-control" name="quantity" value="<?php echo esc($row['quantity']); ?>" placeholder="Enter quantity in kg" min="0" required>
                        </div>
                        <div class="mb-3">
                          <label class="form-label">Description</label>
                          <textarea class="form-control" name="description" rows="3" placeholder="Enter product description"><?php echo esc($row['description'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                          <label class="form-label">Product Image</label>
                          <input type="file" class="form-control" name="image" accept="image/*">
                          <small class="text-muted">Leave empty to keep current image</small>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Update Product</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            <?php endwhile;
          else: ?>
            <tr>
              <td colspan="7" class="text-center text-muted">No products found. Add your first product above!</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
      </div>
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
          
        <?php else: ?>
        <?php endif; ?>
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
  
  <script>
    function deleteProduct(productId, productName) {
      if (confirm('Are you sure you want to delete "' + productName + '"? This action cannot be undone.')) {
        window.location.href = 'delete_product.php?id=' + productId;
      }
    }
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
  
  <script>
  

    // Subcategory options based on category
    const subcategories = {
      'Fruits': ['Apple', 'Banana', 'Orange', 'Mango', 'Grapes', 'Pomegranate', 'Watermelon', 'Papaya', 'Guava', 'Other'],
      'Vegetables': ['Tomato', 'Potato', 'Onion', 'Carrot', 'Cabbage', 'Cauliflower', 'Brinjal', 'Capsicum', 'Cucumber', 'Other'],
      'Grains&Crops': ['Wheat', 'Rice', 'Corn', 'Maize (Makka)', 'Sugarcane', 'Cotton', 'Soybean', 'Groundnut', 'Sunflower', 'Pearl Millet (Bajra)', 'Finger Millet (Ragi)', 'Sorghum (Jowar)', 'Little Millet (Kutki)', 'Other'],
      'Spices': ['Turmeric', 'Red Chili', 'Coriander', 'Cumin', 'cPepper(Kali Mirch)', 'Cardamom(Ilaayachee)', 'Cinnamon(Taj ,daalacheenee)', 'Other'],
      'Pulses': ['Chickpea (Chana)', 'Black Gram (urad)', 'Green Gram (Green Mung)', 'Pigeon Pea(Tuver)', 'Kidney Bean (Rajama)', 'Other'],
      'Other': ['Other']
    };

    function updateSubcategories() {
      const category = document.getElementById('category').value;
      const subcategorySelect = document.getElementById('subcategory');
      const productNameInput = document.getElementById('product_name');
      const hintText = document.getElementById('product_name_hint');
      
      subcategorySelect.innerHTML = '<option value="">-- Select Sub Category --</option>';
      
      // Only clear product name if subcategory was previously selected
      if (subcategorySelect.dataset.previousValue) {
        productNameInput.value = '';
      }
      productNameInput.readOnly = false;
      productNameInput.placeholder = 'Enter product name or select subcategory';
      if (hintText) {
        hintText.textContent = 'Enter product name manually or select subcategory to auto-fill';
      }
      
      if (category && subcategories[category]) {
        subcategories[category].forEach(sub => {
          const option = document.createElement('option');
          option.value = sub;
          option.textContent = sub;
          subcategorySelect.appendChild(option);
        });
      }
    }

    function updateProductName() {
      const subcategorySelect = document.getElementById('subcategory');
      const productNameInput = document.getElementById('product_name');
      const hintText = document.getElementById('product_name_hint');
      
      // Store previous value
      subcategorySelect.dataset.previousValue = subcategorySelect.value;
      
      if (subcategorySelect.value && subcategorySelect.value !== 'Other') {
        productNameInput.value = subcategorySelect.value;
        productNameInput.readOnly = true;
        if (hintText) {
          hintText.textContent = 'Product name set from subcategory';
          hintText.style.color = '';
        }
      } else if (subcategorySelect.value === 'Other') {
        productNameInput.value = '';
        productNameInput.readOnly = false;
        if (hintText) {
          hintText.textContent = 'Enter the specific Indian product name for this listing.';
          hintText.style.color = '#d35400';
        }
      } else {
        productNameInput.readOnly = false;
        if (hintText) {
          hintText.textContent = 'Enter product name manually or select subcategory to auto-fill';
          hintText.style.color = '';
        }
      }
    }

    function checkProductNameMatch() {
      const category = document.getElementById('category').value;
      const productNameInput = document.getElementById('product_name');
      const subcategorySelect = document.getElementById('subcategory');
      const hintText = document.getElementById('product_name_hint');
      
      if (!category || !productNameInput.value.trim()) {
        return;
      }
      
      const typedName = productNameInput.value.trim();
      const categorySubs = subcategories[category] || [];
      
      // Check if typed name matches any subcategory (case-insensitive)
      const matchedSub = categorySubs.find(sub => 
        sub.toLowerCase() === typedName.toLowerCase()
      );
      
      if (matchedSub && subcategorySelect.value !== matchedSub) {
        // Auto-select matching subcategory
        subcategorySelect.value = matchedSub;
        if (matchedSub === 'Other') {
          productNameInput.readOnly = false;
          if (hintText) {
            hintText.textContent = 'Please enter the specific product name.';
            hintText.style.color = '#d35400';
          }
        } else {
          productNameInput.readOnly = true;
          if (hintText) {
            hintText.textContent = 'Subcategory automatically selected!';
            hintText.style.color = '#28a745';
          }
        }
        // Store that subcategory was auto-selected
        subcategorySelect.dataset.previousValue = matchedSub;
      } else if (!matchedSub && subcategorySelect.value && subcategorySelect.value !== 'Other') {
        // If name doesn't match and subcategory is selected, clear subcategory
        subcategorySelect.value = '';
        productNameInput.readOnly = false;
        if (hintText) {
          hintText.textContent = 'Enter product name manually or select subcategory to auto-fill';
          hintText.style.color = '';
        }
      } else if (!matchedSub || subcategorySelect.value === 'Other') {
        // Name doesn't match, allow manual entry
        productNameInput.readOnly = false;
        if (hintText) {
          hintText.textContent = 'Enter product name manually or select subcategory to auto-fill';
          hintText.style.color = '';
        }
      }
    }

    function updateEditSubcategories(productId) {
      const category = document.getElementById('edit_category_' + productId).value;
      const subcategorySelect = document.getElementById('edit_subcategory_' + productId);
      const productNameInput = document.getElementById('edit_product_name_' + productId);
      const hintText = document.getElementById('edit_product_name_hint_' + productId);
      
      subcategorySelect.innerHTML = '<option value="">-- Select Sub Category --</option>';
      
      if (category && subcategories[category]) {
        subcategories[category].forEach(sub => {
          const option = document.createElement('option');
          option.value = sub;
          option.textContent = sub;
          subcategorySelect.appendChild(option);
        });
      }
      
      // Only clear product name if subcategory was previously selected
      if (subcategorySelect.dataset.previousValue && productNameInput) {
        productNameInput.value = '';
      }
      if (productNameInput) {
        productNameInput.readOnly = false;
        productNameInput.placeholder = 'Enter product name or select subcategory';
      }
      if (hintText) {
        hintText.textContent = 'Enter product name manually or select subcategory to auto-fill';
        hintText.style.color = '';
      }
    }

    function updateEditProductName(productId) {
      const subcategorySelect = document.getElementById('edit_subcategory_' + productId);
      const productNameInput = document.getElementById('edit_product_name_' + productId);
      const hintText = document.getElementById('edit_product_name_hint_' + productId);
      
      // Store previous value
      if (subcategorySelect) {
        subcategorySelect.dataset.previousValue = subcategorySelect.value;
      }
      
      if (subcategorySelect && productNameInput && subcategorySelect.value && subcategorySelect.value !== 'Other') {
        productNameInput.value = subcategorySelect.value;
        productNameInput.readOnly = true;
        if (hintText) {
          hintText.textContent = 'Product name set from subcategory';
          hintText.style.color = '';
        }
      } else if (subcategorySelect && productNameInput && subcategorySelect.value === 'Other') {
        productNameInput.value = '';
        productNameInput.readOnly = false;
        if (hintText) {
          hintText.textContent = 'Enter the specific Indian product name for this listing.';
          hintText.style.color = '#d35400';
        }
      } else if (productNameInput) {
        productNameInput.readOnly = false;
        if (hintText) {
          hintText.textContent = 'Enter product name manually or select subcategory to auto-fill';
          hintText.style.color = '';
        }
      }
    }

    function checkEditProductNameMatch(productId) {
      const category = document.getElementById('edit_category_' + productId).value;
      const productNameInput = document.getElementById('edit_product_name_' + productId);
      const subcategorySelect = document.getElementById('edit_subcategory_' + productId);
      const hintText = document.getElementById('edit_product_name_hint_' + productId);
      
      if (!category || !productNameInput || !productNameInput.value.trim()) {
        return;
      }
      
      const typedName = productNameInput.value.trim();
      const categorySubs = subcategories[category] || [];
      
      // Check if typed name matches any subcategory (case-insensitive)
      const matchedSub = categorySubs.find(sub => 
        sub.toLowerCase() === typedName.toLowerCase()
      );
      
      if (matchedSub && subcategorySelect && subcategorySelect.value !== matchedSub) {
        // Auto-select matching subcategory
        subcategorySelect.value = matchedSub;
        if (matchedSub === 'Other') {
          if (productNameInput) {
            productNameInput.readOnly = false;
          }
          if (hintText) {
            hintText.textContent = 'Please enter the specific product name.';
            hintText.style.color = '#d35400';
          }
        } else {
          productNameInput.readOnly = true;
          if (hintText) {
            hintText.textContent = 'Subcategory automatically selected!';
            hintText.style.color = '#28a745';
          }
        }
        // Store that subcategory was auto-selected
        subcategorySelect.dataset.previousValue = matchedSub;
      } else if (!matchedSub && subcategorySelect && subcategorySelect.value && subcategorySelect.value !== 'Other') {
        // If name doesn't match and a different subcategory is selected, clear subcategory
        subcategorySelect.value = '';
        if (productNameInput) {
          productNameInput.readOnly = false;
        }
        if (hintText) {
          hintText.textContent = 'Enter product name manually or select subcategory to auto-fill';
          hintText.style.color = '';
        }
      } else if (!matchedSub || (subcategorySelect && subcategorySelect.value === 'Other')) {
        // Name doesn't match or Other is selected, allow manual entry
        if (productNameInput) {
          productNameInput.readOnly = false;
        }
        if (hintText) {
          hintText.textContent = 'Enter product name manually or select subcategory to auto-fill';
          hintText.style.color = '';
        }
      }
    }

    // Initialize subcategories for edit modals on page load
    document.addEventListener('DOMContentLoaded', function() {
      <?php
      $editProducts = $conn->query("SELECT id, category, subcategory FROM products WHERE farmer_id=".(int)$fid);
      while ($editRow = $editProducts->fetch_assoc()): ?>
        const editCat<?php echo $editRow['id']; ?> = document.getElementById('edit_category_<?php echo $editRow['id']; ?>');
        if (editCat<?php echo $editRow['id']; ?>) {
          editCat<?php echo $editRow['id']; ?>.addEventListener('change', function() {
            updateEditSubcategories(<?php echo $editRow['id']; ?>);
          });
          // Trigger to populate subcategory if category is already set
          if (editCat<?php echo $editRow['id']; ?>.value) {
            updateEditSubcategories(<?php echo $editRow['id']; ?>);
            const subSelect = document.getElementById('edit_subcategory_<?php echo $editRow['id']; ?>');
            if (subSelect) {
              subSelect.value = '<?php echo esc($editRow['subcategory'] ?? ''); ?>';
              // Update product name when subcategory is set
              updateEditProductName(<?php echo $editRow['id']; ?>);
            }
          }
        }
      <?php endwhile; ?>
    });
  </script>
  
 <style>
/* --- Sticky Footer Fix --- */
html, body {
  height: 100%;
  margin: 0;
  padding: 0;
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