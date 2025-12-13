<?php
// add_film.php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();
requireAdmin();
require_once 'includes/header.php';

$errors  = [];
$success = false;

$title      = '';
$year       = '';
$synopsis   = '';
$rating     = '';
$director   = '';
$cast       = '';
$trailerUrl = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title      = trim($_POST['title'] ?? '');
    $year       = trim($_POST['year'] ?? '');
    $synopsis   = trim($_POST['synopsis'] ?? '');
    $rating     = trim($_POST['rating'] ?? '');
    $director   = trim($_POST['director'] ?? '');
    $cast       = trim($_POST['cast'] ?? '');
    $trailerUrl = trim($_POST['trailer_url'] ?? '');

    if ($title === '') {
        $errors[] = 'Judul film wajib diisi.';
    }

    if ($year !== '' && !ctype_digit($year)) {
        $errors[] = 'Tahun harus berupa angka (contoh: 2024).';
    }

    if ($rating !== '' && !is_numeric($rating)) {
        $errors[] = 'Rating harus berupa angka, misalnya 8.5.';
    }


    $posterFilename = null;
    if (!empty($_FILES['poster']['name'])) {
        $uploadDir  = __DIR__ . '/public/img/';
        $fileName   = basename($_FILES['poster']['name']);
        $ext        = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExt = ['jpg','jpeg','png','gif','webp'];

        if (!in_array($ext, $allowedExt, true)) {
            $errors[] = 'Format poster harus jpg/jpeg/png/gif/webp.';
        } else {
       
            $safeBase = preg_replace('~[^a-zA-Z0-9_-]+~', '_', pathinfo($fileName, PATHINFO_FILENAME));
            $posterFilename = time() . '_' . $safeBase . '.' . $ext;
            $targetPath = $uploadDir . $posterFilename;

            if (!move_uploaded_file($_FILES['poster']['tmp_name'], $targetPath)) {
                $errors[] = 'Gagal mengupload poster.';
            }
        }
    }

    if (empty($errors)) {
        $yearVal   = $year === ''   ? null : (int)$year;
        $ratingVal = $rating === '' ? null : (float)$rating;

        $stmt = $conn->prepare("
            INSERT INTO films (title, year, synopsis, rating, director, cast, poster, trailer_url)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            'sisdssss',
            $title,
            $yearVal,
            $synopsis,
            $ratingVal,
            $director,
            $cast,
            $posterFilename,
            $trailerUrl
        );

        if ($stmt->execute()) {
            $success = true;
            // reset form
            $title = $year = $synopsis = $rating = $director = $cast = $trailerUrl = '';
        } else {
            $errors[] = 'Gagal menyimpan film baru: ' . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<div class="card" style="max-width:720px;">
    <h2>Tambah Film Baru</h2>
    <p style="font-size:0.9rem;margin-bottom:12px;">
        Halaman ini hanya bisa diakses admin (login: <code>admin / admin</code>).<br>
        Poster akan disimpan ke folder <code>public/img/</code>.
    </p>

    <?php if ($success): ?>
        <div class="alert alert-success">Film berhasil ditambahkan!</div>
    <?php endif; ?>

    <?php foreach ($errors as $e): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($e); ?></div>
    <?php endforeach; ?>

    <form method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label>Judul Film *</label>
            <input type="text" name="title" required value="<?php echo htmlspecialchars($title); ?>">
        </div>

        <div class="form-group">
            <label>Tahun Rilis</label>
            <input type="text" name="year" placeholder="2024" value="<?php echo htmlspecialchars($year); ?>">
        </div>

        <div class="form-group">
            <label>Rating (IMDB / dll)</label>
            <input type="text" name="rating" placeholder="8.5" value="<?php echo htmlspecialchars($rating); ?>">
        </div>

        <div class="form-group">
            <label>Sutradara</label>
            <input type="text" name="director" value="<?php echo htmlspecialchars($director); ?>">
        </div>

        <div class="form-group">
            <label>Pemain</label>
            <textarea name="cast" rows="2" placeholder="Pisahkan dengan koma"><?php echo htmlspecialchars($cast); ?></textarea>
        </div>

        <div class="form-group">
            <label>Sinopsis</label>
            <textarea name="synopsis" rows="4"><?php echo htmlspecialchars($synopsis); ?></textarea>
        </div>

        <div class="form-group">
            <label>URL Trailer (YouTube)</label>
            <input type="text" name="trailer_url" placeholder="https://www.youtube.com/watch?v=..." value="<?php echo htmlspecialchars($trailerUrl); ?>">
        </div>

        <div class="form-group">
            <label>Poster (gambar)</label>
            <input type="file" name="poster" accept=".jpg,.jpeg,.png,.gif,.webp">
            <small>Opsional, tapi disarankan. Ukuran tidak terlalu besar.</small>
        </div>

        <button type="submit" class="btn-submit">Simpan Film</button>
        <a href="index.php" class="btn-outline" style="margin-left:8px;">Kembali ke Home</a>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
