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

// Get all transactions (orders with payment_status = 'completed')
$transactions = $conn->query("
    SELECT o.*, p.name as product_name, p.image_path, 
           f.username as farmer_name, f.email as farmer_email,
           b.username as buyer_name, b.email as buyer_email
    FROM orders o
    JOIN products p ON o.product_id = p.id
    JOIN farmer_users f ON o.farmer_id = f.id
    JOIN buyer_users b ON o.buyer_id = b.id
    WHERE o.payment_status = 'completed'
    ORDER BY o.order_date DESC
");

$totalTransactions = $transactions ? $transactions->num_rows : 0;
$totalRevenue = 0;
$cardPayments = 0;
$upiPayments = 0;
$codPayments = 0;

if ($transactions) {
    $transactions->data_seek(0);
    while ($txn = $transactions->fetch_assoc()) {
        $totalRevenue += (float)$txn['total_amount'];
        $method = $txn['payment_method'] ?? '';
        if ($method === 'card') $cardPayments++;
        elseif ($method === 'upi') $upiPayments++;
        elseif ($method === 'cod') $codPayments++;
    }
    $transactions->data_seek(0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Transactions - Admin Dashboard</title>
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
                    <h1><i class="fas fa-money-bill-wave"></i> All Transactions</h1>
                    <p class="subtitle">View all completed payment transactions</p>
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
                            <h3><?php echo $totalTransactions; ?></h3>
                            <p>Total Transactions</p>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-money-bill-wave"></i>
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
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3><?php echo $cardPayments; ?></h3>
                            <p>Card Payments</p>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3><?php echo $upiPayments; ?></h3>
                            <p>UPI Payments</p>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3><?php echo $codPayments; ?></h3>
                            <p>COD Payments</p>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-money-bill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="data-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-list"></i> Transaction Details
                    </h2>
                    <span class="badge bg-success"><?php echo $totalTransactions; ?> transactions</span>
                </div>

                <?php if ($transactions && $transactions->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Transaction ID</th>
                                    <th>Order ID</th>
                                    <th>Product</th>
                                    <th>Farmer</th>
                                    <th>Buyer</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                    <th>Date & Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($txn = $transactions->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong style="font-family: monospace; color: #1976d2;">
                                                <?php echo esc($txn['payment_transaction_id'] ?? 'N/A'); ?>
                                            </strong>
                                        </td>
                                        <td><strong>#<?php echo $txn['id']; ?></strong></td>
                                        <td>
                                            <strong><?php echo esc($txn['product_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo number_format($txn['quantity'], 2); ?> kg</small>
                                        </td>
                                        <td><?php echo esc($txn['farmer_name']); ?></td>
                                        <td><?php echo esc($txn['buyer_name']); ?></td>
                                        <td>
                                            <span class="badge bg-success" style="font-size: 14px;">
                                                ₹<?php echo number_format($txn['total_amount'], 2); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $method = $txn['payment_method'] ?? 'N/A';
                                            if ($method === 'card') {
                                                echo '<span class="badge bg-primary"><i class="fas fa-credit-card"></i> Card</span>';
                                            } elseif ($method === 'upi') {
                                                echo '<span class="badge bg-info"><i class="fas fa-mobile-alt"></i> UPI</span>';
                                            } elseif ($method === 'cod') {
                                                echo '<span class="badge bg-warning"><i class="fas fa-money-bill-wave"></i> COD</span>';
                                            } else {
                                                echo esc($method);
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo date('M j, Y H:i', strtotime($txn['order_date'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-money-bill-wave text-muted" style="font-size: 3rem;"></i>
                        <h4 class="mt-3 text-muted">No Transactions Found</h4>
                        <p class="text-muted">No completed transactions yet.</p>
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

