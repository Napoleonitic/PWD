<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();
require_once 'includes/header.php';

$user_id = currentUserId();
$success = '';
$errors = [];

$stmt = $conn->prepare("SELECT email, phone, nickname, photo FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $email    = trim($_POST['email']);
        $phone    = trim($_POST['phone']);
        $nickname = trim($_POST['nickname']);
        $photo = $user['photo'];

        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id <> ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Email sudah digunakan user lain.";
        }
        $stmt->close();

        if (empty($errors)) {
            $stmt = $conn->prepare("UPDATE users SET email=?, phone=?, nickname=? WHERE id=?");
            $stmt->bind_param("sssi", $email, $phone, $nickname, $user_id);
            if ($stmt->execute()) {
                $success = "Profil berhasil diperbarui.";
                $user['email'] = $email;
                $user['phone'] = $phone;
                $user['nickname'] = $nickname;
            } else {
                $errors[] = "Gagal update profil.";
            }
            $stmt->close();
        }
    }

    if (isset($_POST['update_password'])) {
        $newpass = $_POST['new_password'];
        if (strlen($newpass) < 4) {
            $errors[] = "Password minimal 4 karakter.";
        } else {
            $hash = password_hash($newpass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param("si", $hash, $user_id);
            if ($stmt->execute()) {
                $success = "Password berhasil diubah.";
            } else {
                $errors[] = "Gagal update password.";
            }
            $stmt->close();
        }
    }

    if (isset($_POST['deactivate'])) {
        $stmt = $conn->prepare("UPDATE users SET is_active=0 WHERE id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        session_destroy();
        echo "<script>alert('Akun dinonaktifkan.');window.location='index.php';</script>";
        exit;
    }

        if (!empty($_FILES['photo']['name'])) {
            $allowed = ['jpg','jpeg','png'];
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    
            if (!in_array($ext, $allowed)) {
                $errors[] = "Format foto harus JPG atau PNG.";
            } else {
                $newName = 'user_' . $user_id . '.' . $ext;
                $uploadPath = 'uploads/' . $newName;
    
                move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath);
    
                $stmt = $conn->prepare("UPDATE users SET photo=? WHERE id=?");
                $stmt->bind_param("si", $newName, $user_id);
                $stmt->execute();
                $stmt->close();
    
                $user['photo'] = $newName;
                $success = "Foto profil diperbarui.";
            }
        }
    
    if (isset($_POST['delete_photo'])) {
        if ($user['photo']) {
            $file = 'uploads/' . $user['photo'];
            if (file_exists($file)) unlink($file);
        }
    
        $stmt = $conn->prepare("UPDATE users SET photo=NULL WHERE id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    
        $user['photo'] = null;
        $success = "Foto profil dihapus.";
    }    
}

?>

<div class="profile-page">
    <div class="card" style="max-width:600px;">
        <h2>Pengaturan Akun</h2>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php foreach ($errors as $e): ?>
            <div class="alert alert-error"><?php echo $e; ?></div>
        <?php endforeach; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="profile-picture-container">
                
                <img id="profilePreview"
                    src="<?php echo $user['photo'] ? 'uploads/'.$user['photo'] : 'public/img/no-profile.png'; ?>"
                    class="profile-picture">
                <input type="file" id="photoInput" name="photo" accept="image/*" hidden>
                <input type="hidden" name="change_photo" value="1">
                
                <button type="button" class="change-photo-btn" id="openPhotoPicker">
                    <i class="fas fa-pencil-alt"></i>
                </button>
                <?php if ($user['photo']): ?>
                    <button type="submit" name="delete_photo" class="delete-photo-btn">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Nama</label>
                <input type="text" name="nickname" value="<?php echo htmlspecialchars($user['nickname']); ?>" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>

            <div class="form-group">
                <label>Nomor HP</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
            </div>

            <button type="submit" name="update_profile" class="btn-submit">Simpan Profil</button>
        </form>

        <hr style="margin:18px 0;">

        <form method="post">
            <div class="form-group">
                <label>Password Baru</label>
                <input type="password" name="new_password" required>
            </div>
            <button type="submit" name="update_password" class="btn-submit">Update Password</button>
        </form>

        <hr style="margin:18px 0;">

        <form method="post" onsubmit="return confirm('Yakin ingin menonaktifkan akun?');">
            <button type="submit" name="deactivate" class="btn-submit" style="background:#b00020;">Deactivate Account</button>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>