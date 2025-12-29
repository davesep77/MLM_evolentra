<?php
/**
 * Health Check Endpoint
 * 
 * Used by DigitalOcean App Platform to verify application health.
 * Returns JSON with application status and database connectivity.
 */

header('Content-Type: application/json');

$health = [
    'status' => 'healthy',
    'timestamp' => date('c'),
    'checks' => []
];

// Check database connectivity
try {
    require_once __DIR__ . '/lib/DatabaseConfig.php';
    
    $dbConfig = DatabaseConfig::getInstance();
    $dbTest = $dbConfig->testConnection();
    
    $health['checks']['database'] = [
        'status' => $dbTest['success'] ? 'healthy' : 'unhealthy',
        'message' => $dbTest['message'],
        'host' => $dbTest['host'],
        'database' => $dbTest['database']
    ];
    
    if (!$dbTest['success']) {
        $health['status'] = 'unhealthy';
        http_response_code(503); // Service Unavailable
    }
    
} catch (Exception $e) {
    $health['checks']['database'] = [
        'status' => 'unhealthy',
        'message' => 'Database configuration error: ' . $e->getMessage()
    ];
    $health['status'] = 'unhealthy';
    http_response_code(503);
}

// Check PHP version
$health['checks']['php'] = [
    'status' => 'healthy',
    'version' => PHP_VERSION
];

// Check required extensions
$requiredExtensions = ['mysqli', 'pdo_mysql', 'json', 'curl', 'mbstring'];
$missingExtensions = [];

foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}

$health['checks']['extensions'] = [
    'status' => empty($missingExtensions) ? 'healthy' : 'unhealthy',
    'missing' => $missingExtensions
];

if (!empty($missingExtensions)) {
    $health['status'] = 'unhealthy';
    http_response_code(503);
}

// Check environment
$health['checks']['environment'] = [
    'status' => 'healthy',
    'env' => getenv('APP_ENV') ?: 'development'
];

// Return health status
echo json_encode($health, JSON_PRETTY_PRINT);
