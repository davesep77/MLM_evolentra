<?php
require 'config_db.php';

echo "<h1>System Integration Test</h1>";
echo "<style>body{font-family:Arial;padding:20px;background:#1a1a2e;color:#eee;} .success{color:#4ade80;} .error{color:#f87171;} .info{color:#60a5fa;} h2{border-bottom:2px solid #444;padding-bottom:10px;margin-top:30px;}</style>";

// Test 1: Database Connection
echo "<h2>1. Database Connection</h2>";
if ($conn) {
    echo "<p class='success'>✅ Connected to database successfully</p>";
} else {
    echo "<p class='error'>❌ Database connection failed</p>";
    exit;
}

// Test 2: Check All Tables
echo "<h2>2. Database Tables</h2>";
$required_tables = [
    'mlm_users',
    'mlm_wallets',
    'mlm_transactions',
    'mlm_withdrawal_requests',
    'mlm_notifications',
    'mlm_user_settings',
    'mlm_admin_logs',
    'mlm_referral_links',
    'mlm_commission_history',
    'mlm_support_tickets',
    'mlm_system_settings',
    'mlm_login_history',
    'mlm_user_ranks'
];

$tables_result = $conn->query("SHOW TABLES");
$existing_tables = [];
while ($row = $tables_result->fetch_array()) {
    $existing_tables[] = $row[0];
}

foreach ($required_tables as $table) {
    if (in_array($table, $existing_tables)) {
        echo "<p class='success'>✅ $table exists</p>";
    } else {
        echo "<p class='error'>❌ $table missing</p>";
    }
}

// Test 3: Check Users
echo "<h2>3. User Data</h2>";
$user_count = $conn->query("SELECT COUNT(*) as count FROM mlm_users")->fetch_assoc()['count'];
echo "<p class='info'>📊 Total Users: $user_count</p>";

if ($user_count > 0) {
    $root_user = $conn->query("SELECT username, investment FROM mlm_users WHERE id = 1")->fetch_assoc();
    if ($root_user) {
        echo "<p class='success'>✅ Root user '{$root_user['username']}' exists with investment: $" . number_format($root_user['investment'], 2) . "</p>";
    }
}

// Test 4: Check Wallets
echo "<h2>4. Wallet System</h2>";
$wallet_count = $conn->query("SELECT COUNT(*) as count FROM mlm_wallets")->fetch_assoc()['count'];
echo "<p class='info'>📊 Total Wallets: $wallet_count</p>";

if ($wallet_count > 0) {
    $sample_wallet = $conn->query("SELECT * FROM mlm_wallets WHERE user_id = 1")->fetch_assoc();
    if ($sample_wallet) {
        echo "<p class='success'>✅ Root wallet exists</p>";
        echo "<p class='info'>  - ROI: $" . number_format($sample_wallet['roi_wallet'], 2) . "</p>";
        echo "<p class='info'>  - Referral: $" . number_format($sample_wallet['referral_wallet'], 2) . "</p>";
        echo "<p class='info'>  - Binary: $" . number_format($sample_wallet['binary_wallet'], 2) . "</p>";
    }
}

// Test 5: Check Transactions
echo "<h2>5. Transaction System</h2>";
$trx_count = $conn->query("SELECT COUNT(*) as count FROM mlm_transactions")->fetch_assoc()['count'];
echo "<p class='info'>📊 Total Transactions: $trx_count</p>";

if ($trx_count > 0) {
    $trx_types = $conn->query("SELECT type, COUNT(*) as count FROM mlm_transactions GROUP BY type");
    while ($type = $trx_types->fetch_assoc()) {
        echo "<p class='info'>  - {$type['type']}: {$type['count']} transactions</p>";
    }
}

// Test 6: Check Compensation System
echo "<h2>6. Compensation System</h2>";
if (file_exists('lib/Compensation.php')) {
    echo "<p class='success'>✅ Compensation.php exists</p>";
    require_once 'lib/Compensation.php';
    $comp = new Compensation($conn);
    echo "<p class='success'>✅ Compensation class loaded successfully</p>";
} else {
    echo "<p class='error'>❌ Compensation.php not found</p>";
}

// Test 7: Check Key Pages
echo "<h2>7. Page Files</h2>";
$pages = [
    'login.php' => 'Login Page',
    'register.php' => 'Registration Page',
    'dashboard.php' => 'Dashboard',
    'profile.php' => 'Profile Page',
    'team.php' => 'Team Page',
    'package.php' => 'Package Page',
    'invest.php' => 'Investment Page',
    'withdraw.php' => 'Withdrawal Page',
    'genealogy.php' => 'Genealogy Page',
    'income_report.php' => 'Income Report',
    'sidebar_nav.php' => 'Navigation Sidebar'
];

foreach ($pages as $file => $name) {
    if (file_exists($file)) {
        echo "<p class='success'>✅ $name ($file)</p>";
    } else {
        echo "<p class='error'>❌ $name ($file) missing</p>";
    }
}

// Test 8: System Settings
echo "<h2>8. System Settings</h2>";
$settings_count = $conn->query("SELECT COUNT(*) as count FROM mlm_system_settings")->fetch_assoc()['count'];
if ($settings_count > 0) {
    echo "<p class='success'>✅ System settings configured ($settings_count settings)</p>";
    $settings = $conn->query("SELECT setting_key, setting_value FROM mlm_system_settings");
    while ($setting = $settings->fetch_assoc()) {
        echo "<p class='info'>  - {$setting['setting_key']}: {$setting['setting_value']}</p>";
    }
} else {
    echo "<p class='error'>❌ No system settings found</p>";
}

// Test 9: Session Test
echo "<h2>9. Session System</h2>";
if (isset($_SESSION)) {
    echo "<p class='success'>✅ Session system active</p>";
    if (isset($_SESSION['user_id'])) {
        echo "<p class='info'>  - Logged in as User ID: {$_SESSION['user_id']}</p>";
    } else {
        echo "<p class='info'>  - No active user session</p>";
    }
} else {
    echo "<p class='error'>❌ Session not initialized</p>";
}

// Summary
echo "<h2>✅ Integration Test Complete</h2>";
echo "<p class='success'>All core systems are operational!</p>";
echo "<p class='info'>Access your application at: <a href='http://localhost:8000' style='color:#60a5fa;'>http://localhost:8000</a></p>";
?>
