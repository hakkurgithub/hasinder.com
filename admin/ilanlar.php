<?php
require_once '../../config.php';

if (!admin()) { 
    header('Location: /giris.php'); 
    exit; 
}

// Durum güncelleme (Onay/Red)
if (isset($_GET['onay']) && isset($_GET['id'])) {
    $durum = $_GET['onay'] == '1' ? 'AKTIF' : 'PASIF';
    $ilan_id = intval($_GET['id']);
    
    $db->prepare("UPDATE ilanlar SET durum=? WHERE id=?")->execute([$durum, $ilan_id]);
    
    // Üyeye bildirim gönder (onaylandıysa)
    if ($durum === 'AKTIF') {
        $ilan = $db->prepare("SELECT i.*, u.email, u.ad as uye_ad 
                              FROM ilanlar i 
                              JOIN uyeler u ON i.uye_id = u.id 
                              WHERE i.id = ?");
        $ilan->execute([$ilan_id]);
        $ilan_data = $ilan->fetch();
        
        if ($ilan_data) {
            $mesaj = "Sayın {$ilan_data['uye_ad']},\n\n"
                   . "'{$ilan_data['baslik']}' ilanınız admin tarafından onaylandı ve yayına alındı.\n"
                   . "İlan: " . site_url('ilan.php?id=' . $ilan_id);
            mailGonder($ilan_data['email'], 'İlanınız Onaylandı - Hasinder', $mesaj);
        }
    }
    
    bildirim('İlan durumu güncellendi.', 'success');
    header('Location: ilanlar.php'); 
    exit;
}

// Silme işlemi (Çoklu resim desteği ile)
if (isset($_GET['sil'])) {
    $ilan_id = intval($_GET['sil']);
    
    // Ana resmi sil
    $stmt = $db->prepare("SELECT resim FROM ilanlar WHERE id=?");
    $stmt->execute([$ilan_id]);
    $resim = $stmt->fetchColumn();
    if ($resim && file_exists(__DIR__ . '/../../' . $resim)) {
        unlink(__DIR__ . '/../../' . $resim);
    }
    
    // Ek resimleri sil
    $ek_resimler = $db->prepare("SELECT resim_yolu FROM ilan_resimler WHERE ilan_id=?");
    $ek_resimler->execute([$ilan_id]);
    foreach ($ek_resimler->fetchAll(PDO::FETCH_COLUMN) as $ek_resim) {
        if (file_exists(__DIR__ . '/../../' . $ek_resim)) {
            unlink(__DIR__ . '/../../' . $ek_resim);
        }
    }
    
    // Veritabanından sil (cascade ile ilan_resimler de silinir)
    $db->prepare("DELETE FROM ilanlar WHERE id=?")->execute([$ilan_id]);
    
    bildirim('İlan ve tüm resimleri silindi.', 'success');
    header('Location: ilanlar.php'); 
    exit;
}

// Filtreleme parametreleri
$durum_filtre = isset($_GET['durum']) ? guvenlik($_GET['durum']) : '';
$konum_filtre = isset($_GET['konum']) ? guvenlik($_GET['konum']) : '';

$where = [];
$params = [];

if ($durum_filtre) {
    $where[] = "i.durum = ?";
    $params[] = $durum_filtre;
}
if ($konum_filtre) {
    $where[] = "(i.il LIKE ? OR i.ilce LIKE ? OR i.mahalle LIKE ?)";
    $params[] = "%$konum_filtre%";
    $params[] = "%$konum_filtre%";
    $params[] = "%$konum_filtre%";
}

$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// İlanları çek (yeni alanlarla)
$sql = "SELECT i.*, u.ad as uye_ad, u.telefon as uye_telefon, k.ad as kategori_ad,
               (SELECT COUNT(*) FROM ilan_resimler WHERE ilan_id = i.id) as ek_resim_sayisi
        FROM ilanlar i
        LEFT JOIN uyeler u ON i.uye_id = u.id
        LEFT JOIN kategoriler k ON i.kategori_id = k.id
        $where_sql
        ORDER BY i.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$ilanlar = $stmt->fetchAll();

// İstatistikler
$stats = $db->query("SELECT 
    SUM(durum = 'BEKLEMEDE') as beklemede,
    SUM(durum = 'AKTIF') as aktif,
    SUM(durum = 'PASIF') as pasif,
    COUNT(*) as toplam
FROM ilanlar")->fetch();

include '../header.php';
?>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <h2 class="card-title">🏞️ İlan Yönetimi (<?php echo count($ilanlar); ?>)</h2>
        <div style="display:flex; gap:8px;">
            <a href="/ilan-ekle.php" class="btn btn-success btn-sm">+ Yeni İlan</a>
            <a href="/admin/index.php" class="btn btn-secondary btn-sm">← Dashboard</a>
        </div>
    </div>
    
    <?php echo bildirimGoster(); ?>
    
    <!-- İstatistikler -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; padding: 15px; background: #f8f9fa; margin-bottom: 15px;">
        <div style="background: #fff3cd; padding: 10px; border-radius: 6px; text-align: center;">
            <div style="font-size: 20px; font-weight: bold; color: #856404;"><?php echo $stats['beklemede']; ?></div>
            <small>Beklemede</small>
        </div>
        <div style="background: #d4edda; padding: 10px; border-radius: 6px; text-align: center;">
            <div style="font-size: 20px; font-weight: bold; color: #155724;"><?php echo $stats['aktif']; ?></div>
            <small>Aktif</small>
        </div>
        <div style="background: #f8d7da; padding: 10px; border-radius: 6px; text-align: center;">
            <div style="font-size: 20px; font-weight: bold; color: #721c24;"><?php echo $stats['pasif']; ?></div>
            <small>Pasif</small>
        </div>
        <div style="background: #e2e3e5; padding: 10px; border-radius: 6px; text-align: center;">
            <div style="font-size: 20px; font-weight: bold; color: #383d41;"><?php echo $stats['toplam']; ?></div>
            <small>Toplam</small>
        </div>
    </div>
    
    <!-- Filtreler -->
    <div style="padding: 15px; background: #f8f9fa; margin-bottom: 15px; display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="ilanlar.php" class="btn <?php echo !$durum_filtre ? 'btn-primary' : 'btn-outline'; ?> btn-sm">Tümü</a>
        <a href="?durum=BEKLEMEDE" class="btn <?php echo $durum_filtre == 'BEKLEMEDE' ? 'btn-primary' : 'btn-outline'; ?> btn-sm">Beklemede</a>
        <a href="?durum=AKTIF" class="btn <?php echo $durum_filtre == 'AKTIF' ? 'btn-primary' : 'btn-outline'; ?> btn-sm">Aktif</a>
        <a href="?durum=PASIF" class="btn <?php echo $durum_filtre == 'PASIF' ? 'btn-primary' : 'btn-outline'; ?> btn-sm">Pasif</a>
        
        <form method="GET" style="display: flex; gap: 5px; margin-left: auto;">
            <input type="text" name="konum" placeholder="İl/İlçe/Mahalle ara..." 
                   value="<?php echo $konum_filtre; ?>" 
                   style="padding: 5px 10px; border: 1px solid #ddd; border-radius: 4px;">
            <button type="submit" class="btn btn-secondary btn-sm">Ara</button>
        </form>
    </div>
    
    <div style="overflow-x:auto;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #343a40; color: white;">
                <th style="padding: 12px;">Görsel</th>
                <th style="padding: 12px;">Başlık / Konum</th>
                <th style="padding: 12px;">Fiyat / Alan</th>
                <th style="padding: 12px;">Sahibi</th>
                <th style="padding: 12px;">Durum</th>
                <th style="padding: 12px;">Tarih</th>
                <th style="padding: 12px;">İşlem</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($ilanlar as $ilan): 
            $ozellikler = json_decode($ilan['ozellikler'] ?? '[]', true);
            $ozellik_icons = '';
            if (in_array('baraj-manzarali', $ozellikler)) $ozellik_icons .= '🌊';
            if (in_array('resmi-yol-cephe', $ozellikler)) $ozellik_icons .= '🛣️';
            if (in_array('koy-yakin', $ozellikler)) $ozellik_icons .= '🏘️';
        ?>
        <tr style="border-bottom: 1px solid #dee2e6;">
            <td style="padding: 12px; text-align: center;">
                <?php if ($ilan['resim']): ?>
                    <img src="/<?php echo $ilan['resim']; ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                <?php else: ?>
                    <div style="width: 60px; height: 60px; background: #e9ecef; display: flex; align-items: center; justify-content: center; border-radius: 4px; font-size: 24px;">📷</div>
                <?php endif; ?>
                <?php if ($ilan['ek_resim_sayisi'] > 0): ?>
                    <small style="display: block; margin-top: 2px; color: #666;">+<?php echo $ilan['ek_resim_sayisi']; ?> foto</small>
                <?php endif; ?>
            </td>
            <td style="padding: 12px;">
                <strong><?php echo guvenlik($ilan['baslik']); ?></strong>
                <?php if ($ozellik_icons): ?>
                    <span style="margin-left: 5px;"><?php echo $ozellik_icons; ?></span>
                <?php endif; ?>
                <div style="font-size: 12px; color: #666; margin-top: 4px;">
                    <?php echo guvenlik(($ilan['il'] ?? '-') . '/' . ($ilan['ilce'] ?? '-') . '/' . ($ilan['mahalle'] ?? '-')); ?>
                </div>
                <?php if ($ilan['ada_no'] || $ilan['parsel_no']): ?>
                    <div style="font-size: 11px; color: #888; margin-top: 2px;">
                        Ada: <?php echo guvenlik($ilan['ada_no'] ?? '-'); ?> | 
                        Parsel: <?php echo guvenlik($ilan['parsel_no'] ?? '-'); ?>
                        <?php if ($ilan['takip_no']): ?>
                            | Takip: <?php echo guvenlik($ilan['takip_no']); ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </td>
            <td style="padding: 12px;">
                <strong style="color: #28a745; font-size: 14px;"><?php echo para($ilan['fiyat']); ?></strong>
                <?php if ($ilan['alan_m2']): ?>
                    <div style="font-size: 12px; color: #666; margin-top: 4px;">
                        <?php echo number_format($ilan['alan_m2'], 0, ',', '.'); ?> m²
                        <?php if ($ilan['alan_donum']): ?>
                            <br>(<?php echo $ilan['alan_donum']; ?> dönüm)
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </td>
            <td style="padding: 12px; font-size: 13px;">
                <?php echo guvenlik($ilan['uye_ad'] ?? '-'); ?>
                <?php if ($ilan['uye_telefon']): ?>
                    <br><small style="color: #666;"><?php echo guvenlik($ilan['uye_telefon']); ?></small>
                <?php endif; ?>
            </td>
            <td style="padding: 12px;">
                <?php 
                $durum_stil = [
                    'BEKLEMEDE' => ['bg' => '#fff3cd', 'color' => '#856404', 'text' => '⏳ Beklemede'],
                    'AKTIF' => ['bg' => '#d4edda', 'color' => '#155724', 'text' => '✅ Aktif'],
                    'PASIF' => ['bg' => '#f8d7da', 'color' => '#721c24', 'text' => '❌ Pasif']
                ];
                $d = $durum_stil[$ilan['durum']];
                ?>
                <span style="background: <?php echo $d['bg']; ?>; color: <?php echo $d['color']; ?>; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">
                    <?php echo $d['text']; ?>
                </span>
            </td>
            <td style="padding: 12px; font-size: 12px; color: #666;">
                <?php echo date('d.m.Y', strtotime($ilan['created_at'])); ?>
                <br><small><?php echo date('H:i', strtotime($ilan['created_at'])); ?></small>
            </td>
            <td style="padding: 12px;">
                <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                    <?php if ($ilan['durum'] === 'BEKLEMEDE'): ?>
                        <a href="?onay=1&id=<?php echo $ilan['id']; ?>" class="btn btn-success btn-sm" onclick="return confirm('Onaylamak istiyor musunuz?')">✓ Onayla</a>
                    <?php elseif ($ilan['durum'] === 'AKTIF'): ?>
                        <a href="?onay=0&id=<?php echo $ilan['id']; ?>" class="btn btn-secondary btn-sm">Pasifleştir</a>
                    <?php else: ?>
                        <a href="?onay=1&id=<?php echo $ilan['id']; ?>" class="btn btn-success btn-sm">Aktifleştir</a>
                    <?php endif; ?>
                    
                    <a href="/ilan.php?id=<?php echo $ilan['id']; ?>" target="_blank" class="btn btn-info btn-sm" title="Görüntüle">👁️</a>
                    
                    <?php if (file_exists(__DIR__ . '/../../ilan-duzenle.php')): ?>
                        <a href="/ilan-duzenle.php?id=<?php echo $ilan['id']; ?>" class="btn btn-warning btn-sm">✏️</a>
                    <?php endif; ?>
                    
                    <a href="?sil=<?php echo $ilan['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('İlan ve tüm resimleri silinecek. Emin misiniz?')">🗑️</a>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    
    <?php if (empty($ilanlar)): ?>
        <div style="text-align: center; padding: 40px; color: #666;">
            <p>Henüz ilan bulunmuyor veya filtreleme sonucu boş.</p>
            <a href="/ilan-ekle.php" class="btn btn-primary">İlk İlanı Ekle</a>
        </div>
    <?php endif; ?>
</div>

<?php include '../footer.php'; ?>