<?php
require_once '../config.php';

$hatalar  = [];
$basarili = false;

$kurullar = $db->query("SELECT id, kurul_ad, aciklama FROM icra_kurullari WHERE durum='AKTIF' ORDER BY kurul_ad")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ad_soyad  = trim($_POST['ad_soyad']  ?? '');
    $email     = trim($_POST['email']     ?? '');
    $telefon   = trim($_POST['telefon']   ?? '');
    $kurul_id  = intval($_POST['kurul_id'] ?? 0);
    $is_tanimi = trim($_POST['is_tanimi'] ?? '');
    $is_tutari = floatval(str_replace(',', '.', $_POST['is_tutari'] ?? '0'));

    if (mb_strlen($ad_soyad) < 3)                       $hatalar[] = 'Ad soyad en az 3 karakter olmalı.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))     $hatalar[] = 'Geçerli bir e-posta adresi girin.';
    if (mb_strlen($telefon) < 10)                       $hatalar[] = 'Geçerli bir telefon numarası girin.';
    if ($kurul_id === 0)                                $hatalar[] = 'Lütfen bir kurul seçin.';
    if (mb_strlen($is_tanimi) < 20)                     $hatalar[] = 'İş tanımı en az 20 karakter olmalı.';
    if ($is_tutari <= 0)                                $hatalar[] = 'Geçerli bir iş tutarı girin.';

    $dosya_yolu = null;
    if (!empty($_FILES['dosya']['name'])) {
        $uzanti = strtolower(pathinfo($_FILES['dosya']['name'], PATHINFO_EXTENSION));
        if (!in_array($uzanti, ['pdf','jpg','jpeg','png','doc','docx'])) {
            $hatalar[] = 'Sadece PDF, JPG, PNG, DOC, DOCX yüklenebilir.';
        } elseif ($_FILES['dosya']['size'] > MAX_FILE_SIZE) {
            $hatalar[] = 'Dosya 5 MB\'ı geçemez.';
        } else {
            $klasor = __DIR__ . '/uploads/is_basvurulari/';
            if (!is_dir($klasor)) mkdir($klasor, 0755, true);
            $yeni_isim = time() . '_' . bin2hex(random_bytes(5)) . '.' . $uzanti;
            if (move_uploaded_file($_FILES['dosya']['tmp_name'], $klasor . $yeni_isim)) {
                $dosya_yolu = 'uploads/is_basvurulari/' . $yeni_isim;
            } else {
                $hatalar[] = 'Dosya yüklenemedi.';
            }
        }
    }

    if (empty($hatalar)) {
        $stmt = $db->prepare("INSERT INTO is_basvurulari (ad_soyad,email,telefon,kurul_id,is_tanimi,is_tutari,dosya_yolu,durum) VALUES (?,?,?,?,?,?,?,'BEKLEMEDE')");
        $stmt->execute([$ad_soyad,$email,$telefon,$kurul_id,$is_tanimi,$is_tutari,$dosya_yolu]);
        $bId = $db->lastInsertId();

        $kurulAdi = '';
        foreach ($kurullar as $k) { if ($k['id'] == $kurul_id) { $kurulAdi = $k['kurul_ad']; break; } }
        sendAdminNotification("Yeni İş Başvurusu #{$bId}\nBaşvuran: {$ad_soyad}\nKurul: {$kurulAdi}\nTutar: " . para($is_tutari) . "\nEmail: {$email}");

        $basarili = true;
    }
}

include 'header.php';
?>

<div class="card" style="max-width:780px;margin:0 auto;">
    <div class="card-header">
        <h2 class="card-title">📋 İş Başvuru Formu</h2>
    </div>

    <?php if ($basarili): ?>
        <div style="text-align:center;padding:50px 20px;">
            <div style="font-size:64px;margin-bottom:20px;">✅</div>
            <h3 style="color:#28a745;font-size:24px;margin-bottom:12px;">Başvurunuz Alındı!</h3>
            <p style="margin-bottom:25px;">Başvurunuz incelemeye alındı. En kısa sürede sizinle iletişime geçeceğiz.</p>
            <a href="/index.php" class="btn btn-primary">Ana Sayfaya Dön</a>
            <a href="/is-basvuru-formu.php" class="btn btn-secondary" style="margin-left:10px;">Yeni Başvuru</a>
        </div>
    <?php else: ?>
        <?php if ($hatalar): ?>
            <div style="background:#f8d7da;color:#721c24;padding:14px 18px;border-radius:6px;margin-bottom:20px;">
                <strong>Hataları düzeltin:</strong><br>
                <?php foreach ($hatalar as $h): ?>• <?php echo guvenlik($h); ?><br><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <p style="color:#666;margin-bottom:22px;">İlgili icra kuruluna iş başvurusunda bulunun. Komisyon oranları başvuru onaylandıktan sonra iletilir.</p>

        <form method="POST" enctype="multipart/form-data">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
                <div class="form-group">
                    <label class="form-label">Ad Soyad *</label>
                    <input type="text" name="ad_soyad" class="form-control" value="<?php echo guvenlik($_POST['ad_soyad'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">E-posta *</label>
                    <input type="email" name="email" class="form-control" value="<?php echo guvenlik($_POST['email'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Telefon *</label>
                    <input type="tel" name="telefon" class="form-control" value="<?php echo guvenlik($_POST['telefon'] ?? ''); ?>" placeholder="05XX XXX XX XX" required>
                </div>
                <div class="form-group">
                    <label class="form-label">İcra Kurulu *</label>
                    <select name="kurul_id" class="form-control" required>
                        <option value="">-- Kurul Seçin --</option>
                        <?php foreach ($kurullar as $k): ?>
                            <option value="<?php echo $k['id']; ?>" <?php echo (isset($_POST['kurul_id']) && $_POST['kurul_id'] == $k['id']) ? 'selected' : ''; ?>>
                                <?php echo guvenlik($k['kurul_ad']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">İş Tutarı (TL) *</label>
                <input type="number" name="is_tutari" class="form-control" value="<?php echo guvenlik($_POST['is_tutari'] ?? ''); ?>" min="1" step="0.01" required>
            </div>

            <div class="form-group">
                <label class="form-label">İş Tanımı * (en az 20 karakter)</label>
                <textarea name="is_tanimi" class="form-control" rows="5" required><?php echo guvenlik($_POST['is_tanimi'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Belge Ekle (İsteğe Bağlı)</label>
                <input type="file" name="dosya" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                <small style="color:#888;">PDF, JPG, PNG, DOC, DOCX — Maks. 5 MB</small>
            </div>

            <div style="background:#e8f4fd;border-left:4px solid #2196F3;padding:14px;border-radius:0 6px 6px 0;margin-bottom:22px;font-size:14px;color:#1a5276;">
                ℹ️ Başvurunuz ilgili icra kurulu başkanına iletilecek ve değerlendirme sonucunda sizinle iletişime geçilecektir.
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;padding:14px;font-size:16px;">📤 Başvuruyu Gönder</button>
        </form>
    <?php endif; ?>
</div>

<style>@media(max-width:600px){div[style*="grid-template-columns:1fr 1fr"]{grid-template-columns:1fr!important;}}</style>
<?php include 'footer.php'; ?>
