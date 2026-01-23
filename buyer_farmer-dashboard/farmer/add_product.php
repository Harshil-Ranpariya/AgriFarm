<?php
require_once __DIR__ . '/../../db.php';
if (!isset($_SESSION['farmer_id'])) { header('Location: Login.html'); exit(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $category = trim($_POST['category'] ?? '');
  $subcategory = trim($_POST['subcategory'] ?? '');
  $price = (float)($_POST['price'] ?? 0); // Price per 20kg
  $quantity = (float)($_POST['quantity'] ?? 0); // Quantity in kg
  $description = trim($_POST['description'] ?? '');
  $imagePath = null;
  
  // Validation - all fields are compulsory
  $errors = [];
  
  if ($name === '') {
    $errors[] = 'Product Name is required';
  }
  if ($price <= 0) {
    $errors[] = 'Price must be greater than 0';
  }
  if ($quantity <= 0) {
    $errors[] = 'Quantity is required and must be greater than 0';
  }
  
  // Check if image is uploaded
  if (empty($_FILES['image']['name'])) {
    $errors[] = 'Product Image is required';
  }
  
  if (!empty($errors)) {
    echo "<script>alert('" . implode('\\n', $errors) . "'); window.history.back();</script>"; 
    exit();
  }
  
  // Ensure required columns exist
  addColumnIfNotExists($conn, 'products', 'image_path', 'VARCHAR(255) NULL AFTER description');
  addColumnIfNotExists($conn, 'products', 'quantity', 'DECIMAL(10,2) DEFAULT 0 AFTER price');
  addColumnIfNotExists($conn, 'products', 'category', 'VARCHAR(50) NULL AFTER name');
  addColumnIfNotExists($conn, 'products', 'subcategory', 'VARCHAR(50) NULL AFTER category');

  // Handle image upload - REQUIRED
  if (!empty($_FILES['image']['name'])) {
    $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
    if (in_array($_FILES['image']['type'], $allowed) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
      if (!is_dir(__DIR__ . '/uploads')) { @mkdir(__DIR__ . '/uploads', 0777, true); }
      $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
      $filename = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
      $dest = __DIR__ . '/uploads/' . $filename;
      if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
        // Store web-accessible path. The project is hosted under /agrifarm in the webroot.
        $imagePath = '/agrifarm/buyer_farmer-dashboard/farmer/uploads/' . $filename;
      } else {
        echo "<script>alert('Error uploading image'); window.history.back();</script>"; 
        exit();
      }
    } else {
      echo "<script>alert('Invalid image file. Only JPG, PNG, GIF, WebP allowed'); window.history.back();</script>"; 
      exit();
    }
  }

  // Image is required, so if we reach here without imagePath, something went wrong
  if (!$imagePath) {
    echo "<script>alert('Product Image is required'); window.history.back();</script>"; 
    exit();
  }

  $stmt = $conn->prepare("INSERT INTO products (farmer_id, name, category, subcategory, description, image_path, price, quantity, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
  if (!$stmt) {
    echo "<script>alert('Database error: " . addslashes($conn->error) . "'); window.history.back();</script>";
    exit();
  }
  $stmt->bind_param("isssssdd", $_SESSION['farmer_id'], $name, $category, $subcategory, $description, $imagePath, $price, $quantity);
  
  if ($stmt->execute()) {
    header('Location: farmer_add_product.php?success=1'); exit();
  } else {
    echo "<script>alert('Error adding product: " . addslashes($stmt->error) . "'); window.history.back();</script>";
  }
  $stmt->close();
}
?>


