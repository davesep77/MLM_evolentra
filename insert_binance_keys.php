<?php
require 'config_db.php';

$keys = [
    'binance_pay_api_key' => 'YOUR_BINANCE_API_KEY',
    'binance_pay_secret' => 'YOUR_BINANCE_SECRET_KEY',
    'binance_pay_env' => 'sandbox', // or 'live'
    'withdrawal_fee_percent' => '2.00'
];

foreach ($keys as $key => $default_val) {
    // Check if exists
    $stmt = $conn->prepare("SELECT id FROM mlm_system_settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows == 0) {
        $stmt_ins = $conn->prepare("INSERT INTO mlm_system_settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt_ins->bind_param("ss", $key, $default_val);
        $stmt_ins->execute();
        echo "Inserted missing setting: $key\n";
    } else {
        echo "Setting already exists: $key\n";
    }
}
echo "Done.";
?>
