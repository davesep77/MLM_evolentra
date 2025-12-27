<?php
require 'config_db.php';

/**
 * RANK CALCULATION ENGINE
 * Calculates and updates user ranks based on team performance
 */

// Define rank requirements
$RANK_REQUIREMENTS = [
    'Crown Diamond' => ['team_volume' => 1500000, 'direct_referrals' => 500, 'qualifying_legs' => 5, 'leg_rank' => 'Diamond'],
    'Diamond' => ['team_volume' => 500000, 'direct_referrals' => 200, 'qualifying_legs' => 3, 'leg_rank' => 'Emerald'],
    'Emerald' => ['team_volume' => 150000, 'direct_referrals' => 100, 'qualifying_legs' => 5, 'leg_rank' => 'Ruby'],
    'Ruby' => ['team_volume' => 50000, 'direct_referrals' => 50, 'qualifying_legs' => 3, 'leg_rank' => 'Platinum'],
    'Platinum' => ['team_volume' => 15000, 'direct_referrals' => 20, 'qualifying_legs' => 2, 'leg_rank' => 'Gold'],
    'Gold' => ['team_volume' => 5000, 'direct_referrals' => 10, 'qualifying_legs' => 0, 'leg_rank' => null],
    'Silver' => ['team_volume' => 2000, 'direct_referrals' => 5, 'qualifying_legs' => 0, 'leg_rank' => null],
    'Bronze' => ['team_volume' => 500, 'direct_referrals' => 2, 'qualifying_legs' => 0, 'leg_rank' => null],
    'Associate' => ['team_volume' => 0, 'direct_referrals' => 0, 'qualifying_legs' => 0, 'leg_rank' => null]
];

/**
 * Get user's direct referrals count
 */
function getDirectReferrals($user_id, $conn) {
    $result = $conn->query("SELECT COUNT(*) as count FROM mlm_users WHERE sponsor_id = $user_id");
    return $result->fetch_assoc()['count'];
}

/**
 * Get user's team volume (left + right)
 */
function getTeamVolume($user_id, $conn) {
    $result = $conn->query("SELECT left_vol, right_vol FROM mlm_wallets WHERE user_id = $user_id");
    $wallet = $result->fetch_assoc();
    return ($wallet['left_vol'] ?? 0) + ($wallet['right_vol'] ?? 0);
}

/**
 * Count qualifying legs (direct referrals who achieved specific rank)
 */
function getQualifyingLegs($user_id, $required_rank, $conn) {
    if (!$required_rank) return 0;
    
    $result = $conn->query("SELECT COUNT(*) as count FROM mlm_users 
                           WHERE sponsor_id = $user_id 
                           AND current_rank = '$required_rank'");
    return $result->fetch_assoc()['count'];
}

/**
 * Calculate qualifying rank for a user
 */
function calculateRank($user_id, $conn, $RANK_REQUIREMENTS) {
    $team_volume = getTeamVolume($user_id, $conn);
    $direct_referrals = getDirectReferrals($user_id, $conn);
    
    // Check ranks from highest to lowest
    foreach ($RANK_REQUIREMENTS as $rank => $requirements) {
        $qualifying_legs = getQualifyingLegs($user_id, $requirements['leg_rank'], $conn);
        
        // Check if user meets all requirements
        if ($team_volume >= $requirements['team_volume'] &&
            $direct_referrals >= $requirements['direct_referrals'] &&
            $qualifying_legs >= $requirements['qualifying_legs']) {
            
            return [
                'rank' => $rank,
                'team_volume' => $team_volume,
                'direct_referrals' => $direct_referrals,
                'qualifying_legs' => $qualifying_legs
            ];
        }
    }
    
    return [
        'rank' => 'Associate',
        'team_volume' => $team_volume,
        'direct_referrals' => $direct_referrals,
        'qualifying_legs' => 0
    ];
}

/**
 * Update user's rank
 */
function updateUserRank($user_id, $new_rank_data, $conn) {
    $user = $conn->query("SELECT current_rank, highest_rank FROM mlm_users WHERE id = $user_id")->fetch_assoc();
    $old_rank = $user['current_rank'];
    $new_rank = $new_rank_data['rank'];
    
    // Only update if rank changed
    if ($old_rank !== $new_rank) {
        // Update current rank
        $conn->query("UPDATE mlm_users SET 
                     current_rank = '$new_rank',
                     rank_achieved_at = NOW(),
                     highest_rank = CASE 
                         WHEN FIELD('$new_rank', 'Associate', 'Bronze', 'Silver', 'Gold', 'Platinum', 'Ruby', 'Emerald', 'Diamond', 'Crown Diamond') > 
                              FIELD(highest_rank, 'Associate', 'Bronze', 'Silver', 'Gold', 'Platinum', 'Ruby', 'Emerald', 'Diamond', 'Crown Diamond')
                         THEN '$new_rank'
                         ELSE highest_rank
                     END
                     WHERE id = $user_id");
        
        // Log to history
        $team_vol = $new_rank_data['team_volume'];
        $direct_refs = $new_rank_data['direct_referrals'];
        $qual_legs = $new_rank_data['qualifying_legs'];
        
        $conn->query("INSERT INTO mlm_rank_history (user_id, old_rank, new_rank, team_volume, direct_referrals, qualifying_legs)
                     VALUES ($user_id, '$old_rank', '$new_rank', $team_vol, $direct_refs, $qual_legs)");
        
        return true;
    }
    
    return false;
}

// ============================================
// MAIN EXECUTION
// ============================================

if (php_sapi_name() === 'cli' || isset($_GET['run'])) {
    echo "=== RANK CALCULATION ENGINE ===\n\n";
    
    $users = $conn->query("SELECT id, username, current_rank FROM mlm_users ORDER BY id");
    $updated_count = 0;
    $total_users = $users->num_rows;
    
    echo "Processing $total_users users...\n\n";
    
    while ($user = $users->fetch_assoc()) {
        $user_id = $user['id'];
        $username = $user['username'];
        $old_rank = $user['current_rank'];
        
        // Calculate new rank
        $new_rank_data = calculateRank($user_id, $conn, $RANK_REQUIREMENTS);
        $new_rank = $new_rank_data['rank'];
        
        // Update if changed
        if (updateUserRank($user_id, $new_rank_data, $conn)) {
            echo "✓ $username: $old_rank → $new_rank (Team: $" . number_format($new_rank_data['team_volume']) . ", Refs: {$new_rank_data['direct_referrals']})\n";
            $updated_count++;
        } else {
            echo "  $username: $old_rank (no change)\n";
        }
    }
    
    echo "\n=== COMPLETE ===\n";
    echo "Total users processed: $total_users\n";
    echo "Ranks updated: $updated_count\n";
} else {
    echo "Run this script from command line or add ?run=1 to URL";
}
?>
