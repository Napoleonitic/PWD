<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();
$user_id = currentUserId();
require_once 'includes/header.php';

// =========================
// AMBIL DATA TIKET USER
// =========================
$tickets = [];

$stmt = $conn->prepare("
    SELECT
        t.id,
        t.seat,
        t.total_price,
        t.payment_status,
        s.date,
        s.time,
        f.title  AS film_title,
        c.name   AS cinema_name
    FROM tickets t
    JOIN schedules s ON t.schedule_id = s.id
    JOIN films f     ON s.film_id = f.id
    JOIN cinemas c   ON s.cinema_id = c.id
    WHERE t.user_id = ?
    ORDER BY s.date DESC, s.time DESC, t.id DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $tickets[] = $row;
}
$stmt->close();

// =========================
// AMBIL DATA C.FOOD USER
// =========================
$cfoodOrders = [];

$cfoodSql = "
    SELECT id, items_json, total_price, status, created_at
    FROM cfood_orders
    WHERE user_id = ?
    ORDER BY id DESC
";
$stmt2 = $conn->prepare($cfoodSql);
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$res2 = $stmt2->get_result();

while ($row = $res2->fetch_assoc()) {
    $cfoodOrders[] = $row;
}
$stmt2->close();

function formatCfoodItemsText($items_json, $fallbackId) {
    $itemsArr = json_decode($items_json ?? '[]', true);
    if (!is_array($itemsArr) || empty($itemsArr)) {
        return '#'.$fallbackId;
    }

    $parts = [];
    foreach ($itemsArr as $it) {
        $name = isset($it['name']) ? trim($it['name']) : '';
        $qty  = isset($it['qty'])  ? (int)$it['qty']    : 0;
        if ($name === '') continue;
        $label = ($qty > 1 ? $qty.'x ' : '') . $name;
        $parts[] = $label;
    }

    if (empty($parts)) {
        return '#'.$fallbackId;
    }
    return implode(', ', $parts);
}
?>

<section class="page-section">
    <h1 class="page-title">Pesanan</h1>
    <p>Daftar semua tiket dan pesanan C.Food yang pernah kamu buat.</p>

    <!-- ===================== -->
    <!--    TIKET SAYA         -->
    <!-- ===================== -->
    <h2 class="orders-subtitle">Tiket Saya</h2>

    <?php if (!empty($tickets)): ?>
        <div class="orders-table-wrapper">
            <table class="orders-table">
                <thead>
                <tr>
                    <th>Film</th>
                    <th>Bioskop</th>
                    <th>Tanggal &amp; Jam</th>
                    <th>Kursi</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($tickets as $t): ?>
                    <?php $tStatus = strtolower($t['payment_status']); ?>
                    <tr>
                        <td><?php echo htmlspecialchars($t['film_title']); ?></td>
                        <td><?php echo htmlspecialchars($t['cinema_name']); ?></td>
                        <td><?php echo htmlspecialchars($t['date'].' '.$t['time']); ?></td>
                        <td><?php echo htmlspecialchars($t['seat']); ?></td>
                        <td>Rp <?php echo number_format($t['total_price'], 0, ',', '.'); ?></td>
                        <td><?php echo htmlspecialchars($t['payment_status']); ?></td>
                        <td>
                            <a href="ticket_detail.php?id=<?php echo (int)$t['id']; ?>">Detail</a>
                            <?php if ($tStatus !== 'paid'): ?>
                                | <a href="payment.php?type=ticket&ticket_id=<?php echo (int)$t['id']; ?>">Bayar</a>
                            <?php endif; ?>
                            | <a href="edit_ticket.php?id=<?php echo (int)$t['id']; ?>">Ubah</a>
                            | <a href="cancel_ticket.php?id=<?php echo (int)$t['id']; ?>"
                                 onclick="return confirm('Yakin mau batalkan tiket ini?');">Cancel</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p>Kamu belum pernah memesan tiket.</p>
    <?php endif; ?>

    <!-- ===================== -->
    <!--    C.FOOD SAYA        -->
    <!-- ===================== -->
    <h2 class="orders-subtitle" style="margin-top:32px;">C.Food Saya</h2>

    <?php if (!empty($cfoodOrders)): ?>
        <div class="orders-table-wrapper">
            <table class="orders-table">
                <thead>
                <tr>
                    <th>Pesanan</th>
                    <th>Tanggal</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($cfoodOrders as $o): ?>
                    <?php
                        $itemsText   = formatCfoodItemsText($o['items_json'] ?? '[]', $o['id']);
                        $statusLower = strtolower($o['status']);
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($itemsText); ?></td>
                        <td><?php echo htmlspecialchars($o['created_at']); ?></td>
                        <td>Rp <?php echo number_format($o['total_price'], 0, ',', '.'); ?></td>
                        <td><?php echo htmlspecialchars($o['status']); ?></td>
                        <td>
                            <?php if ($statusLower === 'unpaid'): ?>
                                <a href="cfood.php?edit_order=<?php echo (int)$o['id']; ?>">Edit</a> |
                                <a href="payment.php?type=cfood&order_id=<?php echo (int)$o['id']; ?>">Bayar</a> |
                                <a href="cancel_cfood_order.php?id=<?php echo (int)$o['id']; ?>"
                                   onclick="return confirm('Batalkan pesanan C.Food ini?');">Cancel</a> |
                                <a href="payment.php?type=cfood&order_id=<?php echo (int)$o['id']; ?>&view=1">Lihat</a>
                            <?php elseif ($statusLower === 'paid'): ?>
                                <a href="payment.php?type=cfood&order_id=<?php echo (int)$o['id']; ?>&view=1">Lihat</a>
                            <?php elseif ($statusLower === 'canceled'): ?>
                                Dibatalkan
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p>Kamu belum pernah memesan C.Food.</p>
    <?php endif; ?>
</section>

<?php require_once 'includes/footer.php'; ?>
