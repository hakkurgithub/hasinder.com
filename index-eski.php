<?php
require_once '../config.php';

$ilanlar = $db->query("
    SELECT i.*, u.ad as uye_ad, k.ad as kategori_ad
    FROM ilanlar i
    LEFT JOIN uyeler u ON i.uye_id = u.id
    LEFT JOIN kategoriler k ON i.kategori_id = k.id
    WHERE i.durum = 'AKTIF'
    ORDER BY i.created_at DESC
    LIMIT 6
")->fetchAll();

$stats = [
    'toplam_ilan' => $db->query("SELECT COUNT(*) FROM ilanlar WHERE durum='AKTIF'")->fetchColumn(),
    'toplam_uye'  => $db->query("SELECT COUNT(*) FROM uyeler WHERE durum='AKTIF'")->fetchColumn(),
    'bugun'       => $db->query("SELECT COUNT(*) FROM ilanlar WHERE DATE(created_at)=CURDATE()")->fetchColumn(),
];

include 'header.php';
?>

<!-- Hero -->
<div style="background:linear-gradient(135deg,#1B365D,#2a4a7f);color:white;padding:50px 0;margin:-40px 0 30px;border-radius:0 0 20px 20px;">
    <div style="text-align:center;">
        <h1 style="color:#D4AF37;font-size:40px;margin-bottom:10px;letter-spacing:2px;">HAS İNSANDER</h1>
        <p style="color:#ccc;font-size:18px;margin-bottom:25px;">İstanbul &amp; Hatay B2B Ticaret &amp; Borsa Platformu</p>
        <div style="display:flex;justify-content:center;gap:15px;flex-wrap:wrap;">
            <a href="/ilanlar.php" class="btn btn-primary" style="font-size:16px;padding:12px 28px;">📦 İlanları Gör</a>
            <a href="/panel.php" class="btn btn-outline" style="font-size:16px;padding:12px 28px;">📈 Borsa Paneli</a>
            <?php if (!yetkili()): ?>
            <a href="/kayit.php" class="btn" style="background:#28a745;color:white;font-size:16px;padding:12px 28px;">🚀 Üye Ol</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- İstatistikler -->
<div class="grid mb-4">
    <div class="card text-center" style="background:#1B365D;color:white;">
        <h3 style="font-size:36px;color:#D4AF37;"><?php echo $stats['toplam_ilan']; ?></h3>
        <p style="color:#aaa;">Aktif İlan</p>
    </div>
    <div class="card text-center" style="background:#28a745;color:white;">
        <h3 style="font-size:36px;"><?php echo $stats['toplam_uye']; ?></h3>
        <p>Aktif Üye</p>
    </div>
    <div class="card text-center" style="background:#D4AF37;color:#1B365D;">
        <h3 style="font-size:36px;"><?php echo $stats['bugun']; ?></h3>
        <p>Bugün Eklenen</p>
    </div>
</div>

<!-- Son İlanlar -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">📦 Son İlanlar</h2>
        <a href="/ilanlar.php" class="btn btn-primary">Tümünü Gör</a>
    </div>

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
                    <img src="/<?php echo guvenlik($ilan['resim']); ?>" class="ilan-resim" alt="<?php echo guvenlik($ilan['baslik']); ?>">
                <?php else: ?>
                    <div class="ilan-resim-yok">📷 Resim Yok</div>
                <?php endif; ?>

                <h3 style="color:#D4AF37;margin-bottom:8px;font-size:16px;"><?php echo guvenlik($ilan['baslik']); ?></h3>
                <p style="font-size:13px;margin-bottom:10px;"><?php echo guvenlik(mb_substr($ilan['aciklama'], 0, 100)); ?>...</p>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <strong style="color:#1B365D;font-size:18px;"><?php echo para($ilan['fiyat']); ?></strong>
                    <span class="badge badge-aktif"><?php echo guvenlik($ilan['kategori_ad'] ?? 'Genel'); ?></span>
                </div>
                <p style="margin-top:8px;font-size:12px;color:#999;">👤 <?php echo guvenlik($ilan['uye_ad'] ?? 'Bilinmiyor'); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-center" style="color:#aaa;padding:40px;">Henüz ilan bulunmuyor.</p>
    <?php endif; ?>
</div>

<!-- İcra Kurulları Tanıtım -->
<div class="card" style="background:linear-gradient(135deg,#1B365D,#2a4a7f);color:white;text-align:center;padding:40px;">
    <h2 style="color:#D4AF37;font-size:28px;margin-bottom:12px;">🏛️ İcra Kurulları</h2>
    <p style="color:#ccc;margin-bottom:20px;font-size:16px;">İş başvurunuzu ilgili icra kuruluna iletin, uzman ekibimiz sizinle iletişime geçsin.</p>
    <a href="/is-basvuru-formu.php" class="btn btn-primary" style="font-size:16px;padding:12px 30px;">📋 Hemen Başvur</a>
</div>

<?php include 'footer.php'; ?>

<!-- Haberler Slider Bölümü -->
<?php
$sonHaberler = $db->query("SELECT * FROM haberler WHERE durum='AKTIF' ORDER BY yayin_tarihi DESC LIMIT 3")->fetchAll();
if ($sonHaberler):
?>
<section style="padding: 60px 20px; background: #f8f9fa;">
    <div style="max-width: 1200px; margin: 0 auto;">
        <h2 style="text-align: center; color: #1B365D; font-size: 32px; margin-bottom: 40px;">📰 Son Haberler & Duyurular</h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
            <?php foreach ($sonHaberler as $haber): ?>
            <article style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: transform 0.3s, box-shadow 0.3s;" onmouseover="this.style.transform='translateY(-5px)';this.style.boxShadow='0 8px 25px rgba(0,0,0,0.15)'" onmouseout="this.style.transform='';this.style.boxShadow='0 4px 6px rgba(0,0,0,0.1)'">
                <?php if ($haber['gorsel']): ?>
                    <div style="height: 200px; overflow: hidden;">
                        <img src="/<?php echo $haber['gorsel']; ?>" alt="<?php echo guvenlik($haber['baslik']); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                    </div>
                <?php endif; ?>
                <div style="padding: 25px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <span style="background: #D4AF37; color: #1B365D; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold;">HABER</span>
                        <time style="color: #888; font-size: 13px;"><?php echo date('d.m.Y', strtotime($haber['yayin_tarihi'])); ?></time>
                    </div>
                    <h3 style="margin: 0 0 12px 0; font-size: 20px; line-height: 1.4;">
                        <a href="/haber/<?php echo $haber['slug']; ?>" style="color: #1B365D; text-decoration: none;">
                            <?php echo guvenlik($haber['baslik']); ?>
                        </a>
                    </h3>
                    <p style="color: #666; line-height: 1.6; margin: 0 0 20px 0;">
                        <?php echo guvenlik(mb_substr($haber['ozet'], 0, 120)); ?>...
                    </p>
                    <a href="/haber/<?php echo $haber['slug']; ?>" style="color: #D4AF37; text-decoration: none; font-weight: bold; display: inline-flex; align-items: center; gap: 5px;">
                        Devamını Oku →
                    </a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        
        <div style="text-align: center; margin-top: 40px;">
            <a href="/haberler.php" class="btn btn-outline" style="border: 2px solid #1B365D; color: #1B365D; padding: 12px 30px; text-decoration: none; border-radius: 6px; display: inline-block;">Tüm Haberler</a>
        </div>
    </div>
</section>
<?php endif; ?>
