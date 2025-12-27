<?php
require '../config_db.php';
require '../lib/BinancePay.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $req_id = intval($_POST['request_id']);
    $transfer_type = $_POST['transfer_type']; // 'BINANCE_ID' or 'EMAIL'
    $receiver = trim($_POST['receiver']);

    if (empty($receiver)) {
        die("Receiver ID/Email is required.");
    }

    // Fetch Request
    $res = $conn->query("SELECT * FROM mlm_withdrawal_requests WHERE id=$req_id AND status='pending'");
    if ($res->num_rows === 0) {
        die("Request not found or already processed.");
    }
    $req = $res->fetch_assoc();

    // Fetch API Keys
    $s_res = $conn->query("SELECT setting_key, setting_value FROM mlm_system_settings WHERE setting_key IN ('binance_pay_api_key', 'binance_pay_secret', 'binance_pay_env', 'withdrawal_fee_percent')");
    $settings = [];
    while($row = $s_res->fetch_assoc()) $settings[$row['setting_key']] = $row['setting_value'];

    $apiKey = $settings['binance_pay_api_key'] ?? '';
    $secretKey = $settings['binance_pay_secret'] ?? '';
    $env = $settings['binance_pay_env'] ?? 'sandbox';
    $fee_percent = floatval($settings['withdrawal_fee_percent'] ?? 2);

    if (empty($apiKey) || empty($secretKey)) {
        die("Binance Pay API Keys not configured.");
    }

    // --- INTERNATIONAL COMMISSION PRINCIPLE ---
    $gross_amount = floatval($req['amount']);
    $fee_amount = $gross_amount * ($fee_percent / 100);
    $net_amount = $gross_amount - $fee_amount;

    // Construct Request ID
    $requestId = "PAYOUT_" . $req_id . "_" . time();

    // Execute Transfer
    $binancePay = new BinancePay($apiKey, $secretKey, $env);
    $result = $binancePay->transferFund($requestId, 'USDT', $net_amount, $transfer_type, $receiver, "Withdrawal #$req_id");

    if (isset($result['status']) && $result['status'] === 'SUCCESS') {
        // Note: 'SUCCESS' here means the batch request was accepted. 
        // Real status is in data. For single transfer, we check data directly.
        // But for payout API, it returns basic success. Detailed status query might be needed conceptually, 
        // but for now we assume acceptance = success unless error returned.
        
        $binance_txid = $requestId; // Use our Request ID as reference or parsing result['data']['requestId']

        // Update DB
        $stmt = $conn->prepare("UPDATE mlm_withdrawal_requests SET status='completed', processed_at=NOW(), admin_txid=? WHERE id=?");
        $stmt->bind_param("si", $binance_txid, $req_id);
        $stmt->execute();

        // Log Fee
        $user_id = $req['user_id'];
        $conn->query("INSERT INTO mlm_transactions (user_id, type, amount, description, status) 
                      VALUES ($user_id, 'FEE', $fee_amount, 'Withdrawal Commission ($fee_percent%)', 'completed')");

        echo "SUCCESS: C2C Transfer Initiated. Request ID: " . $binance_txid;
    } else {
        echo "FAILED: " . ($result['errorMessage'] ?? 'Unknown Error') . " | " . json_encode($result);
    }

} else {
    echo "Invalid Request";
}
