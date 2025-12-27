<?php
/**
 * API Endpoint: Generate Nonce for Wallet Verification
 */
header('Content-Type: application/json');
session_start();

try {
    // Generate a random 32-byte hex string
    $nonce = bin2hex(random_bytes(32));
    
    // Store in session for verification later
    $_SESSION['wallet_nonce'] = $nonce;
    
    echo json_encode([
        'success' => true,
        'nonce' => $nonce,
        'message' => 'Sign this unique message to prove ownership: Connect to Evolentra: ' . $nonce
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Could not generate nonce']);
}
?>
