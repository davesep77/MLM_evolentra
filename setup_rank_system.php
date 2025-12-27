<?php
require 'config_db.php';

echo "=== CAREER PROGRESSION SYSTEM - DATABASE SETUP ===\n\n";

// 1. Add rank columns to mlm_users
echo "Step 1: Adding rank columns to mlm_users table...\n";

$columns_to_add = [
    "ALTER TABLE mlm_users ADD COLUMN IF NOT EXISTS current_rank VARCHAR(50) DEFAULT 'Associate'",
    "ALTER TABLE mlm_users ADD COLUMN IF NOT EXISTS rank_achieved_at DATETIME NULL",
    "ALTER TABLE mlm_users ADD COLUMN IF NOT EXISTS highest_rank VARCHAR(50) DEFAULT 'Associate'"
];

foreach ($columns_to_add as $sql) {
    if ($conn->query($sql)) {
        echo "✓ Column added successfully\n";
    } else {
        echo "✗ Error: " . $conn->error . "\n";
    }
}

// 2. Create rank history table
echo "\nStep 2: Creating mlm_rank_history table...\n";

$create_table = "CREATE TABLE IF NOT EXISTS mlm_rank_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    old_rank VARCHAR(50),
    new_rank VARCHAR(50),
    achieved_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    team_volume DECIMAL(15,2) DEFAULT 0,
    direct_referrals INT DEFAULT 0,
    qualifying_legs INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES mlm_users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_achieved_at (achieved_at)
)";

if ($conn->query($create_table)) {
    echo "✓ mlm_rank_history table created successfully\n";
} else {
    echo "✗ Error: " . $conn->error . "\n";
}

// 3. Initialize existing users with Associate rank
echo "\nStep 3: Initializing existing users with Associate rank...\n";

$init_sql = "UPDATE mlm_users SET current_rank = 'Associate', highest_rank = 'Associate' WHERE current_rank IS NULL OR current_rank = ''";

if ($conn->query($init_sql)) {
    $affected = $conn->affected_rows;
    echo "✓ Initialized $affected users with Associate rank\n";
} else {
    echo "✗ Error: " . $conn->error . "\n";
}

// 4. Verify setup
echo "\nStep 4: Verifying database setup...\n";

$verify = $conn->query("SELECT COUNT(*) as count FROM mlm_users WHERE current_rank = 'Associate'");
$count = $verify->fetch_assoc()['count'];
echo "✓ Total users with rank: $count\n";

$verify_table = $conn->query("SHOW TABLES LIKE 'mlm_rank_history'");
if ($verify_table->num_rows > 0) {
    echo "✓ Rank history table exists\n";
}

echo "\n=== DATABASE SETUP COMPLETE ===\n";
echo "You can now run calculate_ranks.php to assign ranks based on performance.\n";
?>
