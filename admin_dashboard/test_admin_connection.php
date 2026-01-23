<?php
require_once '../db.php';

echo "<h2>Testing Admin Database Connection</h2>";

// Test database connection
if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $conn->connect_error . "</p>";
} else {
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
}

// Check if admins table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'admins'");
if ($tableCheck->num_rows > 0) {
    echo "<p style='color: green;'>✅ 'admins' table exists!</p>";
    
    // Check table structure
    $structure = $conn->query("DESCRIBE admins");
    echo "<h3>Table Structure:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if there are any admin records
    $adminCount = $conn->query("SELECT COUNT(*) as count FROM admins");
    $count = $adminCount->fetch_assoc()['count'];
    echo "<p><strong>Number of admin records:</strong> " . $count . "</p>";
    
    if ($count > 0) {
        // Show admin records
        $admins = $conn->query("SELECT id, username, created_at FROM admins");
        echo "<h3>Admin Records:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Created At</th></tr>";
        while ($admin = $admins->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $admin['id'] . "</td>";
            echo "<td>" . $admin['username'] . "</td>";
            echo "<td>" . $admin['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ No admin records found. The table is empty.</p>";
    }
    
} else {
    echo "<p style='color: red;'>❌ 'admins' table does not exist!</p>";
    echo "<p>You need to run the schema.sql file to create the table.</p>";
}

// Test the esc function
echo "<h3>Testing esc() function:</h3>";
$testString = "Test & <script>alert('xss')</script>";
echo "<p><strong>Original:</strong> " . $testString . "</p>";
echo "<p><strong>Escaped:</strong> " . esc($testString) . "</p>";

$conn->close();
?>

