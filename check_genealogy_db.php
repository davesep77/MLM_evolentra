<?php
require 'config_db.php';

echo "=== MLM_USERS TABLE STRUCTURE ===\n";
$result = $conn->query("DESCRIBE mlm_users");
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

echo "\n=== SAMPLE DATA ===\n";
$users = $conn->query("SELECT id, username, sponsor_id, binary_position FROM mlm_users LIMIT 5");
while($user = $users->fetch_assoc()) {
    echo "ID: {$user['id']}, User: {$user['username']}, Sponsor: {$user['sponsor_id']}, Position: {$user['binary_position']}\n";
}
?>
