<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();
require_once 'includes/header.php';

$user_id = currentUserId();
$id = (int)($_GET['id'] ?? 0);


$sql = "
    SELECT
        t.id,
        t.total_price,
        t.created_at,
        t.seat,
        t.addon_detail,
        s.date,
        s.time,
        f.title   AS film_title,
        f.synopsis,
        c.name    AS cinema_name,
        c.address
    FROM tickets t
    JOIN schedules s ON t.schedule_id = s.id
    JOIN films f     ON s.film_id = f.id
    JOIN cinemas c   ON s.cinema_id = c.id
    WHERE t.user_id = ? AND t.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $id);
$stmt->execute();
$result = $stmt->get_result();
$ticket = $result->fetch_assoc();
$stmt->close();

if (!$ticket) {
    echo "<p>Tiket tidak ditemukan.</p>";
    require_once 'includes/footer.php';
    exit;
}

$addonText = '-';
if (!empty($ticket['addon_detail'])) {
    $addonText = $ticket['addon_detail'];
}
?>

<div class="card" style="max-width:600px;">
    <h2>Detail Tiket</h2>

    <p><strong>Film:</strong> <?php echo htmlspecialchars($ticket['film_title']); ?></p>
    <p><strong>Bioskop:</strong> <?php echo htmlspecialchars($ticket['cinema_name']); ?></p>
    <p><strong>Alamat:</strong> <?php echo htmlspecialchars($ticket['address']); ?></p>
    <p>
        <strong>Jadwal:</strong>
        <?php echo htmlspecialchars($ticket['date'] . ' ' . $ticket['time']); ?>
    </p>
    <p><strong>Kursi:</strong> <?php echo htmlspecialchars($ticket['seat']); ?></p>
    <p><strong>Add-on:</strong> <?php echo htmlspecialchars($addonText); ?></p>
    <p>
        <strong>Total Bayar:</strong>
        Rp <?php echo number_format($ticket['total_price'], 0, ',', '.'); ?>
    </p>
    <p><strong>Waktu Booking:</strong> <?php echo htmlspecialchars($ticket['created_at']); ?></p>

    <hr style="margin:14px 0;">

    <div style="text-align:center; margin:15px 0;">
        <img src="public/img/qr.png"
             alt="QR Code Tiket"
             style="width:150px; height:150px; object-fit:contain;">
    </div>

    <p style="font-size:0.85rem;">
        Tunjukkan halaman ini sebagai bukti tiket saat masuk studio.
    </p>

    <p style="margin-top:8px;">
        <a href="cancel_ticket.php?id=<?php echo (int)$ticket['id']; ?>"
           onclick="return confirm('Batalkan tiket ini?');"
           class="btn-submit"
           style="background:#b00020;">
            Cancel Booking
        </a>
    </p>
</div>

<?php require_once 'includes/footer.php'; ?>
