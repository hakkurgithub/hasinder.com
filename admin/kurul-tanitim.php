<?php
require_once '../../config.php';
yetkiKontrol();

// Admin tüm kurulları görebilir, başkan sadece kendi kurulunu
$isAdmin = admin();
$uyeId = $_SESSION['user_id'] ?? 0;

if ($isAdmin) {
    $kurullar = $db->query("SELECT * FROM icra_kurullari ORDER BY kurul_ad")->fetchAll();
} else {
    // Başkan hangi kurula atanmış?
    $stmt = $db->prepare("SELECT * FROM icra_kurullari WHERE baskan_uye_id=?");
    $stmt->execute([$uyeId]);
    $kurullar = $stmt->fetchAll();
}

if (!$kurullar) {
    die("Düzenleyebileceğiniz kurul bulunmamaktadır.");
}

// ID seçimi (admin için)
$kurulId = isset($_GET['id']) ? intval($_GET['id']) : ($kurullar[0]['id'] ?? 0);

// Yetki kontrolü (Başkan sadece kendi kurulunu düzenleyebilir)
if (!$isAdmin) {
    $kendiKurulu = array_filter($kurullar, fn($k) => $k['id'] == $kurulId);
    if (!$kendiKurulu) die("Bu kurulu düzenleme yetkiniz yok.");
}

$hatalar = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kurulAd = trim($_POST['kurul_ad'] ?? '');
    $slogan = trim($_POST['slogan'] ?? '');
    $seoUrl = trim($_POST['seo_url'] ?? '');
    $tanitimMetin = trim($_POST['tanitim_metin'] ?? '');
    $hizmetAlani = trim($_POST['hizmet_alani'] ?? '');
    $iletisimAdres = trim($_POST['iletisim_adres'] ?? '');
    $iletisimTelefon = trim($_POST['iletisim_telefon'] ?? '');
    $iletisimEmail = trim($_POST['iletisim_email'] ?? '');
    
    // SEO URL benzersizlik kontrolü
    $kontrol = $db->prepare("SELECT id FROM icra_kurullari WHERE seo_url=? AND id!=?");
    $kontrol->execute([$seoUrl, $kurulId]);
    if ($kontrol->fetch()) $seoUrl .= '-' . time();
    
    // Banner yükleme
    $banner = $_POST['mevcut_banner'] ?? '';
    if (!empty($_FILES['banner']['name'])) {
        $uzanti = strtolower(pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION));
        if (in_array($uzanti, ['jpg','jpeg','png','webp'])) {
            $klasor = __DIR__ . '/../../uploads/kurullar/';
            if (!is_dir($klasor)) mkdir($klasor, 0755, true);
            $yeniIsim = 'kurul-' . $kurulId . '-' . time() . '.' . $uzanti;
            if (move_uploaded_file($_FILES['banner']['tmp_name'], $klasor . $yeniIsim)) {
                if ($banner && file_exists('../../' . $banner)) unlink('../../' . $banner);
                $banner = 'uploads/kurullar/' . $yeniIsim;
            }
        }
    }
    
    // Galeri yükleme
    $galeri = json_decode($_POST['mevcut_galeri'] ?? '[]', true);
    if (!empty($_FILES['galeri']['name'][0])) {
        foreach ($_FILES['galeri']['tmp_name'] as $key => $tmpName) {
            if ($_FILES['galeri']['error'][$key] === 0) {
                $uzanti = strtolower(pathinfo($_FILES['galeri']['name'][$key], PATHINFO_EXTENSION));
                if (in_array($uzanti, ['jpg','jpeg','png','webp'])) {
                    $yeniIsim = 'galeri-' . $kurulId . '-' . time() . '-' . $key . '.' . $uzanti;
                    if (move_uploaded_file($tmpName, $klasor . $yeniIsim)) {
                        $galeri[] = 'uploads/kurullar/' . $yeniIsim;
                    }
                }
            }
        }
    }
    
    $db->prepare("UPDATE icra_kurullari SET 
        kurul_ad=?, seo_url=?, slogan=?, tanitim_metin=?, hizmet_alani=?,
        iletisim_adres=?, iletisim_telefon=?, iletisim_email=?,
        gorsel_banner=?, galeri=?
        WHERE id=?")
        ->execute([
            $kurulAd, $seoUrl, $slogan, $tanitimMetin, $hizmetAlani,
            $iletisimAdres, $iletisimTelefon, $iletisimEmail,
            $banner, json_encode($galeri), $kurulId
        ]);
    
    bildirim('Kurul bilgileri güncellendi.', 'success');
    header("Location: kurul-tanitim.php?id=$kurulId"); exit;
}

// Kurul bilgilerini çek
$stmt = $db->prepare("SELECT * FROM icra_kurullari WHERE id=?");
$stmt->execute([$kurulId]);
$kurul = $stmt->fetch();

if (!$kurul) die("Kurul bulunamadı.");

$galeri = json_decode($kurul['galeri'] ?? '[]', true);

include '../header.php';
?>

<div class="card" style="max-width:1000px;margin:0 auto;">
    <div class="card-header" style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <h2 class="card-title">🏛️ Kurul Tanıtım Sayfası Düzenle</h2>
        <div style="display:flex;gap:8px;">
            <?php if (count($kurullar) > 1): ?>
            <form method="GET" style="display:flex;gap:8px;">
                <select name="id" class="form-control" onchange="this.form.submit()">
                    <?php foreach ($kurullar as $k): ?>
                        <option value="<?php echo $k['id']; ?>" <?php echo $k['id']==$kurulId?'selected':''; ?>>
                            <?php echo guvenlik($k['kurul_ad']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <?php endif; ?>
            <a href="/kurul/<?php echo $kurul['seo_url']; ?>" target="_blank" class="btn btn-info">👁️ Sayfayı Gör</a>
        </div>
    </div>
    
    <?php echo bildirimGoster(); ?>
    
    <form method="POST" enctype="multipart/form-data">
        <div style="display:grid;grid-template-columns:2fr 1fr;gap:18px;">
            <div class="form-group">
                <label>Kurul Adı</label>
                <input type="text" name="kurul_ad" class="form-control" value="<?php echo guvenlik($kurul['kurul_ad']); ?>" required>
            </div>
            <div class="form-group">
                <label>SEO URL (Otomatik oluşur)</label>
                <input type="text" name="seo_url" class="form-control" value="<?php echo guvenlik($kurul['seo_url']); ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label>Slogan / Alt Başlık</label>
            <input type="text" name="slogan" class="form-control" value="<?php echo guvenlik($kurul['slogan'] ?? ''); ?>" placeholder="Örn: Güvenilir Hizmet, Güçlü İşbirliği">
        </div>
        
        <div class="form-group">
            <label>Banner Görseli (Üst kapak fotoğrafı)</label>
            <input type="file" name="banner" class="form-control" accept="image/*">
            <?php if ($kurul['gorsel_banner']): ?>
                <div style="margin-top:10px;">
                    <img src="/<?php echo $kurul['gorsel_banner']; ?>" style="max-width:300px;border-radius:4px;">
                    <input type="hidden" name="mevcut_banner" value="<?php echo $kurul['gorsel_banner']; ?>">
                </div>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label>Hakkımızda / Tanıtım Metni</label>
            <textarea name="tanitim_metin" class="form-control" rows="6" placeholder="Kurulunuzun tanıtımı, misyonu, vizyonu..."><?php echo guvenlik($kurul['tanitim_metin'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label>Hizmetlerimiz (Çalışma Alanları)</label>
            <textarea name="hizmet_alani" class="form-control" rows="4" placeholder="Her satıra bir hizmet yazın"><?php echo guvenlik($kurul['hizmet_alani'] ?? ''); ?></textarea>
            <small>Hizmetleri alt alta yazın (her satır bir hizmet)</small>
        </div>
        
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:18px;">
            <div class="form-group">
                <label>İletişim Adres</label>
                <textarea name="iletisim_adres" class="form-control" rows="3"><?php echo guvenlik($kurul['iletisim_adres'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label>Telefon</label>
                <input type="text" name="iletisim_telefon" class="form-control" value="<?php echo guvenlik($kurul['iletisim_telefon'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>E-posta</label>
                <input type="email" name="iletisim_email" class="form-control" value="<?php echo guvenlik($kurul['iletisim_email'] ?? ''); ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label>Galeri (Çoklu fotoğraf yükleme)</label>
            <input type="file" name="galeri[]" class="form-control" accept="image/*" multiple>
            <input type="hidden" name="mevcut_galeri" value='<?php echo json_encode($galeri); ?>'>
            
            <?php if ($galeri): ?>
                <div style="display:flex;gap:10px;margin-top:15px;flex-wrap:wrap;">
                    <?php foreach ($galeri as $g): ?>
                        <div style="position:relative;">
                            <img src="/<?php echo $g; ?>" style="width:100px;height:70px;object-fit:cover;border-radius:4px;">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <button type="submit" class="btn btn-primary" style="margin-top:20px;">💾 Değişiklikleri Kaydet</button>
    </form>
</div>

<?php include '../footer.php'; ?>