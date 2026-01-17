<?php
require_once '../includes/db.php';

echo "<h3>Testing Database Connection...</h3>";

$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll();
echo "<pre>";
print_r($tables);
echo "</pre>";

echo "<h3>Testing admin email lookup...</h3>";

$stmt2 = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt2->execute(["admin@example.com"]);
$user = $stmt2->fetch();

echo "<pre>";
print_r($user);
echo "</pre>";
