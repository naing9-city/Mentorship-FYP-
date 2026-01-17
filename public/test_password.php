<?php
require_once '../includes/db.php';

$email = "admin@example.com";
$plain = "StrongPass123";

$stmt = $pdo->prepare("SELECT password FROM users WHERE email = ?");
$stmt->execute([$email]);
$row = $stmt->fetch();

if (!$row) {
    die("User not found.");
}

$hash = $row['password'];

echo "<pre>";
echo "Plain: $plain\n";
echo "Hash: $hash\n\n";

echo "password_verify result: ";
var_dump(password_verify($plain, $hash));
