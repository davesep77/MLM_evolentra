<?php
require 'config_db.php';

echo "<h2>Database Debugger</h2>";

// 1. Check if table exists in schema
$res = $conn->query("SHOW TABLES LIKE 'users'");
echo "Show Tables 'users': " . ($res->num_rows > 0 ? "FOUND" : "NOT FOUND") . "<br>";

// 2. Check Table Status
$res = $conn->query("CHECK TABLE users");
if ($res) {
    echo "<h3>Check Table Output:</h3><pre>";
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
} else {
    echo "CHECK TABLE failed: " . $conn->error . "<br>";
}

// 3. Try a Select
echo "<h3>Select Test:</h3>";
try {
    $res = $conn->query("SELECT * FROM users LIMIT 1");
    if ($res) {
        echo "Select SUCCESS. Found " . $res->num_rows . " rows.<br>";
    } else {
        echo "Select FAILED: " . $conn->error . "<br>";
    }
} catch (Exception $e) {
    echo "Select EXCEPTION: " . $e->getMessage() . "<br>";
}
?>
