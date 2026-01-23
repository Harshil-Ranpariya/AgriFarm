<?php
require_once __DIR__ . '/../../db.php';
if (!isset($_SESSION['buyer_id'])) { header('Location: Login.html'); exit(); }

$buyer_id = $_SESSION['buyer_id'];

// Get buyer info
$buyerInfo = $conn->query("SELECT username, email FROM buyer_users WHERE id = ".(int)$buyer_id)->fetch_assoc();
if (!$buyerInfo) {
  header('Location: Login.html');
  exit();
}

// Get cart items with product details
$cartItemsQuery = $conn->query("
  SELECT c.product_id, c.quantity, p.name, p.price, p.image_path, p.farmer_id, p.quantity as available_qty,
         f.username as farmer_name
  FROM cart c
  JOIN products p ON c.product_id = p.id
  JOIN farmer_users f ON p.farmer_id = f.id
  WHERE c.buyer_id = ".$buyer_id."
");

if (!$cartItemsQuery || $cartItemsQuery->num_rows == 0) {
  header('Location: cart.php?error=empty_cart');
  exit();
}

// Validate stock availability and prepare items
$stockErrors = [];
$items = [];
$subtotal = 0;

while ($item = $cartItemsQuery->fetch_assoc()) {
  // Check stock
  if ($item['quantity'] > $item['available_qty']) {
    $stockErrors[] = "Insufficient stock for " . $item['name'];
  } else {
    // Calculate totals - price as entered by farmer (per kg)
    $pricePerKg = (float)$item['price'];
    $itemTotal = $pricePerKg * $item['quantity'];
    $subtotal += $itemTotal;
    $items[] = $item;
  }
}

if (!empty($stockErrors)) {
  $errorMsg = '&error=' . urlencode(implode(', ', $stockErrors));
  header('Location: cart.php?error=insufficient_stock' . $errorMsg);
  exit();
}

$tax = $subtotal * 0.05; // 5% tax
$total = $subtotal + $tax;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment - AgriFarm</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    body {
      background: linear-gradient(135deg, #2e7d32 0%, #4CAF50 100%);
      min-height: 100vh;
      padding: 20px 0;
    }
    .payment-container {
      background: white;
      border-radius: 15px;
      box-shadow: 0 10px 40px rgba(0,0,0,0.1);
      padding: 30px;
      margin-top: 20px;
      margin-bottom: 20px;
    }
    .payment-card {
      border: 2px solid #e0e0e0;
      border-radius: 10px;
      padding: 20px;
      margin-bottom: 20px;
      transition: all 0.3s;
    }
    .payment-card:hover {
      border-color: #4CAF50;
      box-shadow: 0 4px 15px rgba(76, 175, 80, 0.2);
    }
    .payment-card.selected {
      border-color: #4CAF50;
      background: #f1f8f4;
    }
    .payment-icon {
      font-size: 2.5rem;
      color: #4CAF50;
      margin-bottom: 10px;
    }
    .order-summary {
      background: #f8f9fa;
      border-radius: 10px;
      padding: 20px;
      position: sticky;
      top: 20px;
    }
    .product-item {
      display: flex;
      align-items: center;
      padding: 10px 0;
      border-bottom: 1px solid #e0e0e0;
    }
    .product-item:last-child {
      border-bottom: none;
    }
    .product-image {
      width: 60px;
      height: 60px;
      object-fit: cover;
      border-radius: 8px;
      margin-right: 15px;
    }
    .card-input {
      display: none;
    }
    .card-input:checked + .payment-card {
      border-color: #4CAF50;
      background: #f1f8f4;
    }
    .mock-card {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border-radius: 12px;
      padding: 20px;
      margin-top: 15px;
      font-family: 'Courier New', monospace;
    }
    .card-number {
      font-size: 1.5rem;
      letter-spacing: 3px;
      margin: 15px 0;
    }
    .card-details {
      display: flex;
      justify-content: space-between;
      margin-top: 15px;
    }
    .form-group {
      margin-bottom: 20px;
    }
    .form-group label {
      font-weight: 600;
      color: #333;
      margin-bottom: 8px;
      display: block;
    }
    .form-control:focus {
      border-color: #4CAF50;
      box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
    }
    .btn-pay {
      background: linear-gradient(135deg, #2e7d32 0%, #4CAF50 100%);
      border: none;
      padding: 15px 30px;
      font-size: 1.1rem;
      font-weight: 600;
      border-radius: 8px;
      width: 100%;
      color: white;
      transition: all 0.3s;
    }
    .btn-pay:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 20px rgba(76, 175, 80, 0.4);
      color: white;
    }
    .security-badge {
      text-align: center;
      color: #666;
      font-size: 0.9rem;
      margin-top: 15px;
    }
    .security-badge i {
      color: #4CAF50;
      margin-right: 5px;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="payment-container">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-credit-card text-success"></i> Payment</h2>
        <a href="cart.php" class="btn btn-outline-secondary">
          <i class="fas fa-arrow-left"></i> Back to Cart
        </a>
      </div>

      <div class="row">
        <!-- Payment Methods -->
        <div class="col-lg-8">
          <h4 class="mb-4">Select Payment Method</h4>
          
          <form id="paymentForm" method="POST" action="process_payment.php">
            <!-- Credit/Debit Card -->
            <div class="mb-3">
              <input type="radio" name="payment_method" value="card" id="card" class="card-input" checked>
              <label for="card" class="payment-card selected">
                <div class="d-flex align-items-center">
                  <div class="payment-icon">
                    <i class="fas fa-credit-card"></i>
                  </div>
                  <div class="ms-3">
                    <h5 class="mb-1">Credit/Debit Card</h5>
                    <small class="text-muted">Visa, Mastercard, RuPay</small>
                  </div>
                </div>
              </label>
            </div>

            <!-- Card Details Form -->
            <div id="cardDetails" class="mt-4">
              <div class="mock-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <i class="fas fa-credit-card fa-2x"></i>
                  <span style="font-size: 0.9rem;">VALID THRU</span>
                </div>
                <div class="card-number" id="displayCardNumber">**** **** **** ****</div>
                <div class="card-details">
                  <div>
                    <div style="font-size: 0.8rem; opacity: 0.8;">CARDHOLDER NAME</div>
                    <div id="displayCardName" style="text-transform: uppercase;">YOUR NAME</div>
                  </div>
                  <div>
                    <div style="font-size: 0.8rem; opacity: 0.8;">EXPIRES</div>
                    <div id="displayCardExpiry">MM/YY</div>
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label for="card_number"><i class="fas fa-credit-card"></i> Card Number</label>
                <input type="text" class="form-control" id="card_number" name="card_number" 
                       placeholder="1234 5678 9012 3456" maxlength="19" inputmode="numeric" required>
                <small class="text-muted">Enter any 16-digit number</small>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="card_name"><i class="fas fa-user"></i> Cardholder Name</label>
                    <input type="text" class="form-control" id="card_name" name="card_name" 
                           placeholder="Name" required>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label for="card_expiry"><i class="fas fa-calendar"></i> Expiry Date</label>
                    <input type="text" class="form-control" id="card_expiry" name="card_expiry" 
                           placeholder="MM/YY" maxlength="5" required>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label for="card_cvv"><i class="fas fa-lock"></i> CVV</label>
                    <input type="text" class="form-control" id="card_cvv" name="card_cvv" 
                           placeholder="123" maxlength="4" required>
                  </div>
                </div>
              </div>
            </div>

            <!-- UPI Payment -->
            <div class="mb-3">
              <input type="radio" name="payment_method" value="upi" id="upi" class="card-input">
              <label for="upi" class="payment-card">
                <div class="d-flex align-items-center">
                  <div class="payment-icon">
                    <i class="fas fa-mobile-alt"></i>
                  </div>
                  <div class="ms-3">
                    <h5 class="mb-1">UPI</h5>
                    <small class="text-muted">Google Pay, PhonePe, Paytm</small>
                  </div>
                </div>
              </label>
            </div>

            <div id="upiDetails" class="mt-4" style="display: none;">
              <div class="form-group">
                <label for="upi_id"><i class="fas fa-mobile-alt"></i> UPI ID</label>
                <input type="text" class="form-control" id="upi_id" name="upi_id" 
                       placeholder="yourname@upi" disabled>
                <small class="text-muted">Enter any UPI ID</small>
              </div>
            </div>

            <!-- Cash on Delivery -->
            <div class="mb-3">
              <input type="radio" name="payment_method" value="cod" id="cod" class="card-input">
              <label for="cod" class="payment-card">
                <div class="d-flex align-items-center">
                  <div class="payment-icon">
                    <i class="fas fa-money-bill-wave"></i>
                  </div>
                  <div class="ms-3">
                    <h5 class="mb-1">Cash on Delivery</h5>
                    <small class="text-muted">Pay when you receive</small>
                  </div>
                </div>
              </label>
            </div>

            <button type="submit" class="btn btn-pay mt-4">
              <i class="fas fa-lock"></i> Pay ₹<?php echo number_format($total, 2); ?>
            </button>

            <div class="security-badge">
              <i class="fas fa-shield-alt"></i> Secure Payment
            </div>
          </form>
        </div>

        <!-- Order Summary -->
        <div class="col-lg-4">
          <div class="order-summary">
            <h5 class="mb-4"><i class="fas fa-receipt"></i> Order Summary</h5>
            
            <div class="mb-3">
              <strong>Buyer:</strong> <?php echo esc($buyerInfo['username']); ?><br>
              <small class="text-muted"><?php echo esc($buyerInfo['email']); ?></small>
            </div>
            
            <hr>
            
            <h6 class="mb-3">Items (<?php echo count($items); ?>)</h6>
            <?php foreach ($items as $item): 
              $pricePerKg = (float)$item['price']; // Price as entered by farmer (per kg)
              $itemTotal = $pricePerKg * $item['quantity'];
            ?>
              <div class="product-item">
                <?php if (!empty($item['image_path'])): ?>
                  <img src="<?php echo esc($item['image_path']); ?>" alt="<?php echo esc($item['name']); ?>" class="product-image">
                <?php else: ?>
                  <div class="product-image bg-light d-flex align-items-center justify-content-center">
                    <i class="fas fa-image text-muted"></i>
                  </div>
                <?php endif; ?>
                <div class="flex-grow-1">
                  <div class="fw-bold"><?php echo esc($item['name']); ?></div>
                  <small class="text-muted">
                    <?php echo number_format($item['quantity'], 2); ?> kg × ₹<?php echo number_format($pricePerKg, 2); ?>/kg
                  </small>
                  <div class="text-success fw-bold mt-1">₹<?php echo number_format($itemTotal, 2); ?></div>
                </div>
              </div>
            <?php endforeach; ?>
            
            <hr>
            
            <div class="d-flex justify-content-between mb-2">
              <span>Subtotal:</span>
              <span>₹<?php echo number_format($subtotal, 2); ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span>Tax (5%):</span>
              <span>₹<?php echo number_format($tax, 2); ?></span>
            </div>
            <hr>
            <div class="d-flex justify-content-between mb-3">
              <strong>Total:</strong>
              <strong class="text-success" style="font-size: 1.3rem;">₹<?php echo number_format($total, 2); ?></strong>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Format card number input
    document.getElementById('card_number').addEventListener('input', function(e) {
      // allow only digits and format in groups of 4
      let value = e.target.value.replace(/\D/g, '').slice(0, 16);
      let formatted = value.match(/.{1,4}/g)?.join(' ') || value;
      e.target.value = formatted;
      document.getElementById('displayCardNumber').textContent = formatted || '**** **** **** ****';
    });

    // Format expiry date
    document.getElementById('card_expiry').addEventListener('input', function(e) {
      let value = e.target.value.replace(/\D/g, '');
      if (value.length > 4) value = value.substring(0, 4);
      // Ensure month 01-12
      if (value.length >= 1) {
        let mm = value.substring(0, Math.min(2, value.length));
        if (mm.length === 1 && parseInt(mm, 10) > 1) {
          mm = '0' + mm;
        }
        if (mm.length === 2) {
          let month = parseInt(mm, 10);
          if (month === 0) mm = '01';
          if (month > 12) mm = '12';
        }
        value = mm + value.substring(mm.length);
      }
      if (value.length >= 3) {
        value = value.substring(0, 2) + '/' + value.substring(2, 4);
      }
      e.target.value = value;
      document.getElementById('displayCardExpiry').textContent = value || 'MM/YY';
    });

    // Update card name display
    document.getElementById('card_name').addEventListener('input', function(e) {
      document.getElementById('displayCardName').textContent = e.target.value.toUpperCase() || 'YOUR NAME';
    });

    // Show/hide payment method details
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
      radio.addEventListener('change', function() {
        // Update card selection styling
        document.querySelectorAll('.payment-card').forEach(card => {
          card.classList.remove('selected');
        });
        this.closest('.mb-3').querySelector('.payment-card').classList.add('selected');

        // Show/hide form fields
        if (this.value === 'card') {
          document.getElementById('cardDetails').style.display = 'block';
          document.getElementById('upiDetails').style.display = 'none';
          document.getElementById('upi_id').disabled = true;
          document.getElementById('card_number').required = true;
          document.getElementById('card_name').required = true;
          document.getElementById('card_expiry').required = true;
          document.getElementById('card_cvv').required = true;
        } else if (this.value === 'upi') {
          document.getElementById('cardDetails').style.display = 'none';
          document.getElementById('upiDetails').style.display = 'block';
          document.getElementById('upi_id').disabled = false;
          document.getElementById('card_number').required = false;
          document.getElementById('card_name').required = false;
          document.getElementById('card_expiry').required = false;
          document.getElementById('card_cvv').required = false;
        } else {
          document.getElementById('cardDetails').style.display = 'none';
          document.getElementById('upiDetails').style.display = 'none';
          document.getElementById('upi_id').disabled = true;
          document.getElementById('card_number').required = false;
          document.getElementById('card_name').required = false;
          document.getElementById('card_expiry').required = false;
          document.getElementById('card_cvv').required = false;
        }
      });
    });

    // Form validation
    document.getElementById('paymentForm').addEventListener('submit', function(e) {
      const method = document.querySelector('input[name="payment_method"]:checked').value;
      
      if (method === 'card') {
        const cardNumber = document.getElementById('card_number').value.replace(/\s/g, '');
        if (cardNumber.length < 16) {
          e.preventDefault();
          alert('Please enter a valid 16-digit card number');
          return false;
        }
        const expiry = document.getElementById('card_expiry').value.trim();
        if (!/^\d{2}\/\d{2}$/.test(expiry)) {
          e.preventDefault();
          alert('Please enter a valid expiry date (MM/YY)');
          return false;
        }
        // Validate not in the past and month range
        const [mmStr, yyStr] = expiry.split('/');
        const mm = parseInt(mmStr, 10);
        const yy = parseInt(yyStr, 10);
        if (isNaN(mm) || isNaN(yy) || mm < 1 || mm > 12) {
          e.preventDefault();
          alert('Expiry month must be between 01 and 12');
          return false;
        }
        const now = new Date();
        const currentYear = now.getFullYear() % 100; // YY format
        const currentMonth = now.getMonth() + 1; // 1-12
        if (yy < currentYear || (yy === currentYear && mm < currentMonth)) {
          e.preventDefault();
          alert('Your card has expired. Please use a valid card.');
          return false;
        }
        const cvv = document.getElementById('card_cvv').value;
        if (cvv.length < 3) {
          e.preventDefault();
          alert('Please enter a valid CVV');
          return false;
        }
      } else if (method === 'upi') {
        const upiId = document.getElementById('upi_id').value.trim();
        // Validate: handle@provider (letters, numbers, ., -, _ allowed in handle) and provider must be in allow-list
        const upiFormat = /^[a-zA-Z0-9.\-_]{2,}@[a-zA-Z][a-zA-Z0-9.\-]{1,}$/;
        const allowedProviders = [
          'okicici','oksbi','okaxis','okhdfcbank','ybl','ibl','axl','apl','idfcbank','paytm','upi','sbi','hdfcbank','icici','axisbank'
        ];
        if (!upiFormat.test(upiId)) {
          e.preventDefault();
          alert('Please enter a valid UPI ID (e.g., yourname@okicici)');
          return false;
        }
        const provider = upiId.split('@')[1]?.toLowerCase() || '';
        if (!allowedProviders.includes(provider)) {
          e.preventDefault();
          alert('Please use a supported UPI handle (e.g., @okicici, @oksbi, @okaxis, @okhdfcbank, @ybl, @paytm)');
          return false;
        }
      }
    });
  </script>
</body>
</html>

