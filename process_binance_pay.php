<?php
require 'config_db.php';
require 'lib/BinancePay.php';

if (!isset($_SESSION['user_id'])) {
    die("Access Denied.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['amount'])) {
    $amount = floatval($_POST['amount']);
    $user_id = $_SESSION['user_id'];

    if ($amount < 50) {
        die("Minimum investment is $50.");
    }

    // Fetch API Keys
    $res = $conn->query("SELECT setting_key, setting_value FROM mlm_system_settings WHERE setting_key IN ('binance_pay_api_key', 'binance_pay_secret', 'binance_pay_env')");
    $settings = [];
    while($row = $res->fetch_assoc()) $settings[$row['setting_key']] = $row['setting_value'];

    if (empty($settings['binance_pay_api_key']) || empty($settings['binance_pay_secret'])) {
        die("Binance Pay is not configured by Admin.");
    }

    $binancePay = new BinancePay(
        $settings['binance_pay_api_key'], 
        $settings['binance_pay_secret'],
        $settings['binance_pay_env']
    );

    $merchantTradeNo = "EVO_" . $user_id . "_" . time();
    $webhookUrl = "http://localhost:8000/api/binance_pay_webhook.php"; // Update to your live domain in production
    $returnUrl = "http://localhost:8000/dashboard.php?payment=success";

    $response = $binancePay->createOrder($amount, $merchantTradeNo, $webhookUrl, $returnUrl);

    if (isset($response['status']) && $response['status'] === 'SUCCESS') {
        $checkoutUrl = $response['data']['checkoutUrl'];
        $binanceOrderId = $response['data']['prepayId'];

        // Log the pending Binance Pay deposit
        // We store merchantTradeNo in txid so we can match it in the webhook securely
        $sql = "INSERT INTO mlm_deposits (user_id, amount, txid, network, status) 
                VALUES ($user_id, $amount, '$merchantTradeNo', 'BinancePay', 'pending')";
        $conn->query($sql);

        // Redirect to our internal Payment Gateway
        $qrLink = $response['data']['qrcodeLink']; // Using qrcodeLink instead of checkoutUrl
        // If qrcodeLink is empty/missing, fallback to checkoutUrl (as the content for QR)
        if (empty($qrLink)) $qrLink = $response['data']['checkoutUrl']; 
        
        // Encode for URL
        $qrEncoded = urlencode($qrLink);
        header("Location: payment_gateway.php?orderId=$merchantTradeNo&qr=$qrEncoded");
        exit;
    } else {
        echo "<h2>Binance Pay Error</h2>";
        echo "<p>" . ($response['errorMessage'] ?? 'Unknown error occurred.') . "</p>";
        echo "<pre>";
        print_r($response);
        echo "</pre>";
        echo "<a href='invest.php'>Go Back</a>";
    }
} else {
    header("Location: invest.php");
}
