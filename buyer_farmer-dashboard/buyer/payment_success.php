<?php
require_once __DIR__ . '/../../db.php';
if (!isset($_SESSION['buyer_id'])) { header('Location: Login.html'); exit(); }

$buyer_id = $_SESSION['buyer_id'];
$transaction_id = $_GET['txn'] ?? '';
$payment_method = $_GET['method'] ?? '';

if (empty($transaction_id)) {
  header('Location: cart.php');
  exit();
}

// Get order details
$orders = $conn->query("
  SELECT o.*, p.name as product_name, p.image_path, f.username as farmer_name
  FROM orders o
  JOIN products p ON o.product_id = p.id
  JOIN farmer_users f ON o.farmer_id = f.id
  WHERE o.buyer_id = ".$buyer_id." AND o.payment_transaction_id = '".$conn->real_escape_string($transaction_id)."'
  ORDER BY o.order_date DESC
");

$total_amount = 0;
$order_items = [];
while ($order = $orders->fetch_assoc()) {
  $total_amount += $order['total_amount'];
  $order_items[] = $order;
}
$subtotal = $total_amount;
$tax_amount = $subtotal * 0.05;
$grand_total = $subtotal + $tax_amount;

if (empty($order_items)) {
  header('Location: cart.php');
  exit();
}

$buyerInfo = $conn->query("SELECT username, email FROM buyer_users WHERE id = ".(int)$buyer_id)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment Successful - AgriFarm</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    body {
      background: linear-gradient(135deg, #2e7d32 0%, #4CAF50 100%);
      min-height: 100vh;
      padding: 20px 0;
    }
    .success-container {
      background: white;
      border-radius: 15px;
      box-shadow: 0 10px 40px rgba(0,0,0,0.1);
      padding: 40px;
      margin-top: 20px;
      margin-bottom: 20px;
      max-width: 800px;
      margin-left: auto;
      margin-right: auto;
    }
    .success-icon {
      width: 100px;
      height: 100px;
      background: linear-gradient(135deg, #4CAF50, #2e7d32);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 30px;
      animation: scaleIn 0.5s ease-out;
    }
    .success-icon i {
      font-size: 3rem;
      color: white;
    }
    @keyframes scaleIn {
      from {
        transform: scale(0);
      }
      to {
        transform: scale(1);
      }
    }
    .success-title {
      text-align: center;
      color: #2e7d32;
      margin-bottom: 20px;
    }
    .success-hero {
      text-align: center;
    }
    .order-details {
      background: #f8f9fa;
      border-radius: 10px;
      padding: 25px;
      margin: 30px 0;
    }
    .invoice-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 12px;
      margin-bottom: 15px;
    }
    .invoice-title {
      font-size: 1.4rem;
      font-weight: 700;
      color: #2e7d32;
      margin: 0;
    }
    .invoice-meta {
      text-align: right;
      color: #555;
      font-size: 0.95rem;
      line-height: 1.4;
    }
    .invoice-actions {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .detail-row {
      display: flex;
      justify-content: space-between;
      padding: 10px 0;
      border-bottom: 1px solid #e0e0e0;
    }
    .detail-row:last-child {
      border-bottom: none;
    }
    .product-item {
      display: flex;
      align-items: center;
      padding: 15px;
      background: white;
      border-radius: 8px;
      margin-bottom: 10px;
    }
    .product-image {
      width: 60px;
      height: 60px;
      object-fit: cover;
      border-radius: 8px;
      margin-right: 15px;
    }
    table.invoice-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }
    table.invoice-table th, table.invoice-table td {
      padding: 10px;
      border-bottom: 1px solid #e0e0e0;
    }
    table.invoice-table th {
      background: #e9f3eb;
      color: #2e7d32;
      font-weight: 600;
    }
    table.invoice-table td:last-child, table.invoice-table th:last-child {
      text-align: right;
    }
    .btn-primary {
      background: #4CAF50;
      border-color: #4CAF50;
    }
    .btn-primary:hover {
      background: #2e7d32;
      border-color: #2e7d32;
    }
    .print{
      text-align: right;
    }
    .transaction-badge {
      background: #e3f2fd;
      color: #1976d2;
      padding: 10px 15px;
      border-radius: 8px;
      font-family: monospace;
      font-weight: bold;
      text-align: center;
      margin: 20px 0;
    }
    @media print {
      .no-print { display: none !important; }
      .action-buttons { display: none !important; }
      .alert-info { display: none !important; }
      .success-hero { display: none !important; }
      body { background: white; }
      .success-container { box-shadow: none; margin: 0; }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="success-container" id="invoice">
      <div class="success-hero">
      <div class="print">
      <button id="printInvoice" class="btn btn-outline-primary">
          <i class="fas fa-print"></i> Print
        </button>
        </div>
        <div class="success-icon">
          <i class="fas fa-check"></i>
        </div>
        <h2 class="success-title">Payment Successful!</h2>
        <p class="text-center text-muted mb-4">Your order has been placed successfully. Thank you for your purchase!</p>
      </div>
      
      <div class="transaction-badge">
        <i class="fas fa-receipt"></i> Transaction ID: <?php echo esc($transaction_id); ?>
      </div>
      
      <div class="order-details">
        <div class="invoice-header">
          <h4 class="invoice-title">AgriFarm</h4>
          <div class="invoice-meta">
            <div>Invoice: <?php echo esc($transaction_id); ?></div>
            <div>Date: <?php echo date('d M Y, h:i A', strtotime($order_items[0]['order_date'])); ?></div>
            <div>Status: <span class="text-success fw-bold">Paid</span></div>
          </div>
        </div>

        <h5 class="mb-3"><i class="fas fa-info-circle"></i> Order Details</h5>
        
        <div class="detail-row">
          <span><strong>Buyer:</strong></span>
          <span><?php echo esc($buyerInfo['username']); ?></span>
        </div>
        <div class="detail-row">
          <span><strong>Payment Method:</strong></span>
          <span class="text-capitalize">
            <?php 
            if ($payment_method === 'card') echo '<i class="fas fa-credit-card"></i> Credit/Debit Card';
            elseif ($payment_method === 'upi') echo '<i class="fas fa-mobile-alt"></i> UPI';
            elseif ($payment_method === 'cod') echo '<i class="fas fa-money-bill-wave"></i> Cash on Delivery';
            else echo esc($payment_method);
            ?>
          </span>
        </div>
        <div class="detail-row">
          <span><strong>Payment Status:</strong></span>
          <span class="text-success"><i class="fas fa-check-circle"></i> Completed</span>
        </div>
        <div class="detail-row">
          <span><strong>Order Date:</strong></span>
          <span><?php echo date('F d, Y h:i A', strtotime($order_items[0]['order_date'])); ?></span>
        </div>
        <div class="detail-row">
          <span><strong>Total Amount:</strong></span>
          <span class="text-success" style="font-size: 1.2rem; font-weight: bold;">₹<?php echo number_format($total_amount, 2); ?></span>
        </div>
      </div>
      
      <h5 class="mb-3"><i class="fas fa-shopping-bag"></i> Ordered Items</h5>
      <table class="invoice-table">
        <thead>
          <tr>
            <th>Item</th>
            <th>Farmer</th>
            <th>Qty (kg)</th>
            <th>Price/kg</th>
            <th>Line Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($order_items as $item): ?>
            <tr>
              <td><?php echo esc($item['product_name']); ?></td>
              <td><?php echo esc($item['farmer_name']); ?></td>
              <td><?php echo number_format($item['quantity'], 2); ?></td>
              <td>₹<?php echo number_format($item['price_per_kg'], 2); ?></td>
              <td>₹<?php echo number_format($item['total_amount'], 2); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      
      <div class="order-details">
        <div class="detail-row">
          <span><strong>Subtotal:</strong></span>
          <span>₹<?php echo number_format($subtotal, 2); ?></span>
        </div>
        <div class="detail-row">
          <span><strong>Tax (5%):</strong></span>
          <span>₹<?php echo number_format($tax_amount, 2); ?></span>
        </div>
        <div class="detail-row">
          <span><strong>Total (incl. tax):</strong></span>
          <span class="text-success" style="font-size: 1.1rem; font-weight: bold;">₹<?php echo number_format($grand_total, 2); ?></span>
        </div>
      </div>
      
      <div class="text-center mt-4 action-buttons no-print">
        <a href="buyer_home.php" class="btn btn-primary me-2">
          <i class="fas fa-home"></i> Continue Shopping
        </a>
        <a href="cart.php" class="btn btn-outline-secondary me-2">
          <i class="fas fa-shopping-cart"></i> View Cart
        </a>
        <!-- <button id="printInvoice" class="btn btn-outline-primary">
          <i class="fas fa-print"></i> Print
        </button> -->
      </div>
      
      <div class="alert alert-info mt-4">
        <i class="fas fa-envelope"></i> <strong>Email Confirmation:</strong> 
        A confirmation email has been sent to <?php echo esc($buyerInfo['email']); ?>
      </div>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Native print dialog for the invoice section
    document.getElementById('printInvoice').addEventListener('click', function () {
      window.print();
    });
  </script>
</body>
</html>

