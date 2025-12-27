<?php
require 'config_db.php';

echo "Setting up KYC and Security tables...\n";

// 1. Add columns to mlm_users if they don't exist
$columns = [
    "kyc_status" => "ENUM('unverified', 'pending', 'verified', 'rejected') DEFAULT 'unverified'",
    "two_factor_enabled" => "TINYINT(1) DEFAULT 0",
    "two_factor_secret" => "VARCHAR(255) NULL"
];

foreach ($columns as $col => $def) {
    try {
        $conn->query("ALTER TABLE mlm_users ADD COLUMN $col $def");
        echo "Added column: $col\n";
    } catch (Exception $e) {
        // Column likely exists
        echo "Column $col might already exist or error: " . $e->getMessage() . "\n";
    }
}

// 2. Create KYC Documents Table
$sql = "CREATE TABLE IF NOT EXISTS mlm_kyc_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    document_type ENUM('id_front', 'id_back', 'selfie', 'proof_address') NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES mlm_users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table mlm_kyc_documents created or already exists.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// 3. Create Upload Directory
$uploadDir = "uploads/kyc";
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
    echo "Created directory: $uploadDir\n";
} else {
    echo "Directory $uploadDir already exists.\n";
}

echo "Setup Complete.\n";
?>
