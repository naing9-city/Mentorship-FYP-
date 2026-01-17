<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = intval($_GET['id']);

// Check if already liked
$stmt = $pdo->prepare("SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?");
$stmt->execute([$post_id, $user_id]);
$existing = $stmt->fetch();

if ($existing) {
    // Unlike
    $stmt = $pdo->prepare("DELETE FROM post_likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$post_id, $user_id]);
} else {
    // Like
    $stmt = $pdo->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)");
    $stmt->execute([$post_id, $user_id]);
}

// Redirect back
if (isset($_SERVER['HTTP_REFERER'])) {
    header("Location: " . $_SERVER['HTTP_REFERER'] . "#post-" . $post_id);
} else {
    header("Location: learn.php#post-" . $post_id);
}
exit;
?>
