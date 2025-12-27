<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$user = "root";
$passwords = ["", "root", "admin", "password", "123456"];
$port = 3306;

echo "Diagnosing credentials for 3306...\n";

foreach ($passwords as $pass) {
    echo "Testing pass '$pass': ";
    try {
        $conn = new mysqli($host, $user, $pass, "", $port);
        if ($conn->connect_error) {
            echo "Failed (" . $conn->connect_error . ")\n";
        } else {
            echo "SUCCESS! Connected.\n";
            $conn->close();
            break;
        }
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . "\n";
    }
}
?>
