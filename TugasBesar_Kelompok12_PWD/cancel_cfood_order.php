<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();

$user_id = currentUserId();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmt = $conn->prepare("
        UPDATE cfood_orders
        SET status = 'canceled'
        WHERE id = ? AND user_id = ? AND status = 'unpaid'
    ");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $stmt->close();
}

header('Location: my_tickets.php');
exit;