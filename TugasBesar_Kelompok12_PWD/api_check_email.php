<?php
// api_check_email.php
header('Content-Type: application/json');
require_once 'config/database.php';

$email = $_GET['email'] ?? '';

if ($email === '') {
    echo json_encode(['exists' => false]);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

echo json_encode(['exists' => $stmt->num_rows > 0]);
$stmt->close();