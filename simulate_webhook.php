<?php
// Simulate Binance Webhook
$url = 'http://localhost:8000/api/binance_pay_webhook.php';

// Interactive prompt simulation (hardcoded for now)
echo "--------------------------------------------------\n";
echo " BINANCE PAY WEBHOOK SIMULATOR\n";
echo "--------------------------------------------------\n";
echo "Use this to verify 'payment_gateway.php' polling.\n\n";

// 1. You should have a PENDING deposit in your DB.
// Let's assume you ran the process and have a merchantTradeNo
echo "Enter the 'Order ID' (merchantTradeNo) displayed on the Gateway: ";
$handle = fopen ("php://stdin","r");
$orderId = trim(fgets($handle));
echo "Enter Amount (must match DB): ";
$amount = trim(fgets($handle));

if (!$orderId || !$amount) die("Invalid Input");

$payload = [
    "bizType" => "PAY",
    "bizId" => "123456789",
    "bizStatus" => "PAY_SUCCESS",
    "key" => "value",
    "data" => json_encode([
        "merchantTradeNo" => $orderId,
        "productType" => "02",
        "productName" => "Deposit",
        "tradeType" => "WEB",
        "totalFee" => "0.00",
        "currency" => "USDT",
        "orderAmount" => $amount, 
        "openUserId" => "123"
    ])
];

$json = json_encode($payload);

echo "\nSending Webhook to $url ...\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $httpCode\n";
echo "Response Body: $response\n\n";

if ($response === 'SUCCESS') {
    echo "[PASS] Webhook processed successfully! The Gateway should now show 'PAID'.\n";
} else {
    echo "[FAIL] Webhook rejected. Check logs.\n";
}
