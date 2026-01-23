<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!headers_sent()) {
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('Cache-Control: post-check=0, pre-check=0', false);
  header('Pragma: no-cache');
  header('Expires: 0');
}

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "agri_farm";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

function esc($str) {
  return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}


function addColumnIfNotExists($conn, $table, $column, $definition) {
  $colRes = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
  if ($colRes && $colRes->num_rows === 0) {
    $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
    return $conn->query($sql);
  }
  return true; 
}

?>


