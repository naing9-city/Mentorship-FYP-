<?php
require_once __DIR__ . '/includes/db.php';

$token_to_test = $argv[1] ?? $_GET['token'] ?? '';

if (!$token_to_test) {
    die("Usage: php test_reg.php <token> OR visit test_reg.php?token=<token>\n");
}

echo "Testing Token: [$token_to_test]\n\n";

// 1. Check if token exists at all
$stmt = $pdo->prepare("SELECT * FROM admin_keys WHERE token = ?");
$stmt->execute([$token_to_test]);
$raw = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$raw) {
    echo "FAIL: Token not found in database at all.\n";
} else {
    echo "SUCCESS: Token found in database.\n";
    echo "Stored Token: [{$raw['token']}]\n";
    echo "Is Used: {$raw['is_used']}\n";
    echo "Created At: {$raw['created_at']}\n";
    
    // 2. Check time logic
    $stmt = $pdo->query("SELECT NOW() as db_now, NOW() - INTERVAL 6 MINUTE as threshold");
    $times = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "DB NOW: {$times['db_now']}\n";
    echo "Expiry Threshold (6 min): {$times['threshold']}\n";
    
    $is_valid_time = (strtotime($raw['created_at']) > strtotime($times['threshold']));
    echo "Time Check: " . ($is_valid_time ? "VALID" : "EXPIRED") . "\n";
    
    // 3. Replicate exact query
    $stmt = $pdo->prepare("
        SELECT id FROM admin_keys 
        WHERE token = ? AND is_used = 0 
        AND created_at > NOW() - INTERVAL 6 MINUTE
    ");
    $stmt->execute([$token_to_test]);
    $final = $stmt->fetch();
    
    if ($final) {
        echo "\nRESULT: The query WOULD WORK for this token.\n";
    } else {
        echo "\nRESULT: The query FAILED for this token.\n";
    }
}
?>
