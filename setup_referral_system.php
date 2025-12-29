<?php
/**
 * Referral System Database Setup
 * Creates tables for comprehensive referral tracking and analytics
 */

require 'config_db.php';

echo "=== Evolentra Referral System Setup ===\n\n";

// 1. Create mlm_referral_links table
echo "Creating mlm_referral_links table...\n";
$sql_links = "CREATE TABLE IF NOT EXISTS mlm_referral_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    referral_code VARCHAR(50) NOT NULL UNIQUE,
    link_type ENUM('general', 'left', 'right') DEFAULT 'general',
    clicks INT DEFAULT 0,
    conversions INT DEFAULT 0,
    total_earned DECIMAL(15,2) DEFAULT 0.00,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES mlm_users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_code (referral_code),
    INDEX idx_active (is_active),
    INDEX idx_type (link_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql_links) === TRUE) {
    echo "✓ mlm_referral_links table created successfully\n";
} else {
    echo "✗ Error creating mlm_referral_links: " . $conn->error . "\n";
}

// 2. Create mlm_referral_clicks table
echo "\nCreating mlm_referral_clicks table...\n";
$sql_clicks = "CREATE TABLE IF NOT EXISTS mlm_referral_clicks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    link_id INT NOT NULL,
    referral_code VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    country VARCHAR(100),
    clicked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    converted TINYINT(1) DEFAULT 0,
    converted_user_id INT NULL,
    FOREIGN KEY (link_id) REFERENCES mlm_referral_links(id) ON DELETE CASCADE,
    INDEX idx_link (link_id),
    INDEX idx_code (referral_code),
    INDEX idx_converted (converted),
    INDEX idx_ip (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql_clicks) === TRUE) {
    echo "✓ mlm_referral_clicks table created successfully\n";
} else {
    echo "✗ Error creating mlm_referral_clicks: " . $conn->error . "\n";
}

// 3. Create mlm_referral_earnings table
echo "\nCreating mlm_referral_earnings table...\n";
$sql_earnings = "CREATE TABLE IF NOT EXISTS mlm_referral_earnings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referrer_id INT NOT NULL,
    referred_user_id INT NOT NULL,
    investment_amount DECIMAL(15,2) NOT NULL,
    commission_rate DECIMAL(5,4) NOT NULL,
    commission_amount DECIMAL(15,2) NOT NULL,
    level INT DEFAULT 1,
    status ENUM('pending', 'paid', 'capped', 'flushed') DEFAULT 'paid',
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (referrer_id) REFERENCES mlm_users(id) ON DELETE CASCADE,
    FOREIGN KEY (referred_user_id) REFERENCES mlm_users(id) ON DELETE CASCADE,
    INDEX idx_referrer (referrer_id),
    INDEX idx_referred (referred_user_id),
    INDEX idx_status (status),
    INDEX idx_earned_at (earned_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql_earnings) === TRUE) {
    echo "✓ mlm_referral_earnings table created successfully\n";
} else {
    echo "✗ Error creating mlm_referral_earnings: " . $conn->error . "\n";
}

// 4. Ensure referral_code column exists in mlm_users
echo "\nChecking mlm_users table for referral_code column...\n";
$check_column = $conn->query("SHOW COLUMNS FROM mlm_users LIKE 'referral_code'");
if ($check_column->num_rows == 0) {
    echo "Adding referral_code column to mlm_users...\n";
    $add_column = "ALTER TABLE mlm_users ADD COLUMN referral_code VARCHAR(50) UNIQUE AFTER username";
    if ($conn->query($add_column) === TRUE) {
        echo "✓ referral_code column added successfully\n";
    } else {
        echo "✗ Error adding referral_code column: " . $conn->error . "\n";
    }
} else {
    echo "✓ referral_code column already exists\n";
}

// 5. Add index on referral_code if not exists
echo "\nAdding index on referral_code...\n";
$check_index = $conn->query("SHOW INDEX FROM mlm_users WHERE Key_name = 'idx_referral_code'");
if ($check_index->num_rows == 0) {
    $add_index = "ALTER TABLE mlm_users ADD INDEX idx_referral_code (referral_code)";
    if ($conn->query($add_index) === TRUE) {
        echo "✓ Index added successfully\n";
    } else {
        echo "✗ Error adding index: " . $conn->error . "\n";
    }
} else {
    echo "✓ Index already exists\n";
}

// 6. Verify all columns exist in mlm_referral_links
echo "\nVerifying mlm_referral_links columns...\n";
$required_columns = [
    'link_type' => "ENUM('general', 'left', 'right') DEFAULT 'general'",
    'clicks' => "INT DEFAULT 0",
    'conversions' => "INT DEFAULT 0",
    'total_earned' => "DECIMAL(15,2) DEFAULT 0.00"
];

foreach ($required_columns as $column => $definition) {
    $check = $conn->query("SHOW COLUMNS FROM mlm_referral_links LIKE '$column'");
    if ($check->num_rows == 0) {
        echo "Adding column $column...\n";
        $sql = "ALTER TABLE mlm_referral_links ADD COLUMN $column $definition";
        if ($conn->query($sql) === TRUE) {
            echo "✓ Column $column added\n";
        } else {
            echo "✗ Error adding $column: " . $conn->error . "\n";
        }
    } else {
        echo "✓ Column $column exists\n";
    }
}

echo "\n=== Setup Complete ===\n";
echo "Next step: Run migrate_referral_data.php to populate the tables\n";

$conn->close();
?>
