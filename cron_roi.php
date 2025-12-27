<?php
require 'config_db.php';
require 'lib/Compensation.php';

// Instantiate Engine
$comp = new Compensation($conn);

// Run ROI Process
$comp->processDailyRoi();

echo "ROI Cron Completed at " . date('Y-m-d H:i:s') . "\n";
?>
