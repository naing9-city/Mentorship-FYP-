<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');

if (!isset($_GET['id'])) {
    header('Location: users.php');
    exit;
}

$admin_id = $_SESSION['user_id']; // current admin
$user_id = (int)$_GET['id'];

// delete ONLY if this user belongs to the current admin
$stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND created_by = ?");
$stmt->execute([$user_id, $admin_id]);

header('Location: users.php');
exit;
