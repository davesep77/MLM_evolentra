<?php
require 'config_db.php';
require 'lib/BinancePay.php';

if (!isset($_GET['orderId'])) {
    header("Location: dashboard.php");
    exit;
}

$orderId = strip_tags($_GET['orderId']); // This is the merchantTradeNo stored in txid
$qrLink = strip_tags($_GET['qr']); // Passed from process page

// Poll Handler (AJAX)
if (isset($_GET['check_status'])) {
    header('Content-Type: application/json');
    
    // Check DB first (fastest)
    $stmt = $conn->prepare("SELECT status FROM mlm_deposits WHERE txid = ? LIMIT 1");
    $stmt->bind_param("s", $orderId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    
    if ($row && $row['status'] === 'completed') {
        echo json_encode(['status' => 'PAID']);
        exit;
    }

    // If still pending, call Binance Query API (Fallback)
    // Fetch Settings
    $s_res = $conn->query("SELECT setting_key, setting_value FROM mlm_system_settings WHERE setting_key IN ('binance_pay_api_key', 'binance_pay_secret', 'binance_pay_env')");
    $settings = [];
    while($r = $s_res->fetch_assoc()) $settings[$r['setting_key']] = $r['setting_value'];
    
    if (!empty($settings['binance_pay_api_key'])) {
        $binance = new BinancePay($settings['binance_pay_api_key'], $settings['binance_pay_secret'], $settings['binance_pay_env']);
        $apiRes = $binance->queryOrder($orderId);
        
        if (isset($apiRes['status']) && $apiRes['status'] === 'SUCCESS') {
            $bizStatus = $apiRes['data']['status'] ?? '';
            if ($bizStatus === 'PAID') {
                // FORCE UPDATE DB
                $conn->query("UPDATE mlm_deposits SET status='completed', approved_at=NOW() WHERE txid='$orderId' AND status='pending'");
                
                // Credit User (Deduplicate logic is handled by DB transaction ideally, but simple update here)
                // Retrieve user_id and amount to credit
                $d = $conn->query("SELECT user_id, amount FROM mlm_deposits WHERE txid='$orderId'")->fetch_assoc();
                if ($d) {
                     $conn->query("UPDATE mlm_wallets SET investment = investment + {$d['amount']} WHERE user_id={$d['user_id']}");
                     $conn->query("UPDATE mlm_users SET investment = investment + {$d['amount']} WHERE id={$d['user_id']}");
                     $conn->query("INSERT INTO mlm_transactions (user_id, type, amount, description, status) 
                                   VALUES ({$d['user_id']}, 'DEPOSIT', {$d['amount']}, 'Binance Pay Confirmed (Query)', 'completed')");

                     // --- TRIGGER COMMISSIONS ---
                     require_once 'lib/Compensation.php';
                     $comp = new Compensation($conn);
                     $sponsor_res = $conn->query("SELECT sponsor_id FROM mlm_users WHERE id={$d['user_id']}");
                     if ($sponsor_res && $u = $sponsor_res->fetch_assoc()) {
                         if ($u['sponsor_id']) {
                             $comp->processReferral($d['amount'], $u['sponsor_id']);
                         }
                     }
                }
                
                echo json_encode(['status' => 'PAID']);
                exit;
            }
        }
    }
    
    echo json_encode(['status' => 'WAITING']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secure Payment Gateway - Evolentra</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f172a; color: #f8fafc; }
        .glass-card { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 1rem; padding: 2rem; }
        .loader { border: 3px solid rgba(255,255,255,0.1); border-radius: 50%; border-top: 3px solid #f3ba2f; width: 24px; height: 24px; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen bg-[url('https://images.unsplash.com/photo-1639762681485-074b7f938ba0?q=80&w=2832&auto=format&fit=crop')] bg-cover bg-center">
    <div class="absolute inset-0 bg-slate-900/90 backdrop-blur-sm"></div>

    <div class="glass-card w-full max-w-md relative z-10 text-center">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-white mb-2">Complete Payment</h1>
            <p class="text-slate-400 text-sm">Scan the QR code with your Binance App</p>
        </div>

        <div class="bg-white p-4 rounded-xl inline-block mb-6 shadow-2xl shadow-yellow-500/20">
            <div id="qrcode"></div>
        </div>

        <div class="space-y-4">
            <div class="flex items-center justify-center gap-3 text-yellow-400 bg-yellow-400/10 py-2 rounded-lg">
                <div class="loader"></div>
                <span class="font-bold text-sm">Waiting for payment...</span>
            </div>

            <p class="text-slate-500 text-xs mt-4">
                Order ID: <span class="font-mono text-slate-400"><?= $orderId ?></span>
            </p>

            <button onclick="checkStatus()" class="w-full bg-slate-700 hover:bg-slate-600 text-white py-3 rounded-lg text-sm font-semibold transition-colors mt-4">
                I have paid
            </button>
            <a href="invest.php" class="block text-slate-500 hover:text-white text-sm mt-4">Cancel Order</a>
        </div>
    </div>

    <script>
        // Generate QR Code
        new QRCode(document.getElementById("qrcode"), {
            text: "<?= $qrLink ?>",
            width: 200,
            height: 200,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });

        // Polling Logic
        let pollInterval;

        function checkStatus() {
            fetch(`payment_gateway.php?orderId=<?= $orderId ?>&qr=<?= urlencode($qrLink) ?>&check_status=1`)
                .then(r => r.json())
                .then(data => {
                    if(data.status === 'PAID') {
                        clearInterval(pollInterval);
                        window.location.href = 'dashboard.php?payment_success=1';
                    }
                });
        }

        // Poll every 3 seconds
        pollInterval = setInterval(checkStatus, 3000);
    </script>
</body>
</html>
