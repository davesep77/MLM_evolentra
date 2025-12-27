<?php
require 'lib/Compensation.php';

// Mock Connection class
class MockConn {
    public function query($sql) { return true; }
}

$mockConn = new MockConn();
$comp = new Compensation($mockConn);

echo "Testing ROI Rates:\n";
echo "ROOT ($100): " . ($comp->getRoiRate(100) == 0.012 ? "PASS" : "FAIL") . " (" . $comp->getRoiRate(100) . ")\n";
echo "ROOT ($5000): " . ($comp->getRoiRate(5000) == 0.012 ? "PASS" : "FAIL") . " (" . $comp->getRoiRate(5000) . ")\n";
echo "RISE ($5001): " . ($comp->getRoiRate(5001) == 0.013 ? "PASS" : "FAIL") . " (" . $comp->getRoiRate(5001) . ")\n";
echo "RISE ($25000): " . ($comp->getRoiRate(25000) == 0.013 ? "PASS" : "FAIL") . " (" . $comp->getRoiRate(25000) . ")\n";
echo "TERRA ($25001): " . ($comp->getRoiRate(25001) == 0.015 ? "PASS" : "FAIL") . " (" . $comp->getRoiRate(25001) . ")\n";

echo "\nTesting Referral Rates:\n";
echo "Any Amount ($100): " . ($comp->getReferralRate(100) == 0.09 ? "PASS" : "FAIL") . " (" . $comp->getReferralRate(100) . ")\n";
?>
