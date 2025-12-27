<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$hosts = ["127.0.0.1", "localhost"];
$user = "root";
$pass = ""; 
$port = 3306;

echo "Diagnosing host/IP...\n";

foreach ($hosts as $h) {
    echo "Testing host '$h' port $port user '$user' pass '$pass': ";
    try {
        $conn = new mysqli($h, $user, $pass, "", $port);
        if ($conn->connect_error) {
            echo "Failed (" . $conn->connect_error . ")\n";
        } else {
            echo "SUCCESS! Connected to $h.\n";
            $conn->close();
            exit(0);
        }
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . "\n";
    }
}
?>
