<?php
require_once '../config.php';

if (!yetkili()) {
    bildirim('İlan vermek için giriş yapın.', 'danger');
    header('Location: /giris.php');
    exit;
}

// Session'da uye_ad yoksa çekelim
if (!isset($_SESSION['uye_ad'])) {
    $uye = $db->prepare("SELECT ad FROM uyeler WHERE id = ?");
    $uye->execute([$_SESSION['uye_id']]);
    $_SESSION['uye_ad'] = $uye->fetchColumn() ?? 'Bilinmiyor';
}

$kategoriler = $db->query("SELECT * FROM kategoriler ORDER BY ad")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // Temel bilgiler
        $baslik     = guvenlik(trim($_POST['baslik'] ?? ''));
        $aciklama   = guvenlik(trim($_POST['aciklama'] ?? ''));
        $fiyat      = floatval(str_replace(',', '.', $_POST['fiyat'] ?? '0'));
        $kategori_id= intval($_POST['kategori_id'] ?? 0);
        $durum      = admin() ? ($_POST['durum'] ?? 'AKTIF') : 'BEKLEMEDE';
        
        // Lokasyon bilgileri
        $il = guvenlik(trim($_POST['il'] ?? ''));
        $ilce = guvenlik(trim($_POST['ilce'] ?? ''));
        $mahalle = guvenlik(trim($_POST['mahalle'] ?? ''));
        $ada_no = guvenlik(trim($_POST['ada_no'] ?? ''));
        $parsel_no = guvenlik(trim($_POST['parsel_no'] ?? ''));
        $takip_no = guvenlik(trim($_POST['takip_no'] ?? ''));
        
        // Arazi bilgileri
        $alan_m2 = intval($_POST['alan_m2'] ?? 0);
        $alan_donum = $alan_m2 > 0 ? round($alan_m2 / 1000, 2) : 0;
        $ozellikler = isset($_POST['ozellikler']) ? json_encode($_POST['ozellikler']) : '[]';
        
        // Ana resim
        $resim = '';
        if (!empty($_FILES['resim']['name'])) {
            $uzanti = strtolower(pathinfo($_FILES['resim']['name'], PATHINFO_EXTENSION));
            if (in_array($uzanti, ['jpg','jpeg','png'])) {
                $hedef = 'uploads/' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $uzanti;
                if (move_uploaded_file($_FILES['resim']['tmp_name'], __DIR__ . '/' . $hedef)) {
                    $resim = $hedef;
                }
            }
        }
        
        // Çoklu resimler
        $ek_resimler = [];
        if (!empty($_FILES['resimler']['name'][0])) {
            foreach ($_FILES['resimler']['tmp_name'] as $index => $tmp_name) {
                if ($_FILES['resimler']['error'][$index] === 0) {
                    $uzanti = strtolower(pathinfo($_FILES['resimler']['name'][$index], PATHINFO_EXTENSION));
                    if (in_array($uzanti, ['jpg','jpeg','png'])) {
                        $hedef = 'uploads/' . time() . '_' . $index . '_' . bin2hex(random_bytes(4)) . '.' . $uzanti;
                        if (move_uploaded_file($tmp_name, __DIR__ . '/' . $hedef)) {
                            $ek_resimler[] = $hedef;
                        }
                    }
                }
            }
        }
        
        // İlanı kaydet (16 alan)
        $stmt = $db->prepare("INSERT INTO ilanlar 
            (baslik, aciklama, fiyat, kategori_id, uye_id, resim, durum,
             il, ilce, mahalle, ada_no, parsel_no, takip_no, alan_m2, alan_donum, ozellikler) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $baslik, $aciklama, $fiyat, $kategori_id, $_SESSION['uye_id'], $resim, $durum,
            $il, $ilce, $mahalle, $ada_no, $parsel_no, $takip_no, 
            $alan_m2, $alan_donum, $ozellikler
        ]);
        
        $ilan_id = $db->lastInsertId();
        
        // Ek resimleri kaydet
        if (!empty($ek_resimler)) {
            $stmt_resim = $db->prepare("INSERT INTO ilan_resimler (ilan_id, resim_yolu, sira) VALUES (?, ?, ?)");
            foreach ($ek_resimler as $sira => $resim_yolu) {
                $stmt_resim->execute([$ilan_id, $resim_yolu, $sira]);
            }
        }
        
        $db->commit();
        
        // Admin bildirimi
        if ($durum === 'BEKLEMEDE') {
            $mesaj = "YENİ İLAN BEKLEMEDE\n\n"
                   . "Başlık: {$baslik}\n"
                   . "Fiyat: " . para($fiyat) . "\n"
                   . "Konum: {$il}/{$ilce}/{$mahalle}\n"
                   . "Alan: {$alan_m2} m² ({$alan_donum} dönüm)\n"
                   . "Ekleyen: " . $_SESSION['uye_ad'] . "\n"
                   . "Takip No: {$takip_no}\n\n"
                   . "Onaylamak için: " . site_url('admin/ilanlar.php');
            
            sendAdminNotification($mesaj);
        }
        
        bildirim('İlan başarıyla eklendi ve admin onayına gönderildi!', 'success');
        header('Location: /index.php');
        exit;
        
    } catch (Exception $e) {
        $db->rollBack();
        bildirim('Hata: ' . $e->getMessage(), 'danger');
    }
}

include 'header.php';
?>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <h2 class="card-title">🏞️ Yeni İlan Ver</h2>
        <p style="color: #666; margin-top: 5px; font-size: 14px;">Satılık arazi, tarla ve arsa ilanları için gelişmiş form</p>
    </div>

    <form method="POST" enctype="multipart/form-data">
        
        <!-- Temel Bilgiler -->
        <div class="form-section" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h3 style="margin-bottom: 15px; color: #333; border-bottom: 2px solid #007bff; padding-bottom: 5px;">📋 Temel Bilgiler</h3>
            
            <div class="form-group">
                <label class="form-label">İlan Başlığı *</label>
                <input type="text" name="baslik" class="form-control" 
                       placeholder="Örn: Satılık 20.200 m² Baraj Manzaralı Tarla" required>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label class="form-label">Fiyat (TL) *</label>
                    <input type="number" name="fiyat" class="form-control" step="0.01" min="0" 
                           placeholder="1399000" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Kategori *</label>
                    <select name="kategori_id" class="form-control" required>
                        <option value="">Seçin...</option>
                        <?php foreach ($kategoriler as $kat): ?>
                            <option value="<?php echo $kat['id']; ?>" <?php echo $kat['ad'] == 'Arazi/Tarla' ? 'selected' : ''; ?>>
                                <?php echo guvenlik($kat['ad']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Açıklama *</label>
                <textarea name="aciklama" class="form-control" rows="5" required></textarea>
            </div>
        </div>

        <!-- Konum Bilgileri -->
        <div class="form-section" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h3 style="margin-bottom: 15px; color: #333; border-bottom: 2px solid #28a745; padding-bottom: 5px;">📍 Konum ve Tapu</h3>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label class="form-label">İl *</label>
                    <input type="text" name="il" class="form-control" value="Çanakkale" required>
                </div>
                <div class="form-group">
                    <label class="form-label">İlçe *</label>
                    <input type="text" name="ilce" class="form-control" value="Biga" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Mahalle/Köy *</label>
                    <input type="text" name="mahalle" class="form-control" value="Harmanlı" required>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-top: 15px;">
                <div class="form-group">
                    <label class="form-label">Ada No</label>
                    <input type="text" name="ada_no" class="form-control" placeholder="154103">
                </div>
                <div class="form-group">
                    <label class="form-label">Parsel No</label>
                    <input type="text" name="parsel_no" class="form-control" placeholder="778">
                </div>
                <div class="form-group">
                    <label class="form-label">Takip No</label>
                    <input type="text" name="takip_no" class="form-control" placeholder="11091">
                </div>
            </div>
        </div>

        <!-- Arazi Özellikleri -->
        <div class="form-section" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h3 style="margin-bottom: 15px; color: #333; border-bottom: 2px solid #ffc107; padding-bottom: 5px;">🌾 Arazi Özellikleri</h3>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label class="form-label">Alan (m²) *</label>
                    <input type="number" name="alan_m2" class="form-control" value="20200" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Dönüm (Otomatik)</label>
                    <input type="text" class="form-control" value="~20 dönüm" disabled>
                </div>
            </div>
            
            <div class="form-group" style="margin-top: 15px;">
                <label class="form-label">Özellikler</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <label style="display: flex; align-items: center; gap: 8px; padding: 8px; background: white; border-radius: 4px; cursor: pointer;">
                        <input type="checkbox" name="ozellikler[]" value="resmi-yol-cephe" checked style="width: auto;">
                        <span>✅ Resmi yola cepheli</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; padding: 8px; background: white; border-radius: 4px; cursor: pointer;">
                        <input type="checkbox" name="ozellikler[]" value="yol-problemi-yok" checked style="width: auto;">
                        <span>✅ Yol problemi yok</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; padding: 8px; background: white; border-radius: 4px; cursor: pointer;">
                        <input type="checkbox" name="ozellikler[]" value="koy-yakin" checked style="width: auto;">
                        <span>✅ Köyün hemen dibinde</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; padding: 8px; background: white; border-radius: 4px; cursor: pointer;">
                        <input type="checkbox" name="ozellikler[]" value="baraj-manzarali" checked style="width: auto;">
                        <span>✅ Baraj manzaralı</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; padding: 8px; background: white; border-radius: 4px; cursor: pointer;">
                        <input type="checkbox" name="ozellikler[]" value="genis-arazi" checked style="width: auto;">
                        <span>✅ Geniş arazi (20+ dönüm)</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; padding: 8px; background: white; border-radius: 4px; cursor: pointer;">
                        <input type="checkbox" name="ozellikler[]" value="elektrik" style="width: auto;">
                        <span>⚡ Elektrik yakın</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; padding: 8px; background: white; border-radius: 4px; cursor: pointer;">
                        <input type="checkbox" name="ozellikler[]" value="su" style="width: auto;">
                        <span>💧 Su kaynağı yakın</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; padding: 8px; background: white; border-radius: 4px; cursor: pointer;">
                        <input type="checkbox" name="ozellikler[]" value="imar" style="width: auto;">
                        <span>🏗️ İmar potansiyeli</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Resimler -->
        <div class="form-section" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h3 style="margin-bottom: 15px; color: #333; border-bottom: 2px solid #dc3545; padding-bottom: 5px;">📸 Fotoğraflar</h3>
            
            <div class="form-group">
                <label class="form-label">Ana Görsel (Kapak fotoğrafı)</label>
                <input type="file" name="resim" class="form-control" accept=".jpg,.jpeg,.png">
                <small style="color: #666;">Ana görsel olarak kullanılacak</small>
            </div>
            
            <div class="form-group" style="margin-top: 15px;">
                <label class="form-label">Ek Resimler (Max 10 adet)</label>
                <input type="file" name="resimler[]" class="form-control" accept=".jpg,.jpeg,.png" multiple id="cokluResim">
                <small style="color: #666;">Tapu, etraf görüntüleri, baraj manzarası vb.</small>
                <div id="resimOnizleme" style="margin-top: 10px;"></div>
            </div>
        </div>

        <?php if (admin()): ?>
        <div class="form-group">
            <label class="form-label">Durum</label>
            <select name="durum" class="form-control">
                <option value="BEKLEMEDE">Beklemede</option>
                <option value="AKTIF">Aktif</option>
                <option value="PASIF">Pasif</option>
            </select>
        </div>
        <?php endif; ?>

        <div style="display: flex; gap: 10px; margin-top: 20px;">
            <button type="submit" class="btn btn-primary">📤 İlanı Gönder</button>
            <a href="/index.php" class="btn btn-secondary">İptal</a>
        </div>
    </form>
</div>

<script>
document.getElementById('cokluResim').addEventListener('change', function(e) {
    const preview = document.getElementById('resimOnizleme');
    preview.innerHTML = '';
    
    if (this.files.length > 10) {
        alert('En fazla 10 resim yükleyebilirsiniz!');
        this.value = '';
        return;
    }
    
    for (let i = 0; i < this.files.length; i++) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.cssText = 'width:80px;height:80px;object-fit:cover;margin:5px;border-radius:4px;';
            preview.appendChild(img);
        }
        reader.readAsDataURL(this.files[i]);
    }
});
</script>

<?php include 'footer.php'; ?>