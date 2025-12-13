<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>CinePals</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <script src="public/js/main.js" defer></script>
</head>
<body>

<header class="navbar">
    <div class="navbar-left">
        <a href="index.php" class="logo">
            <img src="public/img/logo-cp.png" alt="CinePals" class="logo-mark">
            <span class="logo-text">Cine<span>Pals</span></span>
        </a>
    </div>

    <nav class="navbar-right">
        <a href="index.php">Home</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="booking.php">Booking</a>
            <a href="my_tickets.php">My Tickets</a>
            <a href="profile.php">Profile</a>
            <a href="logout.php" class="btn-outline">Logout</a>
        <?php else: ?>
            <a href="login.php" class="btn-outline">Login</a>
            <a href="register.php" class="btn-primary">Register</a>
        <?php endif; ?>
    </nav>
</header>

<main class="page-container">