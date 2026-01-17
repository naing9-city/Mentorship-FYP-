<?php
require_once '../../includes/db.php';
$stmt = $pdo->query("SELECT certificate FROM mentor_applications WHERE certificate IS NOT NULL LIMIT 1");
echo "Cert: " . $stmt->fetchColumn() . "\n";
?>
