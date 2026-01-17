<?php
session_start();
require_once "../includes/db.php";

$sender = $_SESSION['user_id'];
$receiver = $_POST['receiver_id'];
$message = trim($_POST['message']);

$stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message)
                       VALUES (?, ?, ?)");
$stmt->execute([$sender, $receiver, $message]);

echo "success";
