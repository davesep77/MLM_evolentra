<?php
require 'config_db.php';
require 'lib/Compensation.php';

// Instantiate Engine
$comp = new Compensation($conn);

// Run Binary Process
$comp->processBinary();

echo "Binary Cron Completed at " . date('Y-m-d H:i:s') . "\n";
?>
