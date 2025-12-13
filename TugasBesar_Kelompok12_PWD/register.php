<?php
require_once 'config/database.php';
require_once 'includes/header.php';

$errors = [];
$name = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // ===== VALIDASI DASAR =====
    if ($name === '' || $email === '' || $phone === '' || $password === '' || $confirm === '') {
        $errors[] = 'Semua field wajib diisi.';
    }

 
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    }

    if (!preg_match('/^[0-9+\-]+$/', $phone)) {
        $errors[] = 'Nomor telepon hanya boleh berisi angka, +, dan -.';
    }

    if ($password !== $confirm) {
        $errors[] = 'Konfirmasi password tidak sama.';
    }


    if (strlen($password) < 8 ||
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[a-z]/', $password) ||
        !preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password harus minimal 8 karakter, mengandung huruf besar, huruf kecil, dan angka.';
    }

    // CEK AVAILABILITY EMAIL 
    if (empty($errors)) {
        $st = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $st->bind_param('s', $email);
        $st->execute();
        $st->store_result();
        if ($st->num_rows > 0) {
            $errors[] = 'Email sudah terdaftar.';
        }
        $st->close();
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

     
        $st = $conn->prepare('INSERT INTO users (name, email, phone, password) VALUES (?,?,?,?)');
        $st->bind_param('ssss', $name, $email, $phone, $hash);

        if ($st->execute()) {
  
            header('Location: login.php?registered=1');
            exit;
        } else {
            $errors[] = 'Gagal menyimpan data. Coba lagi.';
        }
        $st->close();
    }
}
?>

<div class="card" style="max-width:480px;">
    <h2>Register</h2>

    <?php foreach ($errors as $e): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($e); ?></div>
    <?php endforeach; ?>

    <form method="post">
        <div class="form-group">
            <label>Nama Lengkap</label>
            <input type="text" name="name" required
                   value="<?php echo htmlspecialchars($name); ?>">
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" onkeyup="checkEmail(this)" required
                   value="<?php echo htmlspecialchars($email); ?>">
            <small id="email-info"></small>
        </div>

        <div class="form-group">
            <label>Nomor Telepon</label>
            <input type="text" name="phone" required
                   value="<?php echo htmlspecialchars($phone); ?>"
                   placeholder="0812xxxx atau +62-812xxxx">
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required
                   placeholder="Minimal 8 karakter, kombinasi huruf & angka">
        </div>

        <div class="form-group">
            <label>Konfirmasi Password</label>
            <input type="password" name="confirm_password" required>
        </div>

        <button type="submit" class="btn-submit">Daftar</button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
