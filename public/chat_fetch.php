<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit("Not allowed");
}

$me = $_SESSION['user_id'];
$other = $_GET['other'] ?? 0;

if (!$other) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, sender_id, receiver_id, message, attachment_path, is_read, created_at
    FROM messages
    WHERE 
        (sender_id = ? AND receiver_id = ?)
        OR
        (sender_id = ? AND receiver_id = ?)
    ORDER BY created_at ASC
");
$stmt->execute([$me, $other, $other, $me]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mark as read (only messages sent BY the other person TO me)
$update = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
$update->execute([$other, $me]);

header('Content-Type: application/json');
echo json_encode($messages);
