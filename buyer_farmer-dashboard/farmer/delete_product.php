<?php
require_once __DIR__ . '/../../db.php';
if (!isset($_SESSION['farmer_id'])) { 
    header('Location: Login.html'); 
    exit(); 
}

$product_id = (int)($_GET['id'] ?? 0);

if ($product_id <= 0) {
    echo "<script>alert('Invalid product ID'); window.history.back();</script>";
    exit();
}

$check = $conn->prepare("SELECT id, image_path FROM products WHERE id = ? AND farmer_id = ?");
$check->bind_param("ii", $product_id, $_SESSION['farmer_id']);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('Product not found or you do not have permission to delete it'); window.history.back();</script>";
    exit();
}

$product = $result->fetch_assoc();

if (!empty($product['image_path'])) {
    $fsPath = rtrim($_SERVER['DOCUMENT_ROOT'], "\\/") . str_replace('/', DIRECTORY_SEPARATOR, $product['image_path']);
    if (file_exists($fsPath)) {
        @unlink($fsPath);
    }
}

$stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND farmer_id = ?");
$stmt->bind_param("ii", $product_id, $_SESSION['farmer_id']);

if ($stmt->execute()) {
    header('Location: farmer_home.php?deleted=1');
} else {
    echo "<script>alert('Error deleting product'); window.history.back();</script>";
}
exit();
?>

