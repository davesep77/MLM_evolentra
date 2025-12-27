<?php
require 'config_db.php';

echo "<h2>Database Re-Installation (New Schema)</h2>";

// Prefix tables to avoid ghost file issues
$t_users = "mlm_users";
$t_wallets = "mlm_wallets";
$t_trx = "mlm_transactions";

// --- DROP TABLES ---
$conn->query("DROP TABLE IF EXISTS $t_trx");
$conn->query("DROP TABLE IF EXISTS $t_wallets");
$conn->query("DROP TABLE IF EXISTS $t_users");
echo "Dropped existing mlm_* tables.<br>";

// --- CREATE USERS ---
$sql_users = "CREATE TABLE $t_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    sponsor_id INT NULL,
    binary_position ENUM('L', 'R') NULL,
    investment DECIMAL(15,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql_users)) {
    echo "Table '$t_users' created.<br>";
} else {
    die("Error creating $t_users: " . $conn->error);
}

// --- CREATE WALLETS ---
$sql_wallets = "CREATE TABLE $t_wallets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    roi_wallet DECIMAL(15,2) DEFAULT 0.00,
    referral_wallet DECIMAL(15,2) DEFAULT 0.00,
    binary_wallet DECIMAL(15,2) DEFAULT 0.00,
    left_vol DECIMAL(15,2) DEFAULT 0.00,
    right_vol DECIMAL(15,2) DEFAULT 0.00,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES $t_users(id) ON DELETE CASCADE
)";
if ($conn->query($sql_wallets)) {
    echo "Table '$t_wallets' created.<br>";
} else {
    die("Error creating $t_wallets: " . $conn->error);
}

// --- CREATE TRANSACTIONS ---
$sql_trx = "CREATE TABLE $t_trx (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    amount DECIMAL(15,2) DEFAULT 0.00,
    status VARCHAR(20) DEFAULT 'completed',
    description VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES $t_users(id) ON DELETE CASCADE
)";
if ($conn->query($sql_trx)) {
    echo "Table '$t_trx' created.<br>";
} else {
    die("Error creating $t_trx: " . $conn->error);
}

// --- SEED ROOT USER ---
$root_pass = password_hash('123456', PASSWORD_DEFAULT);
$sql_seed = "INSERT INTO $t_users (username, email, password, investment) VALUES ('dawit', 'dawit@evolentra.com', '$root_pass', 5000.00)";
if ($conn->query($sql_seed)) {
    $root_id = $conn->insert_id;
    $conn->query("INSERT INTO $t_wallets (user_id) VALUES ($root_id)");
    echo "Root user 'dawit' created (Pass: 123456).<br>";
} else {
    echo "Error seeding root: " . $conn->error . "<br>";
}

echo "<h3>Reinstall Complete.</h3>";
echo "Now we need to update the PHP code to match these new table names.";
?>
