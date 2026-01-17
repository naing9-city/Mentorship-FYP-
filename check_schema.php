<?php
require_once __DIR__ . '/includes/db.php';
$s=$pdo->query('DESCRIBE admin_keys');
print_r($s->fetchAll(PDO::FETCH_ASSOC));
?>
