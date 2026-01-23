<?php
require_once __DIR__ . '/../../db.php';

if (!function_exists('calculateBuyerQuota')) {
  function calculateBuyerQuota(float $originalQty): float {
    if ($originalQty <= 0) {
      return 0.0;
    }
    if ($originalQty < 1) {
      return round($originalQty, 1);
    }
    $limit = $originalQty * 0.4;
    $limit = floor($limit * 10) / 10;
    if ($limit <= 0) {
      $limit = min($originalQty, 0.1);
    }
    return round($limit, 1);
  }
}
if (!isset($_SESSION['buyer_id'])) { header('Location: ../login/Login.html'); exit(); }

if (!isset($_SESSION['cart'])) {
  $_SESSION['cart'] = [];
}

addColumnIfNotExists($conn, 'orders', 'payment_method', 'VARCHAR(50) DEFAULT NULL AFTER remaining_quantity');
addColumnIfNotExists($conn, 'orders', 'payment_status', "ENUM('pending','completed','failed') DEFAULT 'pending' AFTER payment_method");
addColumnIfNotExists($conn, 'orders', 'payment_transaction_id', 'VARCHAR(100) DEFAULT NULL AFTER payment_status');

$productSales = [];
$salesRes = $conn->query("SELECT product_id, SUM(quantity) AS total_sold FROM orders WHERE payment_status='completed' GROUP BY product_id");
if ($salesRes) {
  while ($sale = $salesRes->fetch_assoc()) {
    $productSales[(int)$sale['product_id']] = (float)$sale['total_sold'];
  }
}

$buyerPurchases = [];
$buyerPurchaseStmt = $conn->prepare("SELECT product_id, SUM(quantity) AS total_qty FROM orders WHERE payment_status='completed' AND buyer_id = ? GROUP BY product_id");
if ($buyerPurchaseStmt) {
  $buyerPurchaseStmt->bind_param('i', $_SESSION['buyer_id']);
  $buyerPurchaseStmt->execute();
  $bpRes = $buyerPurchaseStmt->get_result();
  while ($bp = $bpRes->fetch_assoc()) {
    $buyerPurchases[(int)$bp['product_id']] = (float)$bp['total_qty'];
  }
  $buyerPurchaseStmt->close();
}

$cartQuantities = [];
$cartQtyStmt = $conn->prepare("SELECT product_id, quantity FROM cart WHERE buyer_id = ?");
if ($cartQtyStmt) {
  $cartQtyStmt->bind_param('i', $_SESSION['buyer_id']);
  $cartQtyStmt->execute();
  $cartQtyRes = $cartQtyStmt->get_result();
  while ($cq = $cartQtyRes->fetch_assoc()) {
    $cartQuantities[(int)$cq['product_id']] = (float)$cq['quantity'];
  }
  $cartQtyStmt->close();
}

$buyer_id = $_SESSION['buyer_id'];
$cartCountRes = $conn->query("SELECT COUNT(*) AS cnt FROM cart WHERE buyer_id=".(int)$buyer_id);
$cartCount = $cartCountRes ? $cartCountRes->fetch_assoc()['cnt'] : 0;

$buyerInfoRes = $conn->query("SELECT username, email FROM buyer_users WHERE id=".(int)$buyer_id." LIMIT 1");
$buyerInfo = $buyerInfoRes ? $buyerInfoRes->fetch_assoc() : null;
$buyerHeaderName = $buyerInfo['username'] ?? 'Buyer';

if (isset($_POST['submit_rating']) && is_numeric($_POST['product_id'])) {
  $product_id = (int)$_POST['product_id'];
  $rating = (int)$_POST['rating'];
  $review = trim($_POST['review'] ?? '');
  
  $purchaseCheck = $conn->query("SELECT id FROM orders WHERE buyer_id = ".(int)$buyer_id." AND product_id = ".$product_id." AND payment_status = 'completed' LIMIT 1");
  
  if (!$purchaseCheck || $purchaseCheck->num_rows == 0) {
    header('Location: buyer_home.php?error=rating_requires_purchase');
    exit();
  }
  
  if ($rating >= 1 && $rating <= 5) {
    $stmt = $conn->prepare("INSERT INTO product_ratings (product_id, buyer_id, rating, review) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE rating = ?, review = ?");
    $stmt->bind_param("iiisss", $product_id, $buyer_id, $rating, $review, $rating, $review);
    $stmt->execute();
    header('Location: buyer_home.php?rated=1');
    exit();
  }
}

if (isset($_POST['submit_feedback'])) {
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $subject = trim($_POST['subject'] ?? '');
  $message = trim($_POST['message'] ?? '');
  $rating = isset($_POST['feedback_rating']) ? (int)$_POST['feedback_rating'] : null;
  
  if (!empty($name) && !empty($email) && !empty($subject) && !empty($message)) {
    $stmt = $conn->prepare("INSERT INTO website_feedback (buyer_id, name, email, subject, message, rating) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssi", $buyer_id, $name, $email, $subject, $message, $rating);
    $stmt->execute();
    header('Location: buyer_home.php?feedback=1');
    exit();
  }
}

if (isset($_POST['add_to_cart']) && is_numeric($_POST['product_id'])) {
  $product_id = (int)$_POST['product_id'];
  $quantity_kg = (float)($_POST['quantity_kg'] ?? 1);

  if ($quantity_kg <= 0) {
    header('Location: buyer_home.php?error=invalid_quantity');
    exit();
  }

  if (round($quantity_kg * 10) != $quantity_kg * 10) {
    header('Location: buyer_home.php?error=invalid_quantity_increment');
    exit();
  }

  $productStmt = $conn->prepare("SELECT name, quantity FROM products WHERE id = ? AND status='approved'");
  $productStmt->bind_param('i', $product_id);
  $productStmt->execute();
  $productRes = $productStmt->get_result();
  $product = $productRes ? $productRes->fetch_assoc() : null;
  $productStmt->close();

  if (!$product) {
    header('Location: buyer_home.php?error=invalid_product');
    exit();
  }

  $availableQty = (float)$product['quantity'];
  if ($availableQty <= 0) {
    header('Location: buyer_home.php?error=out_of_stock&product=' . urlencode($product['name']));
    exit();
  }

  if ($availableQty + 1e-6 < $quantity_kg) {
    header('Location: buyer_home.php?error=limited_stock&product=' . urlencode($product['name']) . '&remaining=' . urlencode(number_format($availableQty, 1, '.', '')));
    exit();
  }

  $soldQty = $productSales[$product_id] ?? 0.0;
  $originalQty = $availableQty + $soldQty;
  $perBuyerLimit = calculateBuyerQuota($originalQty);
  $buyerPurchasedQty = $buyerPurchases[$product_id] ?? 0.0;
  $remainingBuyerLimit = max($perBuyerLimit - $buyerPurchasedQty, 0.0);

  $checkCart = $conn->prepare("SELECT id, quantity FROM cart WHERE buyer_id = ? AND product_id = ?");
  $checkCart->bind_param("ii", $buyer_id, $product_id);
  $checkCart->execute();
  $cartResult = $checkCart->get_result();
  $existingCart = $cartResult->num_rows > 0 ? $cartResult->fetch_assoc() : null;
  $checkCart->close();

  $cartQty = $existingCart ? (float)$existingCart['quantity'] : 0.0;
  $remainingAfterCart = max($remainingBuyerLimit - $cartQty, 0.0);

  $effectiveLimit = min($remainingAfterCart, $availableQty);
  $limitDisplay = number_format($perBuyerLimit, 1, '.', '');
  $remainingDisplay = number_format(max($effectiveLimit, 0.0), 1, '.', '');

  if ($remainingBuyerLimit <= 0.0001) {
    header('Location: buyer_home.php?error=buyer_limit_reached&product=' . urlencode($product['name']) . '&limit=' . urlencode($limitDisplay));
    exit();
  }

  if ($remainingAfterCart <= 0.0001) {
    header('Location: buyer_home.php?error=cart_limit_reached&product=' . urlencode($product['name']) . '&limit=' . urlencode($limitDisplay));
    exit();
  }

  if ($quantity_kg - $effectiveLimit > 1e-6) {
    header('Location: buyer_home.php?error=limit_exceeded&product=' . urlencode($product['name']) . '&limit=' . urlencode($remainingDisplay));
    exit();
  }

  $newQty = $cartQty + $quantity_kg;

  if (round($newQty * 10) != $newQty * 10) {
    header('Location: buyer_home.php?error=invalid_total_quantity');
    exit();
  }

  if ($newQty - $availableQty > 1e-6) {
    header('Location: buyer_home.php?error=limited_stock&product=' . urlencode($product['name']) . '&remaining=' . urlencode(number_format($availableQty, 1, '.', '')));
    exit();
  }

  if ($newQty - $remainingBuyerLimit > 1e-6) {
    header('Location: buyer_home.php?error=limit_exceeded&product=' . urlencode($product['name']) . '&limit=' . urlencode($limitDisplay));
    exit();
  }

  if ($existingCart) {
    $updateCart = $conn->prepare("UPDATE cart SET quantity = ? WHERE buyer_id = ? AND product_id = ?");
    $updateCart->bind_param("dii", $newQty, $buyer_id, $product_id);
    $updateCart->execute();
    $updateCart->close();
  } else {
    $insertCart = $conn->prepare("INSERT INTO cart (buyer_id, product_id, quantity) VALUES (?, ?, ?)");
    $insertCart->bind_param("iid", $buyer_id, $product_id, $quantity_kg);
    $insertCart->execute();
    $insertCart->close();
  }
  header('Location: buyer_home.php?added=1');
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Buyer Home</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="buyer_home.css" />
  <link rel="stylesheet" href="../../responsive_menu.css" />
</head>
<body>
  <div class="header-bar">
    <div class="header-left">
      <div class="basket-icon">
        <i class="fas fa-shopping-basket"></i>
      </div>
      <div class="header-title">
        <h1>Buyer Marketplace</h1>
        <p>Fresh farm products at your fingertips</p>
      </div>
    </div>
    <div class="header-right">
      <a href="buyer_bidding.php" class="btn-bidding">
        <i class="fas fa-gavel"></i> Bidding
      </a>
      <a href="buyer_product_rating.php" class="btn-bidding" style="background: #ff9800;">
        <i class="fas fa-star"></i> Product Rating
      </a>
      <a href="buyer_website_rating.php" class="btn-bidding" style="background: #8e24aa;">
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
          <i class="fas fa-user-circle"></i>
          <span><?php echo esc($buyerHeaderName); ?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="buyerProfileDropdown">
          <li>
            <button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#buyerProfileModal">
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

  <div class="main-content">

    <?php if (isset($_GET['added'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> Product added to cart successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['rated'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> Thank you for your rating!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> 
        <?php 
        if ($_GET['error'] == 'invalid_quantity') {
          echo 'Invalid quantity selected!';
        } elseif ($_GET['error'] == 'invalid_quantity_increment') {
          echo 'Quantity must be entered in 0.1 kg increments (e.g., 0.1, 0.5, 1.0, 1.5 kg).';
        } elseif ($_GET['error'] == 'invalid_total_quantity') {
          echo 'Total quantity must remain in 0.1 kg increments!';
        } elseif ($_GET['error'] == 'insufficient_stock') {
          echo 'Insufficient stock available!';
        } elseif ($_GET['error'] == 'limited_stock') {
          $productName = esc($_GET['product'] ?? 'this product');
          $remaining = esc($_GET['remaining'] ?? '0');
          echo "Only {$remaining} kg of {$productName} is available right now.";
        } elseif ($_GET['error'] == 'limit_exceeded') {
          $productName = esc($_GET['product'] ?? 'this product');
          $limit = esc($_GET['limit'] ?? '0');
          echo "Purchase limit reached! You can buy up to {$limit} kg of {$productName} right now.";
        } elseif ($_GET['error'] == 'buyer_limit_reached') {
          $productName = esc($_GET['product'] ?? 'this product');
          $limit = esc($_GET['limit'] ?? '0');
          echo "You have already purchased {$limit} kg of {$productName}, which is the maximum allowed (40% of total stock).";
        } elseif ($_GET['error'] == 'cart_limit_reached') {
          $productName = esc($_GET['product'] ?? 'this product');
          $limit = esc($_GET['limit'] ?? '0');
          echo "Your cart already contains {$limit} kg of {$productName}. Remove some quantity to add different amounts.";
        } elseif ($_GET['error'] == 'already_purchased') {
          $productName = esc($_GET['product'] ?? 'this product');
          echo "You have already purchased {$productName}. Each buyer can purchase a product only once (up to 40%).";
        } elseif ($_GET['error'] == 'out_of_stock') {
          $productName = esc($_GET['product'] ?? 'this product');
          echo "{$productName} is currently out of stock.";
        } elseif ($_GET['error'] == 'invalid_product') {
          echo 'Selected product could not be found.';
        } elseif ($_GET['error'] == 'rating_requires_purchase') {
          echo 'You can only rate products you have purchased!';
        }
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <?php
      $statRes = $conn->query("SELECT COUNT(*) AS cnt, MIN(price) AS minp, MAX(price) AS maxp FROM products WHERE status='approved'");
      $stats = $statRes ? $statRes->fetch_assoc() : ['cnt'=>0,'minp'=>0,'maxp'=>0];
      $farmerCountRes = $conn->query("SELECT COUNT(DISTINCT farmer_id) AS fcnt FROM products WHERE status='approved'");
      $farmerStats = $farmerCountRes ? $farmerCountRes->fetch_assoc() : ['fcnt'=>0];
      $comparisonProducts = [];
      $comparisonQuery = $conn->query("SELECT DISTINCT name FROM products WHERE status='approved' ORDER BY name");
      if ($comparisonQuery) {
        while ($prod = $comparisonQuery->fetch_assoc()) {
          $comparisonProducts[] = $prod['name'];
        }
      }
    ?>
    <div class="stats-container">
      <div class="stat-card lowest">
        <div class="stat-info">
          <div class="stat-label">Lowest Price</div>
          <div class="stat-value">₹<?php echo esc(number_format((float)($stats['minp'] ?? 0), 2)); ?></div>
        </div>
        <div class="stat-icon"><i class="fas fa-arrow-down"></i></div>
      </div>
      <div class="stat-card highest">
        <div class="stat-info">
          <div class="stat-label">Highest Price</div>
          <div class="stat-value">₹<?php echo esc(number_format((float)($stats['maxp'] ?? 0), 2)); ?></div>
        </div>
        <div class="stat-icon"><i class="fas fa-arrow-up"></i></div>
      </div>
      <div class="stat-card farmers">
        <div class="stat-info">
          <div class="stat-label">Active Farmers</div>
          <div class="stat-value"><?php echo (int)($farmerStats['fcnt'] ?? 0); ?></div>
        </div>
        <div class="stat-icon"><i class="fas fa-users"></i></div>
      </div>
    </div>

    <!-- Search and Filter Bar -->
    <form method="get" id="catalogSearchForm" class="search-filter-bar">
      <div class="search-section">
        <label class="search-label">Search Products</label>
        <div class="search-input-wrapper">
          <i class="fas fa-search"></i>
          <input type="text" name="q" placeholder="Search by name..." value="<?php echo esc($_GET['q'] ?? ''); ?>">
        </div>
      </div>
      <div class="sort-section">
        <div class="sort-label-wrapper">
          <label class="sort-label">Sort By</label>
          <select name="sort">
            <option value="new" <?php echo (($_GET['sort'] ?? '')==='new')?'selected':''; ?>>Newest</option>
            <option value="price_asc" <?php echo (($_GET['sort'] ?? '')==='price_asc')?'selected':''; ?>>Price: Low to High</option>
            <option value="price_desc" <?php echo (($_GET['sort'] ?? '')==='price_desc')?'selected':''; ?>>Price: High to Low</option>
          </select>
        </div>
        <button class="btn-filter" type="submit">
          <i class="fas fa-chevron-down"></i> Filter
        </button>
      </div>
    </form>

    <div class="pro" id="productGrid">
      <?php
      $q = trim($_GET['q'] ?? '');
      $sort = $_GET['sort'] ?? 'new';
      $order = 'p.id DESC';
      if ($sort === 'price_asc') { $order = 'p.price ASC'; }
      if ($sort === 'price_desc') { $order = 'p.price DESC'; }

      if ($q !== '') {
        $stmt = $conn->prepare("SELECT p.id, p.name, p.image_path, p.description, p.price, p.quantity, f.username AS farmer_name, f.mobile_number AS farmer_mobile FROM products p JOIN farmer_users f ON p.farmer_id=f.id WHERE p.status='approved' AND p.name LIKE CONCAT('%', ?, '%') ORDER BY $order");
        $stmt->bind_param('s', $q);
        $stmt->execute();
        $res = $stmt->get_result();
      } else {
        $res = $conn->query("SELECT p.id, p.name, p.image_path, p.description, p.price, p.quantity, f.username AS farmer_name, f.mobile_number AS farmer_mobile FROM products p JOIN farmer_users f ON p.farmer_id=f.id WHERE p.status='approved' ORDER BY $order");
      }
      if ($res && $res->num_rows > 0):
        while ($row = $res->fetch_assoc()): 
          $productPrice = (float)$row['price']; 
          $availableKg = max((float)$row['quantity'], 0);
          $soldQty = $productSales[(int)$row['id']] ?? 0.0;
          $originalQty = $availableKg + $soldQty;
          $perBuyerLimit = calculateBuyerQuota($originalQty);
          $buyerPurchased = $buyerPurchases[(int)$row['id']] ?? 0.0;
          $cartQty = $cartQuantities[(int)$row['id']] ?? 0.0;
          $remainingBuyerLimit = max($perBuyerLimit - $buyerPurchased, 0.0);
          $remainingAfterCart = max($remainingBuyerLimit - $cartQty, 0.0);
          $maxBuyerQty = min($availableKg, $remainingAfterCart);
          $maxBuyerQty = $maxBuyerQty < 0 ? 0 : $maxBuyerQty;
          $maxBuyerQty = round($maxBuyerQty, 1);
          $limitType = ($availableKg > 0 && $maxBuyerQty + 0.001 < $availableKg) ? 'quota' : 'stock';
          if ($remainingAfterCart <= 0 && $availableKg > 0) {
            $limitType = 'quota';
          }
          $isOutOfStock = $availableKg <= 0;
          $stepSize = $maxBuyerQty >= 1 ? 0.5 : 0.1;
          $defaultQty = $maxBuyerQty > 0 ? min($maxBuyerQty, max(1, $stepSize)) : 0;
          if ($defaultQty < $stepSize && $maxBuyerQty > 0) {
            $defaultQty = $maxBuyerQty;
          }
          $perBuyerLimitLabel = number_format($perBuyerLimit, 1, '.', '');
          $remainingLimitLabel = number_format(max($remainingAfterCart, 0), 1, '.', '');
          $maxAttr = number_format(max($maxBuyerQty, 0), 1, '.', '');
          $stepAttr = number_format($stepSize, 1, '.', '');
          $defaultAttr = number_format(max($defaultQty, 0), 1, '.', '');
          $minAttr = $maxBuyerQty > 0 ? $stepAttr : number_format(0, 1, '.', '');
          $noteClass = 'quantity-limit-note text-muted';
          if ($isOutOfStock || $remainingAfterCart <= 0) {
            $noteClass = 'quantity-limit-note text-danger';
          }
          if ($isOutOfStock) {
            $defaultAttr = number_format(0, 1, '.', '');
            $maxAttr = number_format(0, 1, '.', '');
            $minAttr = number_format(0, 1, '.', '');
            $limitType = 'stock';
          }
          $baseMessage = '';
          if ($isOutOfStock) {
            $baseMessage = 'Currently out of stock.';
          } elseif ($remainingAfterCart <= 0) {
            $baseMessage = $cartQty > 0
              ? 'You already planned ' . number_format($cartQty, 1) . ' kg in your cart (40% limit reached).'
              : 'You have already purchased the maximum 40% allocation for this product.';
          } elseif ($limitType === 'quota') {
            $baseMessage = 'Limit: up to ' . number_format(min($maxBuyerQty, $perBuyerLimit), 1) . ' kg per buyer (40% cap).';
          } else {
            $baseMessage = 'Only ' . number_format($availableKg, 1) . ' kg available.';
          }
          $limitSuffix = '';
          if ($limitType === 'quota') {
            $limitSuffix = ' (40% cap)';
          } elseif ($limitType === 'stock') {
            $limitSuffix = $isOutOfStock ? ' (out of stock)' : ' (limited stock)';
          }
          $canPurchase = !$isOutOfStock && $maxBuyerQty > 0;
        ?>
          <div class="col-xl-3 col-lg-3 col-md-3 col-sm-6 col-12">
            <div class="product-card">
            <div class="product-image-wrapper">
              <?php if (!empty($row['image_path'])): ?>
                <img src="<?php echo esc($row['image_path']); ?>" alt="<?php echo esc($row['name']); ?>">
              <?php else: ?>
                <div class="no-image">
                  <i class="fas fa-image fa-3x"></i>
                </div>
              <?php endif; ?>
              <span class="product-category-label"><?php echo esc(strtolower($row['name'])); ?></span>
              <?php if ($availableKg > 0): ?>
                <span class="in-stock-badge">In Stock</span>
              <?php endif; ?>
            </div>
            <div class="product-body">
              <div class="product-name"><?php echo esc($row['name']); ?></div>
              <div class="product-seller">by <?php echo esc($row['farmer_name']); ?></div>
              <div class="product-description"><?php echo esc($row['description'] ?: 'Fresh product from farm'); ?></div>
              <div class="product-price">₹<?php echo esc(number_format($productPrice, 2)); ?></div>

              <div class="quantity-section">
                <i class="fas fa-warehouse"></i>
                <span>Available: <?php echo esc(number_format($availableKg, 1)); ?> kg</span>
              </div>
              <div class="quantity-section">
                <i class="fas fa-user-check"></i>
                <span>
                  Limit per buyer: <?php echo esc($perBuyerLimitLabel); ?> kg<?php echo esc($limitSuffix); ?>
                  <small class="text-muted">| Remaining for you: <?php echo esc($remainingLimitLabel); ?> kg</small>
                </span>
              </div>
              
              <form method="POST" action="">
                <input type="hidden" name="product_id" value="<?php echo (int)$row['id']; ?>">
                <div class="quantity-controls">
                  <button type="button" class="qty-btn" onclick="decrementQty(<?php echo $row['id']; ?>)" <?php echo (!$canPurchase) ? 'disabled' : ''; ?>>-</button>
                  <input type="number" id="qty_<?php echo $row['id']; ?>" name="quantity_kg" value="<?php echo esc($defaultAttr); ?>" min="<?php echo esc($minAttr); ?>" step="<?php echo $stepAttr; ?>" max="<?php echo esc($maxAttr); ?>" data-step="<?php echo esc($stepAttr); ?>" data-max="<?php echo esc($maxAttr); ?>" data-available="<?php echo esc(number_format($availableKg, 1, '.', '')); ?>" data-product="<?php echo esc($row['name']); ?>" data-limit-type="<?php echo esc($limitType); ?>" data-base-message="<?php echo esc($baseMessage); ?>" data-remaining="<?php echo esc($remainingLimitLabel); ?>" data-total-limit="<?php echo esc($perBuyerLimitLabel); ?>" class="qty-input" <?php echo (!$canPurchase) ? 'disabled' : ''; ?> required>
                  <button type="button" class="qty-btn" onclick="incrementQty(<?php echo $row['id']; ?>)" <?php echo (!$canPurchase) ? 'disabled' : ''; ?>>+</button>
                  <span class="qty-unit">kg</span>
                </div>
                <div class="<?php echo $noteClass; ?>"><?php echo esc($baseMessage); ?></div>
                
                <div class="product-actions">
                  <button class="btn-view" type="button" data-bs-toggle="modal" data-bs-target="#pmodal<?php echo (int)$row['id']; ?>">
                    <i class="fas fa-eye"></i> View
                  </button>
                  <?php if ($canPurchase): ?>
                    <button class="btn-add-cart" type="submit" name="add_to_cart">
                      <i class="fas fa-cart-plus"></i> Add to Cart
                    </button>
                  <?php elseif ($availableKg > 0): ?>
                    <button class="btn-out-stock" type="button" disabled>
                      <i class="fas fa-ban"></i> Limit Reached
                    </button>
                  <?php else: ?>
                    <button class="btn-out-stock" type="button" disabled>
                      <i class="fas fa-times"></i> Out of Stock
                    </button>
                  <?php endif; ?>
                </div>
              </form>
            </div>
        </div>

            <div class="modal fade" id="pmodal<?php echo (int)$row['id']; ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title"><?php echo esc($row['name']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <?php if (!empty($row['image_path'])): ?>
                      <img src="<?php echo esc($row['image_path']); ?>" alt="<?php echo esc($row['name']); ?>" class="img-fluid rounded mb-3" />
                    <?php endif; ?>
                    <div class="mb-2"><strong>Seller:</strong> <?php echo esc($row['farmer_name']); ?></div>
                    <div class="mb-2"><strong>Available Quantity:</strong> <?php echo esc(number_format($availableKg, 2)); ?> kg</div>
                    <div class="mb-2"><strong>Price:</strong> ₹ <?php echo esc(number_format($productPrice, 2)); ?></div>
                    <?php
          
                    $ratingRes = $conn->query("SELECT AVG(rating) as avg_rating, COUNT(*) as total_ratings FROM product_ratings WHERE product_id=".(int)$row['id']);
                    $ratingInfo = $ratingRes ? $ratingRes->fetch_assoc() : ['avg_rating' => null, 'total_ratings' => 0];
                    $avgRating = $ratingInfo['avg_rating'] ? number_format($ratingInfo['avg_rating'], 1) : '0.0';
                    $totalRatings = $ratingInfo['total_ratings'] ?? 0;
                    ?>
                    <div class="mb-2">
                      <strong>Rating:</strong> 
                      <span class="rating-stars">
                        <?php 
                        $fullStars = floor($avgRating);
                        $hasHalf = ($avgRating - $fullStars) >= 0.5;
                        for ($i = 1; $i <= 5; $i++): 
                          if ($i <= $fullStars): ?>
                            <i class="fas fa-star"></i>
                          <?php elseif ($i == $fullStars + 1 && $hasHalf): ?>
                            <i class="fas fa-star-half-alt"></i>
                          <?php else: ?>
                            <i class="far fa-star empty"></i>
                          <?php endif;
                        endfor; ?>
                      </span>
                      <strong><?php echo $avgRating; ?></strong> (<?php echo $totalRatings; ?> reviews)
                    </div>
                    <p><?php echo esc($row['description']); ?></p>
                  </div>
                  <div class="modal-footer">
                    <?php if (!empty($row['farmer_mobile'])): ?>
                      <a href="tel:<?php echo esc($row['farmer_mobile']); ?>" class="btn btn-success">
                        <i class="fas fa-phone"></i> Contact: <?php echo esc($row['farmer_mobile']); ?>
                      </a>
                    <?php else: ?>
                      <button class="btn btn-secondary" disabled>Contact not available</button>
                    <?php endif; ?>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endwhile;
      else: ?>
        <div class="col-12">
          <div class="stat-card text-center" style="padding: 40px;">
            <i class="fas fa-info-circle" style="font-size: 3rem; color: #666; margin-bottom: 15px;"></i>
            <p style="color: #666; font-size: 1.1rem; margin: 0;">No products found. Try adjusting your search filters.</p>
          </div>
        </div>
      <?php endif; ?>
    </div>
</div>
    <!-- Price Comparison -->
    <div class="section-card" style="margin-top: 30px;">
      <div class="section-header" style="background: #c8e6c9; color: #2e7d32;">
        <i class="fas fa-chart-line"></i> Product Price Comparison
      </div>
      <div class="section-body">
        <div class="mb-3">
          <label class="form-label"><strong>Search Product to Compare or Purchase:</strong></label>
          <div class="input-group">
            <input type="text" id="compareSearchInput" class="form-control" list="compareProductsList" placeholder="Type product name..." autocomplete="off">
            <button class="btn btn-success" type="button" onclick="searchPriceComparison()">
              <i class="fas fa-chart-line"></i> Compare Prices
            </button>
            <button class="btn btn-outline-success" type="button" onclick="searchProductCatalog()">
              <i class="fas fa-shopping-basket"></i> Search &amp; Buy
            </button>
          </div>
          <datalist id="compareProductsList">
            <?php foreach ($comparisonProducts as $pname): ?>
              <option value="<?php echo esc($pname); ?>"></option>
            <?php endforeach; ?>
          </datalist>
          <small class="text-muted">Use Compare to view price insights or Search &amp; Buy to filter the catalog below.</small>
        </div>
        <div class="mb-3">
          <label class="form-label"><strong>Or choose from the approved list:</strong></label>
          <select id="compareProduct" class="form-control" onchange="loadPriceComparison()">
            <option value="">-- Select a product --</option>
            <?php foreach ($comparisonProducts as $pname): ?>
              <option value="<?php echo esc($pname); ?>"><?php echo esc($pname); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div id="priceComparisonResults"></div>
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
  <script>
    function getNumber(value, fallback) {
      const parsed = parseFloat(value);
      return Number.isFinite(parsed) ? parsed : fallback;
    }

    function getStep(input) {
      const stepAttr = getNumber(input.dataset.step || input.getAttribute('step'), 0.5);
      return stepAttr > 0 ? stepAttr : 0.5;
    }

    function formatQuantity(value) {
      return (Math.round(value * 10) / 10).toFixed(1);
    }

    function getLimitInfo(input) {
      return {
        max: getNumber(input.dataset.max ?? input.getAttribute('max'), 0),
        available: getNumber(input.dataset.available, 0),
        product: input.dataset.product || 'this product',
        limitType: input.dataset.limitType || 'quota',
        remaining: getNumber(input.dataset.remaining, 0),
        totalLimit: getNumber(input.dataset.totalLimit, 0)
      };
    }

    function buildLimitMessage(input) {
      const info = getLimitInfo(input);
      if (info.max <= 0 && info.available <= 0) {
        return 'Currently out of stock.';
      }
      if (info.remaining <= 0) {
        return `You have reached your 40% purchase limit for ${info.product}.`;
      }
      const remainingStr = (info.remaining > 0 ? info.remaining : info.max).toFixed(1);
      if (info.limitType === 'quota') {
        return `Limit: you can add up to ${remainingStr} kg of ${info.product} right now.`;
      }
      const availableStr = info.available > 0 ? info.available.toFixed(1) : remainingStr;
      return `Only ${availableStr} kg of ${info.product} is available right now.`;
    }

    function setQuantityMessage(input, message, isError = false) {
      if (!input) return;
      const note = input.closest('.quantity-controls')?.nextElementSibling;
      if (!note || !note.classList.contains('quantity-limit-note')) return;

      if (!message) {
        const base = input.dataset.baseMessage || '';
        note.textContent = base;
        note.classList.remove('text-danger');
        note.classList.remove('text-muted');
        if (base) {
          if (base.toLowerCase().includes('out of stock')) {
            note.classList.add('text-danger');
          } else {
            note.classList.add('text-muted');
          }
        }
        return;
      }

      note.textContent = message;
      if (isError) {
        note.classList.add('text-danger');
        note.classList.remove('text-muted');
      } else {
        note.classList.remove('text-danger');
        if (message) {
          note.classList.add('text-muted');
        }
      }
    }

    function decrementQty(productId) {
      const input = document.getElementById('qty_' + productId);
      if (!input) return;
      const step = getStep(input);
      const min = getNumber(input.getAttribute('min'), step);
      const current = getNumber(input.value, min);
      const next = current - step;
      input.value = formatQuantity(next >= min ? next : min);
      const info = getLimitInfo(input);
      if (info.max > 0 && parseFloat(input.value) >= info.max - 0.001) {
        setQuantityMessage(input, buildLimitMessage(input), true);
      } else {
        setQuantityMessage(input, '', false);
      }
    }

    function incrementQty(productId) {
      const input = document.getElementById('qty_' + productId);
      if (!input) return;
      const step = getStep(input);
      const info = getLimitInfo(input);
      const max = info.max > 0 ? info.max : step;
      const current = getNumber(input.value, step);

      if (current >= max - 0.001) {
        input.value = formatQuantity(max);
        setQuantityMessage(input, buildLimitMessage(input), true);
        if (info.limitType === 'quota') {
          alert('You can purchase only up to 40% of available stock for this product.');
        }
        return;
      }

      let next = current + step;
      if (next > max) {
        next = max;
      }

      input.value = formatQuantity(next);

      if (next >= max - 0.001) {
        setQuantityMessage(input, buildLimitMessage(input), true);
        if (info.limitType === 'quota') {
          alert('You can purchase only up to 40% of available stock for this product.');
        }
      } else {
        setQuantityMessage(input, '', false);
      }
    }
    document.addEventListener('DOMContentLoaded', function() {
      const inputs = document.querySelectorAll('input[name="quantity_kg"]');
      inputs.forEach(input => {
        input.addEventListener('change', function() {
          const step = getStep(this);
          const min = getNumber(this.getAttribute('min'), step);
          const info = getLimitInfo(this);
          const max = info.max > 0 ? info.max : step;
          let val = getNumber(this.value, min);

          if (val < min) val = min;
          if (val > max) val = max;

          val = Math.round((val - min) / step) * step + min;
          if (val > max) val = max;
          if (val < min) val = min;

          this.value = formatQuantity(val);
          if (max > 0 && val >= max - 0.001) {
            setQuantityMessage(this, buildLimitMessage(this), true);
            if (info.limitType === 'quota') {
              alert('You can purchase only up to 40% of available stock for this product.');
            }
          } else {
            setQuantityMessage(this, '', false);
          }
        });
      });

      // Prevent submit beyond 40% limit (or max) and show popup
      document.querySelectorAll('.product-card form').forEach(form => {
        form.addEventListener('submit', function(e) {
          const qtyInput = this.querySelector('input[name="quantity_kg"]');
          if (!qtyInput) return;
          const step = getStep(qtyInput);
          const info = getLimitInfo(qtyInput);
          const max = info.max > 0 ? info.max : step;
          const val = getNumber(qtyInput.value, 0);
          if (max > 0 && val - max > 1e-6 && info.limitType === 'quota') {
            e.preventDefault();
            alert('You can purchase only up to 40% of available stock for this product.');
            qtyInput.value = formatQuantity(max);
            setQuantityMessage(qtyInput, buildLimitMessage(qtyInput), true);
          }
        });
      });
    });

    // Price Comparison
    function loadPriceComparison(overrideName = '') {
      const select = document.getElementById('compareProduct');
      const searchInput = document.getElementById('compareSearchInput');
      const resultsDiv = document.getElementById('priceComparisonResults');
      let productName = typeof overrideName === 'string' ? overrideName.trim() : '';

      if (!productName && select) {
        productName = select.value.trim();
      }
      if (!productName && searchInput) {
        productName = searchInput.value.trim();
      }

      if (!resultsDiv) {
        return;
      }

      if (!productName) {
        resultsDiv.innerHTML = '<div class="alert alert-info mb-0">Select a product or use the search box to compare prices.</div>';
        return;
      }

      if (select) {
        const match = Array.from(select.options || []).find(opt => opt.value.toLowerCase() === productName.toLowerCase());
        select.value = match ? match.value : '';
        if (match) {
          productName = match.value;
        }
      }
      if (searchInput && searchInput.value.trim().toLowerCase() !== productName.toLowerCase()) {
        searchInput.value = productName;
      }

      resultsDiv.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-success" role="status"></div></div>';

      fetch('price_comparison_api.php?product=' + encodeURIComponent(productName))
        .then(response => response.json())
        .then(data => {
          if (data.error) {
            resultsDiv.innerHTML = '<div class="alert alert-warning">' + data.error + '</div>';
            return;
          }
          
          let html = '<div class="table-responsive"><table class="comparison-table table table-sm mb-0">';
          html += '<thead><tr><th>Farmer</th><th>Price (per kg)</th><th>Quantity Available</th><th>Difference</th><th>Status</th></tr></thead><tbody>';
          
          if (data.products && data.products.length > 0) {
            data.products.forEach(product => {
              const rowClass = product.is_lowest ? 'table-success' : (product.is_highest ? 'table-danger' : '');
              let diffHtml = '';
              if (product.is_lowest) {
                diffHtml = '<span class="badge bg-success">Best Price</span>';
              } else if (product.is_highest) {
                diffHtml = `<span class="badge bg-danger">+₹${product.price_diff} (+${product.price_diff_percent}%)</span>`;
              } else {
                diffHtml = `<span class="badge bg-warning text-dark">+₹${product.price_diff} (+${product.price_diff_percent}%)</span>`;
              }
              const stockBadge = product.in_stock
                ? '<span class="badge bg-success">In Stock</span>'
                : '<span class="badge bg-danger">Out of Stock</span>';
              html += `<tr class="${rowClass}">
                <td>${product.farmer_name}</td>
                <td>₹${product.price_per_kg}</td>
                <td>${product.quantity} kg</td>
                <td>${diffHtml}</td>
                <td>${stockBadge}</td>
              </tr>`;
            });
            
            html += '</tbody></table></div>';
            html += `
              <div class="mt-3 p-3 border rounded bg-light">
                <strong>${data.total_offers} offer(s) found for "<span class="text-success">${data.search_term}</span>"</strong><br>
                <span class="me-3"><i class="fas fa-arrow-down text-success"></i> Lowest: ₹${data.lowest}</span>
                <span class="me-3"><i class="fas fa-equals text-primary"></i> Market Avg: ₹${data.market_avg}</span>
                <span><i class="fas fa-arrow-up text-danger"></i> Highest: ₹${data.highest}</span>
                <div class="mt-2">
                  <button class="btn btn-sm btn-outline-success" type="button" onclick="scrollToProducts()">
                    <i class="fas fa-shopping-basket"></i> Browse products to purchase
                  </button>
                </div>
              </div>
            `;
          } else {
            html = '<div class="alert alert-info">No products found for comparison.</div>';
          }
          
          resultsDiv.innerHTML = html;
          resultsDiv.scrollIntoView({ behavior: 'smooth' });
        })
        .catch(error => {
          resultsDiv.innerHTML = '<div class="alert alert-danger">Error loading price comparison. Please try again.</div>';
        });
    }

    function searchPriceComparison() {
      const input = document.getElementById('compareSearchInput');
      const value = input ? input.value.trim() : '';
      if (!value) {
        if (input) input.focus();
        return;
      }
      loadPriceComparison(value);
    }

    function searchProductCatalog() {
      const input = document.getElementById('compareSearchInput');
      const value = input ? input.value.trim() : '';
      if (!value) {
        if (input) input.focus();
        return;
      }
      const catalogForm = document.getElementById('catalogSearchForm');
      if (catalogForm) {
        const searchField = catalogForm.querySelector('input[name="q"]');
        if (searchField) {
          searchField.value = value;
        } else {
          const hidden = document.createElement('input');
          hidden.type = 'hidden';
          hidden.name = 'q';
          hidden.value = value;
          catalogForm.appendChild(hidden);
        }
        catalogForm.submit();
      } else {
        window.location.href = 'buyer_home.php?q=' + encodeURIComponent(value) + '#productGrid';
      }
    }

    function scrollToProducts() {
      const grid = document.getElementById('productGrid');
      if (grid) {
        grid.scrollIntoView({ behavior: 'smooth' });
      }
    }

    // Product Rating
    function loadProductRating() {
      const productId = document.getElementById('rateProduct').value;
      const container = document.getElementById('ratingFormContainer');
      const ratingsDiv = document.getElementById('existingRatings');
      
      if (!productId) {
        container.innerHTML = '';
        ratingsDiv.innerHTML = '';
        return;
      }
      
      const option = document.querySelector('#rateProduct option[value="' + productId + '"]');
      const avgRating = option ? option.getAttribute('data-avg') : '0.0';
      const count = option ? option.getAttribute('data-count') : '0';
      
      //rating form
      container.innerHTML = `
        <div class="card p-3 mb-3" style="background: #f9f9f9;">
          <h6>Rate this Product</h6>
          <form method="POST" action="">
            <input type="hidden" name="product_id" value="${productId}">
            <div class="mb-3">
              <label class="form-label">Your Rating:</label>
              <div class="rating-input">
                <input type="radio" name="rating" value="5" id="r5_${productId}"><label for="r5_${productId}">★</label>
                <input type="radio" name="rating" value="4" id="r4_${productId}"><label for="r4_${productId}">★</label>
                <input type="radio" name="rating" value="3" id="r3_${productId}"><label for="r3_${productId}">★</label>
                <input type="radio" name="rating" value="2" id="r2_${productId}"><label for="r2_${productId}">★</label>
                <input type="radio" name="rating" value="1" id="r1_${productId}"><label for="r1_${productId}">★</label>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Your Review (Optional):</label>
              <textarea class="form-control" name="review" rows="3" placeholder="Share your experience..."></textarea>
            </div>
            <button type="submit" name="submit_rating" class="btn btn-success">
              <i class="fas fa-star"></i> Submit Rating
            </button>
          </form>
        </div>
        <div class="mb-3">
          <strong>Average Rating:</strong> 
          <span class="rating-stars">
            ${generateStars(avgRating)}
          </span>
          <strong>${avgRating}</strong> (${count} reviews)
        </div>
      `;
      
      fetch('product_ratings_api.php?product_id=' + productId)
        .then(response => response.json())
        .then(data => {
          if (data.reviews && data.reviews.length > 0) {
            let reviewsHtml = '<h6 class="mb-3">Recent Reviews:</h6>';
            data.reviews.forEach(review => {
              reviewsHtml += `
                <div class="review-item">
                  <div class="review-header">
                    <span class="review-author">${review.buyer_name}</span>
                    <span class="review-date">${review.date}</span>
                  </div>
                  <div class="rating-stars mb-2">${generateStars(review.rating)}</div>
                  ${review.review ? '<p class="mb-0">' + review.review + '</p>' : ''}
                </div>
              `;
            });
            ratingsDiv.innerHTML = reviewsHtml;
          } else {
            ratingsDiv.innerHTML = '<p class="text-muted">No reviews yet. Be the first to rate!</p>';
          }
        })
        .catch(error => {
          ratingsDiv.innerHTML = '';
        });
    }
    
    function generateStars(rating) {
      const num = parseFloat(rating);
      const full = Math.floor(num);
      const hasHalf = (num - full) >= 0.5;
      let html = '';
      for (let i = 1; i <= 5; i++) {
        if (i <= full) {
          html += '<i class="fas fa-star"></i>';
        } else if (i == full + 1 && hasHalf) {
          html += '<i class="fas fa-star-half-alt"></i>';
        } else {
          html += '<i class="far fa-star empty"></i>';
        }
      }
      return html;
    }
  </script>
  
  <footer class="dashboard-footer">
    <div class="footer-content">
      <div class="footer-bottom">
        <p>&copy; 2025 AgriFarm. All rights reserved.</p>
      </div>
    </div>
  </footer>
  <!-- buyer_refresh.js disabled to prevent accidental product grid overwrite during refresh -->
  <!-- <script src="buyer_refresh.js"></script> -->
</body>
</html>
<script>
  // Debug helper: log when productGrid is modified and show stack trace
  (function() {
    try {
      const grid = document.getElementById('productGrid');
      if (!grid) return;
      const mo = new MutationObserver((mutations) => {
        mutations.forEach(m => {
          if (m.type === 'childList') {
            console.warn('productGrid mutated. added:', m.addedNodes.length, 'removed:', m.removedNodes.length);
            console.trace();
          }
          if (m.type === 'attributes') {
            console.warn('productGrid attribute changed:', m.attributeName);
            console.trace();
          }
        });
      });
      mo.observe(grid, { childList: true, subtree: true, attributes: true });
      // Stop observing after 30s to avoid excessive logs
      setTimeout(() => mo.disconnect(), 30000);
    } catch (e) {
      console.debug('mutation observer setup failed', e);
    }
  })();
</script>