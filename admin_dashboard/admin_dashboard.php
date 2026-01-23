<?php
require_once '../db.php';

if (!isset($_SESSION['admin_id'])) { 
    header('Location: admin_login.php'); 
    exit(); 
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

$stats = [];

$productStats = $conn->query("SELECT status, COUNT(*) as count FROM products GROUP BY status");
$productCounts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
while ($row = $productStats->fetch_assoc()) {
    $productCounts[$row['status']] = (int)$row['count'];
}

$farmerCount = $conn->query("SELECT COUNT(*) as count FROM farmer_users")->fetch_assoc()['count'];
$buyerCount = $conn->query("SELECT COUNT(*) as count FROM buyer_users")->fetch_assoc()['count'];
$totalUsers = $farmerCount + $buyerCount;

$recentProducts = $conn->query("
    SELECT p.id, p.name, p.price, p.status, p.created_at, p.image_path, p.quantity, f.username as farmer_name, f.mobile_number as farmer_mobile, f.email as farmer_email
    FROM products p 
    JOIN farmer_users f ON p.farmer_id = f.id 
    ORDER BY p.created_at DESC 
    LIMIT 10
");

$recentUsers = $conn->query("
    (SELECT 'farmer' as type, username, email, created_at FROM farmer_users ORDER BY created_at DESC LIMIT 5)
    UNION ALL
    (SELECT 'buyer' as type, username, email, created_at FROM buyer_users ORDER BY created_at DESC LIMIT 5)
    ORDER BY created_at DESC LIMIT 10
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - AgriFarm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="admin_styles.css">
    <link rel="stylesheet" href="../responsive_menu.css">
</head>
<body>
    <div class="admin-dashboard">
        <!-- Header -->
        <div class="admin-header">
            <div class="container">
                <div class="admin-title">
                    <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
                    <p class="subtitle">Welcome back, <?php echo esc($_SESSION['admin_name']); ?>! Manage your AgriFarm platform</p>
                </div>
                <div class="admin-nav">
                    <a href="admin_dashboard.php" class="btn btn-primary">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a href="admin_orders.php" class="btn btn-success">
                        <i class="fas fa-shopping-bag"></i> Orders
                    </a>
                    <a href="admin_transactions.php" class="btn btn-info">
                        <i class="fas fa-money-bill-wave"></i> Transactions
                    </a>
                    <a href="admin_return_products.php" class="btn btn-warning">
                        <i class="fas fa-undo"></i> Return Products
                    </a>
                    <a href="admin_login.php" class="btn btn-outline-secondary">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>

        <div class="container">
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <a href="admin_dashboard.php?filter=pending" style="text-decoration: none; color: inherit;">
                    <div class="stat-card pending" style="cursor: pointer;">
                        <div class="stat-content">
                            <div class="stat-info">
                                <h3><?php echo $productCounts['pending']; ?></h3>
                                <p>Pending Products</p>
                            </div>
                            <div class="stat-icon pending">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                </a>

                <a href="admin_dashboard.php?filter=approved" style="text-decoration: none; color: inherit;">
                    <div class="stat-card approved" style="cursor: pointer;">
                        <div class="stat-content">
                            <div class="stat-info">
                                <h3><?php echo $productCounts['approved']; ?></h3>
                                <p>Approved Products</p>
                            </div>
                            <div class="stat-icon approved">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </a>

                <a href="admin_dashboard.php?filter=rejected" style="text-decoration: none; color: inherit;">
                    <div class="stat-card rejected" style="cursor: pointer;">
                        <div class="stat-content">
                            <div class="stat-info">
                                <h3><?php echo $productCounts['rejected']; ?></h3>
                                <p>Rejected Products</p>
                            </div>
                            <div class="stat-icon rejected">
                                <i class="fas fa-times-circle"></i>
                            </div>
                        </div>
                    </div>
                </a>

                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3><?php echo $totalUsers; ?></h3>
                            <p>Total Users</p>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
            </div>

            <?php 
            $filter = $_GET['filter'] ?? 'pending';
            $filterTitle = ucfirst($filter) . ' Products';
            $filterIcon = $filter === 'approved' ? 'fa-check-circle' : ($filter === 'rejected' ? 'fa-times-circle' : 'fa-hourglass-half');
            
            // Get products based on filter
            if ($filter === 'approved') {
                $filteredProducts = $conn->query("
                    SELECT p.id, p.name, p.price, p.status, p.created_at, p.image_path, p.quantity, f.username as farmer_name, f.mobile_number as farmer_mobile, f.email as farmer_email
                    FROM products p 
                    JOIN farmer_users f ON p.farmer_id = f.id 
                    WHERE p.status='approved'
                    ORDER BY p.created_at DESC
                ");
            } elseif ($filter === 'rejected') {
                $filteredProducts = $conn->query("
                    SELECT p.id, p.name, p.price, p.status, p.created_at, p.image_path, p.quantity, f.username as farmer_name, f.mobile_number as farmer_mobile, f.email as farmer_email
                    FROM products p 
                    JOIN farmer_users f ON p.farmer_id = f.id 
                    WHERE p.status='rejected'
                    ORDER BY p.created_at DESC
                ");
            } else {
                // For pending, get only pending products
                $filteredProducts = $conn->query("
                    SELECT p.id, p.name, p.price, p.status, p.created_at, p.image_path, p.quantity, f.username as farmer_name, f.mobile_number as farmer_mobile, f.email as farmer_email
                    FROM products p 
                    JOIN farmer_users f ON p.farmer_id = f.id 
                    WHERE p.status='pending'
                    ORDER BY p.created_at DESC
                ");
            }
            ?>
            
            <div class="data-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas <?php echo $filterIcon; ?>"></i> <?php echo $filterTitle; ?>
                    </h2>
                    <span class="badge bg-<?php echo $filter === 'approved' ? 'success' : ($filter === 'rejected' ? 'danger' : 'warning'); ?>">
                        <?php 
                        if ($filter === 'pending') {
                            echo $productCounts['pending'];
                        } else {
                            echo $filteredProducts ? $filteredProducts->num_rows : 0;
                        }
                        ?> items
                    </span>
                    <div class="ms-3">
                        <a href="admin_dashboard.php" class="btn btn-sm btn-outline-secondary">Pending</a>
                        <a href="admin_dashboard.php?filter=approved" class="btn btn-sm btn-outline-success">Approved</a>
                        <a href="admin_dashboard.php?filter=rejected" class="btn btn-sm btn-outline-danger">Rejected</a>
                    </div>
                </div>

                <?php if ($filteredProducts && $filteredProducts->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Product Name</th>
                                    <th>Farmer</th>
                                    <th>Contact</th>
                                    <th>Price (per 1kg)</th>
                                    <th>Quantity (kg)</th>
                                    <th>Date Added</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Reset pointer for filtered products
                                if ($filteredProducts) {
                                    $filteredProducts->data_seek(0);
                                }
                                while ($row = $filteredProducts->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong>#<?php echo $row['id']; ?></strong></td>
                                            <td style="width:100px;">
                                                <?php if (!empty($row['image_path'])): ?>
                                                    <img src="<?php echo esc($row['image_path']); ?>" alt="product" class="img-thumbnail" style="max-width:90px; max-height:90px; object-fit:cover;">
                                                <?php else: ?>
                                                    <span class="text-muted">No image</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo esc($row['name']); ?></td>
                                            <td>
                                                <strong><?php echo esc($row['farmer_name']); ?></strong><br>
                                                <small class="text-muted"><?php echo esc($row['farmer_email']); ?></small>
                                            </td>
                                            <td>
                                                <?php if (!empty($row['farmer_mobile'])): ?>
                                                    <a href="tel:<?php echo esc($row['farmer_mobile']); ?>" class="text-primary">
                                                        <i class="fas fa-phone"></i> <?php echo esc($row['farmer_mobile']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">Not provided</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="badge bg-success">₹<?php echo number_format($row['price'], 2); ?></span></td>
                                            <td><span class="badge bg-info"><?php echo number_format($row['quantity'], 2); ?> kg</span></td>
                                            <td><?php echo date('M j, Y', strtotime($row['created_at'])); ?></td>
                                            <td>
                                                <?php if ($filter === 'pending'): ?>
                                                    <div class="action-buttons">
                                                        <a href="approve_product.php?id=<?php echo $row['id']; ?>&action=approve" 
                                                           class="btn-action btn-approve">
                                                            <i class="fas fa-check"></i> Approve
                                                        </a>
                                                        <a href="approve_product.php?id=<?php echo $row['id']; ?>&action=reject" 
                                                           class="btn-action btn-reject">
                                                            <i class="fas fa-times"></i> Reject
                                                        </a>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="badge bg-<?php echo $filter === 'approved' ? 'success' : 'danger'; ?>">
                                                        <?php echo esc($filter); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas <?php echo $filterIcon; ?> text-<?php echo $filter === 'approved' ? 'success' : ($filter === 'rejected' ? 'danger' : 'warning'); ?>" style="font-size: 3rem;"></i>
                        <h4 class="mt-3 text-muted">No <?php echo $filterTitle; ?></h4>
                        <p class="text-muted"><?php echo $filter === 'pending' ? 'All products have been reviewed!' : 'No products found in this category.'; ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Users Section -->
            <div class="data-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-user-plus"></i> Recent User Registrations
                    </h2>
                    <span class="badge bg-info"><?php echo $totalUsers; ?> total users</span>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <h5 class="text-success"><i class="fas fa-seedling"></i> Farmers (<?php echo $farmerCount; ?>)</h5>
                        <?php
                        // Ensure mobile_number column exists for farmer_users
                        $colRes = $conn->query("SHOW COLUMNS FROM farmer_users LIKE 'mobile_number'");
                        if ($colRes && $colRes->num_rows === 0) {
                            $conn->query("ALTER TABLE farmer_users ADD COLUMN mobile_number VARCHAR(20) NULL AFTER email");
                        }
                        
                        $farmers = $conn->query("SELECT username, email, mobile_number, created_at FROM farmer_users ORDER BY created_at DESC LIMIT 5");
                        while ($farmer = $farmers->fetch_assoc()):
                        ?>
                            <div class="user-card">
                                <div class="user-info">
                                    <div class="user-details">
                                        <h4><?php echo esc($farmer['username']); ?></h4>
                                        <p><i class="fas fa-envelope"></i> <a href="mailto:<?php echo esc($farmer['email']); ?>"><?php echo esc($farmer['email']); ?></a></p>
                                        <?php if (!empty($farmer['mobile_number'])): ?>
                                            <p><i class="fas fa-phone"></i> <a href="tel:<?php echo esc($farmer['mobile_number']); ?>"><?php echo esc($farmer['mobile_number']); ?></a></p>
                                        <?php else: ?>
                                            <p class="text-muted"><i class="fas fa-phone"></i> Not provided</p>
                                        <?php endif; ?>
                                    </div>
                                    <span class="user-role farmer">Farmer</span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <div class="col-md-6">
                        <h5 class="text-primary"><i class="fas fa-shopping-cart"></i> Buyers (<?php echo $buyerCount; ?>)</h5>
                        <?php
                        // Ensure mobile_number column exists for buyer_users
                        $colRes = $conn->query("SHOW COLUMNS FROM buyer_users LIKE 'mobile_number'");
                        if ($colRes && $colRes->num_rows === 0) {
                            $conn->query("ALTER TABLE buyer_users ADD COLUMN mobile_number VARCHAR(20) NULL AFTER email");
                        }
                        
                        $buyers = $conn->query("SELECT username, email, mobile_number, created_at FROM buyer_users ORDER BY created_at DESC LIMIT 5");
                        while ($buyer = $buyers->fetch_assoc()):
                        ?>
                            <div class="user-card">
                                <div class="user-info">
                                    <div class="user-details">
                                        <h4><?php echo esc($buyer['username']); ?></h4>
                                        <p><i class="fas fa-envelope"></i> <a href="mailto:<?php echo esc($buyer['email']); ?>"><?php echo esc($buyer['email']); ?></a></p>
                                        <?php if (!empty($buyer['mobile_number'])): ?>
                                            <p><i class="fas fa-phone"></i> <a href="tel:<?php echo esc($buyer['mobile_number']); ?>"><?php echo esc($buyer['mobile_number']); ?></a></p>
                                        <?php else: ?>
                                            <p class="text-muted"><i class="fas fa-phone"></i> Not provided</p>
                                        <?php endif; ?>
                                    </div>
                                    <span class="user-role buyer">Buyer</span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>

            <!-- Product Ratings & Reviews -->
            <div class="data-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-star"></i> Product Ratings & Reviews
                    </h2>
                </div>
                
                <?php
                // Ensure ratings table exists
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
                
                $allRatings = $conn->query("
                  SELECT pr.*, p.name as product_name, f.username as farmer_name, b.username as buyer_name
                  FROM product_ratings pr
                  JOIN products p ON pr.product_id = p.id
                  JOIN farmer_users f ON p.farmer_id = f.id
                  JOIN buyer_users b ON pr.buyer_id = b.id
                  ORDER BY pr.created_at DESC
                  LIMIT 50
                ");
                
                if ($allRatings && $allRatings->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Farmer</th>
                                    <th>Buyer</th>
                                    <th>Rating</th>
                                    <th>Review</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($rating = $allRatings->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo esc($rating['product_name']); ?></strong></td>
                                        <td><?php echo esc($rating['farmer_name']); ?></td>
                                        <td><?php echo esc($rating['buyer_name']); ?></td>
                                        <td>
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <?php if ($i <= $rating['rating']): ?>
                                                    <i class="fas fa-star text-warning"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star text-muted"></i>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                            (<?php echo $rating['rating']; ?>/5)
                                        </td>
                                        <td><?php echo esc($rating['review'] ?: 'No review'); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($rating['created_at'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-4">No ratings yet.</p>
                <?php endif; ?>
            </div>

            <!-- Website Feedback -->
            <div class="data-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-comment-dots"></i> Website Feedback
                    </h2>
                </div>
                
                <?php  
                $colCheck = $conn->query("SHOW COLUMNS FROM website_feedback LIKE 'farmer_id'");
                if (!$colCheck || $colCheck->num_rows == 0) {
                    $conn->query("ALTER TABLE website_feedback ADD COLUMN farmer_id INT UNSIGNED NULL AFTER buyer_id");
                }
                
                $allFeedback = $conn->query("
                  SELECT wf.*, 
                         CASE 
                           WHEN wf.buyer_id IS NOT NULL THEN 'Buyer'
                           WHEN wf.farmer_id IS NOT NULL THEN 'Farmer'
                           ELSE 'Guest'
                         END as user_type
                  FROM website_feedback wf
                  ORDER BY wf.created_at DESC
                  LIMIT 50
                ");
                
                if ($allFeedback && $allFeedback->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>From</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Subject</th>
                                    <th>Message</th>
                                    <th>Rating</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($feedback = $allFeedback->fetch_assoc()): ?>
                                    <tr>
                                        <td><span class="badge bg-info"><?php echo esc($feedback['user_type']); ?></span></td>
                                        <td><?php echo esc($feedback['name']); ?></td>
                                        <td><?php echo esc($feedback['email']); ?></td>
                                        <td><?php echo esc($feedback['subject']); ?></td>
                                        <td><?php echo esc(substr($feedback['message'], 0, 100)) . (strlen($feedback['message']) > 100 ? '...' : ''); ?></td>
                                        <td>
                                            <?php if ($feedback['rating']): ?>
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?php if ($i <= $feedback['rating']): ?>
                                                        <i class="fas fa-star text-warning"></i>
                                                    <?php else: ?>
                                                        <i class="far fa-star text-muted"></i>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $feedback['status'] == 'new' ? 'danger' : ($feedback['status'] == 'read' ? 'warning' : 'success'); ?>">
                                                <?php echo ucfirst($feedback['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y H:i', strtotime($feedback['created_at'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-4">No feedback yet.</p>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="data-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-bolt"></i> Quick Actions
                    </h2>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <a href="admin_dashboard.php" class="btn btn-outline-primary w-100 mb-2">
                            <i class="fas fa-refresh"></i> Refresh Dashboard
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="test_email.php" class="btn btn-outline-info w-100 mb-2">
                            <i class="fas fa-envelope"></i> Test Email
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="../home_pages/index.html" class="btn btn-outline-success w-100 mb-2">
                            <i class="fas fa-home"></i> View Website
                        </a>
                    </div>
                    <!-- <div class="col-md-3">
                        <a href="logout.php" class="btn btn-outline-danger w-100 mb-2">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div> -->
                </div>
            </div>
        </div>
    </div>

    <script src="../responsive_menu.js"></script>
    <script>
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</div>

<!-- Footer -->
<footer class="dashboard-footer">
    <div class="footer-content">
        <div class="footer-bottom">
            <p>&copy; 2025 AgriFarm. All rights reserved.</p>
        </div>
    </div>
</footer>
</body>
</html>