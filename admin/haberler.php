<?php
require_once '../../config.php';
if (!admin()) { header('Location: /giris.php'); exit; }

// Silme
if (isset($_GET['sil'])) {
    $id = intval($_GET['sil']);
    $db->prepare("DELETE FROM haberler WHERE id=?")->execute([$id]);
    header('Location: haberler.php'); exit;
}

// Durum değiştirme
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $db->prepare("UPDATE haberler SET durum=IF(durum='AKTIF','PASIF','AKTIF') WHERE id=?")->execute([$id]);
    header('Location: haberler.php'); exit;
}

$haberler = $db->query("SELECT * FROM haberler ORDER BY yayin_tarihi DESC")->fetchAll();
include '../header.php';
?>

<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <h2 class="card-title">📰 Haber Yönetimi (<?php echo count($haberler); ?>)</h2>
        <a href="haber-ekle.php" class="btn btn-primary">➕ Yeni Haber Ekle</a>
    </div>

    <?php if (empty($haberler)): ?>
        <div style="text-align:center;padding:50px;">
            <p>Henüz haber eklenmemiş.</p>
            <a href="haber-ekle.php" class="btn btn-primary">İlk Haberi Ekle</a>
        </div>
    <?php else: ?>
    <table style="width:100%;">
        <thead>
            <tr style="background:#1B365D;color:white;">
                <th style="padding:12px;">ID</th>
                <th style="padding:12px;">Başlık</th>
                <th style="padding:12px;">Durum</th>
                <th style="padding:12px;">Tarih</th>
                <th style="padding:12px;">İşlemler</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($haberler as $h): ?>
        <tr style="border-bottom:1px solid #eee;">
            <td style="padding:12px;"><?php echo $h['id']; ?></td>
            <td style="padding:12px;">
                <strong><?php echo guvenlik($h['baslik']); ?></strong><br>
                <small style="color:#666;"><?php echo guvenlik(mb_substr($h['ozet'], 0, 50)); ?>...</small>
            </td>
            <td style="padding:12px;">
                <?php if ($h['durum'] == 'AKTIF'): ?>
                    <span style="color:green;">🟢 Yayında</span>
                <?php else: ?>
                    <span style="color:red;">🔴 Pasif</span>
                <?php endif; ?>
            </td>
            <td style="padding:12px;"><?php echo date('d.m.Y', strtotime($h['yayin_tarihi'])); ?></td>
            <td style="padding:12px;">
                <a href="haber-ekle.php?id=<?php echo $h['id']; ?>" class="btn btn-primary btn-sm">✏️ Düzenle</a>
                <a href="?toggle=<?php echo $h['id']; ?>" class="btn btn-secondary btn-sm">🔄 Durum</a>
                <a href="?sil=<?php echo $h['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Silmek istiyor musunuz?')">🗑️ Sil</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php include '../footer.php'; ?>