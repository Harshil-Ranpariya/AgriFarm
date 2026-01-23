<?php
/**
 * Database Initialization Script
 * Run this once to ensure all tables and columns exist
 */
require_once 'db.php';

echo "<h2>Initializing Database...</h2>";

// Ensure admins table exists with default admin
$conn->query("CREATE TABLE IF NOT EXISTS admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Check if admin exists, if not create default
$adminCheck = $conn->query("SELECT COUNT(*) as count FROM admins");
$adminCount = $adminCheck->fetch_assoc()['count'];
if ($adminCount == 0) {
    $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO admins (username, password) VALUES ('admin', '$defaultPassword')");
    echo "<p>✓ Default admin created: username='admin', password='admin123'</p>";
} else {
    echo "<p>✓ Admin account exists</p>";
}

// Ensure products table has all required columns
$columns = [
    'image_path' => "ALTER TABLE products ADD COLUMN IF NOT EXISTS image_path VARCHAR(255) NULL AFTER description",
    'quantity' => "ALTER TABLE products ADD COLUMN IF NOT EXISTS quantity DECIMAL(10,2) DEFAULT 0 AFTER price",
    'category' => "ALTER TABLE products ADD COLUMN IF NOT EXISTS category VARCHAR(50) NULL AFTER name",
    'subcategory' => "ALTER TABLE products ADD COLUMN IF NOT EXISTS subcategory VARCHAR(50) NULL AFTER category"
];

foreach ($columns as $colName => $sql) {
    $colRes = $conn->query("SHOW COLUMNS FROM products LIKE '$colName'");
    if ($colRes && $colRes->num_rows === 0) {
        if ($conn->query($sql)) {
            echo "<p>✓ Added column 'products.$colName'</p>";
        } else {
            echo "<p>✗ Error adding column 'products.$colName': " . $conn->error . "</p>";
        }
    } else {
        echo "<p>✓ Column 'products.$colName' exists</p>";
    }
}

// Ensure farmer_users and buyer_users have mobile_number
$userTables = ['farmer_users', 'buyer_users'];
foreach ($userTables as $table) {
    $colRes = $conn->query("SHOW COLUMNS FROM $table LIKE 'mobile_number'");
    if ($colRes && $colRes->num_rows === 0) {
        if ($conn->query("ALTER TABLE $table ADD COLUMN mobile_number VARCHAR(20) NULL AFTER email")) {
            echo "<p>✓ Added column '$table.mobile_number'</p>";
        } else {
            echo "<p>✗ Error adding column '$table.mobile_number': " . $conn->error . "</p>";
        }
    } else {
        echo "<p>✓ Column '$table.mobile_number' exists</p>";
    }
}

// Ensure temp_users has mobile_number
$colRes = $conn->query("SHOW COLUMNS FROM temp_users LIKE 'mobile_number'");
if ($colRes && $colRes->num_rows === 0) {
    if ($conn->query("ALTER TABLE temp_users ADD COLUMN mobile_number VARCHAR(20) NULL AFTER email")) {
        echo "<p>✓ Added column 'temp_users.mobile_number'</p>";
    }
}

// Ensure orders table has payment columns
$paymentColumns = [
    'payment_method' => "ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) DEFAULT NULL AFTER remaining_quantity",
    'payment_status' => "ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_status ENUM('pending','completed','failed') DEFAULT 'pending' AFTER payment_method",
    'payment_transaction_id' => "ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_transaction_id VARCHAR(100) DEFAULT NULL AFTER payment_status"
];

foreach ($paymentColumns as $colName => $sql) {
    $colRes = $conn->query("SHOW COLUMNS FROM orders LIKE '$colName'");
    if ($colRes && $colRes->num_rows === 0) {
        if ($conn->query($sql)) {
            echo "<p>✓ Added column 'orders.$colName'</p>";
        }
    }
}

// Ensure product_ratings table exists
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
echo "<p>✓ product_ratings table verified</p>";

// Ensure website_feedback table exists
$conn->query("CREATE TABLE IF NOT EXISTS website_feedback (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  buyer_id INT UNSIGNED NULL,
  farmer_id INT UNSIGNED NULL,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(191) NOT NULL,
  subject VARCHAR(200) NOT NULL,
  message TEXT NOT NULL,
  rating TINYINT UNSIGNED NULL CHECK (rating >= 1 AND rating <= 5),
  status ENUM('new','read','replied') DEFAULT 'new',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (buyer_id) REFERENCES buyer_users(id) ON DELETE SET NULL,
  FOREIGN KEY (farmer_id) REFERENCES farmer_users(id) ON DELETE SET NULL,
  INDEX idx_status (status),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Check if farmer_id column exists in website_feedback
$colCheck = $conn->query("SHOW COLUMNS FROM website_feedback LIKE 'farmer_id'");
if (!$colCheck || $colCheck->num_rows == 0) {
    $conn->query("ALTER TABLE website_feedback ADD COLUMN farmer_id INT UNSIGNED NULL AFTER buyer_id");
    echo "<p>✓ Added column 'website_feedback.farmer_id'</p>";
}
echo "<p>✓ website_feedback table verified</p>";

// Ensure product_returns table exists
$conn->query("CREATE TABLE IF NOT EXISTS product_returns (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  buyer_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  farmer_id INT UNSIGNED NOT NULL,
  return_quantity DECIMAL(10,2) NOT NULL,
  return_reason TEXT NOT NULL,
  return_status ENUM('pending','approved','rejected','completed') DEFAULT 'pending',
  refund_amount DECIMAL(10,2) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (buyer_id) REFERENCES buyer_users(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (farmer_id) REFERENCES farmer_users(id) ON DELETE CASCADE,
  INDEX idx_buyer_status (buyer_id, return_status),
  INDEX idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "<p>✓ product_returns table verified</p>";

echo "<h3>Database initialization complete!</h3>";
echo "<p><a href='admin_login.php'>Go to Admin Login</a></p>";
?>

