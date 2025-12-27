<?php
// Test Binance Connectivity
error_reporting(E_ALL);
ini_set('display_errors', 1);

function test_url($url) {
    echo "Testing URL: $url ... ";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL Verify
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    
    $start = microtime(true);
    $output = curl_exec($ch);
    $duration = round(microtime(true) - $start, 3);
    
    if (curl_errno($ch)) {
        echo "[FAILED] - Time: {$duration}s - Error: " . curl_error($ch) . "\n";
    } else {
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        echo "[SUCCESS] - HTTP $http_code - Time: {$duration}s - Size: " . strlen($output) . " bytes\n";
    }
    curl_close($ch);
}

echo "=== Connectivity Test ===\n";
test_url("https://api.binance.com/api/v3/ping");
test_url("https://data-api.binance.vision/api/v3/ping");
test_url("https://api1.binance.com/api/v3/ping");
test_url("https://api2.binance.com/api/v3/ping");
test_url("https://api3.binance.com/api/v3/ping");
test_url("https://google.com"); // Control
echo "=========================\n";
?>
