<?php
require 'config_db.php';

echo "<h2>Database Schema Patcher</h2>";

// 1. Fix Transactions Table - Add 'description'
echo "Checking 'transactions' table for 'description' column...<br>";
$check = $conn->query("SHOW COLUMNS FROM transactions LIKE 'description'");
if ($check->num_rows == 0) {
    echo "Column missing. Adding 'description'...<br>";
    if ($conn->query("ALTER TABLE transactions ADD COLUMN description VARCHAR(255) NULL")) {
        echo "<span style='color:green'>Success: 'description' column added.</span><br>";
    } else {
        echo "<span style='color:red'>Error: " . $conn->error . "</span><br>";
    }
} else {
    echo "Column exists. Skipping.<br>";
}

echo "<hr>";

// 2. Fix Wallets Table - Add 'left_vol', 'right_vol'
echo "Checking 'wallets' table for binary volume columns...<br>";
$check_left = $conn->query("SHOW COLUMNS FROM wallets LIKE 'left_vol'");
if ($check_left->num_rows == 0) {
    echo "Adding 'left_vol'...<br>";
    $conn->query("ALTER TABLE wallets ADD COLUMN left_vol DECIMAL(15,2) DEFAULT 0.00");
}

$check_right = $conn->query("SHOW COLUMNS FROM wallets LIKE 'right_vol'");
if ($check_right->num_rows == 0) {
    echo "Adding 'right_vol'...<br>";
    $conn->query("ALTER TABLE wallets ADD COLUMN right_vol DECIMAL(15,2) DEFAULT 0.00");
}
echo "<span style='color:green'>Wallet check complete.</span><br>";

echo "<hr>";
echo "<h3>Patch Complete! You can now use Invest/Deposit.</h3>";
echo "<a href='invest.php'>Go back to Invest</a>";
?>
