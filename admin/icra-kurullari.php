<?php
require_once '../../config.php';
if (!admin()) { header('Location: /giris.php'); exit; }

if (isset($_GET['sil'])) {
    $id  = intval($_GET['sil']);
    $say = $db->prepare("SELECT COUNT(*) FROM is_basvurulari WHERE kurul_id=?");
    $say->execute([$id]);
    if ($say->fetchColumn() > 0) {
        bildirim('Bu kurula ait başvurular var. Önce başvuruları silin.', 'danger');
    } else {
        $db->prepare("DELETE FROM icra_kurullari WHERE id=?")->execute([$id]);
        bildirim('Kurul silindi.', 'success');
    }
    header('Location: icra-kurullari.php'); exit;
}

if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $db->prepare("UPDATE icra_kurullari SET durum=IF(durum='AKTIF','PASIF','AKTIF') WHERE id=?")->execute([$id]);
    bildirim('Durum güncellendi.', 'success');
    header('Location: icra-kurullari.php'); exit;
}

$kurullar = $db->query("
    SELECT ik.*, u.ad AS baskan_ad, u.email AS baskan_email,
           (SELECT COUNT(*) FROM is_basvurulari b WHERE b.kurul_id=ik.id) AS basvuru_sayisi
    FROM icra_kurullari ik
    LEFT JOIN uyeler u ON ik.baskan_uye_id=u.id
    ORDER BY ik.olusturma_tarihi DESC
")->fetchAll();

include '../header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">🏛️ İcra Kurulları (<?php echo count($kurullar); ?>)</h2>
        <div style="display:flex;gap:8px;">
            <a href="/admin/is-basvurulari.php" class="btn btn-info btn-sm">📑 Başvurular</a>
            <a href="/admin/icra-kurulu-duzenle.php" class="btn btn-primary btn-sm">➕ Yeni Kurul</a>
            <a href="/admin/index.php" class="btn btn-secondary btn-sm">← Dashboard</a>
        </div>
    </div>
    <?php echo bildirimGoster(); ?>

    <?php if (!$kurullar): ?>
        <div style="text-align:center;padding:50px;color:#aaa;">
            <p style="font-size:18px;margin-bottom:16px;">Henüz kurul eklenmemiş.</p>
            <a href="/admin/icra-kurulu-duzenle.php" class="btn btn-primary">➕ İlk Kurulu Ekle</a>
        </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
    <table>
        <thead>
            <tr><th>#</th><th>Kurul Adı</th><th>Başkan</th><th>Başvurular</th><th>Durum</th><th>Tarih</th><th>İşlem</th></tr>
        </thead>
        <tbody>
        <?php foreach ($kurullar as $k): ?>
        <tr>
            <td style="color:#999;">#<?php echo $k['id']; ?></td>
            <td>
                <!-- KURUL ADI ARTıK LİNK - ÖN YÜZE GİDİYOR -->
                <a href="/kurul-detay.php?id=<?php echo (int)$k['id']; ?>" target="_blank" style="color:#1e3a8a;text-decoration:none;font-weight:600;">
                    <?php echo guvenlik($k['kurul_ad']); ?>
                </a>
                <?php if ($k['aciklama']): ?>
                    <br><small style="color:#888;"><?php echo guvenlik(mb_substr($k['aciklama'], 0, 50)); ?>...</small>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($k['baskan_ad']): ?>
                    👤 <?php echo guvenlik($k['baskan_ad']); ?><br>
                    <small style="color:#888;"><?php echo guvenlik($k['baskan_email']); ?></small>
                <?php else: ?>
                    <span style="color:#bbb;font-style:italic;">Atanmamış</span>
                <?php endif; ?>
            </td>
            <td>
                <a href="/admin/is-basvurulari.php?kurul_id=<?php echo (int)$k['id']; ?>" style="background:#e3f2fd;color:#1565c0;padding:3px 9px;border-radius:4px;font-size:12px;text-decoration:none;">
                    📄 <?php echo $k['basvuru_sayisi']; ?>
                </a>
            </td>
            <td>
                <?php if ($k['durum'] === 'AKTIF'): ?>
                    <span class="badge badge-aktif">🟢 Aktif</span>
                <?php else: ?>
                    <span class="badge badge-pasif">🔴 Pasif</span>
                <?php endif; ?>
            </td>
            <td style="font-size:12px;color:#888;"><?php echo date('d.m.Y', strtotime($k['olusturma_tarihi'])); ?></td>
            <td style="display:flex;gap:4px;flex-wrap:wrap;">
                <!-- ÖN YÜZDE GÖRÜNTÜLE BUTONU (YENİ) -->
                <a href="/kurul-detay.php?id=<?php echo (int)$k['id']; ?>" target="_blank" class="btn btn-success btn-sm" title="Ön Yüzde Görüntüle">👁️</a>
                
                <a href="/admin/icra-kurulu-duzenle.php?id=<?php echo (int)$k['id']; ?>" class="btn btn-info btn-sm" title="Düzenle">✏️</a>
                <a href="?toggle=<?php echo (int)$k['id']; ?>" class="btn btn-secondary btn-sm" onclick="return confirm('Durum değiştirilsin mi?')" title="Durum Değiştir"><?php echo $k['durum']==='AKTIF' ? '⏸' : '▶'; ?></a>
                <a href="?sil=<?php echo (int)$k['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Silinsin mi?')" title="Sil">🗑️</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<?php include '../footer.php'; ?>