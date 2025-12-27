<?php
require '../config_db.php';

// Ensure required settings exist
$conn->query("INSERT IGNORE INTO mlm_system_settings (setting_key, setting_value, description) VALUES ('master_binance_address', '', 'Central Binance Address for all collections')");

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = "";

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        if ($key === 'update_settings') continue;
        
        $value = $conn->real_escape_string($value);
        $conn->query("UPDATE mlm_system_settings SET setting_value='$value' WHERE setting_key='$key'");
    }
    $message = "Settings updated successfully.";
}

// Fetch all settings
$settings = [];
$res = $conn->query("SELECT * FROM mlm_system_settings");
while ($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Settings - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f172a; color: #f8fafc; }
        .glass-card { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 1rem; padding: 1.5rem; }
    </style>
</head>
<body class="flex">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 ml-[280px] p-8">
        <header class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-white">System Settings</h1>
        </header>

        <?php if($message): ?>
            <div class="bg-indigo-500/20 text-indigo-200 p-4 rounded mb-6 border border-indigo-500/50">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="glass-card max-w-2xl">
            <form method="POST">
                <h3 class="text-xl font-bold mb-6 pb-2 border-b border-white/10">Financial Settings</h3>
                
                <div class="mb-6">
                    <label class="block text-sm font-bold text-slate-300 mb-2">Minimum Withdrawal Amount ($)</label>
                    <input type="number" name="min_withdrawal" value="<?= htmlspecialchars($settings['min_withdrawal']['setting_value'] ?? '10') ?>" class="w-full bg-slate-900 border border-slate-700 rounded p-3 text-white focus:outline-none focus:border-indigo-500">
                    <p class="text-xs text-slate-500 mt-1">Users cannot withdraw less than this amount.</p>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-bold text-slate-300 mb-2">Withdrawal Fee (%)</label>
                    <input type="number" name="withdrawal_fee_percent" value="<?= htmlspecialchars($settings['withdrawal_fee_percent']['setting_value'] ?? '2') ?>" class="w-full bg-slate-900 border border-slate-700 rounded p-3 text-white focus:outline-none focus:border-indigo-500">
                    <p class="text-xs text-slate-500 mt-1">Percentage deducted from every withdrawal.</p>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-bold text-slate-300 mb-2">Max Daily Withdrawal Limit ($)</label>
                    <input type="number" name="max_daily_withdrawal" value="<?= htmlspecialchars($settings['max_daily_withdrawal']['setting_value'] ?? '10000') ?>" class="w-full bg-slate-900 border border-slate-700 rounded p-3 text-white focus:outline-none focus:border-indigo-500">
                </div>

                <h3 class="text-xl font-bold mb-6 pb-2 border-b border-white/10 pt-4">Binance Integration</h3>
                
                <div class="mb-6">
                    <label class="block text-sm font-bold text-slate-300 mb-2">Master Admin Binance Address (USDT - BEP20/TRC20)</label>
                    <input type="text" name="master_binance_address" value="<?= htmlspecialchars($settings['master_binance_address']['setting_value'] ?? '') ?>" placeholder="Enter Master Wallet Address" class="w-full bg-slate-900 border border-slate-700 rounded p-3 text-white font-mono text-sm focus:outline-none focus:border-indigo-500">
                    <p class="text-xs text-slate-500 mt-1">Manual collection point.</p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-bold text-slate-300 mb-2">Binance Pay API Key</label>
                    <input type="text" name="binance_pay_api_key" value="<?= htmlspecialchars($settings['binance_pay_api_key']['setting_value'] ?? '') ?>" class="w-full bg-slate-900 border border-slate-700 rounded p-3 text-white font-mono text-sm focus:outline-none focus:border-indigo-500">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-bold text-slate-300 mb-2">Binance Pay Secret Key</label>
                    <input type="password" name="binance_pay_secret" value="<?= htmlspecialchars($settings['binance_pay_secret']['setting_value'] ?? '') ?>" class="w-full bg-slate-900 border border-slate-700 rounded p-3 text-white font-mono text-sm focus:outline-none focus:border-indigo-500">
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-bold text-slate-300 mb-2">Binance Pay Environment</label>
                    <select name="binance_pay_env" class="w-full bg-slate-900 border border-slate-700 rounded p-3 text-white focus:outline-none focus:border-indigo-500">
                        <option value="sandbox" <?= ($settings['binance_pay_env']['setting_value'] ?? 'sandbox') == 'sandbox' ? 'selected' : '' ?>>Sandbox (Testnet)</option>
                        <option value="live" <?= ($settings['binance_pay_env']['setting_value'] ?? 'sandbox') == 'live' ? 'selected' : '' ?>>Production (Live)</option>
                    </select>
                </div>

                <h3 class="text-xl font-bold mb-6 pb-2 border-b border-white/10 pt-4">Maintenance</h3>
                
                <div class="mb-6 flex items-center gap-4">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="maintenance_mode" value="0">
                        <input type="checkbox" name="maintenance_mode" value="1" class="sr-only peer" <?= ($settings['maintenance_mode']['setting_value'] ?? '0') == '1' ? 'checked' : '' ?>>
                        <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                        <span class="ml-3 text-sm font-medium text-slate-300">Enable Maintenance Mode</span>
                    </label>
                </div>

                <h3 class="text-xl font-bold mb-6 pb-2 border-b border-white/10 pt-4">Capital Control</h3>
                <p class="text-xs text-slate-400 mb-4">Consolidate all system funds from user wallets into your master admin account for bulk binance transfer.</p>
                <div class="mb-8">
                    <a href="consolidate_funds.php" onclick="return confirm('CRITICAL ACTION: This will zero out ALL user balances and transfer them to your admin account. Proceed?');" class="block text-center bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3 rounded border border-emerald-400/30 transition-all">
                        <i class="fas fa-money-bill-transfer mr-2"></i> Consolidate All System Funds
                    </a>
                </div>

                <div class="pt-4 border-t border-white/10">
                    <button type="submit" name="update_settings" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-3 rounded transition-all">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
