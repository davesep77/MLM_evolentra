<?php
/**
 * AUTOMATED RANK CALCULATION CRON JOB
 * Run this script periodically (recommended: daily) to update user ranks
 * Can be triggered via cron: 0 2 * * * /usr/bin/php /path/to/cron_ranks.php
 */

require 'config_db.php';
require 'calculate_ranks.php';

// Log start
$log_file = __DIR__ . '/logs/rank_updates.log';
$log_dir = dirname($log_file);
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0777, true);
}

function log_message($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

log_message("=== STARTING AUTOMATED RANK CALCULATION ===");

try {
    $users = $conn->query("SELECT id, username, current_rank FROM mlm_users ORDER BY id");
    $updated_count = 0;
    $total_users = $users->num_rows;
    
    log_message("Processing $total_users users...");
    
    while ($user = $users->fetch_assoc()) {
        $user_id = $user['id'];
        $username = $user['username'];
        $old_rank = $user['current_rank'];
        
        // Calculate new rank
        $new_rank_data = calculateRank($user_id, $conn, $RANK_REQUIREMENTS);
        $new_rank = $new_rank_data['rank'];
        
        // Update if changed
        if (updateUserRank($user_id, $new_rank_data, $conn)) {
            log_message("✓ $username: $old_rank → $new_rank (Team: $" . number_format($new_rank_data['team_volume']) . ", Refs: {$new_rank_data['direct_referrals']})");
            $updated_count++;
            
            // Create notification for user
            $notification_msg = "Congratulations! You've been promoted to $new_rank rank!";
            $conn->query("INSERT INTO mlm_notifications (user_id, type, message, created_at) 
                         VALUES ($user_id, 'rank_update', '$notification_msg', NOW())");
        }
    }
    
    log_message("=== COMPLETE ===");
    log_message("Total users processed: $total_users");
    log_message("Ranks updated: $updated_count");
    
    // Output for CLI
    if (php_sapi_name() === 'cli') {
        echo "Rank calculation complete.\n";
        echo "Total users: $total_users\n";
        echo "Ranks updated: $updated_count\n";
    }
    
} catch (Exception $e) {
    log_message("ERROR: " . $e->getMessage());
    if (php_sapi_name() === 'cli') {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

log_message("");
?>
