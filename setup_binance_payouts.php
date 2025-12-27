<?php
require 'config_db.php';

echo "<h2>Migrating for Binance Integration (User Addresses)</h2>";

// Add Binance columns to mlm_user_settings
$sql = "ALTER TABLE mlm_user_settings 
        ADD COLUMN IF NOT EXISTS usdt_address_bep20 VARCHAR(255) NULL,
        ADD COLUMN IF NOT EXISTS usdt_address_trc20 VARCHAR(255) NULL";

if ($conn->query($sql) === TRUE) {
    echo "✅ Binance address columns added to mlm_user_settings<br>";
} else {
    echo "❌ Error adding columns: " . $conn->error . "<br>";
}

echo "✅ Database ready for Binance connection.<br>";
echo "<p><a href='dashboard.php'>Back to Dashboard</a></p>";
?>
