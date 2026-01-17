<?php
require_once __DIR__ . '/includes/db.php';
$s=$pdo->query('SELECT @@global.time_zone, @@session.time_zone, @@system_time_zone, NOW()');
print_r($s->fetch(PDO::FETCH_ASSOC));
?>
