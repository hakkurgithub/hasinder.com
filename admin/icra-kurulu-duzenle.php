<?php
require_once '../../config.php';
if (!admin()) { header('Location: /giris.php'); exit; }

$id     = isset($_GET['id']) ? intval($_GET['id']) : 0;
$hatalar = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kurul_ad       = trim($_POST['kurul_ad']      ?? '');
    $aciklama       = trim($_POST['aciklama']       ?? '');
    $baskan_uye_id  = !empty($_POST['baskan_uye_id']) ? intval($_POST['baskan_uye_id']) : null;
    $durum          = in_array($_POST['durum'] ?? '', ['AKTIF','PASIF']) ? $_POST['durum'] : 'AKTIF';

    if (mb_strlen($kurul_ad) < 3) $hatalar[] = 'Kurul adı en az 3 karakter olmalı.';

    if (!$hatalar) {
        if ($id > 0) {
            $db->prepare("UPDATE icra_kurullari SET kurul_ad=?,aciklama=?,baskan_uye_id=?,durum=?,guncellenme_tarihi=NOW() WHERE id=?")
               ->execute([$kurul_ad,$aciklama,$baskan_uye_id,$durum,$id]);
            bildirim('Kurul güncellendi.', 'success');
        } else {
            $db->prepare("INSERT INTO icra_kurullari (kurul_ad,aciklama,baskan_uye_id,durum) VALUES (?,?,?,?)")
               ->execute([$kurul_ad,$aciklama,$baskan_uye_id,$durum]);
            bildirim('Yeni kurul eklendi.', 'success');
        }
        header('Location: /admin/icra-kurullari.php'); exit;
    }

    $kurul = compact('kurul_ad','aciklama','baskan_uye_id','durum');
} else {
    $kurul = ['kurul_ad'=>'','aciklama'=>'','baskan_uye_id'=>'','durum'=>'AKTIF'];
    if ($id > 0) {
        $stmt = $db->prepare("SELECT * FROM icra_kurullari WHERE id=?");
        $stmt->execute([$id]);
        $satir = $stmt->fetch();
        if (!$satir) { bildirim('Kurul bulunamadı.','danger'); header('Location: /admin/icra-kurullari.php'); exit; }
        $kurul = $satir;
    }
}

$uyeler = $db->query("SELECT id, ad, email FROM uyeler WHERE durum='AKTIF' ORDER BY ad")->fetchAll();
include '../header.php';
?>

<div class="card" style="max-width:760px;margin:0 auto;">
    <div class="card-header">
        <h2 class="card-title"><?php echo $id ? '✏️ Kurul Düzenle' : '➕ Yeni Kurul Ekle'; ?></h2>
        <a href="/admin/icra-kurullari.php" class="btn btn-secondary btn-sm">← Listeye Dön</a>
    </div>
    <?php echo bildirimGoster(); ?>

    <?php if ($hatalar): ?>
        <div style="background:#f8d7da;color:#721c24;padding:14px;border-radius:6px;margin-bottom:18px;">
            <?php foreach ($hatalar as $h): ?>• <?php echo guvenlik($h); ?><br><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label class="form-label">Kurul Adı *</label>
            <input type="text" name="kurul_ad" class="form-control" value="<?php echo guvenlik($kurul['kurul_ad']); ?>" required maxlength="255">
        </div>

        <div class="form-group">
            <label class="form-label">Açıklama</label>
            <textarea name="aciklama" class="form-control" rows="3"><?php echo guvenlik($kurul['aciklama']); ?></textarea>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
            <div class="form-group">
                <label class="form-label">Durum</label>
                <select name="durum" class="form-control">
                    <option value="AKTIF" <?php echo $kurul['durum']==='AKTIF' ? 'selected' : ''; ?>>🟢 Aktif</option>
                    <option value="PASIF" <?php echo $kurul['durum']==='PASIF' ? 'selected' : ''; ?>>🔴 Pasif</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Kurul Başkanı (İsteğe Bağlı)</label>
                <select name="baskan_uye_id" class="form-control">
                    <option value="">-- Başkan Atanmamış --</option>
                    <?php foreach ($uyeler as $uye): ?>
                        <option value="<?php echo $uye['id']; ?>" <?php echo (string)($kurul['baskan_uye_id'] ?? '') === (string)$uye['id'] ? 'selected' : ''; ?>>
                            <?php echo guvenlik($uye['ad']); ?> (<?php echo guvenlik($uye['email']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div style="display:flex;gap:12px;margin-top:25px;">
            <button type="submit" class="btn btn-primary"><?php echo $id ? '💾 Kaydet' : '➕ Oluştur'; ?></button>
            <a href="/admin/icra-kurullari.php" class="btn btn-secondary">İptal</a>
        </div>
    </form>
</div>

<?php include '../footer.php'; ?>