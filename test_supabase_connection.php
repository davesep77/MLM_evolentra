<?php
require_once 'lib/DatabaseConfig.php';

header('Content-Type: application/json');

try {
    $db = DatabaseConfig::getInstance();
    $pdo = $db->getPDOConnection();

    echo json_encode([
        'status' => 'success',
        'message' => 'Database connection successful',
        'connection_info' => [
            'host' => $db->getHost(),
            'database' => $db->getDatabase(),
            'type' => $db->getDbType(),
            'port' => $db->getPort()
        ],
        'test_query' => 'Attempting to query users...'
    ], JSON_PRETTY_PRINT) . "\n\n";

    $stmt = $pdo->query("SELECT COUNT(*) as user_count FROM mlm_users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'query_result' => 'Users table accessible',
        'user_count' => $result['user_count']
    ], JSON_PRETTY_PRINT) . "\n\n";

    $stmt = $pdo->query("
        SELECT
            id,
            username,
            email,
            role,
            current_rank,
            created_at
        FROM mlm_users
        ORDER BY created_at DESC
        LIMIT 3
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'sample_users' => $users
    ], JSON_PRETTY_PRINT) . "\n\n";

    $stmt = $pdo->query("
        SELECT
            u.username,
            w.roi_wallet,
            w.referral_wallet,
            w.binary_wallet
        FROM mlm_users u
        LEFT JOIN mlm_wallets w ON u.id = w.user_id
        LIMIT 3
    ");
    $wallets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'wallet_test' => 'Wallets accessible',
        'sample_wallets' => $wallets
    ], JSON_PRETTY_PRINT) . "\n\n";

    echo json_encode([
        'final_status' => 'ALL TESTS PASSED',
        'message' => 'Your Supabase database is fully configured and ready to use!'
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
