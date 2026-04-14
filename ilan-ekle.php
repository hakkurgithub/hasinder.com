<?php
require_once '../config.php';

if (!admin()) {
    header('Location: ../giris.php');
    exit;
}

// Durum güncelleme
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if ($action === 'onayla') {
        $db->prepare("UPDATE ilanlar SET durum = 'AKTIF' WHERE id = ?")->execute([$id]);
        
        // Üyeye bildirim
        $ilan = $db->prepare("SELECT i.*, u.email, u.telefon, u.ad as uye_ad 
                              FROM ilanlar i 
                              JOIN uyeler u ON i.uye_id = u.id 
                              WHERE i.id = ?");
        $ilan->execute([$id]);
        $ilan_data = $ilan->fetch();
        
        if ($ilan_data) {
            $mesaj = "Sayın {$ilan_data['uye_ad']},\n\n"
                   . "'{$ilan_data['baslik']}' ilanınız onaylandı ve yayına alındı.\n"
                   . "İlan: " . site_url('ilan.php?id=' . $id);
            mailGonder($ilan_data['email'], 'İlanınız Onaylandı', $mesaj);
        }
        
        bildirim('İlan onaylandı.', 'success');
    } elseif ($action === 'reddet') {
        $db->prepare("UPDATE ilanlar SET durum = 'PASIF' WHERE id = ?")->execute([$id]);
        bildirim('İlan reddedildi.', 'warning');
    }
    
    header('Location: ilanlar.php');
    exit;
}

// Filtreleme
$durum_filtre = isset($_GET['durum']) ? guvenlik($_GET['durum']) : '';
$where = $durum_filtre ? "WHERE i.durum = '$durum_filtre'" : "";

$ilanlar = $db->query("
    SELECT i.*, k.ad as kategori_adi, u.ad as uye_adi, u.telefon as uye_telefon,
           (SELECT COUNT(*) FROM ilan_resimler WHERE ilan_id = i.id) as resim_sayisi
    FROM ilanlar i
    LEFT JOIN kategoriler k ON i.kategori_id = k.id
    LEFT JOIN uyeler u ON i.uye_id = u.id
    $where
    ORDER BY i.created_at DESC
")->fetchAll();

include '../header.php';
?>

<div class="container">
    <h1>🏢 Admin Panel - İlan Yönetimi</h1>
    
    <!-- İstatistikler -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px;">
        <?php
        $stats = $db->query("SELECT 
            SUM(durum = 'BEKLEMEDE') as beklemede,
            SUM(durum = 'AKTIF') as aktif,
            SUM(durum = 'PASIF') as pasif,
            COUNT(*) as toplam
        FROM ilanlar")->fetch();
        ?>
        <div style="background: #fff3cd; padding: 15px; border-radius: 8px; text-align: center;">
            <div style="font-size: 24px; font-weight: bold; color: #856404;"><?php echo $stats['beklemede']; ?></div>
            <div>Beklemede</div>
        </div>
        <div style="background: #d4edda; padding: 15px; border-radius: 8px; text-align: center;">
            <div style="font-size: 24px; font-weight: bold; color: #155724;"><?php echo $stats['aktif']; ?></div>
            <div>Aktif</div>
        </div>
        <div style="background: #f8d7da; padding: 15px; border-radius: 8px; text-align: center;">
            <div style="font-size: 24px; font-weight: bold; color: #721c24;"><?php echo $stats['pasif']; ?></div>
            <div>Pasif</div>
        </div>
        <div style="background: #e2e3e5; padding: 15px; border-radius: 8px; text-align: center;">
            <div style="font-size: 24px; font-weight: bold; color: #383d41;"><?php echo $stats['toplam']; ?></div>
            <div>Toplam</div>
        </div>
    </div>
    
    <!-- Filtreler -->
    <div style="margin-bottom: 20px;">
        <a href="ilanlar.php" class="btn <?php echo !$durum_filtre ? 'btn-primary' : 'btn-outline'; ?>">Tümü</a>
        <a href="?durum=BEKLEMEDE" class="btn <?php echo $durum_filtre == 'BEKLEMEDE' ? 'btn-primary' : 'btn-outline'; ?>">Beklemede</a>
        <a href="?durum=AKTIF" class="btn <?php echo $durum_filtre == 'AKTIF' ? 'btn-primary' : 'btn-outline'; ?>">Aktif</a>
        <a href="?durum=PASIF" class="btn <?php echo $durum_filtre == 'PASIF' ? 'btn-primary' : 'btn-outline'; ?>">Pasif</a>
    </div>
    
    <!-- İlan Listesi -->
    <div style="overflow-x: auto;">
        <table style="width: 100%; background: white; border-radius: 8px; overflow: hidden; border-collapse: collapse;">
            <thead style="background: #343a40; color: white;">
                <tr>
                    <th style="padding: 12px;">ID</th>
                    <th style="padding: 12px;">Görsel</th>
                    <th style="padding: 12px;">Başlık / Konum</th>
                    <th style="padding: 12px;">Fiyat / Alan</th>
                    <th style="padding: 12px;">Ekleyen</th>
                    <th style="padding: 12px;">Durum</th>
                    <th style="padding: 12px;">Tarih</th>
                    <th style="padding: 12px;">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ilanlar as $ilan): 
                    $ozellikler = json_decode($ilan['ozellikler'] ?? '[]', true);
                    $ozellik_text = '';
                    if (in_array('baraj-manzarali', $ozellikler)) $ozellik_text .= '🌊 ';
                    if (in_array('resmi-yol-cephe', $ozellikler)) $ozellik_text .= '🛣️ ';
                ?>
                <tr style="border-bottom: 1px solid #dee2e6;">
                    <td style="padding: 12px;"><?php echo $ilan['id']; ?></td>
                    <td style="padding: 12px;">
                        <?php if ($ilan['resim']): ?>
                            <img src="../<?php echo $ilan['resim']; ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                        <?php else: ?>
                            <div style="width: 60px; height: 60px; background: #e9ecef; display: flex; align-items: center; justify-content: center; border-radius: 4px;">📷</div>
                        <?php endif; ?>
                        <?php if ($ilan['resim_sayisi'] > 0): ?>
                            <small style="display: block; text-align: center;">+<?php echo $ilan['resim_sayisi']; ?></small>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 12px;">
                        <strong><?php echo guvenlik($ilan['baslik']); ?></strong>
                        <div style="font-size: 12px; color: #666; margin-top: 4px;">
                            <?php echo $ozellik_text; ?>
                            <?php echo guvenlik($ilan['il'] . '/' . $ilan['ilce'] . '/' . $ilan['mahalle']); ?><br>
                            <small>Ada: <?php echo guvenlik($ilan['ada_no']); ?> | Parsel: <?php echo guvenlik($ilan['parsel_no']); ?></small>
                        </div>
                    </td>
                    <td style="padding: 12px;">
                        <strong style="color: #28a745;"><?php echo para($ilan['fiyat']); ?></strong>
                        <div style="font-size: 12px; color: #666;">
                            <?php echo number_format($ilan['alan_m2'], 0, ',', '.'); ?> m²<br>
                            (<?php echo $ilan['alan_donum']; ?> dönüm)
                        </div>
                    </td>
                    <td style="padding: 12px;">
                        <?php echo guvenlik($ilan['uye_adi']); ?><br>
                        <small><?php echo guvenlik($ilan['uye_telefon']); ?></small>
                    </td>
                    <td style="padding: 12px;">
                        <?php 
                        $durum_renk = [
                            'BEKLEMEDE' => ['bg' => '#fff3cd', 'color' => '#856404', 'text' => '⏳ Beklemede'],
                            'AKTIF' => ['bg' => '#d4edda', 'color' => '#155724', 'text' => '✅ Aktif'],
                            'PASIF' => ['bg' => '#f8d7da', 'color' => '#721c24', 'text' => '❌ Pasif']
                        ];
                        $d = $durum_renk[$ilan['durum']];
                        ?>
                        <span style="background: <?php echo $d['bg']; ?>; color: <?php echo $d['color']; ?>; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                            <?php echo $d['text']; ?>
                        </span>
                    </td>
                    <td style="padding: 12px; font-size: 12px;">
                        <?php echo date('d.m.Y H:i', strtotime($ilan['created_at'])); ?>
                    </td>
                    <td style="padding: 12px;">
                        <?php if ($ilan['durum'] == 'BEKLEMEDE'): ?>
                            <a href="?action=onayla&id=<?php echo $ilan['id']; ?>" 
                               class="btn btn-success btn-sm" 
                               onclick="return confirm('Onaylamak istiyor musunuz?')"
                               style="margin-bottom: 5px; display: block;">✓ Onayla</a>
                            <a href="?action=reddet&id=<?php echo $ilan['id']; ?>" 
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Reddetmek istiyor musunuz?')"
                               style="display: block;">✗ Reddet</a>
                        <?php else: ?>
                            <a href="../ilan.php?id=<?php echo $ilan['id']; ?>" target="_blank" class="btn btn-outline btn-sm">Görüntüle</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../footer.php'; ?>