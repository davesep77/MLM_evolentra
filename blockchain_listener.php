<?php
/**
 * Blockchain Transaction Listener
 * Monitors BSC blockchain for incoming USDT payments and updates payment status
 * Run this script continuously or via cron job
 */

require 'config_db.php';
require_once 'lib/WalletManager.php';

// Configuration
$PLATFORM_ADDRESS = '0xYourPlatformWalletAddress'; // TODO: Set your platform wallet
$USDT_CONTRACT = '0x55d398326f99059fF775485246999027B3197955';
$BSC_RPC = 'https://bsc-dataseed.binance.org/';
$CHECK_INTERVAL = 30; // seconds
$REQUIRED_CONFIRMATIONS = 12;

echo "=== Blockchain Listener Started ===\n";
echo "Platform Address: $PLATFORM_ADDRESS\n";
echo "Check Interval: {$CHECK_INTERVAL}s\n";
echo "Required Confirmations: $REQUIRED_CONFIRMATIONS\n\n";

$walletManager = new WalletManager($conn);

/**
 * Make RPC call to BSC node
 */
function rpcCall($method, $params = []) {
    global $BSC_RPC;
    
    $data = json_encode([
        'jsonrpc' => '2.0',
        'method' => $method,
        'params' => $params,
        'id' => 1
    ]);
    
    $ch = curl_init($BSC_RPC);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    return $result['result'] ?? null;
}

/**
 * Get current block number
 */
function getCurrentBlock() {
    $blockHex = rpcCall('eth_blockNumber');
    return hexdec($blockHex);
}

/**
 * Get transaction receipt
 */
function getTransactionReceipt($txHash) {
    return rpcCall('eth_getTransactionReceipt', [$txHash]);
}

/**
 * Get transaction details
 */
function getTransaction($txHash) {
    return rpcCall('eth_getTransactionByHash', [$txHash]);
}

/**
 * Decode USDT transfer from transaction input
 */
function decodeUSDTTransfer($input) {
    // ERC20 transfer function signature: 0xa9059cbb
    if (substr($input, 0, 10) !== '0xa9059cbb') {
        return null;
    }
    
    // Extract recipient address (bytes 10-74)
    $toAddress = '0x' . substr($input, 34, 40);
    
    // Extract amount (bytes 74-138)
    $amountHex = substr($input, 74, 64);
    $amount = hexdec($amountHex);
    
    // Convert from wei to USDT (18 decimals)
    $amountUSDT = $amount / 1e18;
    
    return [
        'to' => strtolower($toAddress),
        'amount' => $amountUSDT
    ];
}

/**
 * Monitor pending payments
 */
function monitorPendingPayments() {
    global $walletManager, $PLATFORM_ADDRESS, $USDT_CONTRACT, $REQUIRED_CONFIRMATIONS;
    
    $currentBlock = getCurrentBlock();
    echo "[" . date('Y-m-d H:i:s') . "] Current Block: $currentBlock\n";
    
    // Get pending and confirming payments
    $pending = $walletManager->getPendingPayments();
    
    if (empty($pending)) {
        echo "No pending payments\n";
        return;
    }
    
    echo "Checking " . count($pending) . " pending payment(s)...\n";
    
    foreach ($pending as $payment) {
        $paymentId = $payment['id'];
        $txHash = $payment['tx_hash'];
        $status = $payment['status'];
        
        // If no tx hash yet, skip
        if (empty($txHash)) {
            echo "  Payment #{$paymentId}: Waiting for transaction...\n";
            continue;
        }
        
        // Get transaction receipt
        $receipt = getTransactionReceipt($txHash);
        
        if (!$receipt) {
            echo "  Payment #{$paymentId}: Transaction not found yet\n";
            continue;
        }
        
        // Check if transaction was successful
        if ($receipt['status'] !== '0x1') {
            echo "  Payment #{$paymentId}: Transaction FAILED\n";
            $walletManager->processPaymentCallback(
                $txHash,
                $payment['wallet_address'],
                $PLATFORM_ADDRESS,
                $payment['amount'],
                hexdec($receipt['blockNumber']),
                0
            );
            continue;
        }
        
        // Get transaction details
        $tx = getTransaction($txHash);
        
        if (!$tx) {
            echo "  Payment #{$paymentId}: Cannot fetch transaction details\n";
            continue;
        }
        
        // Verify it's a USDT transfer to our platform
        if (strtolower($tx['to']) !== strtolower($USDT_CONTRACT)) {
            echo "  Payment #{$paymentId}: Not a USDT transaction\n";
            continue;
        }
        
        // Decode transfer data
        $transfer = decodeUSDTTransfer($tx['input']);
        
        if (!$transfer || strtolower($transfer['to']) !== strtolower($PLATFORM_ADDRESS)) {
            echo "  Payment #{$paymentId}: Not sent to platform address\n";
            continue;
        }
        
        // Calculate confirmations
        $blockNumber = hexdec($receipt['blockNumber']);
        $confirmations = $currentBlock - $blockNumber;
        
        echo "  Payment #{$paymentId}: {$confirmations}/{$REQUIRED_CONFIRMATIONS} confirmations\n";
        
        // Update payment status
        $walletManager->processPaymentCallback(
            $txHash,
            strtolower($tx['from']),
            $transfer['to'],
            $transfer['amount'],
            $blockNumber,
            $confirmations
        );
        
        if ($confirmations >= $REQUIRED_CONFIRMATIONS) {
            echo "  Payment #{$paymentId}: CONFIRMED! ✓\n";
        }
    }
    
    echo "\n";
}

// Main loop
while (true) {
    try {
        monitorPendingPayments();
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    
    sleep($CHECK_INTERVAL);
}
?>
