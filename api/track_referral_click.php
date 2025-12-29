<?php
/**
 * API: Track Referral Click
 * Logs when someone clicks on a referral link
 */

// Allow this to be included or called directly
if (!isset($conn)) {
    require_once '../config_db.php';
}
require_once '../lib/ReferralEngine.php';

header('Content-Type: application/json');

// Function to track click (can be called from other scripts)
function trackReferralClick($referralCode, $ipAddress, $userAgent) {
    global $conn;
    
    $engine = new ReferralEngine($conn);
    
    // Validate code
    if (!$engine->validateCode($referralCode)) {
        return ['success' => false, 'error' => 'Invalid referral code'];
    }
    
    // Track the click
    $clickId = $engine->trackClick($referralCode, $ipAddress, $userAgent);
    
    if ($clickId) {
        return ['success' => true, 'click_id' => $clickId];
    } else {
        return ['success' => false, 'error' => 'Failed to track click'];
    }
}

// If called directly via HTTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    $refCode = $_GET['ref'] ?? $_POST['ref'] ?? null;
    
    if (!$refCode) {
        echo json_encode(['success' => false, 'error' => 'No referral code provided']);
        exit;
    }
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $result = trackReferralClick($refCode, $ipAddress, $userAgent);
    echo json_encode($result);
}
?>
