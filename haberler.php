<?php 
require_once '../config.php'; 
include 'header.php';

// Aktif haberleri çek (en yeni başta)
$haberler = $db->query("SELECT * FROM haberler WHERE durum='AKTIF' ORDER BY yayin_tarihi DESC")->fetchAll();
?>

<div style="text-align:center;margin-bottom:30px;">
    <h1 style="font-size:32px;color:#1B365D;">HABERLER & <span style="color:#D4AF37;">DUYURULAR</span></h1>
    <div style="width:60px;height:4px;background:#D4AF37;margin:12px auto;border-radius:2px;"></div>
</div>

<?php if (empty($haberler)): ?>
<!-- Henüz haber yoksa statik gösterim (isteğe bağlı) -->
<div class="grid">
    <div class="card">
        <div style="width:100%;height:160px;background:linear-gradient(135deg,#1B365D,#2a4a7f);border-radius:8px;margin-bottom:16px;display:flex;align-items:center;justify-content:center;color:#D4AF37;font-size:40px;">📊</div>
        <div style="font-size:12px;color:#888;margin-bottom:8px;">20.03.2026</div>
        <h3 style="font-size:18px;margin-bottom:10px;">TİB Borsa Sistemi Devreye Girdi</h3>
        <p style="font-size:14px;margin-bottom:14px;">Otonom ticaret borsa ağı ilk işlemlerine başladı. Üyeler artık gerçek zamanlı piyasa verilerini takip edebiliyor.</p>
        <a href="#" class="btn btn-primary btn-sm">Devamını Oku</a>
    </div>
</div>
<?php else: ?>
<div class="grid">
    <?php foreach ($haberler as $h): ?>
    <div class="card">
        <!-- Görsel varsa göster, yoksa varsayılan gradient -->
        <?php if (!empty($h['gorsel']) && file_exists('../' . $h['gorsel'])): ?>
            <div style="width:100%;height:160px;background:url('/<?php echo $h['gorsel']; ?>') center/cover;border-radius:8px;margin-bottom:16px;"></div>
        <?php else: ?>
            <div style="width:100%;height:160px;background:linear-gradient(135deg,#1B365D,#2a4a7f);border-radius:8px;margin-bottom:16px;display:flex;align-items:center;justify-content:center;color:#D4AF37;font-size:40px;">📰</div>
        <?php endif; ?>
        
        <!-- Tarih -->
        <div style="font-size:12px;color:#888;margin-bottom:8px;"><?php echo date('d.m.Y', strtotime($h['yayin_tarihi'])); ?></div>
        
        <!-- Başlık -->
        <h3 style="font-size:18px;margin-bottom:10px;"><?php echo htmlspecialchars($h['baslik'], ENT_QUOTES, 'UTF-8'); ?></h3>
        
        <!-- Özet -->
        <p style="font-size:14px;margin-bottom:14px;"><?php echo htmlspecialchars(mb_substr($h['ozet'], 0, 120), ENT_QUOTES, 'UTF-8'); ?>...</p>
        
        <!-- Buton -->
        <a href="/haber-detay.php?slug=<?php echo $h['slug']; ?>" class="btn btn-primary btn-sm">Devamını Oku</a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include 'footer.php'; ?>