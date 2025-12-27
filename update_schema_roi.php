<?php
require 'config_db.php';

echo "Checking schema for ROI duration tracking...\n";

// Check if column exists
$res = $conn->query("SHOW COLUMNS FROM mlm_users LIKE 'roi_days_paid'");
if ($res->num_rows == 0) {
    echo "Adding 'roi_days_paid' column...\n";
    $conn->query("ALTER TABLE mlm_users ADD COLUMN roi_days_paid INT DEFAULT 0");
    echo "Column added.\n";
} else {
    echo "Column 'roi_days_paid' already exists.\n";
}

echo "Done.\n";
?>
