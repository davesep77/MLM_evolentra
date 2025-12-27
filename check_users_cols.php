<?php
require 'config_db.php';
$res = $conn->query("SHOW COLUMNS FROM mlm_users");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
