<?php
require_once "../includes/db.php";

$u1 = $_GET['u1'];
$u2 = $_GET['u2'];

$stmt = $pdo->prepare("
    SELECT * FROM messages 
    WHERE (sender_id = ? AND receiver_id = ?)
       OR (sender_id = ? AND receiver_id = ?)
    ORDER BY created_at ASC
");
$stmt->execute([$u1, $u2, $u2, $u1]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
