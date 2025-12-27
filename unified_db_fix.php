<?php
require 'config_db.php';

echo "<!DOCTYPE html><html><head><title>Database Fix Report</title>";
echo "<style>body{font-family:sans-serif;background:#1e293b;color:#f8fafc;padding:2rem;} .card{background:#0f172a;padding:1.5rem;border-radius:0.5rem;border:1px solid #334155;margin-bottom:1rem;} .success{color:#4ade80;} .error{color:#f87171;} .info{color:#60a5fa;} h2{border-bottom:1px solid #334155;padding-bottom:0.5rem;} pre{background:#000;padding:0.5rem;overflow-x:auto;}</style>";
echo "</head><body><h1>Unified Database Fix Report</h1>";

function report($msg, $type = 'info') {
    echo "<div class='$type'>$msg</div>";
}

function addColumnIfNotExists($conn, $table, $column, $definition) {
    try {
        $check = $conn->query("SHOW COLUMNS FROM $table LIKE '$column'");
        if ($check->num_rows == 0) {
            if ($conn->query("ALTER TABLE $table ADD COLUMN $column $definition")) {
                report("✅ Added column '$column' to table '$table'.", 'success');
            } else {
                report("❌ Failed to add column '$column' to '$table': " . $conn->error, 'error');
            }
        } else {
            // report("ℹ️ Column '$column' already exists in '$table'.");
        }
    } catch (Exception $e) {
        report("⚠️ Error checking column '$column' in '$table': " . $e->getMessage(), 'error');
    }
}

// 1. MLM_USERS
echo "<div class='card'><h2>1. Checking mlm_users</h2>";
$sql = "CREATE TABLE IF NOT EXISTS mlm_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    sponsor_id INT DEFAULT NULL,
    binary_position ENUM('L', 'R') DEFAULT NULL,
    investment DECIMAL(10,2) DEFAULT 0.00,
    kyc_status ENUM('unverified', 'pending', 'verified', 'rejected') DEFAULT 'unverified',
    two_factor_enabled TINYINT(1) DEFAULT 0,
    two_factor_secret VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql)) report("Table mlm_users check/create OK.", 'success');
else report("Error creating mlm_users: " . $conn->error, 'error');

addColumnIfNotExists($conn, 'mlm_users', 'sponsor_id', 'INT DEFAULT NULL');
addColumnIfNotExists($conn, 'mlm_users', 'binary_position', "ENUM('L', 'R') DEFAULT NULL");
addColumnIfNotExists($conn, 'mlm_users', 'investment', 'DECIMAL(10,2) DEFAULT 0.00');
addColumnIfNotExists($conn, 'mlm_users', 'kyc_status', "ENUM('unverified', 'pending', 'verified', 'rejected') DEFAULT 'unverified'");
addColumnIfNotExists($conn, 'mlm_users', 'two_factor_enabled', "TINYINT(1) DEFAULT 0");
addColumnIfNotExists($conn, 'mlm_users', 'two_factor_secret', "VARCHAR(255) NULL");
echo "</div>";

// 2. MLM_WALLETS
echo "<div class='card'><h2>2. Checking mlm_wallets</h2>";
$sql = "CREATE TABLE IF NOT EXISTS mlm_wallets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    roi_wallet DECIMAL(15,2) DEFAULT 0.00,
    referral_wallet DECIMAL(15,2) DEFAULT 0.00,
    binary_wallet DECIMAL(15,2) DEFAULT 0.00,
    left_vol DECIMAL(15,2) DEFAULT 0.00,
    right_vol DECIMAL(15,2) DEFAULT 0.00,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY(user_id)
)";
if ($conn->query($sql)) report("Table mlm_wallets check/create OK.", 'success');
else report("Error creating mlm_wallets: " . $conn->error, 'error');

addColumnIfNotExists($conn, 'mlm_wallets', 'left_vol', 'DECIMAL(15,2) DEFAULT 0.00');
addColumnIfNotExists($conn, 'mlm_wallets', 'right_vol', 'DECIMAL(15,2) DEFAULT 0.00');
addColumnIfNotExists($conn, 'mlm_wallets', 'roi_wallet', 'DECIMAL(15,2) DEFAULT 0.00');
addColumnIfNotExists($conn, 'mlm_wallets', 'binary_wallet', 'DECIMAL(15,2) DEFAULT 0.00');
addColumnIfNotExists($conn, 'mlm_wallets', 'referral_wallet', 'DECIMAL(15,2) DEFAULT 0.00');
echo "</div>";

// 3. MLM_TRANSACTIONS
echo "<div class='card'><h2>3. Checking mlm_transactions</h2>";
$sql = "CREATE TABLE IF NOT EXISTS mlm_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    description VARCHAR(255) NULL,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES mlm_users(id) ON DELETE CASCADE
)";
if ($conn->query($sql)) report("Table mlm_transactions check/create OK.", 'success');
else report("Error creating mlm_transactions: " . $conn->error, 'error');

addColumnIfNotExists($conn, 'mlm_transactions', 'description', 'VARCHAR(255) NULL');
addColumnIfNotExists($conn, 'mlm_transactions', 'status', "ENUM('pending', 'completed', 'failed') DEFAULT 'completed'");
echo "</div>";

// 4. MLM_DEPOSITS
echo "<div class='card'><h2>4. Checking mlm_deposits</h2>";
$sql = "CREATE TABLE IF NOT EXISTS mlm_deposits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    txid VARCHAR(255) NULL UNIQUE,
    network VARCHAR(50) DEFAULT 'BEP20',
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES mlm_users(id)
)";
if ($conn->query($sql)) report("Table mlm_deposits check/create OK.", 'success');
else report("Error creating mlm_deposits: " . $conn->error, 'error');
echo "</div>";

// 5. MLM_WITHDRAWAL_REQUESTS
echo "<div class='card'><h2>5. Checking mlm_withdrawal_requests</h2>";
$sql = "CREATE TABLE IF NOT EXISTS mlm_withdrawal_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    wallet_type ENUM('roi_wallet', 'referral_wallet', 'binary_wallet') NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    payment_address VARCHAR(255) NULL,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    admin_note TEXT NULL,
    admin_txid VARCHAR(255) NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES mlm_users(id) ON DELETE CASCADE
)";
if ($conn->query($sql)) report("Table mlm_withdrawal_requests check/create OK.", 'success');
else report("Error creating mlm_withdrawal_requests: " . $conn->error, 'error');

addColumnIfNotExists($conn, 'mlm_withdrawal_requests', 'admin_txid', 'VARCHAR(255) NULL');
echo "</div>";

// 6. MLM_SYSTEM_SETTINGS
echo "<div class='card'><h2>6. Checking mlm_system_settings</h2>";
$sql = "CREATE TABLE IF NOT EXISTS mlm_system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    description VARCHAR(255) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
if ($conn->query($sql)) {
    report("Table mlm_system_settings check/create OK.", 'success');
    // Ensure defaults
    $defaults = [
        'min_withdrawal' => '10',
        'withdrawal_fee_percent' => '2',
        'max_daily_withdrawal' => '10000',
        'master_binance_address' => '0xYourMasterBinanceAddressHere',
        'binance_pay_api_key' => '',
        'binance_pay_secret' => ''
    ];
    foreach($defaults as $key => $val) {
        $check = $conn->query("SELECT id FROM mlm_system_settings WHERE setting_key='$key'");
        if ($check->num_rows == 0) {
            $conn->query("INSERT INTO mlm_system_settings (setting_key, setting_value) VALUES ('$key', '$val')");
            report("➕ Inserted default setting: $key", 'success');
        }
    }
} else {
    report("Error creating mlm_system_settings: " . $conn->error, 'error');
}
echo "</div>";

echo "<div class='card check'><h2>✅ Database Fix Completed</h2><p>You can now use the application.</p><a href='dashboard.php' style='color:#fff;text-decoration:underline;'>Go to Dashboard</a></div>";
echo "</body></html>";
?>
