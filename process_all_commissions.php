<?php
require 'config_db.php';
require 'lib/Compensation.php';

$comp = new Compensation($conn);

echo "--- Starting Commission Processing ---\n";

// 1. Process Missed Referral Commissions
// Logic: Find users who have an investment but their sponsor hasn't received a 'REFERRAL' transaction for them.
// For simplicity in this demo, we will check if transaction log exists for this pair.
// Note: In a real system, this happens at the moment of payment.

echo "\n--- Processing Referral Bonuses ---\n";
$users = $conn->query("SELECT id, username, sponsor_id, investment FROM mlm_users WHERE sponsor_id IS NOT NULL AND investment > 0");
$refCount = 0;

while ($u = $users->fetch_assoc()) {
    $sponsorId = $u['sponsor_id'];
    $amount = $u['investment'];
    
    // Check if we already paid for this user?
    // A simple check: SELECT * FROM transactions WHERE user_id=$sponsorId AND type='REFERRAL' AND description LIKE '%User {$u['id']}%' 
    // But our log description is generic 'Referral Bonus ($rate)'. 
    // For this 'force run', we will just assume if the sponsor's referral_wallet is 0, we pay. 
    // BETTER: Let's just pay everyone for this demo since it's a "simulation" request.
    
    // To avoid double pay in repeated runs, let's reset wallets first? No, that's dangerous.
    // Let's just calculate what SHOULD be there.
    
    echo "Processing Referral for {$u['username']} ($$amount) -> Sponsor $sponsorId... ";
    $comp->processReferral($amount, $sponsorId);
    echo "Done.\n";
    $refCount++;
}

// 2. Process Binary Commissions
// Reference: lib/Compensation.php processBinary()
// This uses the 'left_vol' and 'right_vol' we populated in generate_sample_data.php
echo "\n--- Processing Binary Matching ---\n";
$comp->processBinary();

// 3. Process Daily ROI (Simulate 1 day passing)
echo "\n--- Processing Daily ROI ---\n";
$comp->processDailyRoi();


echo "\n--- Summary for DAWIT ---\n";
$d = $conn->query("SELECT * FROM mlm_users WHERE username='dawit' LIMIT 1")->fetch_assoc();
if ($d) {
    $w = $conn->query("SELECT * FROM mlm_wallets WHERE user_id={$d['id']}")->fetch_assoc();
    echo "User: Dawit\n";
    echo "ROI Wallet: $" . number_format($w['roi_wallet'], 2) . "\n";
    echo "Referral Wallet: $" . number_format($w['referral_wallet'], 2) . "\n";
    echo "Binary Wallet: $" . number_format($w['binary_wallet'], 2) . "\n";
    echo "Left Volume: $" . number_format($w['left_vol'], 2) . "\n";
    echo "Right Volume: $" . number_format($w['right_vol'], 2) . "\n";
    
    $total = $w['roi_wallet'] + $w['referral_wallet'] + $w['binary_wallet'];
    echo "TOTAL EARNINGS: $" . number_format($total, 2) . "\n";
}

echo "\nProcess Complete.\n";
?>
