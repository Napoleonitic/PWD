<?php
require_once 'config/database.php';
require_once 'includes/header.php';

$errors = [];

// pesan sukses setelah register
$justRegistered = isset($_GET['registered']) && $_GET['registered'] == '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    // =========================
    // 1. LOGIN SEBAGAI ADMIN
    // =========================
    if ($identifier === 'admin' && $password === 'admin') {
  
        $_SESSION['user_id']  = 0;
        $_SESSION['is_admin'] = true;

        header("Location: index.php");
        exit;
    }

    // =========================
    // 2. LOGIN USER BIASA
    // =========================
    if ($identifier === '' || $password === '') {
        $errors[] = 'Email/No HP dan password wajib diisi.';
    } else {
  
        $stmt = $conn->prepare("
            SELECT id, password, 
                   IFNULL(is_active, 1) AS is_active
            FROM users
            WHERE email = ? OR phone = ?
            LIMIT 1
        ");
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // cek status aktif / nonaktif
            if ((int)$user['is_active'] === 0) {
                // akun nonaktif → tidak boleh login
                $errors[] = 'Akun Anda sudah dinonaktifkan. Silakan hubungi admin.';
            } else {
                // akun aktif → cek password
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id']  = (int)$user['id'];
                    $_SESSION['is_admin'] = false;

                    header("Location: index.php");
                    exit;
                } else {
                    $errors[] = 'Password salah.';
                }
            }
        } else {
            $errors[] = 'Akun tidak ditemukan.';
        }

        $stmt->close();
    }
}
?>

<div class="card">
    <h2>Login</h2>

    <?php if ($justRegistered): ?>
        <div class="alert alert-success">
            Registrasi berhasil. Silakan login dengan akun yang baru dibuat.
        </div>
    <?php endif; ?>

    <?php foreach ($errors as $e): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($e); ?></div>
    <?php endforeach; ?>

    <p style="font-size:0.9rem;margin-bottom:10px;">
        Masuk dengan email atau nomor HP Anda.<br>
        
    </p>

    <form method="post">
        <div class="form-group">
            <label>Email / Nomor HP / ID Admin</label>
            <input type="text" name="identifier" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit" class="btn-submit">Masuk</button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
