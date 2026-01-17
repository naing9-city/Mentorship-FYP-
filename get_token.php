<?php
require_once __DIR__ . '/includes/db.php';
$s=$pdo->query('SELECT token FROM admin_keys ORDER BY id DESC LIMIT 1');
echo $s->fetchColumn();
?>
