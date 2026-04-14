<?php
require_once '../config.php';
if (!admin()) { header('Location: /giris.php'); exit; }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $db->prepare("SELECT * FROM ilanlar WHERE id = ?");
$stmt->execute([$id]);
$ilan = $stmt->fetch();
if (!$ilan) { bildirim('İlan bulunamadı!', 'danger'); header('Location: /index.php'); exit; }

$kategoriler = $db->query("SELECT * FROM kategoriler ORDER BY ad")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $baslik     = trim($_POST['baslik'] ?? '');
    $aciklama   = trim($_POST['aciklama'] ?? '');
    $fiyat      = floatval(str_replace(',', '.', $_POST['fiyat'] ?? '0'));
    $kategori_id= intval($_POST['kategori_id'] ?? 0);
    $durum      = $_POST['durum'] ?? 'AKTIF';

    $stmt = $db->prepare("UPDATE ilanlar SET baslik=?, aciklama=?, fiyat=?, kategori_id=?, durum=? WHERE id=?");
    $stmt->execute([$baslik, $aciklama, $fiyat, $kategori_id, $durum, $id]);
    bildirim('İlan güncellendi!', 'success');
    header('Location: /index.php');
    exit;
}

include 'header.php';
?>
<div class="card" style="max-width:750px;margin:0 auto;">
    <div class="card-header">
        <h2 class="card-title">✏️ İlan Düzenle #<?php echo $ilan['id']; ?></h2>
        <a href="/index.php" class="btn btn-secondary btn-sm">← Geri</a>
    </div>
    <form method="POST">
        <div class="form-group">
            <label class="form-label">Başlık</label>
            <input type="text" name="baslik" class="form-control" value="<?php echo guvenlik($ilan['baslik']); ?>" required>
        </div>
        <div class="form-group">
            <label class="form-label">Açıklama</label>
            <textarea name="aciklama" class="form-control" rows="5" required><?php echo guvenlik($ilan['aciklama']); ?></textarea>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
            <div class="form-group">
                <label class="form-label">Fiyat (TL)</label>
                <input type="number" name="fiyat" class="form-control" step="0.01" value="<?php echo $ilan['fiyat']; ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Kategori</label>
                <select name="kategori_id" class="form-control" required>
                    <?php foreach ($kategoriler as $kat): ?>
                        <option value="<?php echo $kat['id']; ?>" <?php echo $ilan['kategori_id'] == $kat['id'] ? 'selected' : ''; ?>>
                            <?php echo guvenlik($kat['ad']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Durum</label>
            <select name="durum" class="form-control">
                <option value="AKTIF"     <?php echo $ilan['durum']=='AKTIF'     ? 'selected' : ''; ?>>Aktif</option>
                <option value="BEKLEMEDE" <?php echo $ilan['durum']=='BEKLEMEDE' ? 'selected' : ''; ?>>Beklemede</option>
                <option value="PASIF"     <?php echo $ilan['durum']=='PASIF'     ? 'selected' : ''; ?>>Pasif</option>
            </select>
        </div>
        <div style="display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary">Kaydet</button>
            <a href="/index.php" class="btn btn-secondary">İptal</a>
        </div>
    </form>
</div>
<?php include 'footer.php'; ?>
