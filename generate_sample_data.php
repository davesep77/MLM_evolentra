<?php
require 'config_db.php';

function generateRandomString($length = 8) {
    return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
}

function createUser($conn, $username, $email, $sponsorId = null, $position = null) {
    $password = password_hash('123456', PASSWORD_DEFAULT);
    $investment = rand(100, 5000); // Random investment
    
    // Check if user exists
    $check = $conn->query("SELECT id FROM mlm_users WHERE username='$username'");
    if ($check->num_rows > 0) {
        $u = $check->fetch_assoc();
        echo "User $username already exists (ID: {$u['id']}).\n";
        return $u['id'];
    }

    $sql = "INSERT INTO mlm_users (username, email, password, sponsor_id, binary_position, investment) 
            VALUES ('$username', '$email', '$password', " . ($sponsorId ? $sponsorId : "NULL") . ", " . ($position ? "'$position'" : "NULL") . ", $investment)";
    
    if ($conn->query($sql) === TRUE) {
        $userId = $conn->insert_id;
        $conn->query("INSERT INTO mlm_wallets (user_id) VALUES ($userId)");
        echo "Created User: $username (ID: $userId, Spon: " . ($sponsorId ?? 'None') . ", Pos: " . ($position ?? 'None') . ")\n";
        
        // Add Volume to Upline (Simple Logic: Add to immediate sponsor only for now, can be recursive)
        // In a real system, this bubbles up efficiently.
        if ($sponsorId && $position) {
            $col = ($position == 'L') ? 'left_vol' : 'right_vol';
            $conn->query("UPDATE mlm_wallets SET $col = $col + $investment WHERE user_id = $sponsorId");
            echo "   -> Added $investment volume to Sponsor $sponsorId ($col)\n";
        }
        
        return $userId;
    } else {
        echo "Error creating $username: " . $conn->error . "\n";
        return false;
    }
}

// 1. Ensure 'dawit' exists
echo "--- Ensuring Root User 'dawit' ---\n";
$dawitId = createUser($conn, 'dawit', 'dawit@evolentra.com'); // Root user, no sponsor

if (!$dawitId) { die("Could not create/find dawit."); }

// 2. BFS Queue for Placement
// Structure: [userId, depth]
// We want to fill 10 users.
$queue = [];
$queue[] = $dawitId;

$usersCreated = 0;
$targetUsers = 15; // Create 15 users to be safe

echo "\n--- Generating Sample Downline ---\n";

while ($usersCreated < $targetUsers && !empty($queue)) {
    $parentId = array_shift($queue); // Get next parent to fill
    
    // Check Left Leg
    $checkL = $conn->query("SELECT id FROM mlm_users WHERE sponsor_id=$parentId AND binary_position='L'");
    if ($checkL->num_rows == 0) {
        $newId = createUser($conn, 'user_' . generateRandomString(5), generateRandomString(5).'@test.com', $parentId, 'L');
        if ($newId) {
            $queue[] = $newId;
            $usersCreated++;
        }
    } else {
        $row = $checkL->fetch_assoc();
        $queue[] = $row['id']; // Algorithm should traverse existing nodes too
    }

    if ($usersCreated >= $targetUsers) break;

    // Check Right Leg
    $checkR = $conn->query("SELECT id FROM mlm_users WHERE sponsor_id=$parentId AND binary_position='R'");
    if ($checkR->num_rows == 0) {
        $newId = createUser($conn, 'user_' . generateRandomString(5), generateRandomString(5).'@test.com', $parentId, 'R');
        if ($newId) {
            $queue[] = $newId;
            $usersCreated++;
        }
    } else {
        $row = $checkR->fetch_assoc();
        $queue[] = $row['id'];
    }
}

echo "\n--- Generation Complete ---\n";
echo "Total sample users generated in tree: $usersCreated\n";
?>
