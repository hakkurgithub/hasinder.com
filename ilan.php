<?php
require_once 'config.php';

// Cache engelleme - her zaman güncel veriyi göster
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: /ilanlar.php');
    exit;
}

try {
    // İlan detayını çek
    $stmt = $db->prepare("
        SELECT i.*, u.ad as uye_ad, u.telefon as uye_telefon, u.email as uye_email, 
               k.ad as kategori_ad
        FROM ilanlar i
        LEFT JOIN uyeler u ON i.uye_id = u.id
        LEFT JOIN kategoriler k ON i.kategori_id = k.id
        WHERE i.id = ? AND i.durum = 'AKTIF'
    ");
    $stmt->execute([$id]);
    $ilan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ilan) {
        // İlan yoksa veya pasifse listeye dön
        header('Location: /ilanlar.php');
        exit;
    }

    // Ek resimleri çek
    $stmt_resimler = $db->prepare("SELECT * FROM ilan_resimler WHERE ilan_id = ? ORDER BY sira ASC, id ASC");
    $stmt_resimler->execute([$id]);
    $ek_resimler = $stmt_resimler->fetchAll(PDO::FETCH_ASSOC);

    // Özellikleri decode et
    $ozellikler = [];
    if (!empty($ilan['ozellikler'])) {
        $decoded = json_decode($ilan['ozellikler'], true);
        if (is_array($decoded)) {
            $ozellikler = $decoded;
        }
    }

} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}

include 'header.php';
?>

<style>
.ilan-detay-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
.galeri-container { display: grid; grid-template-columns: 2fr 1fr; gap: 15px; margin-bottom: 30px; }
.ana-resim { width: 100%; height: 400px; object-fit: cover; border-radius: 8px; cursor: pointer; transition: transform 0.3s; }
.ana-resim:hover { transform: scale(1.02); }
.yan-resimler { display: flex; flex-direction: column; gap: 10px; overflow-y: auto; max-height: 400px; }
.yan-resim { width: 100%; height: 120px; object-fit: cover; border-radius: 6px; cursor: pointer; opacity: 0.8; transition: 0.3s; border: 2px solid transparent; }
.yan-resim:hover { opacity: 1; border-color: #D4AF37; }
.ozellik-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin: 20px 0; }
.ozellik-kutu { background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 20px; border-radius: 8px; border-left: 4px solid #D4AF37; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.fiyat-buyuk { font-size: 36px; color: #1B365D; font-weight: bold; text-align: center; padding: 25px; background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%); border-radius: 10px; margin: 20px 0; border: 2px solid #D4AF37; }
.iletisim-kutu { background: linear-gradient(135deg, #1B365D 0%, #2c4a7c 100%); color: white; padding: 25px; border-radius: 8px; margin-top: 20px; text-align: center; }
.btn-whatsapp { background: #25D366; color: white; padding: 15px 30px; border-radius: 25px; text-decoration: none; display: inline-block; margin-top: 15px; font-weight: bold; font-size: 16px; transition: transform 0.2s; box-shadow: 0 4px 6px rgba(0,0,0,0.2); }
.btn-whatsapp:hover { transform: translateY(-2px); box-shadow: 0 6px 8px rgba(0,0,0,0.3); }
.aciklama-metin { background: white; padding: 25px; border-radius: 8px; border: 1px solid #e0e0e0; line-height: 1.8; font-size: 16px; color: #333; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
.modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); cursor: pointer; }
.modal-content { margin: auto; display: block; max-width: 90%; max-height: 90%; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); border-radius: 8px; }
.close { position: absolute; top: 20px; right: 40px; color: white; font-size: 40px; font-weight: bold; cursor: pointer; z-index: 1001; }
.badge-ozellik { background: white; padding: 8px 16px; border-radius: 20px; border: 2px solid #D4AF37; font-size: 14px; display: inline-flex; align-items: center; gap: 5px; margin: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
@media (max-width: 768px) {
    .galeri-container { grid-template-columns: 1fr; }
    .yan-resimler { flex-direction: row; overflow-x: auto; height: auto; }
    .yan-resim { min-width: 150px; height: 100px; }
    .fiyat-buyuk { font-size: 28px; }
}
</style>

<div class="ilan-detay-container">
    <!-- Geri Butonu -->
    <div style="margin-bottom: 20px;">
        <a href="/ilanlar.php" class="btn btn-secondary">← Tüm İlanlara Dön</a>
        <span style="float: right; color: #666; font-size: 14px;">İlan No: #<?php echo $ilan['id']; ?></span>
    </div>

    <!-- Başlık ve Kategori -->
    <div style="margin-bottom: 25px; border-bottom: 3px solid #D4AF37; padding-bottom: 15px;">
        <span class="badge badge-aktif" style="font-size: 14px; padding: 8px 16px; margin-bottom: 10px; display: inline-block;">
            <?php echo guvenlik($ilan['kategori_ad'] ?? 'Genel'); ?>
        </span>
        <h1 style="color: #1B365D; margin-top: 10px; font-size: 32px; line-height: 1.3; margin: 0;">
            <?php echo guvenlik($ilan['baslik']); ?>
        </h1>
    </div>

    <!-- Resim Galerisi -->
    <div class="galeri-container">
        <!-- Ana Resim -->
        <div style="position: relative;">
            <?php if (!empty($ilan['resim']) && file_exists($ilan['resim'])): ?>
                <img src="/<?php echo guvenlik($ilan['resim']); ?>" 
                     class="ana-resim" 
                     onclick="openModal(this.src)"
                     alt="<?php echo guvenlik($ilan['baslik']); ?>"
                     id="mainImage">
                <div style="position: absolute; bottom: 10px; right: 10px; background: rgba(0,0,0,0.6); color: white; padding: 8px 12px; border-radius: 4px; font-size: 13px;">
                    🔍 Büyütmek için tıklayın
                </div>
            <?php else: ?>
                <div class="ana-resim" style="display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #f0f0f0 0%, #e0e0e0 100%);">
                    <div style="text-align: center;">
                        <span style="font-size: 72px; display: block; margin-bottom: 10px;">📷</span>
                        <span style="color: #999;">Ana resim yok</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Yan Resimler -->
        <div class="yan-resimler">
            <?php if (count($ek_resimler) > 0): ?>
                <?php foreach ($ek_resimler as $index => $resim): ?>
                    <?php if (file_exists($resim['resim_yolu'])): ?>
                        <img src="/<?php echo guvenlik($resim['resim_yolu']); ?>" 
                             class="yan-resim" 
                             onclick="changeMainImage(this.src)"
                             alt="İlan resmi <?php echo $index + 1; ?>"
                             title="Tıklayın">
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="height: 100%; display: flex; align-items: center; justify-content: center; color: #999; background: #f5f5f5; border-radius: 6px;">
                    <span style="font-size: 14px;">Ek resim yok</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Fiyat -->
    <div class="fiyat-buyuk">
        <?php echo para($ilan['fiyat']); ?>
        <?php if ($ilan['fiyat'] > 0 && $ilan['alan_m2'] > 0): ?>
            <div style="font-size: 16px; color: #666; margin-top: 8px; font-weight: normal;">
                Birim Fiyat: <?php echo number_format($ilan['fiyat'] / $ilan['alan_m2'], 2, ',', '.'); ?> TL/m²
            </div>
        <?php endif; ?>
    </div>

    <!-- Lokasyon ve Temel Bilgiler -->
    <div class="ozellik-grid">
        <?php if ($ilan['il']): ?>
        <div class="ozellik-kutu">
            <strong style="color: #1B365D; display: block; margin-bottom: 8px; font-size: 16px;">📍 Konum</strong>
            <span style="font-size: 15px;">
                <?php echo guvenlik($ilan['il']); ?> / 
                <?php echo guvenlik($ilan['ilce'] ?? '-'); ?> / 
                <?php echo guvenlik($ilan['mahalle'] ?? '-'); ?>
            </span>
        </div>
        <?php endif; ?>

        <?php if ($ilan['alan_m2']): ?>
        <div class="ozellik-kutu">
            <strong style="color: #1B365D; display: block; margin-bottom: 8px; font-size: 16px;">📐 Alan</strong>
            <span style="font-size: 18px; font-weight: bold; color: #28a745;">
                <?php echo number_format($ilan['alan_m2'], 0, ',', '.'); ?> m²
            </span>
            <?php if ($ilan['alan_donum']): ?>
                <br><span style="color: #666; font-size: 14px;">(<?php echo $ilan['alan_donum']; ?> dönüm)</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($ilan['takip_no']): ?>
        <div class="ozellik-kutu">
            <strong style="color: #1B365D; display: block; margin-bottom: 8px; font-size: 16px;">🔢 Takip No</strong>
            <span style="font-size: 16px; font-family: monospace; background: #e9ecef; padding: 4px 8px; border-radius: 4px;">
                <?php echo guvenlik($ilan['takip_no']); ?>
            </span>
        </div>
        <?php endif; ?>

        <?php if ($ilan['created_at']): ?>
        <div class="ozellik-kutu">
            <strong style="color: #1B365D; display: block; margin-bottom: 8px; font-size: 16px;">📅 İlan Tarihi</strong>
            <span style="font-size: 15px;">
                <?php echo date('d.m.Y', strtotime($ilan['created_at'])); ?>
            </span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Özellikler -->
    <?php if (!empty($ozellikler)): ?>
    <div style="margin: 30px 0; padding: 25px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 8px;">
        <h3 style="color: #1B365D; margin-bottom: 20px; border-bottom: 2px solid #D4AF37; padding-bottom: 10px; display: inline-block;">✨ Özellikler</h3>
        <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 15px;">
            <?php 
            $ozellik_map = [
                'baraj-manzarali' => ['🌊', 'Baraj Manzaralı'],
                'resmi-yol-cephe' => ['🛣️', 'Resmi Yol Cepheli'],
                'koy-yakin' => ['🏘️', 'Köye Yakın'],
                'elektrik' => ['⚡', 'Elektrik Var'],
                'su' => ['💧', 'Su Kaynağı'],
                'imar' => ['🏗️', 'İmarlı'],
                'yatirimlik' => ['📈', 'Yatırımlık'],
                'tarla' => ['🌾', 'Tarla'],
                'bag' => ['🍇', 'Bağ'],
                'zeytinlik' => ['🫒', 'Zeytinlik'],
                'yol-problemi-yok' => ['🛤️', 'Yol Problemi Yok']
            ];
            foreach ($ozellikler as $oz): 
                $icon = isset($ozellik_map[$oz]) ? $ozellik_map[$oz][0] : '✓';
                $label = isset($ozellik_map[$oz]) ? $ozellik_map[$oz][1] : guvenlik($oz);
            ?>
                <span class="badge-ozellik">
                    <?php echo $icon . ' ' . $label; ?>
                </span>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Açıklama -->
    <div style="margin: 30px 0;">
        <h3 style="color: #1B365D; margin-bottom: 15px; border-bottom: 2px solid #D4AF37; padding-bottom: 10px; display: inline-block;">📝 Açıklama</h3>
        <div class="aciklama-metin">
            <?php echo nl2br(guvenlik($ilan['aciklama'])); ?>
        </div>
    </div>

    <!-- İletişim Bilgileri -->
    <div class="iletisim-kutu">
        <h3 style="margin-bottom: 15px; font-size: 24px;">📞 İletişim Bilgileri</h3>
        <p style="font-size: 18px; margin-bottom: 10px;">
            <strong>👤 İlan Sahibi:</strong> <?php echo guvenlik($ilan['uye_ad'] ?? 'Admin'); ?>
        </p>
        
        <?php if (!empty($ilan['uye_telefon'])): ?>
            <p style="font-size: 20px; margin: 15px 0;">
                <strong>📱 Telefon:</strong> 
                <a href="tel:<?php echo preg_replace('/[^0-9]/', '', $ilan['uye_telefon']); ?>" style="color: #D4AF37; text-decoration: none;">
                    <?php echo guvenlik($ilan['uye_telefon']); ?>
                </a>
            </p>
            
            <?php 
            // WhatsApp mesaj hazırlama
            $wp_text = "Merhaba, hasinder.com'daki \"" . $ilan['baslik'] . "\" ilanınız hakkında bilgi almak istiyorum. İlan No: #" . $ilan['id'];
            $wp_link = "https://wa.me/" . preg_replace('/[^0-9]/', '', $ilan['uye_telefon']) . "?text=" . urlencode($wp_text);
            ?>
            <a href="<?php echo $wp_link; ?>" class="btn-whatsapp" target="_blank" rel="noopener">
                💬 WhatsApp'tan Hemen Yaz
            </a>
        <?php endif; ?>
        
        <?php if (!empty($ilan['uye_email'])): ?>
            <p style="margin-top: 15px; font-size: 14px; opacity: 0.9;">
                <strong>✉️ E-posta:</strong> <?php echo guvenlik($ilan['uye_email']); ?>
            </p>
        <?php endif; ?>
    </div>

    <!-- Admin Düzenle Butonu -->
    <?php if (admin()): ?>
    <div style="margin-top: 30px; padding: 20px; background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px; text-align: center;">
        <p style="margin-bottom: 15px; color: #856404; font-weight: bold;">🔧 Admin Paneli</p>
        <a href="/admin/ilan-duzenle.php?id=<?php echo $ilan['id']; ?>" class="btn btn-primary" style="margin-right: 10px; padding: 10px 20px;">
            ✏️ İlanı Düzenle
        </a>
        <a href="/admin/ilanlar.php?sil=<?php echo $ilan['id']; ?>" 
           class="btn btn-danger" 
           style="padding: 10px 20px;"
           onclick="return confirm('⚠️ BU İLANI SİLMEK İSTEDİĞİNİZE EMİN MİSİNİZ?\n\nBu işlem geri alınamaz!')">
           🗑️ İlanı Sil
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Lightbox Modal -->
<div id="imageModal" class="modal" onclick="closeModal()">
    <span class="close">&times;</span>
    <img class="modal-content" id="modalImg">
</div>

<script>
function openModal(src) {
    document.getElementById('modalImg').src = src;
    document.getElementById('imageModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('imageModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

function changeMainImage(src) {
    document.getElementById('mainImage').src = src;
}

// ESC tuşuyla kapatma
document.addEventListener('keydown', function(event) {
    if (event.key === "Escape") {
        closeModal();
    }
});

// Modal dışına tıklayınca kapatma
document.getElementById('imageModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php include 'footer.php'; ?>