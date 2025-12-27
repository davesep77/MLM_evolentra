<?php
require 'config_db.php';

echo "=== MLM_USERS TABLE STRUCTURE ===\n";
$result = $conn->query("DESCRIBE mlm_users");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}

echo "\n=== MLM_WALLETS TABLE STRUCTURE ===\n";
$result = $conn->query("DESCRIBE mlm_wallets");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
