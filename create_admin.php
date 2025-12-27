<?php
require 'config_db.php';

$username = 'admin_debug';
$password = 'admin123';
$email = 'admin@debug.com';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Check if exists
$check = $conn->query("SELECT id FROM mlm_users WHERE username='$username'");
if ($check->num_rows > 0) {
    $row = $check->fetch_assoc();
    $id = $row['id'];
    echo "User $username already exists (ID: $id). Promoting to admin...<br>";
    $conn->query("UPDATE mlm_users SET role='admin', password='$hashed_password' WHERE id=$id");
} else {
    echo "Creating new admin user...<br>";
    $sql = "INSERT INTO mlm_users (username, email, password, role) VALUES ('$username', '$email', '$hashed_password', 'admin')";
    if ($conn->query($sql) === TRUE) {
        echo "Created user $username with ID: " . $conn->insert_id . "<br>";
    } else {
        echo "Error: " . $conn->error . "<br>";
    }
}

echo "Done. Login with $username / $password";
?>
