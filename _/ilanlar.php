<?php
require_once '../config.php';

$kategori_id = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;
$arama       = isset($_GET['arama'])    ? trim($_GET['arama'])    : '';

$sql    = "SELECT i.*, u.ad as uye_ad, k.ad as kategori_ad
           FROM ilanlar i
           LEFT JOIN uyeler u ON i.uye_id = u.id
           LEFT JOIN kategoriler k ON i.kategori_id = k.id
           WHERE i.durum = 'AKTIF'";
$params = [];

if ($kategori_id > 0) { $sql .= " AND i.kategori_id = ?"; $params[] = $kategori_id; }
if ($arama !== '')    { $sql .= " AND (i.baslik LIKE ? OR i.aciklama LIKE ?)"; $params[] = "%$arama%"; $params[] = "%$arama%"; }
$sql .= " ORDER BY i.created_at DESC";

$stmt = $db->prepare($sql); $stmt->execute($params);
$ilanlar    = $stmt->fetchAll();
$kategoriler = $db->query("SELECT * FROM kategoriler ORDER BY ad")->fetchAll();

include 'header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">📦 Tüm İlanlar (<?php echo count($ilanlar); ?>)</h2>
        <?php if (admin()): ?>
            <a href="/ilan-ver.php" class="btn btn-success">+ Yeni İlan</a>
        <?php endif; ?>
    </div>

    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;">
        <select name="kategori" class="form-control" style="width:auto;min-width:180px;">
            <option value="">Tüm Kategoriler</option>
            <?php foreach ($kategoriler as $kat): ?>
                <option value="<?php echo $kat['id']; ?>" <?php echo $kategori_id == $kat['id'] ? 'selected' : ''; ?>>
                    <?php echo guvenlik($kat['ad']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="arama" class="form-control" placeholder="İlan ara..." value="<?php echo guvenlik($arama); ?>" style="width:auto;min-width:200px;">
        <button type="submit" class="btn btn-primary">Filtrele</button>
        <?php if ($kategori_id || $arama): ?>
            <a href="/ilanlar.php" class="btn btn-secondary">✖ Temizle</a>
        <?php endif; ?>
    </form>

    <?php if ($ilanlar): ?>
        <div class="grid">
            <?php foreach ($ilanlar as $ilan): ?>
            <div class="ilan-card card" style="margin-bottom:0;">
                <?php if (admin()): ?>
                <div class="admin-buttons">
                    <a href="/ilan-duzenle.php?id=<?php echo $ilan['id']; ?>" class="btn btn-primary btn-sm">Düzenle</a>
                    <a href="/ilan-sil.php?id=<?php echo $ilan['id']; ?>" class="btn btn-danger btn-sm"
                       onclick="return confirm('Silinsin mi?')">Sil</a>
                </div>
                <?php endif; ?>

                <?php if (!empty($ilan['resim']) && file_exists($ilan['resim'])): ?>
                    <img src="/<?php echo guvenlik($ilan['resim']); ?>" class="ilan-resim" alt="">
                <?php else: ?>
                    <div class="ilan-resim-yok">📷 Resim Yok</div>
                <?php endif; ?>

                <h3 style="color:#D4AF37;margin-bottom:8px;font-size:16px;"><?php echo guvenlik($ilan['baslik']); ?></h3>
                <p style="font-size:13px;margin-bottom:10px;"><?php echo guvenlik(mb_substr($ilan['aciklama'], 0, 120)); ?>...</p>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <strong style="color:#1B365D;font-size:18px;"><?php echo para($ilan['fiyat']); ?></strong>
                    <span class="badge badge-aktif"><?php echo guvenlik($ilan['kategori_ad'] ?? 'Genel'); ?></span>
                </div>
                <p style="margin-top:8px;font-size:12px;color:#999;">👤 <?php echo guvenlik($ilan['uye_ad'] ?? 'Bilinmiyor'); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-center" style="color:#aaa;padding:40px;">İlan bulunamadı.</p>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
