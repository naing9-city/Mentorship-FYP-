<?php
require_once __DIR__ . '/includes/db.php';

echo "PHP Time: " . date('Y-m-d H:i:s') . "\n";

$stmt = $pdo->query("SELECT NOW() as db_now");
$db_now = $stmt->fetchColumn();
echo "MySQL NOW(): " . $db_now . "\n";

$stmt = $pdo->query("SELECT * FROM admin_keys ORDER BY created_at DESC LIMIT 5");
$keys = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\nRecent Admin Keys:\n";
print_r($keys);
?>
