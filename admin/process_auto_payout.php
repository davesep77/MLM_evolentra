<?php
require '../config_db.php';
require '../lib/BinanceWithdraw.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $req_id = intval($_POST['request_id']);

    // Fetch Request
    $res = $conn->query("SELECT * FROM mlm_withdrawal_requests WHERE id=$req_id AND status='pending'");
    if ($res->num_rows === 0) {
        die("Request not found or already processed.");
    }
    $req = $res->fetch_assoc();

    // Fetch API Keys
    $s_res = $conn->query("SELECT setting_key, setting_value FROM mlm_system_settings WHERE setting_key IN ('binance_pay_api_key', 'binance_pay_secret', 'withdrawal_fee_percent')");
    $settings = [];
    while($row = $s_res->fetch_assoc()) $settings[$row['setting_key']] = $row['setting_value'];

    $apiKey = $settings['binance_pay_api_key'] ?? '';
    $secretKey = $settings['binance_pay_secret'] ?? '';
    $fee_percent = floatval($settings['withdrawal_fee_percent'] ?? 2); // Default 2%

    if (empty($apiKey) || empty($secretKey)) {
        die("API Keys not configured in Settings.");
    }

    // --- INTERNATIONAL COMMISSION PRINCIPLE ---
    // Calculate Net Amount
    $gross_amount = floatval($req['amount']);
    $fee_amount = $gross_amount * ($fee_percent / 100);
    $net_amount = $gross_amount - $fee_amount;

    // Parse Payment Method (Format: USDT-BEP20)
    $parts = explode('-', $req['payment_method']);
    $coin = $parts[0] ?? 'USDT';
    $network = $parts[1] ?? 'BEP20';
    $address = $req['payment_address'];

    // Construct Unique Order ID
    $orderId = "WID_" . $req_id . "_" . time();

    // Execute Withdrawal
    $binance = new BinanceWithdraw($apiKey, $secretKey);
    $result = $binance->withdraw($coin, $network, $address, $net_amount, $orderId);

    if ($result['success']) {
        $binance_txid = $result['id']; // This is the Binance internal withdrawal ID, not tx hash yet.
        
        // Update DB
        $stmt = $conn->prepare("UPDATE mlm_withdrawal_requests SET status='completed', processed_at=NOW(), admin_txid=? WHERE id=?");
        $stmt->bind_param("si", $binance_txid, $req_id);
        $stmt->execute();

        // Log Fee Deduction
        $user_id = $req['user_id'];
        $conn->query("INSERT INTO mlm_transactions (user_id, type, amount, description, status) 
                      VALUES ($user_id, 'FEE', $fee_amount, 'Withdrawal Commission ($fee_percent%)', 'completed')");

        echo "SUCCESS: Payout Initiated. Binance ID: " . $binance_txid;
    } else {
        echo "FAILED: " . htmlspecialchars($result['error']);
    }

} else {
    echo "Invalid Request";
}
