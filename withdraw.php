<?php
require 'config_db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";
$error_msg = "";

// Fetch User Investment & Wallets
$u_query = $conn->query("SELECT investment FROM mlm_users WHERE id=$user_id");
$investment = $u_query->fetch_assoc()['investment'] ?? 0;

$w_query = $conn->query("SELECT * FROM mlm_wallets WHERE user_id=$user_id");
$wallet = $w_query->fetch_assoc();

// Fetch User Local Payout Settings
$settings_query = $conn->query("SELECT * FROM mlm_user_settings WHERE user_id=$user_id");
$u_settings = $settings_query->fetch_assoc();

// Fetch Master Binance Address (Admin Only / Fallback)
$master_addr_res = $conn->query("SELECT setting_value FROM mlm_system_settings WHERE setting_key='master_binance_address'");
$master_binance = $master_addr_res->fetch_assoc()['setting_value'] ?? '';

// Determine Daily Limit (Section 6.1)
$daily_limit = 2000;
if ($investment > 5000) $daily_limit = 2500;
if ($investment > 20000) $daily_limit = 10000; // Admin defined fallback

// Calculate Today's Withdrawals
$today = date('Y-m-d');
$trx_query = $conn->query("SELECT SUM(amount) as total FROM mlm_transactions WHERE user_id=$user_id AND type='WITHDRAWAL' AND DATE(created_at) = '$today'");
$withdrawn_today = $trx_query->fetch_assoc()['total'] ?? 0;
$remaining_limit = $daily_limit - $withdrawn_today;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $amount = $_POST['amount'];
    $wallet_type = $_POST['wallet_type']; // roi_wallet, referral_wallet, binary_wallet
    $network = $_POST['network'] ?? 'BEP20';
    $binance_status = $u_settings['binance_link_status'] ?? 'unlinked';

    if ($binance_status !== 'verified') {
        $error_msg = "Error: Your Binance account is not verified. Please connect and verify your account in your profile.";
    } elseif ($amount > 0) {
        if ($amount > $remaining_limit) {
            $error_msg = "Error: Daily limit exceeded. You can only withdraw $" . number_format($remaining_limit, 2) . " more today.";
        } elseif ($amount > $wallet[$wallet_type]) {
            $error_msg = "Error: Insufficient funds in selected wallet.";
        } else {
            // Process Withdrawal
            // 1. Deduct from wallet
            $conn->query("UPDATE mlm_wallets SET $wallet_type = $wallet_type - $amount WHERE user_id=$user_id");

            // 2. Log Transaction
            $clean_type = strtoupper(str_replace('_wallet', '', $wallet_type));
            $payout_address = ($network == 'BEP20') ? ($u_settings['usdt_address_bep20'] ?? 'Not Set') : ($u_settings['usdt_address_trc20'] ?? 'Not Set');
            
            $conn->query("INSERT INTO mlm_withdrawal_requests (user_id, amount, wallet_type, payment_method, payment_address, status) 
                          VALUES ($user_id, $amount, '$wallet_type', 'USDT-$network', '$payout_address', 'pending')");
            
            $conn->query("INSERT INTO mlm_transactions (user_id, type, amount, description) 
                          VALUES ($user_id, 'WITHDRAWAL', $amount, 'Withdrawal to Binance ($network: $payout_address)')");

            $message = "Success! Your withdrawal to your Binance $network account is being processed.";
            
            // Refresh logic
            $withdrawn_today += $amount;
            $remaining_limit -= $amount;
            $wallet[$wallet_type] -= $amount; // Visual update
        }
    } else {
        $error_msg = "Enter a valid amount.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Withdraw - Evolentra</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="glass-card" style="max-width: 500px; margin: 2rem auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2>Request Withdrawal</h2>
                <a href="dashboard.php" class="btn btn-outline">&larr; Back</a>
            </div>

            <!-- Limit Info -->
            <div style="background: rgba(59, 130, 246, 0.1); padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; border-left: 4px solid #3b82f6;">
                <p style="margin: 0; font-size: 0.875rem; color: #e2e8f0;">Active Tier Limit: <b>$<?= number_format($daily_limit) ?>/day</b></p>
                <div style="display: flex; justify-content: space-between; margin-top: 0.5rem; font-size: 0.75rem; color: #94a3b8;">
                    <span>Used: $<?= number_format($withdrawn_today, 2) ?></span>
                    <span style="color: #4ade80;">Remaining: $<?= number_format($remaining_limit, 2) ?></span>
                </div>
            </div>

            <?php if($message): ?>
                <div style="background: rgba(16, 185, 129, 0.2); color: #86efac; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                    <?= $message ?>
                </div>
            <?php endif; ?>
            <?php if($error_msg): ?>
                <div style="background: rgba(239, 68, 68, 0.2); color: #fca5a5; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                    <?= $error_msg ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Select Wallet</label>
                    <select name="wallet_type" style="width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); color: #fff; border-radius: 0.5rem;">
                        <option value="roi_wallet">ROI Wallet ($<?= number_format($wallet['roi_wallet'], 2) ?>)</option>
                        <option value="referral_wallet">Referral Wallet ($<?= number_format($wallet['referral_wallet'], 2) ?>)</option>
                        <option value="binary_wallet">Binary Wallet ($<?= number_format($wallet['binary_wallet'], 2) ?>)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Binance Payout Network</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 0.5rem;">
                        <label class="method-card <?= !empty($u_settings['usdt_address_bep20']) ? 'active' : 'disabled' ?>">
                            <input type="radio" name="network" value="BEP20" <?= !empty($u_settings['usdt_address_bep20']) ? 'checked' : 'disabled' ?> hidden>
                            <i class="fa-brands fa-bitcoin" style="color: #f3ba2f; font-size: 1.25rem;"></i>
                            <span>USDT (BEP20)</span>
                            <div style="font-size: 0.6rem; opacity: 0.6; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100%;">
                                <?= htmlspecialchars($u_settings['usdt_address_bep20'] ?? 'Not Linked') ?>
                            </div>
                        </label>
                        <label class="method-card <?= !empty($u_settings['usdt_address_trc20']) ? 'active' : 'disabled' ?>">
                            <input type="radio" name="network" value="TRC20" <?= empty($u_settings['usdt_address_bep20']) && !empty($u_settings['usdt_address_trc20']) ? 'checked' : '' ?> <?= !empty($u_settings['usdt_address_trc20']) ? '' : 'disabled' ?> hidden>
                            <i class="fa-solid fa-circle-t" style="color: #26a17b; font-size: 1.25rem;"></i>
                            <span>USDT (TRC20)</span>
                            <div style="font-size: 0.6rem; opacity: 0.6; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100%;">
                                <?= htmlspecialchars($u_settings['usdt_address_trc20'] ?? 'Not Linked') ?>
                            </div>
                        </label>
                    </div>
                    <?php if(empty($u_settings['usdt_address_bep20']) && empty($u_settings['usdt_address_trc20'])): ?>
                        <p style="font-size: 0.75rem; color: #fca5a5; margin-top: 0.5rem;">
                            <i class="fas fa-exclamation-triangle"></i> No Binance address linked. <a href="profile.php" style="color: #fff; text-decoration: underline;">Connect now &rarr;</a>
                        </p>
                    <?php endif; ?>
                </div>

                <style>
                    .method-card {
                        background: rgba(255,255,255,0.03);
                        border: 1px solid rgba(255,255,255,0.1);
                        border-radius: 0.75rem;
                        padding: 1rem;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        gap: 0.25rem;
                        cursor: pointer;
                        transition: all 0.2s;
                        text-align: center;
                    }
                    .method-card:hover { border-color: rgba(243, 186, 47, 0.4); }
                    .method-card.disabled { opacity: 0.4; cursor: not-allowed; }
                    .method-card input:checked + * + span, 
                    .method-card:has(input:checked) {
                        background: rgba(243, 186, 47, 0.1);
                        border-color: #f3ba2f;
                    }
                </style>

                <div class="form-group">
                    <label>Amount (USD)</label>
                    <input type="number" name="amount" min="10" placeholder="Enter amount" required <?= ($u_settings['binance_link_status'] ?? 'unlinked') !== 'verified' ? 'disabled' : '' ?>>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;" <?= ($u_settings['binance_link_status'] ?? 'unlinked') !== 'verified' ? 'disabled' : '' ?>>
                    <?= ($u_settings['binance_link_status'] ?? 'unlinked') !== 'verified' ? 'Account Unverified' : 'Process Request' ?>
                </button>
            </form>
            
            <div style="margin-top: 2rem; font-size: 0.75rem; color: #94a3b8; text-align: center;">
                Subject to platform withdrawal rules (Section 6.1).<br>
                Binance Smart Chain (BEP20) & TRON (TRC20) processed within 24h.
            </div>
        </div>
    </div>
</body>
</html>
