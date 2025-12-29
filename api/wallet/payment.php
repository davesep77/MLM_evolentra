<?php
/**
 * API: Wallet Payment
 * Handles payment initiation and transaction verification
 */

header('Content-Type: application/json');
require_once '../config_db.php';
require_once '../lib/WalletManager.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];
$walletManager = new WalletManager($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    
    if ($action === 'initiate') {
        // Initiate new payment
        $amount = floatval($data['amount'] ?? 0);
        $walletType = $data['walletType'] ?? 'metamask';
        $walletAddress = $data['walletAddress'] ?? '';
        $token = $data['token'] ?? 'USDT';
        $network = $data['network'] ?? 'BSC';
        
        if ($amount < 50) {
            echo json_encode(['success' => false, 'error' => 'Minimum investment is $50']);
            exit;
        }
        
        if (empty($walletAddress)) {
            echo json_encode(['success' => false, 'error' => 'Wallet address required']);
            exit;
        }
        
        $result = $walletManager->initiatePayment($userId, $amount, $walletType, $walletAddress, $token, $network);
        echo json_encode($result);
        
    } elseif ($action === 'verify') {
        // Verify transaction
        $txHash = $data['txHash'] ?? '';
        $network = $data['network'] ?? 'BSC';
        
        if (empty($txHash)) {
            echo json_encode(['success' => false, 'error' => 'Transaction hash required']);
            exit;
        }
        
        $result = $walletManager->verifyTransaction($txHash, $network);
        
        // If transaction is valid, update payment record
        if ($result['success']) {
            $amount = floatval($data['amount'] ?? 0);
            $walletAddress = $data['walletAddress'] ?? '';
            
            // Update payment with tx hash
            $conn->query("UPDATE mlm_crypto_payments 
                         SET tx_hash='$txHash', status='confirming' 
                         WHERE user_id=$userId 
                         AND wallet_address='$walletAddress' 
                         AND amount=$amount 
                         AND status='pending' 
                         ORDER BY created_at DESC LIMIT 1");
        }
        
        echo json_encode($result);
        
    } elseif ($action === 'callback') {
        // Process blockchain callback (from listener or webhook)
        $txHash = $data['txHash'] ?? '';
        $fromAddress = $data['from'] ?? '';
        $toAddress = $data['to'] ?? '';
        $amount = floatval($data['amount'] ?? 0);
        $blockNumber = intval($data['blockNumber'] ?? 0);
        $confirmations = intval($data['confirmations'] ?? 0);
        
        $result = $walletManager->processPaymentCallback($txHash, $fromAddress, $toAddress, $amount, $blockNumber, $confirmations);
        echo json_encode($result);
        
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    
} else {
    // GET request - return payment history
    $history = $walletManager->getPaymentHistory($userId);
    
    echo json_encode([
        'success' => true,
        'payments' => $history
    ]);
}
?>
