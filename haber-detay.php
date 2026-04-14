<?php 
require_once '../config.php'; 
include 'header.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) { header('Location: /haberler.php'); exit; }

$stmt = $db->prepare("SELECT * FROM haberler WHERE slug=? AND durum='AKTIF'");
$stmt->execute([$slug]);
$haber = $stmt->fetch();

if (!$haber) { header('Location: /haberler.php'); exit; }
?>

<div style="max-width:800px;margin:40px auto;padding:0 20px;">
    <div style="margin-bottom:20px;">
        <a href="/haberler.php" style="color:#1B365D;text-decoration:none;">← Tüm Haberler</a>
    </div>
    
    <div class="card" style="padding:40px;">
        <?php if (!empty($haber['gorsel'])): ?>
            <img src="/<?php echo $haber['gorsel']; ?>" style="width:100%;max-height:400px;object-fit:cover;border-radius:8px;margin-bottom:30px;">
        <?php endif; ?>
        
        <div style="color:#888;margin-bottom:15px;font-size:14px;">
            📅 <?php echo date('d.m.Y', strtotime($haber['yayin_tarihi'])); ?>
        </div>
        
        <h1 style="color:#1B365D;font-size:32px;margin-bottom:20px;"><?php echo htmlspecialchars($haber['baslik'], ENT_QUOTES, 'UTF-8'); ?></h1>
        
        <div style="font-size:16px;color:#444;line-height:1.8;">
            <?php echo nl2br(htmlspecialchars($haber['icerik'], ENT_QUOTES, 'UTF-8')); ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>