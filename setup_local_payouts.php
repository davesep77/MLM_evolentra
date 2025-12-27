<?php
require 'config_db.php';

echo "<h2>Migrating for Local Payouts (Telebirr/CBE)</h2>";

// Add Telebirr and CBE columns to mlm_user_settings
$sql = "ALTER TABLE mlm_user_settings 
        ADD COLUMN IF NOT EXISTS telebirr_number VARCHAR(20) NULL,
        ADD COLUMN IF NOT EXISTS cbe_account VARCHAR(30) NULL";

if ($conn->query($sql) === TRUE) {
    echo "✅ Telebirr and CBE columns added to mlm_user_settings<br>";
} else {
    echo "❌ Error adding columns: " . $conn->error . "<br>";
}

// Add descriptive description for these to settings if needed, 
// though these are per-user so they belong in user_settings as added above.

// Update system settings to allow enabling/disabling these methods
$conn->query("INSERT IGNORE INTO mlm_system_settings (setting_key, setting_value, description) VALUES ('enable_telebirr', '1', 'Enable Telebirr Payouts (0=No, 1=Yes)')");
$conn->query("INSERT IGNORE INTO mlm_system_settings (setting_key, setting_value, description) VALUES ('enable_cbe', '1', 'Enable CBE Payouts (0=No, 1=Yes)')");

echo "✅ System settings updated for local payouts<br>";
echo "<p><a href='dashboard.php'>Back to Dashboard</a></p>";
?>
