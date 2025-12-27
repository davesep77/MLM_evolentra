<?php
require '../config_db.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied.");
}

$conn->begin_transaction();

try {
    // 1. Get Admin Details
    $admin_id = $_SESSION['user_id'];
    
    // 2. Fetch the Master Binance Address
    $set_res = $conn->query("SELECT setting_value FROM mlm_system_settings WHERE setting_key='master_binance_address'");
    $master_address = $set_res->fetch_assoc()['setting_value'] ?? 'NOT_SET';

    if (empty($master_address) || $master_address == 'NOT_SET') {
        throw new Exception("Error: Master Binance Address is not configured in Settings.");
    }

    // 3. Sum all user funds (excluding the admin themselves)
    $res = $conn->query("SELECT SUM(roi_wallet) as total_roi, SUM(referral_wallet) as total_ref, SUM(binary_wallet) as total_bin FROM mlm_wallets WHERE user_id != $admin_id");
    $sums = $res->fetch_assoc();
    $total_to_sweep = ($sums['total_roi'] ?? 0) + ($sums['total_ref'] ?? 0) + ($sums['total_bin'] ?? 0);

    if ($total_to_sweep <= 0) {
        throw new Exception("Zero balance found in user accounts. Nothing to sweep.");
    }

    // 4. Update User Wallets to Zero
    $conn->query("UPDATE mlm_wallets SET roi_wallet = 0, referral_wallet = 0, binary_wallet = 0 WHERE user_id != $admin_id");

    // 5. Transfer to Admin Wallet (Consolidate into binary_wallet as a pooled fund)
    $conn->query("UPDATE mlm_wallets SET binary_wallet = binary_wallet + $total_to_sweep WHERE user_id = $admin_id");

    // 6. Log Transaction
    $conn->query("INSERT INTO mlm_transactions (user_id, type, amount, description) 
                  VALUES ($admin_id, 'CONSOLIDATION', $total_to_sweep, 'Funds swept from all user accounts to Master Wallet: $master_address')");

    $conn->commit();
    $status = "success";
    $message = "Successfully consolidated $" . number_format($total_to_sweep, 2) . " into the Admin Master Account.";
} catch (Exception $e) {
    $conn->rollback();
    $status = "error";
    $message = $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Funds Consolidation - Evolentra</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#0f172a] text-white flex items-center justify-center min-height-screen p-8">
    <div class="max-w-md w-full bg-slate-800 border border-slate-700 p-8 rounded-2xl text-center">
        <?php if ($status === 'success'): ?>
            <div class="text-emerald-400 text-6xl mb-4">✅</div>
            <h2 class="text-2xl font-bold mb-2">Consolidation Complete</h2>
            <p class="text-slate-400 mb-6"><?= $message ?></p>
        <?php else: ?>
            <div class="text-red-400 text-6xl mb-4">❌</div>
            <h2 class="text-2xl font-bold mb-2">Sweep Failed</h2>
            <p class="text-slate-400 mb-6"><?= $message ?></p>
        <?php endif; ?>
        
        <a href="settings.php" class="inline-block bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-3 px-8 rounded-xl transition-all">Return to Settings</a>
    </div>
</body>
</html>
