<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();

$user_id = currentUserId();
$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("DELETE FROM tickets WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$stmt->close();

header("Location: my_tickets.php");
exit;