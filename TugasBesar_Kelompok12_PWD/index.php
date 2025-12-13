<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/header.php';

$bookingSuccess = isset($_GET['booking']) && $_GET['booking'] === 'success';

function getFilmPoster($trailer_url, $poster) {
    if (!empty($poster)) {
        if (strpos($poster, 'http') === 0) {
            return $poster;
        }
        return 'public/img/' . $poster;
    }
    if (!empty($trailer_url)) {
        if (preg_match('~(?:v=|be/)([A-Za-z0-9_-]{11})~', $trailer_url, $m)) {
            return 'https://img.youtube.com/vi/' . $m[1] . '/hqdefault.jpg';
        }
    }
    return 'public/img/default-poster.jpg';
}

function getYoutubeId($url) {
    if (!empty($url) && preg_match('~(?:v=|be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
        return $m[1];
    }
    return '';
}

// TRAILER HERO
$heroTrailerId = '';
$heroRes = $conn->query("
    SELECT trailer_url
    FROM films
    WHERE trailer_url IS NOT NULL AND trailer_url <> ''
      AND `year` IN (2023, 2024)
    ORDER BY `year` DESC, rating DESC, title ASC
    LIMIT 1
");
if ($heroRes && $heroRes->num_rows > 0) {
    $row = $heroRes->fetch_assoc();
    $heroTrailerId = getYoutubeId($row['trailer_url'] ?? '');
}


$filmsCarousel = $conn->query("
    SELECT * FROM films
    WHERE `year` IN (2023, 2024)
    ORDER BY `year` DESC, rating DESC, title ASC
    LIMIT 10
");
?>
<section class="hero">
    <div class="hero-card">
        <h1>Nonton film, jadi lebih simpel!</h1>
        <p>Pilih film favorit, tentukan jadwal, dan booking kursi dalam beberapa klik saja!</p>
        <div class="hero-actions">
            <a href="<?php echo isset($_SESSION['user_id']) ? 'booking.php' : 'register.php'; ?>" class="primary">
                <?php echo isset($_SESSION['user_id']) ? 'Mulai Booking' : 'Daftar Sekarang'; ?>
            </a>
            <button class="ghost" type="button" onclick="findNearestCinema()">Cari Bioskop Terdekat</button>
            <?php if (isAdmin()): ?>
                <a href="add_film.php" class="primary" style="margin-left:8px;background:#ff7b39;">
                    + Tambah Film
                </a>
            <?php endif; ?>
        </div>
        <p id="geo-info" style="margin-top:8px;font-size:0.85rem;"></p>
    </div>

    <div class="hero-side hero-side-trailer">
        <h3>Trailer Pilihan CinePals</h3>

        <?php if ($heroTrailerId !== ''): ?>
            <div class="hero-trailer-wrapper">
                <iframe
                    id="hero-trailer-iframe"
                    src="https://www.youtube.com/embed/<?php echo htmlspecialchars($heroTrailerId); ?>"
                    title="Trailer Film"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                ></iframe>
            </div>
            <p class="hero-film-hint">Trailer pilihan dari film terbaru kami.</p>
        <?php else: ?>
            <p style="font-size:0.9rem;">Trailer belum tersedia.</p>
        <?php endif; ?>
    </div>
</section>


<?php if ($bookingSuccess): ?>
    <div class="alert alert-success" style="max-width:600px;margin:0 auto 16px;">
        Yeyy, booking berhasil! Silakan cek tiketmu di My Tickets.
    </div>
<?php endif; ?>

<!-- SECTION LAGI TAYANG – POSTER ONLY + LIHAT SEMUA -->
<section class="carousel-section">
    <div class="section-header">
        <h2 class="section-title">Sedang Tayang</h2>
        <a href="now_showing.php" class="see-all-link">Lihat semua &gt;</a>
    </div>

    <?php if ($filmsCarousel && $filmsCarousel->num_rows > 0): ?>
        <?php $rank = 1; ?>
        <div class="film-carousel">
            <?php while ($film = $filmsCarousel->fetch_assoc()): ?>
                <?php $thumb = getFilmPoster($film['trailer_url'] ?? '', $film['poster'] ?? ''); ?>
                <!-- <div class="poster-only-card"> [!] ini aku ganti biar tiap posternya bisa diklik -->
                <a href="booking.php?film_id=<?= $film['id']; ?>" class="poster-only-card">
                    <div class="poster-wrapper">
                        <img src="<?php echo htmlspecialchars($thumb); ?>"
                             alt="Poster <?php echo htmlspecialchars($film['title']); ?>">
                        <span class="film-rank-badge"><?php echo $rank++; ?></span>
                    </div>
                </a>
                <!-- </div> -->
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p>Belum ada data film.</p>
    <?php endif; ?>
</section>

<!-- SECTION C.FOOD BANNER -->
<section class="cfood-wrapper">
    <h2 class="section-title">Yakin gak pengen sambil nyemil?</h2>

    <div class="cfood-banner">
        <div class="cfood-text">
            <p>Makanan & minuman enak yang cocok buat temen nonton kamu!</p>
            <a href="cfood.php" class="cfood-btn">Pesan C.Food Sekarang!</a>
        </div>
        <div class="cfood-image">
            <div class="cfood-image-box">
                <img src="public/img/cfood-banner.png" alt="Snack dan minuman C.Food">
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>