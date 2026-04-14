<?php
require_once '../config.php';

if (yetkili()) { header('Location: /index.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ad      = trim($_POST['ad'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $telefon = trim($_POST['telefon'] ?? '');
    $sifre   = $_POST['sifre'] ?? '';

    if (mb_strlen($ad) < 2)              bildirim('Ad soyad en az 2 karakter olmalı.', 'danger');
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) bildirim('Geçerli e-posta girin.', 'danger');
    elseif (mb_strlen($sifre) < 6)       bildirim('Şifre en az 6 karakter olmalı.', 'danger');
    else {
        try {
            $stmt = $db->prepare("INSERT INTO uyeler (ad, email, telefon, sifre, durum, is_admin) VALUES (?, ?, ?, ?, 'BEKLEMEDE', 0)");
            $stmt->execute([$ad, $email, $telefon, password_hash($sifre, PASSWORD_DEFAULT)]);
            sendAdminNotification("Yeni üye kaydı: {$ad} ({$email})");
            bildirim('Kayıt başarılı! Hesabınız onaylandıktan sonra giriş yapabilirsiniz.', 'success');
            header('Location: /giris.php');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) bildirim('Bu e-posta zaten kayıtlı.', 'danger');
            else bildirim('Kayıt sırasında hata oluştu.', 'danger');
        }
    }
}

include 'header.php';
?>

<div class="card" style="max-width:480px;margin:0 auto;">
    <div class="card-header">
        <h2 class="card-title">🚀 Üye Kayıt</h2>
    </div>

    <form method="POST">
        <div class="form-group">
            <label class="form-label">Ad Soyad *</label>
            <input type="text" name="ad" class="form-control" required value="<?php echo guvenlik($_POST['ad'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label class="form-label">E-posta *</label>
            <input type="email" name="email" class="form-control" required value="<?php echo guvenlik($_POST['email'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Telefon</label>
            <input type="text" name="telefon" class="form-control" value="<?php echo guvenlik($_POST['telefon'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Şifre * (en az 6 karakter)</label>
            <input type="password" name="sifre" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;padding:12px;font-size:16px;">Kayıt Ol</button>
    </form>

    <p class="text-center mt-4" style="font-size:14px;">
        Zaten üyesiniz? <a href="/giris.php" style="color:#D4AF37;">Giriş yapın</a>
    </p>
</div>

<?php include 'footer.php'; ?>
