<?php
require_once 'config.php';

$kategori_id = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;
$arama       = isset($_GET['arama'])    ? trim($_GET['arama'])    : '';
$il_filtre   = isset($_GET['il'])       ? guvenlik($_GET['il'])   : '';

$sql    = "SELECT i.*, u.ad as uye_ad, u.telefon as uye_telefon, k.ad as kategori_ad,
                  (SELECT COUNT(*) FROM ilan_resimler WHERE ilan_id = i.id) as ek_resim_sayisi
           FROM ilanlar i
           LEFT JOIN uyeler u ON i.uye_id = u.id
           LEFT JOIN kategoriler k ON i.kategori_id = k.id
           WHERE i.durum = 'AKTIF'";
$params = [];

if ($kategori_id > 0) { 
    $sql .= " AND i.kategori_id = ?"; 
    $params[] = $kategori_id; 
}
if ($arama !== '') { 
    $sql .= " AND (i.baslik LIKE ? OR i.aciklama LIKE ? OR i.il LIKE ? OR i.ilce LIKE ? OR i.mahalle LIKE ?)"; 
    $params[] = "%$arama%"; 
    $params[] = "%$arama%";
    $params[] = "%$arama%";
    $params[] = "%$arama%";
    $params[] = "%$arama%";
}
if ($il_filtre !== '') {
    $sql .= " AND i.il = ?";
    $params[] = $il_filtre;
}

$sql .= " ORDER BY i.created_at DESC";

$stmt = $db->prepare($sql); 
$stmt->execute($params);
$ilanlar = $stmt->fetchAll();

$kategoriler = $db->query("SELECT * FROM kategoriler ORDER BY ad")->fetchAll();

// İlleri çek (filtre için)
$iller = $db->query("SELECT DISTINCT il FROM ilanlar WHERE durum = 'AKTIF' AND il IS NOT NULL ORDER BY il")->fetchAll(PDO::FETCH_COLUMN);

include 'header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">🏞️ Tüm İlanlar (<?php echo count($ilanlar); ?>)</h2>
        <?php if (yetkili()): ?>
            <a href="/ilan-ekle.php" class="btn btn-success">+ Yeni İlan</a>
        <?php endif; ?>
    </div>

    <!-- Filtreler -->
    <form method="GET" style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:20px; align-items: end;">
        <div>
            <label style="font-size: 12px; color: #666; display: block; margin-bottom: 4px;">Kategori</label>
            <select name="kategori" class="form-control" style="width:auto; min-width:150px;">
                <option value="">Tümü</option>
                <?php foreach ($kategoriler as $kat): ?>
                    <option value="<?php echo $kat['id']; ?>" <?php echo $kategori_id == $kat['id'] ? 'selected' : ''; ?>>
                        <?php echo guvenlik($kat['ad']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div>
            <label style="font-size: 12px; color: #666; display: block; margin-bottom: 4px;">İl</label>
            <select name="il" class="form-control" style="width:auto; min-width:130px;">
                <option value="">Tümü</option>
                <?php foreach ($iller as $il): ?>
                    <option value="<?php echo guvenlik($il); ?>" <?php echo $il_filtre == $il ? 'selected' : ''; ?>>
                        <?php echo guvenlik($il); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div style="flex: 1; min-width: 200px;">
            <label style="font-size: 12px; color: #666; display: block; margin-bottom: 4px;">Arama</label>
            <input type="text" name="arama" class="form-control" placeholder="İlan, konum, ilan no ara..." 
                   value="<?php echo guvenlik($arama); ?>">
        </div>
        
        <button type="submit" class="btn btn-primary">🔍 Filtrele</button>
        <?php if ($kategori_id || $arama || $il_filtre): ?>
            <a href="/ilanlar.php" class="btn btn-secondary">✖ Temizle</a>
        <?php endif; ?>
    </form>

    <?php if ($ilanlar): ?>
        <div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
            <?php foreach ($ilanlar as $ilan): 
                $ozellikler = json_decode($ilan['ozellikler'] ?? '[]', true);
                $ozellik_html = '';
                
                // Özellik ikonları
                if (in_array('baraj-manzarali', $ozellikler)) $ozellik_html .= '<span title="Baraj Manzaralı">🌊</span>';
                if (in_array('resmi-yol-cephe', $ozellikler)) $ozellik_html .= '<span title="Resmi Yol Cephe">🛣️</span>';
                if (in_array('koy-yakin', $ozellikler)) $ozellik_html .= '<span title="Köy Yakını">🏘️</span>';
                if (in_array('elektrik', $ozellikler)) $ozellik_html .= '<span title="Elektrik Yakın">⚡</span>';
                if (in_array('su', $ozellikler)) $ozellik_html .= '<span title="Su Kaynağı">💧</span>';
                if (in_array('imar', $ozellikler)) $ozellik_html .= '<span title="İmar Potansiyeli">🏗️</span>';
            ?>
            <div class="ilan-card card" style="margin-bottom: 0; position: relative; overflow: hidden;">
                
                <!-- Admin Butonları -->
                <?php if (admin()): ?>
                <div class="admin-buttons" style="position: absolute; top: 10px; right: 10px; z-index: 10; display: flex; gap: 5px;">
                    <a href="/ilan-duzenle.php?id=<?php echo $ilan['id']; ?>" class="btn btn-primary btn-sm">✏️</a>
                    <a href="/admin/ilanlar.php?sil=<?php echo $ilan['id']; ?>" class="btn btn-danger btn-sm"
                       onclick="return confirm('Silinsin mi?')">🗑️</a>
                </div>
                <?php endif; ?>

                <!-- Resim Alanı -->
                <div style="position: relative; height: 200px; overflow: hidden; background: #f0f0f0;">
                    <?php if (!empty($ilan['resim']) && file_exists($ilan['resim'])): ?>
                        <img src="/<?php echo guvenlik($ilan['resim']); ?>" 
                             style="width: 100%; height: 100%; object-fit: cover;" 
                             alt="<?php echo guvenlik($ilan['baslik']); ?>">
                    <?php else: ?>
                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #999; font-size: 48px;">📷</div>
                    <?php endif; ?>
                    
                    <!-- Fiyat Badge -->
                    <div style="position: absolute; bottom: 10px; left: 10px; background: rgba(27, 54, 93, 0.95); color: #D4AF37; padding: 8px 12px; border-radius: 4px; font-weight: bold; font-size: 16px;">
                        <?php echo para($ilan['fiyat']); ?>
                    </div>
                    
                    <!-- Ek Resim Sayısı -->
                    <?php if ($ilan['ek_resim_sayisi'] > 0): ?>
                        <div style="position: absolute; bottom: 10px; right: 10px; background: rgba(0,0,0,0.7); color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                            📷 +<?php echo $ilan['ek_resim_sayisi']; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- İçerik -->
                <div style="padding: 15px;">
                    <!-- Kategori & Özellikler -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span class="badge badge-aktif" style="font-size: 11px;"><?php echo guvenlik($ilan['kategori_ad'] ?? 'Genel'); ?></span>
                        <?php if ($ozellik_html): ?>
                            <div style="font-size: 16px; display: flex; gap: 3px;"><?php echo $ozellik_html; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Başlık -->
                    <h3 style="color: #1B365D; margin-bottom: 8px; font-size: 16px; line-height: 1.3; height: 42px; overflow: hidden;">
                        <?php echo guvenlik($ilan['baslik']); ?>
                    </h3>
                    
                    <!-- Açıklama -->
                    <p style="font-size: 13px; color: #666; margin-bottom: 12px; line-height: 1.4; height: 54px; overflow: hidden;">
                        <?php echo guvenlik(mb_substr($ilan['aciklama'], 0, 100)); ?>...
                    </p>

                    <!-- Lokasyon Bilgileri -->
                    <?php if ($ilan['il'] || $ilan['alan_m2']): ?>
                    <div style="background: #f8f9fa; padding: 10px; border-radius: 6px; margin-bottom: 12px; font-size: 12px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <?php if ($ilan['il']): ?>
                                <span>📍 <?php echo guvenlik(($ilan['ilce'] ?? '') . '/' . ($ilan['mahalle'] ?? '')); ?></span>
                            <?php endif; ?>
                            <?php if ($ilan['alan_m2']): ?>
                                <span style="color: #28a745; font-weight: 600;">
                                    📐 <?php echo number_format($ilan['alan_m2'], 0, ',', '.'); ?> m²
                                    <?php if ($ilan['alan_donum']): ?>
                                        (<?php echo $ilan['alan_donum']; ?> dönüm)
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php if ($ilan['takip_no']): ?>
                            <div style="color: #888; margin-top: 4px;">
                                Takip No: <?php echo guvenlik($ilan['takip_no']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Alt Bilgi -->
                    <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 10px; border-top: 1px solid #eee;">
                        <span style="font-size: 12px; color: #666;">
                            👤 <?php echo guvenlik($ilan['uye_ad'] ?? 'Bilinmiyor'); ?>
                        </span>
                        <a href="/ilan.php?id=<?php echo $ilan['id']; ?>" class="btn btn-primary btn-sm">Detaylar →</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 60px 20px; color: #666;">
            <div style="font-size: 48px; margin-bottom: 15px;">🔍</div>
            <h3>İlan bulunamadı</h3>
            <p>Filtreleri temizleyin veya yeni bir ilan ekleyin.</p>
            <a href="/ilan-ekle.php" class="btn btn-primary" style="margin-top: 15px;">+ İlan Ver</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>