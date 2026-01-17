<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = intval($_POST['post_id']);
$content = trim($_POST['content']);

if (!empty($content)) {
    $stmt = $pdo->prepare("INSERT INTO post_comments (post_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->execute([$post_id, $user_id, $content]);
}

// Redirect back
if (isset($_SERVER['HTTP_REFERER'])) {
    header("Location: " . $_SERVER['HTTP_REFERER'] . "#post-" . $post_id);
} else {
    header("Location: learn.php#post-" . $post_id);
}
exit;
?>
