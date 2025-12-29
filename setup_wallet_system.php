<?php
/**
 * Multi-Wallet Payment System Database Setup
 * Creates tables for wallet connections and crypto payments
 */

require 'config_db.php';

echo "=== Multi-Wallet Payment System Setup ===\n\n";

// 1. Create mlm_wallet_connections table
echo "Creating mlm_wallet_connections table...\n";
$sql_connections = "CREATE TABLE IF NOT EXISTS mlm_wallet_connections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    wallet_type ENUM('binance', 'trust', 'metamask', 'walletconnect', 'other') DEFAULT 'other',
    wallet_address VARCHAR(100) NOT NULL,
    network VARCHAR(50) DEFAULT 'BSC',
    is_verified TINYINT(1) DEFAULT 0,
    signature TEXT,
    connected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES mlm_users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_address (wallet_address),
    INDEX idx_wallet_type (wallet_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql_connections) === TRUE) {
    echo "✓ mlm_wallet_connections table created successfully\n";
} else {
    echo "✗ Error creating mlm_wallet_connections: " . $conn->error . "\n";
}

// 2. Create mlm_crypto_payments table
echo "\nCreating mlm_crypto_payments table...\n";
$sql_payments = "CREATE TABLE IF NOT EXISTS mlm_crypto_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    wallet_type VARCHAR(50),
    wallet_address VARCHAR(100),
    amount DECIMAL(15,2) NOT NULL,
    token VARCHAR(20) DEFAULT 'USDT',
    network VARCHAR(50) DEFAULT 'BSC',
    tx_hash VARCHAR(100) UNIQUE,
    block_number INT,
    status ENUM('pending', 'confirming', 'completed', 'failed') DEFAULT 'pending',
    confirmations INT DEFAULT 0,
    required_confirmations INT DEFAULT 12,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    confirmed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES mlm_users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_tx_hash (tx_hash),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql_payments) === TRUE) {
    echo "✓ mlm_crypto_payments table created successfully\n";
} else {
    echo "✗ Error creating mlm_crypto_payments: " . $conn->error . "\n";
}

// 3. Add wallet_address column to mlm_users if not exists
echo "\nChecking mlm_users table for wallet_address column...\n";
$check_column = $conn->query("SHOW COLUMNS FROM mlm_users LIKE 'wallet_address'");
if ($check_column->num_rows == 0) {
    echo "Adding wallet_address column to mlm_users...\n";
    $add_column = "ALTER TABLE mlm_users ADD COLUMN wallet_address VARCHAR(100) AFTER email";
    if ($conn->query($add_column) === TRUE) {
        echo "✓ wallet_address column added successfully\n";
    } else {
        echo "✗ Error adding wallet_address column: " . $conn->error . "\n";
    }
} else {
    echo "✓ wallet_address column already exists\n";
}

// 4. Add index on wallet_address
echo "\nAdding index on wallet_address...\n";
$check_index = $conn->query("SHOW INDEX FROM mlm_users WHERE Key_name = 'idx_wallet_address'");
if ($check_index->num_rows == 0) {
    $add_index = "ALTER TABLE mlm_users ADD INDEX idx_wallet_address (wallet_address)";
    if ($conn->query($add_index) === TRUE) {
        echo "✓ Index added successfully\n";
    } else {
        echo "✗ Error adding index: " . $conn->error . "\n";
    }
} else {
    echo "✓ Index already exists\n";
}

echo "\n=== Setup Complete ===\n";
echo "Multi-wallet payment system database is ready!\n";

$conn->close();
?>
