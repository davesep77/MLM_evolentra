<?php
require 'config_db.php';

function generateRandomString($length = 8) {
    return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
}

function createUser($conn, $username, $email, $sponsorId, $position, $investment) {
    $password = password_hash('123456', PASSWORD_DEFAULT);
    
    // Check if user exists
    $check = $conn->query("SELECT id FROM mlm_users WHERE username='$username'");
    if ($check->num_rows > 0) {
        return false;
    }

    $sql = "INSERT INTO mlm_users (username, email, password, sponsor_id, binary_position, investment) 
            VALUES ('$username', '$email', '$password', $sponsorId, '$position', $investment)";
    
    try {
        if ($conn->query($sql) === TRUE) {
            $userId = $conn->insert_id;
            $conn->query("INSERT INTO mlm_wallets (user_id) VALUES ($userId)");
            echo "Created VIP User: $username (Inv: $$investment) -> Sponsor: $sponsorId ($position)\n";
            
            // Add Volume to Immediate Sponsor (Simplistic)
            $col = ($position === 'L') ? 'left_vol' : 'right_vol';
            $conn->query("UPDATE mlm_wallets SET $col = $col + $investment WHERE user_id = $sponsorId");
            
            return $userId;
        }
    } catch (Exception $e) {
        echo "Error creating $username: " . $e->getMessage() . "\n";
        return false;
    }
    return false;
}

// 1. Find Root
$dawit = $conn->query("SELECT id FROM mlm_users WHERE username='dawit'")->fetch_assoc();
if (!$dawit) die("Root user 'dawit' not found.");
$rootId = $dawit['id'];

// 2. BFS to find empty slots
$queue = [$rootId];
$addedCount = 0;
$target = 20;

echo "--- Adding 20 Rich Investors to the Tree ---\n";

while ($addedCount < $target && !empty($queue)) {
    $parentId = array_shift($queue); // Get next node

    // Check Left
    $checkL = $conn->query("SELECT id FROM mlm_users WHERE sponsor_id=$parentId AND binary_position='L'");
    if ($checkL->num_rows == 0) {
        // Empty Spot! Create Rich User
        $inv = rand(25000, 50000); // TERRA Plan Range
        $name = "VIP_" . generateRandomString(4);
        $email = $name . "@evolentra.vip";
        
        if (createUser($conn, $name, $email, $parentId, 'L', $inv)) {
            $addedCount++;
            $queue[] = $conn->insert_id; // Add new node to queue for their future children
        }
    } else {
        $queue[] = $checkL->fetch_assoc()['id']; // Existing node, add to queue to traverse deeper
    }

    if ($addedCount >= $target) break;

    // Check Right
    $checkR = $conn->query("SELECT id FROM mlm_users WHERE sponsor_id=$parentId AND binary_position='R'");
    if ($checkR->num_rows == 0) {
        // Empty Spot! Create Rich User
        $inv = rand(25000, 50000); // TERRA Plan Range
        $name = "VIP_" . generateRandomString(4);
        $email = $name . "@evolentra.vip";
        
        if (createUser($conn, $name, $email, $parentId, 'R', $inv)) {
            $addedCount++;
            $queue[] = $conn->insert_id;
        }
    } else {
        $queue[] = $checkR->fetch_assoc()['id'];
    }
}

echo "\nCompleted. Added $addedCount new VIP investors.\n";
?>
