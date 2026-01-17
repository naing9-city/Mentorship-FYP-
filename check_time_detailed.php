<?php
require_once __DIR__ . '/includes/db.php';

echo "Local System Time: " . date('Y-m-d H:i:s') . "\n";

$stmt = $pdo->query("SELECT NOW() as db_now, NOW() - INTERVAL 5 MINUTE as five_min_ago");
$times = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Database NOW(): " . $times['db_now'] . "\n";
echo "Database 5 Min Ago: " . $times['five_min_ago'] . "\n";

$stmt = $pdo->query("SELECT *, (created_at > NOW() - INTERVAL 5 MINUTE) as is_valid_now FROM admin_keys ORDER BY created_at DESC LIMIT 10");
$keys = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\nKey Status Details:\n";
foreach ($keys as $k) {
    echo "ID: {$k['id']} | Token: {$k['token']} | Created: {$k['created_at']} | Valid: " . ($k['is_valid_now'] ? 'YES' : 'NO') . " | Used: " . ($k['is_used'] ? 'YES' : 'NO') . "\n";
}
?>
