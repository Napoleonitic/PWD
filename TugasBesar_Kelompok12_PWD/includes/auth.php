<?php
// includes/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

function currentUserId() {
    return $_SESSION['user_id'] ?? null;
}


function isAdmin() {
    return !empty($_SESSION['is_admin']);
}


function requireAdmin() {
    if (!isAdmin()) {
        header("Location: index.php");
        exit;
    }
}
?>
