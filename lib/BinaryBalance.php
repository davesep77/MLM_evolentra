<?php
/**
 * Binary Tree Auto-Balance System
 * Automatically places new referrals on the weaker leg
 */

require 'config_db.php';

/**
 * Get the weaker leg for a sponsor
 * Returns 'L' or 'R' based on team size
 */
function getWeakerLeg($sponsor_id, $conn) {
    // Count members in left leg
    $leftCount = countTeamMembers($sponsor_id, 'L', $conn);
    
    // Count members in right leg  
    $rightCount = countTeamMembers($sponsor_id, 'R', $conn);
    
    // Get investment volume for each leg
    $leftVolume = getTeamVolume($sponsor_id, 'L', $conn);
    $rightVolume = getTeamVolume($sponsor_id, 'R', $conn);
    
    // Decision logic: Prioritize by volume, then by count
    if ($leftVolume != $rightVolume) {
        return ($rightVolume < $leftVolume) ? 'R' : 'L';
    }
    
    // If volumes are equal, use member count
    return ($rightCount < $leftCount) ? 'R' : 'L';
}

/**
 * Count all team members in a leg (recursive)
 */
function countTeamMembers($user_id, $position, $conn) {
    $query = "SELECT id FROM mlm_users WHERE sponsor_id = $user_id AND binary_position = '$position'";
    $result = $conn->query($query);
    
    if ($result->num_rows == 0) {
        return 0;
    }
    
    $count = $result->num_rows;
    
    while ($row = $result->fetch_assoc()) {
        $count += countTeamMembers($row['id'], 'L', $conn);
        $count += countTeamMembers($row['id'], 'R', $conn);
    }
    
    return $count;
}

/**
 * Get total investment volume in a leg (recursive)
 */
function getTeamVolume($user_id, $position, $conn) {
    $query = "SELECT id, investment FROM mlm_users WHERE sponsor_id = $user_id AND binary_position = '$position'";
    $result = $conn->query($query);
    
    if ($result->num_rows == 0) {
        return 0;
    }
    
    $volume = 0;
    
    while ($row = $result->fetch_assoc()) {
        $volume += $row['investment'];
        $volume += getTeamVolume($row['id'], 'L', $conn);
        $volume += getTeamVolume($row['id'], 'R', $conn);
    }
    
    return $volume;
}

/**
 * Get binary tree balance info for a user
 */
function getBinaryBalance($user_id, $conn) {
    $leftCount = countTeamMembers($user_id, 'L', $conn);
    $rightCount = countTeamMembers($user_id, 'R', $conn);
    $leftVolume = getTeamVolume($user_id, 'L', $conn);
    $rightVolume = getTeamVolume($user_id, 'R', $conn);
    
    return [
        'left_count' => $leftCount,
        'right_count' => $rightCount,
        'left_volume' => $leftVolume,
        'right_volume' => $rightVolume,
        'weaker_leg' => getWeakerLeg($user_id, $conn),
        'balance_ratio' => $leftCount > 0 ? round(($rightCount / $leftCount) * 100, 2) : 0
    ];
}

/**
 * API endpoint to get balance info
 */
if (isset($_GET['action']) && $_GET['action'] == 'get_balance') {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $balance = getBinaryBalance($_SESSION['user_id'], $conn);
    echo json_encode($balance);
    exit;
}
?>
