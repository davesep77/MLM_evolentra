<?php
require 'config_db.php';

echo "<h2>Setting up Transfer Requests Table</h2>";

$sql = "CREATE TABLE IF NOT EXISTS mlm_transfer_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    wallet_type VARCHAR(50) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    FOREIGN KEY (sender_id) REFERENCES mlm_users(id),
    FOREIGN KEY (receiver_id) REFERENCES mlm_users(id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "✅ mlm_transfer_requests table created successfully<br>";
} else {
    echo "❌ Error: " . $conn->error . "<br>";
}
?>
