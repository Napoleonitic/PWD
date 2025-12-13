<?php
require_once 'config/database.php';
require_once 'includes/header.php';

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

$films = $conn->query("
    SELECT * FROM films
    WHERE `year` IN (2023, 2024)
    ORDER BY `year` DESC, rating DESC, title ASC
");
?>
<section class="page-section">
    <div class="page-section-header">
        <div class="page-back-circle" onclick="history.back()">
            <span>&larr;</span>
        </div>
        <div>
            <h2 class="section-title section-title-highlight">Film Trending 2023 &amp; 2024</h2>
            <p class="section-subtitle">Film-film terbaru yang sedang tayang di bioskop kami.</p>
        </div>
    </div>

    <div class="film-grid">
        <?php if ($films && $films->num_rows > 0): ?>
            <?php while ($film = $films->fetch_assoc()): ?>
                <?php $thumb = getFilmPoster($film['trailer_url'] ?? '', $film['poster'] ?? ''); ?>
                <div class="film-card">
                    <img src="<?php echo htmlspecialchars($thumb); ?>"
                         alt="Poster <?php echo htmlspecialchars($film['title']); ?>">
                    <div class="film-content">
                        <div class="film-title"><?php echo htmlspecialchars($film['title']); ?></div>
                        <div class="film-meta">
                            <?php echo $film['year']; ?> • Rating: <?php echo $film['rating']; ?>
                        </div>
                        <div style="font-size:0.8rem; margin-bottom:8px; max-height:50px; overflow:hidden;">
                            <?php echo htmlspecialchars(substr($film['synopsis'], 0, 100)).'...'; ?>
                        </div>
                        <div class="film-actions">
                            <a href="booking.php?film_id=<?php echo $film['id']; ?>" class="film-book-btn">
                                Booking Sekarang
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>Belum ada data film.</p>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>