<?php
require '../config_db.php';

echo "<h2>RBAC Verification Test</h2>";

// Test 1: Check Admin Role
echo "<h3>1. Checking Session Role</h3>";
if (isset($_SESSION['role'])) {
    echo "Current Role: " . $_SESSION['role'] . "<br>";
    if ($_SESSION['role'] === 'admin') {
        echo "✅ Admin role recognized.<br>";
    } else {
        echo "⚠️ Current user is not admin.<br>";
    }
} else {
    echo "❌ No role in session. (Try logging out and back in)<br>";
}

// Test 2: Database Role
echo "<h3>2. Checking Database Role for ID 1</h3>";
$res = $conn->query("SELECT role FROM mlm_users WHERE id=1");
if ($res) {
    echo "Role in DB: " . $res->fetch_row()[0] . "<br>";
    echo "✅ Database schema update confirmed.<br>";
} else {
    echo "❌ Failed to query role column.<br>";
}

echo "<br><a href='dashboard.php'>Go to Admin Dashboard</a>";
?>
