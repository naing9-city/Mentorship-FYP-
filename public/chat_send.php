<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Check logged in user
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo "Not logged in";
    exit;
}

$sender_id = $_SESSION['user_id'];
$receiver_id = $_POST['receiver_id'] ?? 0;
$message = trim($_POST['message'] ?? '');

// Handle File Upload
$attachment_path = null;
if (!empty($_FILES['file']['name'])) {
    $filename = time() . "_" . uniqid() . "_" . basename($_FILES['file']['name']);
    // Ensure uploads directory exists (in project root/uploads)
    $target_dir = __DIR__ . '/uploads/';
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $target_file = $target_dir . $filename;
    
    if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
        $attachment_path = $filename;
    }
}

// Validation: Must have either message or attachment
if (!$receiver_id || ($message === '' && !$attachment_path)) {
    http_response_code(400);
    echo "Invalid input";
    exit;
}

// Insert message into DB
$stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, attachment_path) VALUES (?, ?, ?, ?)");
$stmt->execute([$sender_id, $receiver_id, $message, $attachment_path]);

// ------------------------------
// EMAIL NOTIFICATION SECTION
// ------------------------------

$stmt = $pdo->prepare("SELECT email, name FROM users WHERE id = ?");
$stmt->execute([$receiver_id]);
$receiver = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$sender_id]);
$sender = $stmt->fetch(PDO::FETCH_ASSOC);

if ($receiver && $sender) {

    $to = $receiver['email'];
    $subject = "New Message from " . $sender['name'];
    $body = "
Hello " . $receiver['name'] . ",

You have received a new message from " . $sender['name'] . ":

----------------------------------
" . $message . "
----------------------------------

Please log in to your dashboard to reply.

Mentorship System
";

    $headers = "From: no-reply@yourdomain.com";

    // Send email
    @mail($to, $subject, $body, $headers);
}

echo "OK";
