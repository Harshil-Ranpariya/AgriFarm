<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['admin_id'])) { 
    header('Location: admin_login.php'); 
    exit(); 
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Get all orders
$orders = $conn->query("
    SELECT o.*, p.name as product_name, p.image_path, 
           f.username as farmer_name, f.email as farmer_email, f.mobile_number as farmer_mobile,
           b.username as buyer_name, b.email as buyer_email, b.mobile_number as buyer_mobile
    FROM orders o
    JOIN products p ON o.product_id = p.id
    JOIN farmer_users f ON o.farmer_id = f.id
    JOIN buyer_users b ON o.buyer_id = b.id
    ORDER BY o.order_date DESC
");

$totalOrders = $orders ? $orders->num_rows : 0;
$totalRevenue = 0;
if ($orders) {
    $orders->data_seek(0);
    while ($order = $orders->fetch_assoc()) {
        $totalRevenue += (float)$order['total_amount'];
    }
    $orders->data_seek(0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Orders - Admin Dashboard</title>
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
                    <h1><i class="fas fa-shopping-bag"></i> All Orders</h1>
                    <p class="subtitle">Manage and view all orders on AgriFarm platform</p>
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
                    <a href="logout.php" class="btn btn-outline-secondary">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>

        <div class="container">
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3><?php echo $totalOrders; ?></h3>
                            <p>Total Orders</p>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3>₹<?php echo number_format($totalRevenue, 2); ?></h3>
                            <p>Total Revenue</p>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-rupee-sign"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="data-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-list"></i> Order Details
                    </h2>
                    <span class="badge bg-success"><?php echo $totalOrders; ?> orders</span>
                </div>

                <?php if ($orders && $orders->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Image</th>
                                    <th>Product</th>
                                    <th>Farmer</th>
                                    <th>Buyer</th>
                                    <th>Quantity</th>
                                    <th>Price/kg</th>
                                    <th>Total Amount</th>
                                    <th>Payment Status</th>
                                    <th>Payment Method</th>
                                    <th>Transaction ID</th>
                                    <th>Order Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = $orders->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong>#<?php echo $order['id']; ?></strong></td>
                                        <td style="width:80px;">
                                            <?php if (!empty($order['image_path'])): ?>
                                                <img src="<?php echo esc($order['image_path']); ?>" alt="product" class="img-thumbnail" style="max-width:70px; max-height:70px; object-fit:cover;">
                                            <?php else: ?>
                                                <span class="text-muted">No image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo esc($order['product_name']); ?></strong>
                                        </td>
                                        <td>
                                            <strong><?php echo esc($order['farmer_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo esc($order['farmer_email']); ?></small><br>
                                            <?php if (!empty($order['farmer_mobile'])): ?>
                                                <small><i class="fas fa-phone"></i> <?php echo esc($order['farmer_mobile']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo esc($order['buyer_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo esc($order['buyer_email']); ?></small><br>
                                            <?php if (!empty($order['buyer_mobile'])): ?>
                                                <small><i class="fas fa-phone"></i> <?php echo esc($order['buyer_mobile']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-info"><?php echo number_format($order['quantity'], 2); ?> kg</span></td>
                                        <td>₹<?php echo number_format($order['price_per_kg'], 2); ?></td>
                                        <td><span class="badge bg-success">₹<?php echo number_format($order['total_amount'], 2); ?></span></td>
                                        <td>
                                            <span class="badge bg-<?php echo $order['payment_status'] == 'completed' ? 'success' : ($order['payment_status'] == 'failed' ? 'danger' : 'warning'); ?>">
                                                <?php echo ucfirst($order['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $method = $order['payment_method'] ?? 'N/A';
                                            if ($method === 'card') echo '<i class="fas fa-credit-card"></i> Card';
                                            elseif ($method === 'upi') echo '<i class="fas fa-mobile-alt"></i> UPI';
                                            elseif ($method === 'cod') echo '<i class="fas fa-money-bill-wave"></i> COD';
                                            else echo esc($method);
                                            ?>
                                        </td>
                                        <td><small class="text-muted"><?php echo esc($order['payment_transaction_id'] ?? 'N/A'); ?></small></td>
                                        <td><?php echo date('M j, Y H:i', strtotime($order['order_date'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-bag text-muted" style="font-size: 3rem;"></i>
                        <h4 class="mt-3 text-muted">No Orders Found</h4>
                        <p class="text-muted">No orders have been placed yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="dashboard-footer">
        <div class="footer-content">
            <div class="footer-bottom">
                <p>&copy; 2025 AgriFarm. All rights reserved.</p>
            </div>
        </div>
    </footer>
    <script src="../responsive_menu.js"></script>
</body>
</html>

