<?php
require 'config_db.php';

// 1. Add Setting if missing
$check = $conn->query("SELECT id FROM mlm_system_settings WHERE setting_key='master_binance_address'");
if ($check->num_rows == 0) {
    $conn->query("INSERT INTO mlm_system_settings (setting_key, setting_value, description) 
                  VALUES ('master_binance_address', '', 'Central Binance Address for all collections')");
    echo "✅ master_binance_address added to settings.<br>";
}

// 2. Ensure Admin has a wallet
$res = $conn->query("SELECT id FROM mlm_users WHERE role='admin' LIMIT 1");
if ($row = $res->fetch_assoc()) {
    $admin_id = $row['id'];
    $check_w = $conn->query("SELECT user_id FROM mlm_wallets WHERE user_id=$admin_id");
    if ($check_w->num_rows == 0) {
        $conn->query("INSERT INTO mlm_wallets (user_id) VALUES ($admin_id)");
        echo "✅ Admin wallet created (ID: $admin_id).<br>";
    }
}

echo "Database preparation complete.<br><a href='admin/settings.php'>Go to Settings</a>";
?>
