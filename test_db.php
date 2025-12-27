<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Attempting to connect...\n";

try {
    $conn = new mysqli("localhost", "root", "", "mlm_system", 3310);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    echo "Connected successfully to database 'mlm_system' on port 3310.\n";
    
    // Check if users table exists as a basic sanity check
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows > 0) {
        echo "Table 'users' exists.\n";
    } else {
        echo "Table 'users' does not exist (or connection is to wrong DB).\n";
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}
?>
