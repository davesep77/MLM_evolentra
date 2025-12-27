<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing DB Connection...\n";

$conn = mysqli_init();
if (!$conn) {
    die("mysqli_init failed");
}

$host = 'localhost';
$user = 'root';
$pass = ''; // Assuming empty password as per config
$db = 'mlm_system';

// Try 3310
echo "Attempting port 3310...\n";
if (@$conn->real_connect($host, $user, $pass, $db, 3310)) {
    echo "Connected successfully on port 3310\n";
} else {
    echo "Failed on port 3310: " . $conn->connect_error . "\n";
    // Try 3306
    echo "Attempting port 3306...\n";
    if (@$conn->real_connect($host, $user, $pass, $db, 3306)) {
        echo "Connected successfully on port 3306\n";
    } else {
        echo "Failed on port 3306: " . $conn->connect_error . "\n";
        die("Could not connect to database.\n");
    }
}

echo "Server Info: " . $conn->server_info . "\n";
echo "Host Info: " . $conn->host_info . "\n";
?>
