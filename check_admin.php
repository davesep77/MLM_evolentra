<?php
require 'config_db.php';
$u = $conn->query("SELECT id, username, email, role FROM mlm_users WHERE id=1")->fetch_assoc();
echo "User ID 1: " . $u['username'] . " (" . $u['email'] . ") - Role: " . $u['role'];
?>
