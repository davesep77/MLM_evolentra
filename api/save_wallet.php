<?php
/**
 * API endpoint to save wallet address to database
 * Secure Version: Verifies Cryptographic Signature
 */
header('Content-Type: application/json');
require_once '../config_db.php';
require_once '../lib/EllipticValidation.php';

use Evolentra\Lib\EllipticValidation;

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['wallet_address']) || !isset($data['signature'])) {
    echo json_encode(['success' => false, 'error' => 'Wallet address and signature required']);
    exit;
}

if (!isset($_SESSION['wallet_nonce'])) {
    echo json_encode(['success' => false, 'error' => 'Session expired. Refresh page.']);
    exit;
}

$wallet_address = strtolower($data['wallet_address']);
$signature = $data['signature'];
$nonce = $_SESSION['wallet_nonce'];
$message_to_sign = "Connect to Evolentra: " . $nonce;

// Validate Ethereum address format
if (!preg_match('/^0x[a-fA-F0-9]{40}$/', $wallet_address)) {
    echo json_encode(['success' => false, 'error' => 'Invalid wallet address']);
    exit;
}

try {
    // SECURITY: Verify that the signature was signed by the wallet_address
    $isValid = EllipticValidation::recover($message_to_sign, $signature, $wallet_address);
    
    if (!$isValid) {
        echo json_encode(['success' => false, 'error' => 'Signature verification failed. Ownership not proven.']);
        exit;
    }

    // Update user's wallet address
    $stmt = $conn->prepare("UPDATE mlm_users SET wallet_address = ? WHERE id = ?");
    $stmt->bind_param("si", $wallet_address, $user_id);
    $stmt->execute();
    
    // Log the connection
    $stmt = $conn->prepare("
        INSERT INTO mlm_admin_logs (action, description, created_at) 
        VALUES ('wallet_connected', ?, NOW())
    ");
    $description = "User ID $user_id connected wallet: $wallet_address (Verified)";
    $stmt->bind_param("s", $description);
    $stmt->execute();
    
    // Clear nonce after successful use to prevent replay
    unset($_SESSION['wallet_nonce']);

    echo json_encode([
        'success' => true,
        'message' => 'Wallet verified and saved successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Wallet save error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Verification error: ' . $e->getMessage()
    ]);
}
