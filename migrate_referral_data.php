<?php
/**
 * Referral Data Migration Script
 * Migrates existing users to the new referral tracking system
 */

require 'config_db.php';

echo "=== Referral Data Migration ===\n\n";

/**
 * Generate unique referral code
 */
function generateReferralCode($username, $userId) {
    // Create code from username + random string
    $clean_username = preg_replace('/[^a-zA-Z0-9]/', '', $username);
    $random = strtoupper(substr(md5($userId . time()), 0, 4));
    return strtoupper(substr($clean_username, 0, 6)) . $random;
}

// Step 1: Generate referral codes for all users who don't have one
echo "Step 1: Generating referral codes for users...\n";
$users = $conn->query("SELECT id, username, referral_code FROM mlm_users");
$updated = 0;

while ($user = $users->fetch_assoc()) {
    if (empty($user['referral_code'])) {
        $code = generateReferralCode($user['username'], $user['id']);
        
        // Ensure uniqueness
        $attempt = 0;
        while (true) {
            $check = $conn->query("SELECT id FROM mlm_users WHERE referral_code='$code'");
            if ($check->num_rows == 0) break;
            
            $attempt++;
            $code = generateReferralCode($user['username'], $user['id'] + $attempt);
            if ($attempt > 10) {
                $code = strtoupper(bin2hex(random_bytes(5)));
                break;
            }
        }
        
        $conn->query("UPDATE mlm_users SET referral_code='$code' WHERE id={$user['id']}");
        $updated++;
    }
}
echo "✓ Generated referral codes for $updated users\n";

// Step 2: Create referral links for all users
echo "\nStep 2: Creating referral links (general, left, right)...\n";
$users = $conn->query("SELECT id, referral_code FROM mlm_users WHERE referral_code IS NOT NULL");
$links_created = 0;

while ($user = $users->fetch_assoc()) {
    $userId = $user['id'];
    $refCode = $user['referral_code'];
    
    // Check if links already exist
    $existing = $conn->query("SELECT COUNT(*) as count FROM mlm_referral_links WHERE user_id=$userId");
    $count = $existing->fetch_assoc()['count'];
    
    if ($count == 0) {
        // Create 3 links: general, left, right
        $types = ['general', 'left', 'right'];
        foreach ($types as $type) {
            $sql = "INSERT INTO mlm_referral_links (user_id, referral_code, link_type) 
                    VALUES ($userId, '$refCode', '$type')";
            $conn->query($sql);
            $links_created++;
        }
    }
}
echo "✓ Created $links_created referral links\n";

// Step 3: Calculate conversions from existing referrals
echo "\nStep 3: Calculating conversion statistics...\n";
$users = $conn->query("SELECT id FROM mlm_users");
$conversions_updated = 0;

while ($user = $users->fetch_assoc()) {
    $userId = $user['id'];
    
    // Count direct referrals (conversions)
    $referrals = $conn->query("SELECT COUNT(*) as count FROM mlm_users WHERE sponsor_id=$userId");
    if ($referrals) {
        $conversion_count = $referrals->fetch_assoc()['count'];
        
        if ($conversion_count > 0) {
            // Update all link types for this user
            $conn->query("UPDATE mlm_referral_links SET conversions=$conversion_count WHERE user_id=$userId");
            $conversions_updated++;
        }
    }
}
echo "✓ Updated conversion stats for $conversions_updated users\n";

// Step 4: Calculate total earnings from historical REFERRAL transactions
echo "\nStep 4: Calculating historical referral earnings...\n";
$users = $conn->query("SELECT id FROM mlm_users");
$earnings_updated = 0;

while ($user = $users->fetch_assoc()) {
    $userId = $user['id'];
    
    // Sum all REFERRAL transactions
    $earnings = $conn->query("SELECT SUM(amount) as total FROM mlm_transactions 
                              WHERE user_id=$userId AND type='REFERRAL'");
    $total = $earnings->fetch_assoc()['total'] ?? 0;
    
    if ($total > 0) {
        $conn->query("UPDATE mlm_referral_links SET total_earned=$total WHERE user_id=$userId");
        $earnings_updated++;
    }
}
echo "✓ Updated earnings for $earnings_updated users\n";

// Step 5: Migrate historical referral earnings to new table
echo "\nStep 5: Migrating historical referral earnings...\n";
$referrals = $conn->query("
    SELECT u1.id as referrer_id, u2.id as referred_id, u2.investment, u2.created_at
    FROM mlm_users u1
    JOIN mlm_users u2 ON u2.sponsor_id = u1.id
    WHERE u2.investment > 0
");

$earnings_migrated = 0;
while ($ref = $referrals->fetch_assoc()) {
    $referrerId = $ref['referrer_id'];
    $referredId = $ref['referred_id'];
    $investment = $ref['investment'];
    $createdAt = $ref['created_at'];
    
    // Calculate commission (9%)
    $rate = 0.09;
    $commission = $investment * $rate;
    
    // Check if already exists
    $check = $conn->query("SELECT id FROM mlm_referral_earnings 
                           WHERE referrer_id=$referrerId AND referred_user_id=$referredId");
    
    if ($check->num_rows == 0) {
        $sql = "INSERT INTO mlm_referral_earnings 
                (referrer_id, referred_user_id, investment_amount, commission_rate, commission_amount, earned_at)
                VALUES ($referrerId, $referredId, $investment, $rate, $commission, '$createdAt')";
        $conn->query($sql);
        $earnings_migrated++;
    }
}
echo "✓ Migrated $earnings_migrated referral earning records\n";

// Step 6: Generate summary report
echo "\n=== Migration Summary ===\n";
$total_users = $conn->query("SELECT COUNT(*) as count FROM mlm_users")->fetch_assoc()['count'];
$total_links = $conn->query("SELECT COUNT(*) as count FROM mlm_referral_links")->fetch_assoc()['count'];
$total_conversions = $conn->query("SELECT SUM(conversions) as total FROM mlm_referral_links")->fetch_assoc()['total'] ?? 0;
$total_earnings = $conn->query("SELECT SUM(total_earned) as total FROM mlm_referral_links")->fetch_assoc()['total'] ?? 0;

echo "Total Users: $total_users\n";
echo "Total Referral Links: $total_links\n";
echo "Total Conversions: $total_conversions\n";
echo "Total Earnings Tracked: $" . number_format($total_earnings, 2) . "\n";

echo "\n✓ Migration Complete!\n";
echo "Users can now access their referral links from the dashboard.\n";

$conn->close();
?>
