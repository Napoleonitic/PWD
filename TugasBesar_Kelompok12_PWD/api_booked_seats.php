<?php
require_once 'config/database.php';

header('Content-Type: application/json');

$cinema_id = isset($_GET['cinema_id']) ? (int)$_GET['cinema_id'] : 0;
$film_id   = isset($_GET['film_id']) ? (int)$_GET['film_id'] : 0;
$date      = $_GET['date'] ?? '';
$time      = $_GET['time'] ?? '';

if (!$cinema_id || !$film_id || $date === '' || $time === '') {
    echo json_encode(['seats' => []]);
    exit;
}

$st = $conn->prepare("SELECT id FROM schedules WHERE film_id=? AND cinema_id=? AND date=? AND time=?");
$st->bind_param("iiss", $film_id, $cinema_id, $date, $time);
$st->execute();
$st->store_result();

if ($st->num_rows === 0) {
    $st->close();
    echo json_encode(['seats' => []]);
    exit;
}

$st->bind_result($schedule_id);
$st->fetch();
$st->close();

$seats = [];
$st = $conn->prepare("SELECT seat FROM tickets WHERE schedule_id=?");
$st->bind_param("i", $schedule_id);
$st->execute();
$res = $st->get_result();
while ($row = $res->fetch_assoc()) {
    $parts = explode(',', $row['seat']);
    foreach ($parts as $s) {
        $s = trim($s);
        if ($s !== '') {
            $seats[$s] = true;
        }
    }
}
$st->close();

echo json_encode(['seats' => array_keys($seats)]);