<?php
/**
 * API: Wallet Connection
 * Handles wallet connection requests from frontend
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $walletType = $data['walletType'] ?? '';
    $walletAddress = $data['walletAddress'] ?? '';
    $signature = $data['signature'] ?? '';
    $message = $data['message'] ?? '';
    $network = $data['network'] ?? 'BSC';
    
    if (empty($walletAddress) || empty($signature)) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }
    
    $walletManager = new WalletManager($conn);
    $result = $walletManager->connectWallet($userId, $walletType, $walletAddress, $signature, $message, $network);
    
    echo json_encode($result);
} else {
    // GET request - return connected wallets
    $walletManager = new WalletManager($conn);
    $wallets = $walletManager->getUserWallets($userId);
    
    echo json_encode([
        'success' => true,
        'wallets' => $wallets
    ]);
}
?>
