<?php
require_once '../../config.php';
if (!admin()) { header('Location: /giris.php'); exit; }

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$hatalar = [];

function slugOlustur($baslik) {
    $tr = ['ı','İ','ş','Ş','ğ','Ğ','ü','Ü','ö','Ö','ç','Ç',' '];
    $en = ['i','i','s','s','g','g','u','u','o','o','c','c','-'];
    $slug = strtolower(str_replace($tr, $en, $baslik));
    $slug = preg_replace('/[^a-z0-9-]/', '', $slug);
    return $slug;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $baslik = trim($_POST['baslik'] ?? '');
    $ozet = trim($_POST['ozet'] ?? '');
    $icerik = trim($_POST['icerik'] ?? '');
    $durum = $_POST['durum'] ?? 'TASLAK';
    $slug = slugOlustur($baslik);
    
    if (mb_strlen($baslik) < 5) $hatalar[] = 'Başlık en az 5 karakter olmalı.';
    
    // Görsel yükleme
    $gorsel = '';
    if (!empty($_FILES['gorsel']['name'])) {
        $uzanti = strtolower(pathinfo($_FILES['gorsel']['name'], PATHINFO_EXTENSION));
        if (in_array($uzanti, ['jpg','jpeg','png'])) {
            $yeni_isim = $slug . '-' . time() . '.' . $uzanti;
            move_uploaded_file($_FILES['gorsel']['tmp_name'], '../../uploads/' . $yeni_isim);
            $gorsel = 'uploads/' . $yeni_isim;
        }
    }
    
    if (empty($hatalar)) {
        if ($id > 0) {
            $db->prepare("UPDATE haberler SET baslik=?, slug=?, ozet=?, icerik=?, durum=? WHERE id=?")
               ->execute([$baslik, $slug, $ozet, $icerik, $durum, $id]);
        } else {
            $db->prepare("INSERT INTO haberler (baslik, slug, ozet, icerik, durum, gorsel, yayin_tarihi) VALUES (?,?,?,?,?,?,NOW())")
               ->execute([$baslik, $slug, $ozet, $icerik, $durum, $gorsel]);
        }
        header('Location: haberler.php'); exit;
    }
}

$haber = ['baslik'=>'','ozet'=>'','icerik'=>'','durum'=>'TASLAK'];
if ($id > 0) {
    $stmt = $db->prepare("SELECT * FROM haberler WHERE id=?");
    $stmt->execute([$id]);
    $haber = $stmt->fetch() ?: $haber;
}

include '../header.php';
?>

<div class="card" style="max-width:800px;margin:0 auto;">
    <div class="card-header">
        <h2 class="card-title"><?php echo $id ? '✏️ Haber Düzenle' : '➕ Yeni Haber'; ?></h2>
        <a href="haberler.php" class="btn btn-secondary btn-sm">← Listeye Dön</a>
    </div>

    <?php if ($hatalar): ?>
        <div style="background:#f8d7da;color:#721c24;padding:14px;border-radius:6px;margin-bottom:20px;">
            <?php foreach ($hatalar as $h): echo "• $h<br>"; endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Haber Başlığı *</label>
            <input type="text" name="baslik" class="form-control" value="<?php echo guvenlik($haber['baslik']); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Kısa Özet</label>
            <textarea name="ozet" class="form-control" rows="3"><?php echo guvenlik($haber['ozet']); ?></textarea>
        </div>
        
        <div class="form-group">
            <label>Kapak Görseli</label>
            <input type="file" name="gorsel" class="form-control" accept="image/*">
        </div>
        
        <div class="form-group">
            <label>Durum</label>
            <select name="durum" class="form-control">
                <option value="TASLAK" <?php echo $haber['durum']=='TASLAK'?'selected':''; ?>>Taslak</option>
                <option value="AKTIF" <?php echo $haber['durum']=='AKTIF'?'selected':''; ?>>Yayında</option>
                <option value="PASIF" <?php echo $haber['durum']=='PASIF'?'selected':''; ?>>Pasif</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>İçerik</label>
            <textarea name="icerik" class="form-control" rows="10"><?php echo guvenlik($haber['icerik']); ?></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary">💾 Kaydet</button>
    </form>
</div>

<?php include '../footer.php'; ?>