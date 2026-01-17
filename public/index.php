<?php
session_start();
require_once '../includes/db.php';

// Auto-redirect if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'student') {
        header("Location: student/index.php");
        exit;
    } elseif ($_SESSION['role'] === 'mentor') {
        header("Location: mentor/index.php");
        exit;
    }
}

// Redirect to the new redesigned login page
header("Location: login.php");
exit;
?>
