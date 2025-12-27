<?php
require_once 'config_db.php';

echo "Checking database structure...\n";

// Check if wallet_address column exists in mlm_users
$result = $conn->query("SHOW COLUMNS FROM mlm_users LIKE 'wallet_address'");
if ($result->num_rows == 0) {
    echo "Adding wallet_address column to mlm_users...\n";
    $sql = "ALTER TABLE mlm_users ADD COLUMN wallet_address VARCHAR(255) NULL AFTER email";
    if ($conn->query($sql) === TRUE) {
        echo "Successfully added wallet_address column.\n";
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
} else {
    echo "wallet_address column already exists.\n";
}

// Verify the column was added
$result = $conn->query("SHOW COLUMNS FROM mlm_users LIKE 'wallet_address'");
if ($result->num_rows > 0) {
    echo "Verification SUCCESS: wallet_address column is present.\n";
} else {
    echo "Verification FAILED: wallet_address column is missing.\n";
}
?>
