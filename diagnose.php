<?php
require 'config_db.php';

echo "<h2>System Diagnostic</h2>";

function checkColumn($conn, $table, $col) {
    echo "Checking <b>$table.$col</b>: ";
    $res = $conn->query("SHOW COLUMNS FROM $table LIKE '$col'");
    if ($res && $res->num_rows > 0) {
        echo "<span style='color:green'>EXISTS</span><br>";
    } else {
        echo "<span style='color:red'>MISSING (Attempting Fix...)</span><br>";
        
        // Auto-fix attempt
        if ($table == 'users' && $col == 'investment') {
            $conn->query("ALTER TABLE users ADD COLUMN investment DECIMAL(15,2) DEFAULT 0.00");
            echo "-> Added 'investment' column to users.<br>";
        }
    }
}

// 1. Check Tables
checkColumn($conn, 'users', 'investment');
checkColumn($conn, 'users', 'sponsor_id');
checkColumn($conn, 'wallets', 'roi_wallet');
checkColumn($conn, 'wallets', 'referral_wallet');
checkColumn($conn, 'wallets', 'binary_wallet');

// 2. Check User Data
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    echo "<h3>Current User Data (ID: $uid)</h3>";
    
    $u = $conn->query("SELECT * FROM users WHERE id=$uid")->fetch_assoc();
    echo "<pre>"; print_r($u); echo "</pre>";
    
    $w = $conn->query("SELECT * FROM wallets WHERE user_id=$uid")->fetch_assoc();
    echo "<pre>"; print_r($w); echo "</pre>";
} else {
    echo "<br>Not logged in. <a href='login.php'>Login</a> to check data.";
}

echo "<hr><a href='dashboard.php'>Back to Dashboard</a>";
?>
