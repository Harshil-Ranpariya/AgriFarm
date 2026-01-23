<?php
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../email/email_functions.php';
if (!isset($_SESSION['farmer_id'])) { header('Location: Login.html'); exit(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $product_id = (int)($_POST['product_id'] ?? 0);
  $name = trim($_POST['name'] ?? '');
  $category = trim($_POST['category'] ?? '');
  $subcategory = trim($_POST['subcategory'] ?? '');
  $price = (float)($_POST['price'] ?? 0);
  $quantity = (float)($_POST['quantity'] ?? 0);
  $description = trim($_POST['description'] ?? '');
  
  if ($product_id <= 0 || $name === '' || $price <= 0 || $quantity < 0) {
    echo "<script>alert('Invalid product data'); window.history.back();</script>"; exit();
  }

  $check = $conn->prepare("SELECT id FROM products WHERE id = ? AND farmer_id = ?");
  $check->bind_param("ii", $product_id, $_SESSION['farmer_id']);
  $check->execute();
  if ($check->get_result()->num_rows === 0) {
    echo "<script>alert('Product not found'); window.history.back();</script>"; exit();
  }

  $imagePath = null;
  
  if (!empty($_FILES['image']['name'])) {
    $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
    if (in_array($_FILES['image']['type'], $allowed) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
      if (!is_dir(__DIR__ . '/uploads')) { @mkdir(__DIR__ . '/uploads', 0777, true); }
      $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
      $filename = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
      $dest = __DIR__ . '/uploads/' . $filename;
         if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
           // Use correct web path for uploaded images
           $imagePath = '/agrifarm/buyer_farmer-dashboard/farmer/uploads/' . $filename;
      }
    }
  }

  // Ensure category and subcategory columns exist
  $colRes = $conn->query("SHOW COLUMNS FROM products LIKE 'category'");
  if ($colRes && $colRes->num_rows === 0) {
    $conn->query("ALTER TABLE products ADD COLUMN category VARCHAR(50) NULL AFTER name");
  }
  $colRes = $conn->query("SHOW COLUMNS FROM products LIKE 'subcategory'");
  if ($colRes && $colRes->num_rows === 0) {
    $conn->query("ALTER TABLE products ADD COLUMN subcategory VARCHAR(50) NULL AFTER category");
  }
  
  // Update product and set status back to pending for admin approval
  if ($imagePath) {
    $stmt = $conn->prepare("UPDATE products SET name = ?, category = ?, subcategory = ?, description = ?, image_path = ?, price = ?, quantity = ?, status = 'pending' WHERE id = ? AND farmer_id = ?");
    $stmt->bind_param("sssssddii", $name, $category, $subcategory, $description, $imagePath, $price, $quantity, $product_id, $_SESSION['farmer_id']);
  } else {
    $stmt = $conn->prepare("UPDATE products SET name = ?, category = ?, subcategory = ?, description = ?, price = ?, quantity = ?, status = 'pending' WHERE id = ? AND farmer_id = ?");
    $stmt->bind_param("ssssddii", $name, $category, $subcategory, $description, $price, $quantity, $product_id, $_SESSION['farmer_id']);
  }
  
  if ($stmt->execute()) {
    // Check if quantity is 0 and send notification
    if ($quantity <= 0) {
      $farmerInfo = $conn->query("SELECT username, email FROM farmer_users WHERE id=".(int)$_SESSION['farmer_id'])->fetch_assoc();
      if ($farmerInfo) {
        sendZeroQuantityNotificationEmail(
          $farmerInfo['email'],
          $farmerInfo['username'],
          $name,
          $product_id
        );
      }
    }
    header('Location: farmer_home.php?updated=1');
  } else {
    echo "<script>alert('Error updating product'); window.history.back();</script>";
  }
  exit();
}
header('Location: farmer_home.php');
?>
