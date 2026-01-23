<?php
require_once '../db.php';

if (!isset($_SESSION['admin_id'])) { 
  header('Location: admin_login.php'); 
  exit(); 
}

// No update functionality - admin only views return history

// Get all return requests
$filter = $_GET['filter'] ?? 'all';
$statusFilter = $filter !== 'all' ? "WHERE pr.return_status = '$filter'" : '';

$returnsQuery = $conn->query("
  SELECT pr.*, 
         o.order_date, o.payment_transaction_id, o.quantity as order_qty, o.price_per_kg, o.total_amount,
         p.name as product_name, p.image_path,
         f.username as farmer_name, f.email as farmer_email,
         b.username as buyer_name, b.email as buyer_email
  FROM product_returns pr
  JOIN orders o ON pr.order_id = o.id
  JOIN products p ON pr.product_id = p.id
  JOIN farmer_users f ON pr.farmer_id = f.id
  JOIN buyer_users b ON pr.buyer_id = b.id
  $statusFilter
  ORDER BY pr.created_at DESC
");

// Get statistics
$statsQuery = $conn->query("
  SELECT 
    return_status,
    COUNT(*) as count,
    SUM(return_quantity) as total_qty,
    SUM(COALESCE(refund_amount, 0)) as total_refund
  FROM product_returns
  GROUP BY return_status
");
$stats = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'completed' => 0];
$totalRefund = 0;
while ($stat = $statsQuery->fetch_assoc()) {
  $stats[$stat['return_status']] = (int)$stat['count'];
  if ($stat['return_status'] === 'completed') {
    $totalRefund += (float)$stat['total_refund'];
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Return Products - Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="admin_styles.css">
    <link rel="stylesheet" href="../responsive_menu.css">
</head>
<body>
  <div class="admin-dashboard">
    <div class="admin-header">
      <div class="container">
        <div class="admin-title">
          <h1><i class="fas fa-undo"></i> Return Products History</h1>
          <p class="subtitle">View all product return requests</p>
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
          <a href="admin_return_products.php" class="btn btn-warning active">
            <i class="fas fa-undo"></i> Return Products
          </a>
          <a href="logout.php" class="btn btn-outline-secondary">
            <i class="fas fa-sign-out-alt"></i> Logout
          </a>
        </div>
      </div>
    </div>

    <div class="container">

      <div class="stats-grid">
        <div class="stat-card pending">
          <div class="stat-content">
            <div class="stat-info">
              <h3><?php echo $stats['pending']; ?></h3>
              <p>Pending Returns</p>
            </div>
            <div class="stat-icon pending"><i class="fas fa-clock"></i></div>
          </div>
        </div>
        <div class="stat-card approved">
          <div class="stat-content">
            <div class="stat-info">
              <h3><?php echo $stats['approved']; ?></h3>
              <p>Approved Returns</p>
            </div>
            <div class="stat-icon approved"><i class="fas fa-check-circle"></i></div>
          </div>
        </div>
        <div class="stat-card rejected">
          <div class="stat-content">
            <div class="stat-info">
              <h3><?php echo $stats['rejected']; ?></h3>
              <p>Rejected Returns</p>
            </div>
            <div class="stat-icon rejected"><i class="fas fa-times-circle"></i></div>
          </div>
        </div>
        
        </div>
      </div>

      <div class="data-section">
        <div class="section-header">
          <h2 class="section-title">
            <i class="fas fa-list"></i> Return Requests
          </h2>
        </div>

        <?php if ($returnsQuery && $returnsQuery->num_rows > 0): ?>
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Order</th>
                  <th>Product</th>
                  <th>Buyer</th>
                  <th>Farmer</th>
                  <th>Quantity</th>
                  <th>Reason</th>
                  <th>Status</th>
                  <th>Refund</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($return = $returnsQuery->fetch_assoc()): ?>
                  <tr>
                    <td><strong>#<?php echo $return['id']; ?></strong></td>
                    <td>
                      Order #<?php echo $return['order_id']; ?><br>
                      <small class="text-muted">TXN: <?php echo esc($return['payment_transaction_id']); ?></small>
                    </td>
                    <td>
                      <?php if (!empty($return['image_path'])): ?>
                        <img src="<?php echo esc($return['image_path']); ?>" alt="" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px; margin-right: 10px;">
                      <?php endif; ?>
                      <?php echo esc($return['product_name']); ?>
                    </td>
                    <td>
                      <strong><?php echo esc($return['buyer_name']); ?></strong><br>
                      <small class="text-muted"><?php echo esc($return['buyer_email']); ?></small>
                    </td>
                    <td>
                      <strong><?php echo esc($return['farmer_name']); ?></strong><br>
                      <small class="text-muted"><?php echo esc($return['farmer_email']); ?></small>
                    </td>
                    <td>
                      <strong><?php echo number_format($return['return_quantity'], 2); ?> kg</strong><br>
                      <small class="text-muted">of <?php echo number_format($return['order_qty'], 2); ?> kg</small>
                    </td>
                    <td>
                      <small><?php echo esc(substr($return['return_reason'], 0, 100)); ?><?php echo strlen($return['return_reason']) > 100 ? '...' : ''; ?></small>
                    </td>
                    <td>
                      <span class="badge bg-<?php 
                        echo $return['return_status'] === 'approved' ? 'success' : 
                            ($return['return_status'] === 'rejected' ? 'danger' : 
                            ($return['return_status'] === 'completed' ? 'info' : 'warning')); 
                      ?>">
                        <?php echo ucfirst($return['return_status']); ?>
                      </span>
                    </td>
                    <td>
                      <?php if ($return['refund_amount']): ?>
                        ₹<?php echo number_format($return['refund_amount'], 2); ?>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>
                    <td><?php echo date('M d, Y', strtotime($return['created_at'])); ?></td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="text-center py-5">
            <i class="fas fa-inbox text-muted" style="font-size: 3rem;"></i>
            <h4 class="mt-3 text-muted">No return requests found</h4>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

   <style>
html, body {
  height: 100%;
  margin: 0;
  padding: 0;
}

.main-container {
  flex: 1;
}

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

  <footer class="footer">
    <div class="content">
      <div class="bottom">
        <p>&copy; 2025 AgriFarm. All rights reserved.</p>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../responsive_menu.js"></script>
</body>
</html>