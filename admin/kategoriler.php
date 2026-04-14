<?php
require_once '../../config.php';
if (!admin()) { header('Location: /giris.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ekle') {
    $ad       = trim($_POST['ad'] ?? '');
    $aciklama = trim($_POST['aciklama'] ?? '');
    if ($ad) {
        $db->prepare("INSERT INTO kategoriler (ad, aciklama) VALUES (?, ?)")->execute([$ad, $aciklama]);
        bildirim("'{$ad}' kategorisi eklendi.", 'success');
    }
    header('Location: kategoriler.php'); exit;
}

if (isset($_GET['sil'])) {
    $id      = intval($_GET['sil']);
    $ilanVar = $db->prepare("SELECT COUNT(*) FROM ilanlar WHERE kategori_id=?");
    $ilanVar->execute([$id]);
    if ($ilanVar->fetchColumn() > 0) {
        bildirim('Bu kategoride ilanlar var. Önce ilanları silin.', 'danger');
    } else {
        $db->prepare("DELETE FROM kategoriler WHERE id=?")->execute([$id]);
        bildirim('Kategori silindi.', 'success');
    }
    header('Location: kategoriler.php'); exit;
}

$kategoriler = $db->query("SELECT k.*, (SELECT COUNT(*) FROM ilanlar WHERE kategori_id=k.id) as ilan_sayisi FROM kategoriler k ORDER BY k.ad")->fetchAll();
include '../header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">🏷️ Kategori Yönetimi</h2>
        <a href="/admin/index.php" class="btn btn-secondary btn-sm">← Dashboard</a>
    </div>
    <?php echo bildirimGoster(); ?>

    <div class="card" style="background:#f9f9f9;margin-bottom:24px;">
        <h3 style="color:#1B365D;margin-bottom:16px;">+ Yeni Kategori Ekle</h3>
        <form method="POST" style="display:grid;grid-template-columns:1fr 2fr auto;gap:14px;align-items:end;">
            <input type="hidden" name="action" value="ekle">
            <div>
                <label class="form-label">Kategori Adı *</label>
                <input type="text" name="ad" class="form-control" placeholder="örn: Lojistik" required>
            </div>
            <div>
                <label class="form-label">Açıklama</label>
                <input type="text" name="aciklama" class="form-control" placeholder="Kısa açıklama...">
            </div>
            <button type="submit" class="btn btn-success">Ekle</button>
        </form>
    </div>

    <table>
        <thead><tr><th>#</th><th>Kategori</th><th>Açıklama</th><th>İlan Sayısı</th><th>İşlem</th></tr></thead>
        <tbody>
        <?php foreach ($kategoriler as $kat): ?>
        <tr>
            <td style="color:#999;"><?php echo $kat['id']; ?></td>
            <td><strong style="color:#1B365D;"><?php echo guvenlik($kat['ad']); ?></strong></td>
            <td><?php echo guvenlik($kat['aciklama']); ?></td>
            <td><span class="badge badge-aktif"><?php echo $kat['ilan_sayisi']; ?> ilan</span></td>
            <td>
                <a href="?sil=<?php echo $kat['id']; ?>" class="btn btn-danger btn-sm"
                   onclick="return confirm('<?php echo guvenlik($kat['ad']); ?> silinsin mi?')">Sil</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include '../footer.php'; ?>
