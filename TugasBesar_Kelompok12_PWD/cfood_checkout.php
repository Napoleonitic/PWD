<?php
// cfood_checkout.php
require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();
$user_id = currentUserId();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cfood.php');
    exit;
}

// Ambil payload keranjang dari hidden input
$rawItemsJson  = isset($_POST['items_json']) ? trim($_POST['items_json']) : '';
$editOrderId   = isset($_POST['edit_order_id']) ? (int)$_POST['edit_order_id'] : 0;

// Kalau tidak ada data, balik ke halaman C.Food
if ($rawItemsJson === '') {
    header('Location: cfood.php');
    exit;
}

// Decode JSON item dari JS
$items = json_decode($rawItemsJson, true);
if (!is_array($items) || empty($items)) {
    header('Location: cfood.php');
    exit;
}

// Bersihkan dan hitung total
$cleanItems   = [];
$total_amount = 0;

foreach ($items as $it) {
    $id    = isset($it['id'])   ? (int)$it['id']   : 0;
    $name  = isset($it['name']) ? trim($it['name']) : '';
    $price = isset($it['price'])? (int)$it['price'] : 0;
    $qty   = isset($it['qty'])  ? (int)$it['qty']   : 0;

    if ($id <= 0 || $name === '' || $price <= 0 || $qty <= 0) {
        continue;
    }

    $cleanItems[] = [
        'id'    => $id,
        'name'  => $name,
        'price' => $price,
        'qty'   => $qty,
    ];

    $total_amount += ($price * $qty);
}

if (empty($cleanItems) || $total_amount <= 0) {
    header('Location: cfood.php');
    exit;
}

$finalItemsJson = json_encode($cleanItems, JSON_UNESCAPED_UNICODE);

// ==============================
// MODE EDIT: update pesanan lama
// ==============================
if ($editOrderId > 0) {

    $stmt = $conn->prepare("
        UPDATE cfood_orders
        SET items_json = ?, total_price = ?, status = 'unpaid', payment_method = NULL
        WHERE id = ? AND user_id = ?
    ");

    if (!$stmt) {
        die('Gagal menyiapkan statement (UPDATE): ' . $conn->error);
    }

    $stmt->bind_param('siii', $finalItemsJson, $total_amount, $editOrderId, $user_id);

    if (!$stmt->execute()) {
        die('Gagal mengupdate pesanan C.Food: ' . $stmt->error);
    }

   
    if ($stmt->affected_rows > 0) {
        $order_id = $editOrderId;
        $stmt->close();

        header('Location: payment.php?type=cfood&order_id=' . $order_id);
        exit;
    }

    $stmt->close();
}

// =======================================
// MODE BARU: insert pesanan C.Food baru
// =======================================
$stmt = $conn->prepare("
    INSERT INTO cfood_orders (user_id, items_json, total_price, status, payment_method, related_ticket_id, created_at)
    VALUES (?, ?, ?, 'unpaid', NULL, NULL, NOW())
");

if (!$stmt) {
    die('Gagal menyiapkan statement (INSERT): ' . $conn->error);
}

$stmt->bind_param('isi', $user_id, $finalItemsJson, $total_amount);

if (!$stmt->execute()) {
    die('Gagal menyimpan pesanan C.Food: ' . $stmt->error);
}

$order_id = $stmt->insert_id;
$stmt->close();


header('Location: payment.php?type=cfood&order_id=' . $order_id);
exit;
