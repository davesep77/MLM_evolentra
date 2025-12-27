<?php
require 'config_db.php';

echo "<h2>Adding Additional Database Tables</h2>";

// 1. Withdrawal Requests Table
echo "<h3>Creating mlm_withdrawal_requests table...</h3>";
$sql = "CREATE TABLE IF NOT EXISTS mlm_withdrawal_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    wallet_type ENUM('roi_wallet', 'referral_wallet', 'binary_wallet') NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    payment_address VARCHAR(255) NULL,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    admin_note TEXT NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES mlm_users(id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, status),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "✅ mlm_withdrawal_requests table created successfully<br>";
} else {
    echo "❌ Error: " . $conn->error . "<br>";
}

// 2. Notifications Table
echo "<h3>Creating mlm_notifications table...</h3>";
$sql = "CREATE TABLE IF NOT EXISTS mlm_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES mlm_users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "✅ mlm_notifications table created successfully<br>";
} else {
    echo "❌ Error: " . $conn->error . "<br>";
}

// 3. User Settings Table
echo "<h3>Creating mlm_user_settings table...</h3>";
$sql = "CREATE TABLE IF NOT EXISTS mlm_user_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    payment_method VARCHAR(50) DEFAULT 'BTC',
    payment_address VARCHAR(255) NULL,
    email_notifications BOOLEAN DEFAULT TRUE,
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    two_factor_secret VARCHAR(255) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES mlm_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "✅ mlm_user_settings table created successfully<br>";
} else {
    echo "❌ Error: " . $conn->error . "<br>";
}

// 4. Admin Activity Log
echo "<h3>Creating mlm_admin_logs table...</h3>";
$sql = "CREATE TABLE IF NOT EXISTS mlm_admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    target_user_id INT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES mlm_users(id) ON DELETE CASCADE,
    INDEX idx_admin (admin_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "✅ mlm_admin_logs table created successfully<br>";
} else {
    echo "❌ Error: " . $conn->error . "<br>";
}

// 5. Referral Links/Codes Table
echo "<h3>Creating mlm_referral_links table...</h3>";
$sql = "CREATE TABLE IF NOT EXISTS mlm_referral_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    referral_code VARCHAR(50) NOT NULL UNIQUE,
    clicks INT DEFAULT 0,
    conversions INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES mlm_users(id) ON DELETE CASCADE,
    INDEX idx_code (referral_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "✅ mlm_referral_links table created successfully<br>";
} else {
    echo "❌ Error: " . $conn->error . "<br>";
}

// 6. Commission History Table (detailed tracking)
echo "<h3>Creating mlm_commission_history table...</h3>";
$sql = "CREATE TABLE IF NOT EXISTS mlm_commission_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    commission_type ENUM('roi', 'referral', 'binary') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    from_user_id INT NULL,
    level INT DEFAULT 1,
    calculation_details TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES mlm_users(id) ON DELETE CASCADE,
    INDEX idx_user_type (user_id, commission_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "✅ mlm_commission_history table created successfully<br>";
} else {
    echo "❌ Error: " . $conn->error . "<br>";
}

// 7. Support Tickets Table
echo "<h3>Creating mlm_support_tickets table...</h3>";
$sql = "CREATE TABLE IF NOT EXISTS mlm_support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('open', 'in_progress', 'closed') DEFAULT 'open',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    admin_response TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES mlm_users(id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "✅ mlm_support_tickets table created successfully<br>";
} else {
    echo "❌ Error: " . $conn->error . "<br>";
}

// 8. System Settings Table
echo "<h3>Creating mlm_system_settings table...</h3>";
$sql = "CREATE TABLE IF NOT EXISTS mlm_system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    description VARCHAR(255) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "✅ mlm_system_settings table created successfully<br>";
    
    // Insert default settings
    $defaults = [
        ['min_withdrawal', '10', 'Minimum withdrawal amount'],
        ['withdrawal_fee_percent', '2', 'Withdrawal fee percentage'],
        ['max_daily_withdrawal', '10000', 'Maximum daily withdrawal limit'],
        ['roi_processing_time', '00:00:00', 'Daily ROI processing time'],
        ['binary_processing_time', '00:00:00', 'Binary commission processing time'],
        ['maintenance_mode', '0', 'Maintenance mode (0=off, 1=on)']
    ];
    
    foreach ($defaults as $setting) {
        $conn->query("INSERT IGNORE INTO mlm_system_settings (setting_key, setting_value, description) 
                      VALUES ('{$setting[0]}', '{$setting[1]}', '{$setting[2]}')");
    }
    echo "✅ Default system settings inserted<br>";
} else {
    echo "❌ Error: " . $conn->error . "<br>";
}

// 9. Login History Table (Security)
echo "<h3>Creating mlm_login_history table...</h3>";
$sql = "CREATE TABLE IF NOT EXISTS mlm_login_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    login_status ENUM('success', 'failed') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES mlm_users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "✅ mlm_login_history table created successfully<br>";
} else {
    echo "❌ Error: " . $conn->error . "<br>";
}

// 10. Rank/Achievement Table
echo "<h3>Creating mlm_user_ranks table...</h3>";
$sql = "CREATE TABLE IF NOT EXISTS mlm_user_ranks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    rank_name VARCHAR(100) NOT NULL,
    rank_level INT NOT NULL,
    achieved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    requirements_met TEXT NULL,
    FOREIGN KEY (user_id) REFERENCES mlm_users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "✅ mlm_user_ranks table created successfully<br>";
} else {
    echo "❌ Error: " . $conn->error . "<br>";
}

echo "<br><h2>✅ All Additional Tables Created Successfully!</h2>";
echo "<p><a href='dashboard.php'>Go to Dashboard</a></p>";
?>
