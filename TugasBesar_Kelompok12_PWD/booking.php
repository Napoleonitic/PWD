<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();
require_once 'includes/header.php';

$user_id = currentUserId();

// ===== BLOK TAMBAHAN: larang admin melakukan booking =====
if (isAdmin()) {
    ?>
    <div class="card">
        <h2>Booking Tidak Tersedia untuk Admin</h2>
        <p>
            Akun <strong>admin</strong> hanya digunakan untuk mengelola data film
            (menambah / meng-edit film).
        </p>
        <p>
            Untuk melakukan pemesanan tiket, silakan logout dulu lalu login
            menggunakan akun pengguna biasa.
        </p>
        <p style="margin-top:10px;">
            <a href="logout.php" class="btn-submit">Logout &amp; Login sebagai User</a>
        </p>
    </div>
    <?php
    require_once 'includes/footer.php';
    exit;
}
// ===== akhir blok tambahan =====

$errors = [];


// BIOSKOP
$cinemas = $conn->query("SELECT * FROM cinemas ORDER BY name ASC");

$currentFilmId = 0;
if (isset($_POST['film_id']) && $_POST['film_id'] !== '') {
    $currentFilmId = (int)$_POST['film_id'];
} elseif (isset($_GET['film_id'])) {
    $currentFilmId = (int)$_GET['film_id'];
}

// LIST FILM
$films = $conn->query("
    SELECT * FROM films
    WHERE `year` IN (2023, 2024)
    ORDER BY `year` DESC, title ASC
");

// ADD-ON dari tabel cfood_items
// kategori: "Add-on Makanan" dan "Add-on Minuman"
$addonsFood  = $conn->query("
    SELECT id, name, price
    FROM cfood_items
    WHERE category = 'Add-on Makanan' AND is_available = 1
    ORDER BY price ASC, name ASC
");
$addonsDrink = $conn->query("
    SELECT id, name, price
    FROM cfood_items
    WHERE category = 'Add-on Minuman' AND is_available = 1
    ORDER BY price ASC, name ASC
");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cinema_id  = (int)($_POST['cinema_id'] ?? 0);
    $date       = $_POST['date'] ?? '';
    $time       = $_POST['time'] ?? '';
    $film_id    = (int)($_POST['film_id'] ?? 0);
    $qty        = (int)($_POST['qty'] ?? 0);
    $selectedSeatsStr = trim($_POST['selected_seats'] ?? '');
    $currentFilmId = $film_id;

    // ID add-on dipilih user (boleh kosong)
    $food_id  = isset($_POST['food_id'])  && $_POST['food_id']  !== '' ? (int)$_POST['food_id']  : null;
    $drink_id = isset($_POST['drink_id']) && $_POST['drink_id'] !== '' ? (int)$_POST['drink_id'] : null;

    $selectedSeatsArr = $selectedSeatsStr !== '' ? explode(',', $selectedSeatsStr) : [];

    if ($cinema_id && $date && $time && $film_id && $qty > 0 && !empty($selectedSeatsArr)) {
        if (count($selectedSeatsArr) !== $qty) {
            $errors[] = 'Jumlah kursi yang dipilih harus sama dengan jumlah tiket.';
        } else {
            $harga_tiket = 50000;
            $addon_price = 0;
            $addon_names = [];

            // ====== AMBIL HARGA ADD-ON DARI cfood_items ======
            if ($food_id) {
                $st = $conn->prepare("
                    SELECT name, price
                    FROM cfood_items
                    WHERE id = ? AND is_available = 1
                    LIMIT 1
                ");
                $st->bind_param("i", $food_id);
                $st->execute();
                $res = $st->get_result()->fetch_assoc();
                if ($res) {
                    $addon_price += (int)$res['price'];
                    $addon_names[] = $res['name'];
                }
                $st->close();
            }

            if ($drink_id) {
                $st = $conn->prepare("
                    SELECT name, price
                    FROM cfood_items
                    WHERE id = ? AND is_available = 1
                    LIMIT 1
                ");
                $st->bind_param("i", $drink_id);
                $st->execute();
                $res = $st->get_result()->fetch_assoc();
                if ($res) {
                    $addon_price += (int)$res['price'];
                    $addon_names[] = $res['name'];
                }
                $st->close();
            }
            // ==============================================

            $addon_detail = !empty($addon_names) ? implode(', ', $addon_names) : null;
            $total = $harga_tiket * $qty + $addon_price;

            // CARI / BUAT SCHEDULE
            $st = $conn->prepare("SELECT id FROM schedules WHERE film_id=? AND cinema_id=? AND date=? AND time=?");
            $st->bind_param("iiss", $film_id, $cinema_id, $date, $time);
            $st->execute();
            $st->store_result();
            if ($st->num_rows > 0) {
                $st->bind_result($schedule_id);
                $st->fetch();
            } else {
                $st->close();
                $st = $conn->prepare("INSERT INTO schedules (film_id, cinema_id, date, time) VALUES (?,?,?,?)");
                $st->bind_param("iiss", $film_id, $cinema_id, $date, $time);
                $st->execute();
                $schedule_id = $st->insert_id;
            }
            $st->close();

            $bookedSeats = [];
            $st = $conn->prepare("SELECT seat FROM tickets WHERE schedule_id=?");
            $st->bind_param("i", $schedule_id);
            $st->execute();
            $res = $st->get_result();
            while ($row = $res->fetch_assoc()) {
                $parts = explode(',', $row['seat']);
                foreach ($parts as $s) {
                    $s = trim($s);
                    if ($s !== '') {
                        $bookedSeats[$s] = true;
                    }
                }
            }
            $st->close();

            foreach ($selectedSeatsArr as $s) {
                if (isset($bookedSeats[$s])) {
                    $errors[] = 'Kursi ' . htmlspecialchars($s) . ' sudah terisi, silakan pilih kursi lain.';
                }
            }

            if (empty($errors)) {
                $seatStr = implode(',', $selectedSeatsArr);
                $st = $conn->prepare("
                    INSERT INTO tickets (user_id, schedule_id, seat, addon_detail, total_price)
                    VALUES (?,?,?,?,?)
                ");
                $st->bind_param("iissi", $user_id, $schedule_id, $seatStr, $addon_detail, $total);
               if ($st->execute()) {

    $newTicketId = $st->insert_id;

    header('Location: payment.php?type=ticket&ticket_id=' . $newTicketId);
    exit;
} else {
    $errors[] = 'Gagal menyimpan booking.';
}

                $st->close();
            }
        }
    } else {
        $errors[] = 'Lengkapi semua data booking dan pilih kursi.';
    }
}
?>

<div class="card" style="max-width:900px;">
    <?php foreach ($errors as $e): ?>
        <div class="alert alert-error"><?php echo $e; ?></div>
    <?php endforeach; ?>

    <!-- PREVIEW FILM DI ATAS -->
    <div id="film-preview" style="margin-bottom:20px;">
        <h3 class="section-title" style="margin-bottom:6px;">Preview Film</h3>
        <p id="preview-empty" style="font-size:0.85rem; color:#555;">
            Pilih film terlebih dahulu untuk melihat detail dan trailer.
        </p>

        <div id="preview-card" style="display:none; margin-bottom:12px; background:#f8f8f8; padding:12px 14px; border-radius:12px;">
            <div id="preview-meta" style="font-size:0.85rem; color:#555; margin-bottom:4px;"></div>
            <div id="preview-title" style="font-weight:600; margin-bottom:6px;"></div>
            <p id="preview-synopsis" style="font-size:0.9rem; line-height:1.4;"></p>
        </div>

        <iframe
            id="preview-iframe"
            width="100%"
            height="260"
            style="border-radius:12px; display:none; border:none;"
            src=""
            title="Trailer Film"
            frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen
        ></iframe>
    </div>

    <h2>Booking Tiket</h2>
    <form method="post" id="booking-form">
        <div class="form-group">
            <label>Lokasi Bioskop</label>
            <select name="cinema_id" id="cinema-select" required>
                <option value="">-- Pilih Bioskop --</option>
                <?php if ($cinemas): ?>
                    <?php while ($c = $cinemas->fetch_assoc()): ?>
                        <option value="<?php echo $c['id']; ?>">
                            <?php echo htmlspecialchars($c['name']); ?>
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
        </div>

        <!-- TANGGAL: STRIP HARI + INPUT DATE HIDDEN -->
        <div class="form-group">
            <label>Tanggal</label>
            <div class="date-strip" id="date-strip"></div>
            <input type="date" name="date" id="date-input" class="hidden-date" required>
        </div>

        <!-- JAM: STRIP JAM + SELECT HIDDEN -->
        <div class="form-group">
            <label>Jam</label>
            <div class="time-strip" id="time-strip">
                <button type="button" class="time-chip" data-time="10:00:00">10:00</button>
                <button type="button" class="time-chip" data-time="14:30:00">14:30</button>
                <button type="button" class="time-chip" data-time="19:00:00">19:00</button>
            </div>
            <select name="time" id="time-select" required class="hidden-select">
                <option value="">-- Pilih Jam --</option>
                <option value="10:00:00">10:00</option>
                <option value="14:30:00">14:30</option>
                <option value="19:00:00">19:00</option>
            </select>
            <div class="time-price">Reguler 2D • Rp50.000</div>
        </div>

        <div class="form-group">
            <label>Film (2023 &amp; 2024)</label>
            <select name="film_id" id="film-select" required>
                <option value="">-- Pilih Film --</option>
                <?php if ($films): ?>
                    <?php while ($f = $films->fetch_assoc()): ?>
                        <option
                            value="<?php echo $f['id']; ?>"
                            data-trailer="<?php echo htmlspecialchars($f['trailer_url'] ?? ''); ?>"
                            data-title="<?php echo htmlspecialchars($f['title']); ?>"
                            data-synopsis="<?php echo htmlspecialchars($f['synopsis']); ?>"
                            data-year="<?php echo (int)$f['year']; ?>"
                            data-rating="<?php echo htmlspecialchars($f['rating']); ?>"
                            <?php echo ($currentFilmId == (int)$f['id']) ? 'selected' : ''; ?>
                        >
                            <?php echo htmlspecialchars($f['title']) . ' (' . $f['year'] . ')'; ?>
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Jumlah Tiket</label>
            <input type="number" name="qty" id="qty-input" min="1" value="1" required>
        </div>

        <div class="form-group">
            <label>Pilih Kursi</label>
            <div class="seat-map" id="seat-map">
                <div class="seat-screen">LAYAR</div>
                <div id="seat-grid"></div>
                <div class="seat-legend">
                    <span><span class="seat-legend-box"></span> Tersedia</span>
                    <span><span class="seat-legend-box selected"></span> Dipilih</span>
                    <span><span class="seat-legend-box booked"></span> Terisi</span>
                </div>
                <div id="selected-seat-text">Belum ada kursi yang dipilih.</div>
            </div>
            <input type="hidden" name="selected_seats" id="selected-seats-input">
        </div>

        <div class="form-group">
            <label>Add-on Makanan</label>
            <select name="food_id" id="food-select">
                <option value="" data-price="0">Tanpa Makanan</option>
                <?php if ($addonsFood && $addonsFood->num_rows > 0): ?>
                    <?php while ($a = $addonsFood->fetch_assoc()): ?>
                        <option
                            value="<?php echo (int)$a['id']; ?>"
                            data-price="<?php echo (int)$a['price']; ?>"
                        >
                            <?php
                                echo htmlspecialchars($a['name'])
                                    . " (Rp " . number_format($a['price'], 0, ',', '.') . ")";
                            ?>
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Add-on Minuman</label>
            <select name="drink_id" id="drink-select">
                <option value="" data-price="0">Tanpa Minuman</option>
                <?php if ($addonsDrink && $addonsDrink->num_rows > 0): ?>
                    <?php while ($a = $addonsDrink->fetch_assoc()): ?>
                        <option
                            value="<?php echo (int)$a['id']; ?>"
                            data-price="<?php echo (int)$a['price']; ?>"
                        >
                            <?php
                                echo htmlspecialchars($a['name'])
                                    . " (Rp " . number_format($a['price'], 0, ',', '.') . ")";
                            ?>
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
        </div>

        <div id="booking-summary" style="margin:10px 0; padding:10px 12px; border-radius:10px; background:#f4f4f4; font-size:0.9rem;">
            Ringkasan booking akan muncul di sini setelah memilih data.
        </div>

        <button type="submit" class="btn-submit">Konfirmasi Booking</button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>