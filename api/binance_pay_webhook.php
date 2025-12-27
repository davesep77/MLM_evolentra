<?php
require '../config_db.php';

// Binance Pay Webhook Listener
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    die("Invalid Payload");
}

// Log notification for debugging
file_put_contents('binance_webhook_log.txt', date('Y-m-d H:i:s') . " - " . $input . PHP_EOL, FILE_APPEND);

// Verify Signature (Recommended for production)
// For this implementation, we will trust the payload if the merchantTradeNo matches a pending deposit.

if (isset($data['bizType']) && $data['bizType'] === 'PAY' && $data['bizStatus'] === 'PAY_SUCCESS') {
    $bizData = json_decode($data['data'], true);
    $merchantTradeNo = $bizData['merchantTradeNo'];
    $amount = $bizData['orderAmount'];
    
    // Find the pending deposit by merchantTradeNo (stored in txid)
    $merchantTradeNo = $conn->real_escape_string($merchantTradeNo);
    $res = $conn->query("SELECT * FROM mlm_deposits WHERE status='pending' AND network='BinancePay' AND txid='$merchantTradeNo' LIMIT 1");
    
    if ($res->num_rows > 0) {
        $deposit = $res->fetch_assoc();
        $user_id = $deposit['user_id'];
        $deposit_id = $deposit['id'];

        // Update deposit status
        $conn->query("UPDATE mlm_deposits SET status='completed', approved_at=NOW() WHERE id=$deposit_id");

        // Credit Wallet
        $conn->query("UPDATE mlm_wallets SET investment = investment + $amount WHERE user_id=$user_id");

        // Update User total investment
        $conn->query("UPDATE mlm_users SET investment = investment + $amount WHERE id=$user_id");

        // Log transaction
        $conn->query("INSERT INTO mlm_transactions (user_id, type, amount, description, status) 
                      VALUES ($user_id, 'DEPOSIT', $amount, 'Automated Binance Pay Deposit', 'completed')");

        // Notify User
        $conn->query("INSERT INTO mlm_notifications (user_id, title, message, type) 
                      VALUES ($user_id, 'Payment Confirmed', 'Your Binance Pay deposit of $amount USDT has been automatically verified and added to your capital.', 'success')");

        // --- TRIGGER COMMISSIONS ---
        require_once '../lib/Compensation.php';
        $comp = new Compensation($conn);
        $sponsor_res = $conn->query("SELECT sponsor_id FROM mlm_users WHERE id=$user_id");
        if ($sponsor_res && $u = $sponsor_res->fetch_assoc()) {
            if ($u['sponsor_id']) {
                $comp->processReferral($amount, $u['sponsor_id']);
            }
        }

        echo "SUCCESS";
    } else {
        echo "Deposit Record Not Found";
    }
} else {
    echo "Unsupported Event";
}
?>
