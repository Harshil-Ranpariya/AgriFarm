<?php

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "agri_farm";

// Connect to database
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Fix image paths in products table - replace /agrifarm11/ with /agrifarm/
$old_prefix = '/agrifarm11/';
$new_prefix = '/agrifarm/';

// Use SQL REPLACE function to update paths
$sql = "UPDATE products 
        SET image_path = REPLACE(image_path, '$old_prefix', '$new_prefix') 
        WHERE image_path LIKE '%$old_prefix%'";

if ($conn->query($sql)) {
  $affected = $conn->affected_rows;
  echo "<div style='padding: 20px; font-family: Arial; background: #d4edda; color: #155724; border-radius: 5px;'>";
  echo "<strong>Success!</strong> Fixed $affected product image paths in the database.<br>";
  echo "Image paths have been updated from: <code>$old_prefix</code> to <code>$new_prefix</code><br><br>";
  echo "<strong>Code changes made:</strong><br>";
  echo "✓ <code>add_product.php</code> - Image upload path corrected to /agrifarm/<br>";
  echo "✓ <code>update_product.php</code> - Image upload path corrected to /agrifarm/<br>";
  echo "<strong>New uploads will use the correct path.</strong>";
  echo "</div>";
} else {
  echo "<div style='padding: 20px; font-family: Arial; background: #f8d7da; color: #721c24; border-radius: 5px;'>";
  echo "<strong>Error:</strong> " . $conn->error;
  echo "</div>";
}

$conn->close();
?>
