<?php
require 'config_db.php';

echo "Checking mlm_system_settings table...\n";

$result = $conn->query("SHOW TABLES LIKE 'mlm_system_settings'");
if ($result->num_rows == 0) {
    echo "Table mlm_system_settings DOES NOT EXIST.\n";
    exit;
}

$result = $conn->query("SELECT * FROM mlm_system_settings");
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "Key: " . $row['setting_key'] . " | Value: " . $row['setting_value'] . "\n";
    }
} else {
    echo "Table contains NO DATA.\n";
}
?>
