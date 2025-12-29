<?php
/**
 * API: Get Referral Statistics
 * Returns comprehensive referral stats for a user
 */

require_once '../config_db.php';
require_once '../lib/ReferralEngine.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];
$engine = new ReferralEngine($conn);

// Get comprehensive stats
$stats = $engine->getReferralStats($userId);

// Add success flag
$stats['success'] = true;

echo json_encode($stats);
?>
