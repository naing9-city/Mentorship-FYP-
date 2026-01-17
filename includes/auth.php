<?php
// includes/auth.php
session_start();

function is_logged_in() {
    return !empty($_SESSION['user_id']);
}
function require_login() {
    if (!is_logged_in()) {
        header('Location: /login.php');
        exit;
    }
}
function require_role($role) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        header('Location: /login.php');
        exit;
    }
}
