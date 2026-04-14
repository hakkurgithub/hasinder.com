<?php
require_once '../config.php';

if (yetkili()) { header('Location: /index.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $sifre = $_POST['sifre'] ?? '';

    $stmt = $db->prepare("SELECT * FROM uyeler WHERE email = ?");
    $stmt->execute([$email]);
    $uye = $stmt->fetch();

    if ($uye && password_verify($sifre, $uye['sifre'])) {
        if ($uye['durum'] === 'BEKLEMEDE') {
            bildirim('Hesabınız henüz onaylanmadı. Lütfen bekleyin.', 'danger');
        } elseif ($uye['durum'] === 'PASIF') {
            bildirim('Hesabınız pasif durumda. Yönetici ile iletişime geçin.', 'danger');
        } else {
            $_SESSION['uye_id']  = $uye['id'];
            $_SESSION['ad']      = $uye['ad'];
            $_SESSION['email']   = $uye['email'];
            $_SESSION['durum']   = $uye['durum'];
            $_SESSION['is_admin']= $uye['is_admin'];
            header('Location: /index.php');
            exit;
        }
    } else {
        bildirim('E-posta veya şifre hatalı!', 'danger');
    }
}

include 'header.php';
?>

<div class="card" style="max-width:480px;margin:0 auto;">
    <div class="card-header">
        <h2 class="card-title">🔐 Üye Girişi</h2>
    </div>

    <form method="POST">
        <div class="form-group">
            <label class="form-label">E-posta</label>
            <input type="email" name="email" class="form-control" required
                   value="<?php echo guvenlik($_POST['email'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Şifre</label>
            <input type="password" name="sifre" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;padding:12px;font-size:16px;">Giriş Yap</button>
    </form>

    <p class="text-center mt-4" style="font-size:14px;">
        Hesabınız yok mu? <a href="/kayit.php" style="color:#D4AF37;">Kayıt olun</a>
    </p>
</div>

<?php include 'footer.php'; ?>
