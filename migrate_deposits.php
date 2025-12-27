<?php
require 'config_db.php';

echo "<h2>Migrating for Deposit Verification System</h2>";

// Create mlm_deposits table
$sql = "CREATE TABLE IF NOT EXISTS mlm_deposits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    txid VARCHAR(255) NOT NULL UNIQUE,
    network ENUM('BEP20', 'TRC20') NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES mlm_users(id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, status),
    INDEX idx_txid (txid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "✅ mlm_deposits table created successfully<br>";
} else {
    echo "❌ Error creating mlm_deposits: " . $conn->error . "<br>";
}

// Add admin_txid to mlm_withdrawal_requests for proof of payment
$sql = "ALTER TABLE mlm_withdrawal_requests ADD COLUMN IF NOT EXISTS admin_txid VARCHAR(255) NULL AFTER processed_at";
if ($conn->query($sql) === TRUE) {
    echo "✅ admin_txid column added to withdrawals<br>";
}

echo "✅ Database migrations complete.<br>";
echo "<p><a href='dashboard.php'>Back to Dashboard</a></p>";
?>
