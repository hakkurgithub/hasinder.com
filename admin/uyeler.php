<?php
require_once '../../config.php';
if (!admin()) { header('Location: /giris.php'); exit; }

// Onay / Reddet
if (isset($_GET['onay']) && isset($_GET['id'])) {
    $id    = intval($_GET['id']);
    $durum = $_GET['onay'] == '1' ? 'AKTIF' : 'PASIF';
    $db->prepare("UPDATE uyeler SET durum=? WHERE id=?")->execute([$durum, $id]);
    $eylem = $durum === 'AKTIF' ? 'onaylandı' : 'reddedildi';
    bildirim("Üye {$eylem}.", $durum === 'AKTIF' ? 'success' : 'danger');
    header('Location: uyeler.php');
    exit;
}

// Silme
if (isset($_GET['sil'])) {
    $id = intval($_GET['sil']);
    $db->prepare("DELETE FROM uyeler WHERE id=? AND is_admin=0")->execute([$id]);
    bildirim('Üye silindi.', 'success');
    header('Location: uyeler.php');
    exit;
}

$uyeler = $db->query("SELECT * FROM uyeler ORDER BY created_at DESC")->fetchAll();
include '../header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">👥 Üye Yönetimi (<?php echo count($uyeler); ?>)</h2>
        <a href="/admin/index.php" class="btn btn-secondary btn-sm">← Dashboard</a>
    </div>
    <?php echo bildirimGoster(); ?>
    <div style="overflow-x:auto;">
    <table>
        <thead>
            <tr><th>Ad</th><th>E-posta</th><th>Telefon</th><th>Durum</th><th>Admin</th><th>Tarih</th><th>İşlem</th></tr>
        </thead>
        <tbody>
        <?php foreach ($uyeler as $uye): ?>
        <tr>
            <td><strong><?php echo guvenlik($uye['ad']); ?></strong></td>
            <td><?php echo guvenlik($uye['email']); ?></td>
            <td><?php echo guvenlik($uye['telefon']); ?></td>
            <td><span class="badge badge-<?php echo strtolower($uye['durum']); ?>"><?php echo $uye['durum']; ?></span></td>
            <td><?php echo $uye['is_admin'] ? '⭐ Evet' : '-'; ?></td>
            <td style="font-size:12px;color:#888;"><?php echo date('d.m.Y', strtotime($uye['created_at'])); ?></td>
            <td style="display:flex;gap:5px;flex-wrap:wrap;">
                <?php if ($uye['durum'] === 'BEKLEMEDE'): ?>
                    <a href="?onay=1&id=<?php echo $uye['id']; ?>" class="btn btn-success btn-sm">✓ Onayla</a>
                    <a href="?onay=0&id=<?php echo $uye['id']; ?>" class="btn btn-danger btn-sm">✗ Reddet</a>
                <?php elseif ($uye['durum'] === 'AKTIF'): ?>
                    <a href="?onay=0&id=<?php echo $uye['id']; ?>" class="btn btn-secondary btn-sm">Pasifleştir</a>
                <?php else: ?>
                    <a href="?onay=1&id=<?php echo $uye['id']; ?>" class="btn btn-success btn-sm">Aktifleştir</a>
                <?php endif; ?>
                <?php if (!$uye['is_admin']): ?>
                    <a href="?sil=<?php echo $uye['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Üye silinsin mi?')">Sil</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<?php include '../footer.php'; ?>
