<?php
require 'config_db.php';

$output = "=== MLM_USERS TABLE ===\n";
$result = $conn->query("DESCRIBE mlm_users");
while ($row = $result->fetch_assoc()) {
    $output .= $row['Field'] . "\n";
}

$output .= "\n=== MLM_WALLETS TABLE ===\n";
$result = $conn->query("DESCRIBE mlm_wallets");
while ($row = $result->fetch_assoc()) {
    $output .= $row['Field'] . "\n";
}

file_put_contents('db_structure.txt', $output);
echo "Database structure saved to db_structure.txt\n";
echo $output;
?>
