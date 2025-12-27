<?php
require 'config_db.php';

echo "<h2>Updating User Roles Schema</h2>";

// 1. Add role column if not exists
$check = $conn->query("SHOW COLUMNS FROM mlm_users LIKE 'role'");
if ($check->num_rows == 0) {
    echo "Adding 'role' column to mlm_users... ";
    $sql = "ALTER TABLE mlm_users ADD COLUMN role ENUM('user', 'admin') DEFAULT 'user' AFTER email";
    if ($conn->query($sql) === TRUE) {
        echo "✅ Success<br>";
    } else {
        echo "❌ Error: " . $conn->error . "<br>";
    }
} else {
    echo "ℹ️ 'role' column already exists.<br>";
}

// 2. Promote User ID 1 to Admin
echo "Promoting User ID 1 to Admin... ";
$sql = "UPDATE mlm_users SET role='admin' WHERE id=1";
if ($conn->query($sql) === TRUE) {
    echo "✅ Success<br>";
} else {
    echo "❌ Error: " . $conn->error . "<br>";
}

// 3. Verify
$user = $conn->query("SELECT id, username, role FROM mlm_users WHERE id=1")->fetch_assoc();
if ($user) {
    echo "<h3>Verification:</h3>";
    echo "User: " . $user['username'] . " (ID: " . $user['id'] . ") is now: <strong>" . $user['role'] . "</strong>";
} else {
    echo "❌ User ID 1 not found.";
}
?>
