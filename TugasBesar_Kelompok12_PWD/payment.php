<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();
require_once 'includes/header.php';

$user_id = currentUserId();

$type      = $_GET['type']     ?? '';      // 'ticket' atau 'cfood'
$ticket_id = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : 0;
$order_id  = isset($_GET['order_id'])  ? (int)$_GET['order_id']  : 0;
$viewMode  = isset($_GET['view']) && $_GET['view'] == '1';

$ticket  = null;
$cfood   = null;
$errors  = [];
$success = false;

// ==============================
// Ambil data sesuai tipe
// ==============================
if ($type === 'ticket' && $ticket_id > 0) {
    $sql = "
        SELECT
            t.id,
            t.total_price,
            t.seat,
            t.addon_detail,
            t.payment_status,
            t.payment_method,
            t.created_at,
            s.date,
            s.time,
            f.title  AS film_title,
            c.name   AS cinema_name,
            c.address AS cinema_address
        FROM tickets t
        JOIN schedules s ON t.schedule_id = s.id
        JOIN films f     ON s.film_id = f.id
        JOIN cinemas c   ON s.cinema_id = c.id
        WHERE t.id = ? AND t.user_id = ?
    ";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('ii', $ticket_id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $ticket = $res->fetch_assoc();
        $stmt->close();
    }
    if (!$ticket) {
        $errors[] = 'Tiket tidak ditemukan atau bukan milik Anda.';
    }
} elseif ($type === 'cfood' && $order_id > 0) {
    $sql = "
        SELECT
            id,
            items_json,
            total_price,
            status,
            payment_method,
            related_ticket_id,
            created_at
        FROM cfood_orders
        WHERE id = ? AND user_id = ?
    ";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('ii', $order_id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $cfood = $res->fetch_assoc();
        $stmt->close();
    }
    if ($cfood) {
        $statusLower = strtolower($cfood['status']);
        if ($statusLower === 'canceled') {
            // pesanan yang sudah dibatalkan tidak boleh diakses lagi
            $errors[] = 'Pesanan C.Food ini sudah dibatalkan dan tidak dapat diproses lagi.';
            $cfood = null;
        }
    } else {
        $errors[] = 'Pesanan C.Food tidak ditemukan atau bukan milik Anda.';
    }
} else {
    $errors[] = 'Parameter pembayaran tidak valid.';
}

// cek jika redirect dengan paid=1
if (isset($_GET['paid']) && $_GET['paid'] == '1') {
    $success = true;
}

// tentukan apakah masih boleh bayar
$canPay = false;
if (!$viewMode && empty($errors)) {
    if ($ticket && strtolower($ticket['payment_status']) === 'unpaid') {
        $canPay = true;
    }
    if ($cfood && strtolower($cfood['status']) === 'unpaid') {
        $canPay = true;
    }
}

// tentukan link kembali
if ($viewMode) {
    $backLink = 'my_tickets.php';
} else {
    if ($type === 'ticket') {
        $backLink = 'booking.php';
    } elseif ($type === 'cfood') {
        $backLink = 'index.php';
    } else {
        $backLink = 'index.php';
    }
}

// ==============================
// Proses submit pembayaran (POST)
// ==============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canPay && empty($errors)) {
    $payment_method = trim($_POST['payment_method'] ?? '');

    if (!in_array($payment_method, ['card', 'cash', 'gopay', 'ovo', 'shopeepay'], true)) {
        $errors[] = 'Metode pembayaran tidak valid.';
    } else {
        if ($type === 'ticket' && $ticket) {
            $stmt = $conn->prepare("
                UPDATE tickets
                SET payment_status = 'paid',
                    payment_method = ?
                WHERE id = ? AND user_id = ?
            ");
            if ($stmt) {
                $stmt->bind_param('sii', $payment_method, $ticket['id'], $user_id);
                if ($stmt->execute()) {
                    $success = true;
                    header('Location: payment.php?type=ticket&ticket_id=' . $ticket['id'] . '&paid=1');
                    exit;
                } else {
                    $errors[] = 'Gagal memperbarui status pembayaran tiket.';
                }
                $stmt->close();
            }
        } elseif ($type === 'cfood' && $cfood) {
            $stmt = $conn->prepare("
                UPDATE cfood_orders
                SET status = 'paid',
                    payment_method = ?
                WHERE id = ? AND user_id = ?
            ");
            if ($stmt) {
                $stmt->bind_param('sii', $payment_method, $cfood['id'], $user_id);
                if ($stmt->execute()) {
                    $success = true;
                    header('Location: payment.php?type=cfood&order_id=' . $cfood['id'] . '&paid=1');
                    exit;
                } else {
                    $errors[] = 'Gagal memperbarui status pembayaran C.Food.';
                }
                $stmt->close();
            }
        } else {
            $errors[] = 'Data yang dibayar tidak valid.';
        }
    }
}
?>
<div class="card" style="max-width:800px;">
    <h2>Pembayaran</h2>

    <?php if ($success): ?>
        <div class="alert alert-success">
            Pembayaran berhasil diproses.
        </div>
    <?php endif; ?>

    <?php foreach ($errors as $e): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($e); ?></div>
    <?php endforeach; ?>

    <?php if (!$ticket && !$cfood && empty($errors)): ?>
        <p>Tidak ada data yang akan dibayar.</p>
    <?php elseif (!$ticket && !$cfood && !empty($errors)): ?>
        <!-- sudah ditampilkan di atas -->
    <?php else: ?>

        <?php if ($ticket): ?>
            <h3 style="margin-top:10px;">Ringkasan Tiket</h3>
            <div style="background:#f8f8f8; padding:10px 12px; border-radius:10px; margin-bottom:10px; font-size:0.9rem;">
                <p><strong>Film:</strong> <?php echo htmlspecialchars($ticket['film_title']); ?></p>
                <p><strong>Bioskop:</strong> <?php echo htmlspecialchars($ticket['cinema_name']); ?></p>
                <p><strong>Alamat:</strong> <?php echo htmlspecialchars($ticket['cinema_address']); ?></p>
                <p><strong>Jadwal:</strong> <?php echo htmlspecialchars($ticket['date'] . ' ' . $ticket['time']); ?></p>
                <p><strong>Kursi:</strong> <?php echo htmlspecialchars($ticket['seat']); ?></p>
                <p><strong>Add-on:</strong>
                    <?php echo $ticket['addon_detail'] ? htmlspecialchars($ticket['addon_detail']) : '-'; ?>
                </p>
                <p><strong>Total Bayar:</strong>
                    Rp <?php echo number_format($ticket['total_price'], 0, ',', '.'); ?>
                </p>
                <p><strong>Status:</strong>
                    <?php echo htmlspecialchars($ticket['payment_status']); ?>
                    <?php if ($ticket['payment_method']): ?>
                        (<?php echo htmlspecialchars($ticket['payment_method']); ?>)
                    <?php endif; ?>
                </p>
                <p><strong>Waktu Booking:</strong>
                    <?php echo htmlspecialchars($ticket['created_at']); ?>
                </p>
            </div>
        <?php endif; ?>

        <?php if ($cfood): ?>
            <h3 style="margin-top:10px;">Ringkasan C.Food</h3>
            <div style="background:#f8f8f8; padding:10px 12px; border-radius:10px; margin-bottom:10px; font-size:0.9rem;">
                <?php
                $itemsText = '-';
                if (!empty($cfood['items_json'])) {
                    $itemsArr = json_decode($cfood['items_json'], true);
                    if (is_array($itemsArr) && !empty($itemsArr)) {
                        $parts = [];
                        foreach ($itemsArr as $it) {
                            $nm  = isset($it['name']) ? $it['name'] : '';
                            $qty = isset($it['qty'])  ? (int)$it['qty'] : 0;
                            if ($nm === '' || $qty <= 0) continue;
                            $label = ($qty > 1 ? $qty . 'x ' : '') . $nm;
                            $parts[] = $label;
                        }
                        if (!empty($parts)) {
                            $itemsText = implode(', ', $parts);
                        }
                    }
                }
                ?>
                <p><strong>Item:</strong> <?php echo htmlspecialchars($itemsText); ?></p>
                <p><strong>Total Bayar:</strong>
                    Rp <?php echo number_format($cfood['total_price'], 0, ',', '.'); ?>
                </p>
                <p><strong>Status:</strong>
                    <?php echo htmlspecialchars($cfood['status']); ?>
                    <?php if ($cfood['payment_method']): ?>
                        (<?php echo htmlspecialchars($cfood['payment_method']); ?>)
                    <?php endif; ?>
                </p>
                <p><strong>Waktu Pesan:</strong>
                    <?php echo htmlspecialchars($cfood['created_at']); ?>
                </p>
            </div>
        <?php endif; ?>

        <?php if ($canPay): ?>
            <form method="post" style="margin-top:16px;">
                <div class="form-group">
                    <label>Metode Pembayaran</label>
                    <select name="payment_method" required>
                        <option value="">-- Pilih Metode --</option>
                        <option value="card">Kartu Debit/Kredit</option>
                        <option value="cash">Cash di Tempat</option>
                        <option value="gopay">GoPay</option>
                        <option value="ovo">OVO</option>
                        <option value="shopeepay">ShopeePay</option>
                    </select>
                </div>

                <button type="submit" class="btn-submit">Bayar Sekarang</button>
                <a href="<?php echo $backLink; ?>" class="btn-back">Kembali</a>
            </form>
        <?php else: ?>
            <p style="margin-top:16px;">
                <a href="<?php echo $backLink; ?>" class="btn-back">Kembali</a>
            </p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
