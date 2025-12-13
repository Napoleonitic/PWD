<?php
// edit_ticket.php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();
$user_id = currentUserId();
require_once 'includes/header.php';

$ticket_id = isset($_GET['id']) ? (int)($_GET['id'] ?? 0) : 0;
if ($ticket_id <= 0) {
    header('Location: my_tickets.php');
    exit;
}

$errors = [];

// ambil data tiket + jadwal lama
$sql = "
    SELECT
        t.id,
        t.user_id,
        t.schedule_id,
        t.seat,
        t.payment_status,
        t.total_price,
        t.addon_detail,
        s.date,
        s.time,
        f.title       AS film_title,
        c.name        AS cinema_name,
        c.address     AS cinema_address
    FROM tickets t
    JOIN schedules s ON t.schedule_id = s.id
    JOIN films f     ON s.film_id = f.id
    JOIN cinemas c   ON s.cinema_id = c.id
    WHERE t.id = ? AND t.user_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $ticket_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$ticket = $result->fetch_assoc();
$stmt->close();

if (!$ticket) {
    echo "<section class=\"page-section\"><p>Tiket tidak ditemukan.</p></section>";
    require_once 'includes/footer.php';
    exit;
}

$schedule_id  = (int)$ticket['schedule_id'];
$oldSeatsStr  = $ticket['seat'];
$oldSeatsArr  = $oldSeatsStr !== '' ? explode(',', $oldSeatsStr) : [];
$oldSeatsArr  = array_values(array_filter(array_map('trim', $oldSeatsArr)));
$oldSeatCount = count($oldSeatsArr);
$displayDateTime = $ticket['date'] . ' ' . $ticket['time'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $seatInput = trim($_POST['seat'] ?? '');

    if ($seatInput === '') {
        $errors[] = 'Kursi tidak boleh kosong.';
    } else {
    
        $parts = explode(',', $seatInput);
        $newSeatsArr = [];
        foreach ($parts as $p) {
            $s = strtoupper(trim($p));
            if ($s === '') continue;
            $newSeatsArr[] = $s;
        }


        $newSeatsArr = array_values(array_unique($newSeatsArr));
        $newSeatCount = count($newSeatsArr);

        if ($newSeatCount === 0) {
            $errors[] = 'Minimal pilih 1 kursi.';
        }

        if ($oldSeatCount > 0 && $newSeatCount !== $oldSeatCount) {
            $errors[] = 'Jumlah kursi harus sama dengan sebelumnya (' . $oldSeatCount . ' kursi).';
        }

    
        foreach ($newSeatsArr as $s) {
            if (!preg_match('/^[A-E][1-8]$/', $s)) {
                $errors[] = 'Format kursi tidak valid: ' . htmlspecialchars($s) . ' (contoh: B5, C3).';
            }
        }

        if (empty($errors)) {
            $bookedSeats = [];

            $sql = "SELECT seat FROM tickets WHERE schedule_id = ? AND id <> ?";
            $st = $conn->prepare($sql);
            $st->bind_param("ii", $schedule_id, $ticket_id);
            $st->execute();
            $res = $st->get_result();
            while ($row = $res->fetch_assoc()) {
                $seatsStr = $row['seat'];
                $seatsArr = $seatsStr !== '' ? explode(',', $seatsStr) : [];
                foreach ($seatsArr as $ss) {
                    $ss = strtoupper(trim($ss));
                    if ($ss !== '') {
                        $bookedSeats[$ss] = true;
                    }
                }
            }
            $st->close();

            foreach ($newSeatsArr as $s) {
                if (isset($bookedSeats[$s])) {
                    $errors[] = 'Kursi ' . htmlspecialchars($s) . ' sudah dibooking orang lain untuk jadwal ini.';
                }
            }
        }

        if (empty($errors)) {
            $cleanSeatStr = implode(',', $newSeatsArr);

            $st = $conn->prepare("
                UPDATE tickets
                SET seat = ?
                WHERE id = ? AND user_id = ?
            ");
            $st->bind_param('sii', $cleanSeatStr, $ticket_id, $user_id);
            $st->execute();
            $st->close();

            header('Location: my_tickets.php');
            exit;
        }
    }
}
?>

<section class="page-section">
    <h1 class="page-title">Ubah Tiket</h1>
    <p>Edit jadwal dan kursi untuk tiketmu.</p>
    <p><strong>Film:</strong> <?php echo htmlspecialchars($ticket['film_title']); ?></p>
    <p><strong>Bioskop:</strong> <?php echo htmlspecialchars($ticket['cinema_name']); ?></p>

    <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($err); ?></div>
    <?php endforeach; ?>

    <form method="post" class="card" style="max-width:600px;">
        <div class="form-group">
            <label for="schedule">Jadwal (Tanggal &amp; Jam)</label>
           
            <select id="schedule" class="form-control" disabled>
                <option><?php echo htmlspecialchars($displayDateTime); ?></option>
            </select>
           
            <input type="hidden" name="schedule_id" value="<?php echo (int)$schedule_id; ?>">
        </div>

        <div class="form-group">
            <label for="seat">Kursi (pisahkan dengan koma, contoh: B5, B6)</label>
            <input
                type="text"
                id="seat"
                name="seat"
                value="<?php echo htmlspecialchars($ticket['seat']); ?>"
                required
            >
        </div>

        <button type="submit" class="btn-primary">Simpan Perubahan</button>
        <a href="my_tickets.php" class="btn-secondary" style="margin-left:8px;">Batal</a>
    </form>
</section>

<?php require_once 'includes/footer.php'; ?>
