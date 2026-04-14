<?php
require_once 'config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header('Location: kurul.php');
    exit;
}

// Kurul bilgilerini çek
$stmt = $db->prepare("SELECT ik.id, ik.kurul_ad, ik.aciklama, ik.durum, ik.olusturma_tarihi, ik.baskan_uye_id, 
                      u.ad as baskan_ad, u.email as baskan_email, u.telefon as baskan_telefon 
                      FROM icra_kurullari ik 
                      LEFT JOIN uyeler u ON ik.baskan_uye_id = u.id 
                      WHERE ik.id = ?");
$stmt->execute([$id]);
$kurul = $stmt->fetch();

if (!$kurul) {
    header('Location: kurul.php');
    exit;
}

// Yetki kontrolü (Admin, Başkan veya Kurul Üyesi mi?)
$yetkili = false;
$benim_basvurularim = [];

if (isset($_SESSION['uye_id'])) {
    $uye_id = (int)$_SESSION['uye_id'];
    
    // Admin mi?
    if (admin()) {
        $yetkili = true;
    }
    // Başkan mı?
    elseif ($kurul['baskan_uye_id'] == $uye_id) {
        $yetkili = true;
    }
    // Kurul üyesi mi?
    else {
        $uye_kontrol = $db->prepare("SELECT * FROM kurul_uyeleri WHERE kurul_id = ? AND uye_id = ?");
        $uye_kontrol->execute([$id, $uye_id]);
        if ($uye_kontrol->fetch()) {
            $yetkili = true;
        }
    }
    
    // Benim bu kurula yaptığım başvuruları çek (dashboard benzeri)
    $benim_stmt = $db->prepare("SELECT b.*, DATE_FORMAT(b.basvuru_tarihi, '%d.%m.%Y %H:%i') as tarih_formatli,
                               CASE b.durum 
                                   WHEN 'BEKLEMEDE' THEN 'Beklemede'
                                   WHEN 'INCELEMEDE' THEN 'İncelemede'
                                   WHEN 'TAMAMLANDI' THEN 'Tamamlandı'
                                   WHEN 'REDDEDILDI' THEN 'Reddedildi'
                               END as durum_text
                               FROM is_basvurulari b 
                               WHERE b.kurul_id = ? AND (b.email = (SELECT email FROM uyeler WHERE id = ?) OR b.uye_id = ?)
                               ORDER BY b.basvuru_tarihi DESC");
    $benim_stmt->execute([$id, $uye_id, $uye_id]);
    $benim_basvurularim = $benim_stmt->fetchAll();
}

// Tüm başvurular (sadece yetkililer için)
$tum_basvurular = [];
if ($yetkili) {
    $durum_filtre = isset($_GET['basvuru_durum']) ? guvenlik($_GET['basvuru_durum']) : '';
    $sql = "SELECT b.*, DATE_FORMAT(b.basvuru_tarihi, '%d.%m.%Y %H:%i') as tarih_formatli,
            CASE b.durum 
                WHEN 'BEKLEMEDE' THEN 'bg-yellow-100 text-yellow-700'
                WHEN 'INCELEMEDE' THEN 'bg-blue-100 text-blue-700'
                WHEN 'TAMAMLANDI' THEN 'bg-green-100 text-green-700'
                WHEN 'REDDEDILDI' THEN 'bg-red-100 text-red-700'
            END as durum_class,
            CASE b.durum 
                WHEN 'BEKLEMEDE' THEN 'Beklemede'
                WHEN 'INCELEMEDE' THEN 'İncelemede'
                WHEN 'TAMAMLANDI' THEN 'Tamamlandı'
                WHEN 'REDDEDILDI' THEN 'Reddedildi'
            END as durum_text
            FROM is_basvurulari b 
            WHERE b.kurul_id = ?";
    $params = [$id];
    if ($durum_filtre) {
        $sql .= " AND b.durum = ?";
        $params[] = $durum_filtre;
    }
    $sql .= " ORDER BY b.basvuru_tarihi DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $tum_basvurular = $stmt->fetchAll();
}

// Kurul üyeleri - HATA DÜZELTİLDİ: u.profil_resmi kaldırıldı
$uyeler = $db->prepare("SELECT ku.kurul_id, ku.uye_id, ku.yetki, ku.atama_tarihi, 
                        u.ad, u.email, u.telefon 
                        FROM kurul_uyeleri ku 
                        JOIN uyeler u ON ku.uye_id = u.id 
                        WHERE ku.kurul_id = ? 
                        ORDER BY ku.yetki DESC, u.ad ASC");
$uyeler->execute([$id]);
$kurul_uyeleri = $uyeler->fetchAll();

// İstatistikler (sadece yetkililer için) - SQL INJECTION DÜZELTİLDİ
$istatistik = null;
if ($yetkili) {
    $istat_stmt = $db->prepare("SELECT COUNT(*) FROM is_basvurulari WHERE kurul_id = ?");
    $istat_stmt->execute([$id]);
    
    $istatistik = [
        'toplam' => $istat_stmt->fetchColumn(),
        'bekleyen' => 0,
        'incelemede' => 0,
        'tamamlanan' => 0
    ];
    
    $istat_stmt2 = $db->prepare("SELECT durum, COUNT(*) as sayi FROM is_basvurulari WHERE kurul_id = ? GROUP BY durum");
    $istat_stmt2->execute([$id]);
    while ($row = $istat_stmt2->fetch()) {
        if ($row['durum'] == 'BEKLEMEDE') $istatistik['bekleyen'] = $row['sayi'];
        elseif ($row['durum'] == 'INCELEMEDE') $istatistik['incelemede'] = $row['sayi'];
        elseif ($row['durum'] == 'TAMAMLANDI') $istatistik['tamamlanan'] = $row['sayi'];
    }
}

$sayfa_baslik = guvenlik($kurul['kurul_ad']) . ' - İcra Kurulu';

// Başvuru formu gönderildi mi?
$form_mesaj = '';
$form_hata = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['is_basvuru'])) {
    $ad_soyad = guvenlik(trim($_POST['ad_soyad'] ?? ''));
    $email = guvenlik(trim($_POST['email'] ?? ''));
    $telefon = guvenlik(trim($_POST['telefon'] ?? ''));
    $is_tanimi = guvenlik(trim($_POST['is_tanimi'] ?? ''));
    $is_tutari = floatval(str_replace(',', '.', $_POST['is_tutari'] ?? '0'));
    
    if (empty($ad_soyad) || empty($email) || empty($is_tanimi)) {
        $form_hata = 'Ad soyad, e-posta ve iş tanımı zorunludur.';
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO is_basvurulari 
                                (kurul_id, uye_id, ad_soyad, email, telefon, is_tanimi, is_tutari, durum, basvuru_tarihi) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, 'BEKLEMEDE', NOW())");
            $stmt->execute([
                $id, 
                isset($_SESSION['uye_id']) ? (int)$_SESSION['uye_id'] : null, 
                $ad_soyad, 
                $email, 
                $telefon, 
                $is_tanimi, 
                $is_tutari
            ]);
            $form_mesaj = 'İş başvurunuz başarıyla alındı. Kurul üyeleri en kısa sürede size dönüş yapacaktır.';
            
            // Sayfayı yenile ki başvuru listesinde görünsün
            header("Location: kurul-detay.php?id=$id&basvuru=ok");
            exit;
        } catch (PDOException $e) {
            $form_hata = 'Başvuru kaydedilirken hata oluştu: ' . $e->getMessage();
        }
    }
}

include 'header-modern.php';
?>

<!-- Başarı mesajı (URL'den gelen) -->
<?php if (isset($_GET['basvuru']) && $_GET['basvuru'] === 'ok'): ?>
<div class="max-w-7xl mx-auto px-4 mt-6">
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl relative" role="alert">
        <div class="flex items-center">
            <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i>
            <span class="block sm:inline">İş başvurunuz başarıyla alındı! Kurul üyeleri size en kısa sürede dönüş yapacaktır.</span>
        </div>
    </div>
</div>
<?php endif; ?>

<section class="gradient-hero text-white py-16">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-center text-sm text-gray-300 mb-4">
            <a href="kurul.php" class="hover:text-white">Kurullar</a>
            <i data-lucide="chevron-right" class="w-4 h-4 mx-2"></i>
            <span>Kurul Detayı</span>
        </div>
        <h1 class="text-3xl font-bold mb-2"><?php echo guvenlik($kurul['kurul_ad']); ?></h1>
        <p class="text-gray-300">Kuruluş: <?php echo date('d.m.Y', strtotime($kurul['olusturma_tarihi'])); ?></p>
    </div>
</section>

<section class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Sol Sidebar -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Kurul Bilgileri -->
                <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                    <h3 class="text-lg font-bold text-primary mb-4 flex items-center">
                        <i data-lucide="info" class="w-5 h-5 mr-2"></i>
                        Kurul Bilgileri
                    </h3>
                    
                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-4 bg-gray-50 rounded-xl">
                            <span class="text-gray-600">Durum</span>
                            <span class="<?php echo $kurul['durum'] == 'AKTIF' ? 'text-green-600' : 'text-red-600'; ?> font-semibold flex items-center">
                                <span class="w-2 h-2 <?php echo $kurul['durum'] == 'AKTIF' ? 'bg-green-500' : 'bg-red-500'; ?> rounded-full mr-2"></span>
                                <?php echo $kurul['durum'] == 'AKTIF' ? 'Aktif' : 'Pasif'; ?>
                            </span>
                        </div>
                        
                        <div class="p-4 bg-blue-50 rounded-xl">
                            <h4 class="font-semibold text-primary mb-2">Açıklama</h4>
                            <p class="text-sm text-gray-600 leading-relaxed">
                                <?php echo nl2br(guvenlik($kurul['aciklama'])); ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Kurul Üyeleri (Herkes görebilir - isim listesi) -->
                <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                    <h3 class="text-lg font-bold text-primary mb-4 flex items-center">
                        <i data-lucide="users" class="w-5 h-5 mr-2"></i>
                        Kurul Üyeleri
                    </h3>
                    <p class="text-xs text-gray-500 mb-4">Bu kurulun uzman üyeleri aşağıda listelenmektedir. İş başvurusu yaparak bu üyelerle iletişime geçebilirsiniz.</p>
                    
                    <div class="space-y-3">
                        <?php if ($kurul['baskan_ad']): ?>
                        <div class="flex items-center p-3 bg-gradient-to-r from-secondary/20 to-transparent rounded-xl border border-secondary/30">
                            <div class="w-12 h-12 bg-secondary rounded-full flex items-center justify-center text-primary font-bold text-lg mr-3">
                                <?php echo substr(guvenlik($kurul['baskan_ad']), 0, 1); ?>
                            </div>
                            <div class="flex-1">
                                <div class="font-semibold text-sm"><?php echo guvenlik($kurul['baskan_ad']); ?></div>
                                <div class="text-xs text-secondary font-bold uppercase tracking-wider">Başkan</div>
                            </div>
                            <?php if ($kurul['baskan_telefon'] && $yetkili): ?>
                            <a href="tel:<?php echo guvenlik($kurul['baskan_telefon']); ?>" class="p-2 text-green-600 hover:bg-green-50 rounded-lg" title="Ara">
                                <i data-lucide="phone" class="w-4 h-4"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php foreach ($kurul_uyeleri as $uye): ?>
                        <div class="flex items-center p-3 bg-gray-50 rounded-xl border border-gray-100">
                            <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center text-white font-bold mr-3">
                                <?php echo substr(guvenlik($uye['ad']), 0, 1); ?>
                            </div>
                            <div class="flex-1">
                                <div class="font-semibold text-sm text-gray-800"><?php echo guvenlik($uye['ad']); ?></div>
                                <div class="text-xs text-gray-500">
                                    <?php echo $uye['yetki'] == 'YARDIMCI' ? 'Yardımcı Başkan' : 'Kurul Üyesi'; ?>
                                </div>
                            </div>
                            <?php if ($yetkili && !empty($uye['telefon'])): ?>
                            <a href="tel:<?php echo guvenlik($uye['telefon']); ?>" class="p-2 text-green-600 hover:bg-green-50 rounded-lg" title="Ara">
                                <i data-lucide="phone" class="w-4 h-4"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if (!$kurul['baskan_ad'] && count($kurul_uyeleri) == 0): ?>
                        <div class="text-center py-4 text-gray-500 text-sm">
                            Henüz üye atanmamış.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- İletişim (Sadece yetkililere) -->
                <?php if ($yetkili && $kurul['baskan_email']): ?>
                <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                    <h3 class="text-lg font-bold text-primary mb-4">Acil İletişim</h3>
                    <a href="mailto:<?php echo guvenlik($kurul['baskan_email']); ?>" class="flex items-center p-3 bg-blue-50 rounded-xl text-blue-700 hover:bg-blue-100 transition">
                        <i data-lucide="mail" class="w-5 h-5 mr-3"></i>
                        <span class="font-semibold">Başkana E-Posta Gönder</span>
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Sağ Content -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- İŞ BAŞVURU FORMU (Sadece giriş yapmış üyeler) -->
                <?php if (isset($_SESSION['uye_id'])): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" id="basvuru-formu">
                    <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-primary to-blue-800 text-white">
                        <h2 class="text-xl font-bold flex items-center">
                            <i data-lucide="briefcase" class="w-6 h-6 mr-3 text-secondary"></i>
                            İş Başvurusu Yap
                        </h2>
                        <p class="text-blue-100 text-sm mt-1">Bu kurul üyelerine iş talebinizi iletin. Başvurunuz sadece kurul üyeleri ve yöneticiler tarafından görüntülenecektir.</p>
                    </div>
                    
                    <div class="p-6">
                        <?php if ($form_hata): ?>
                        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 flex items-center">
                            <i data-lucide="alert-circle" class="w-5 h-5 mr-2"></i>
                            <?php echo $form_hata; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($form_mesaj): ?>
                        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-xl text-green-700 flex items-center">
                            <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i>
                            <?php echo $form_mesaj; ?>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="#basvuru-formu" class="space-y-5">
                            <input type="hidden" name="is_basvuru" value="1">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Ad Soyad <span class="text-red-500">*</span></label>
                                    <input type="text" name="ad_soyad" required 
                                           value="<?php echo guvenlik($_SESSION['uye_ad'] ?? ''); ?>"
                                           class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-secondary outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">E-Posta <span class="text-red-500">*</span></label>
                                    <input type="email" name="email" required 
                                           value="<?php echo guvenlik($_SESSION['uye_email'] ?? ''); ?>"
                                           class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-secondary outline-none transition">
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Telefon</label>
                                    <input type="tel" name="telefon" 
                                           value="<?php echo guvenlik($_SESSION['uye_telefon'] ?? ''); ?>"
                                           class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-secondary outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">İş Tutarı (TL) <span class="text-xs text-gray-400">(Opsiyonel)</span></label>
                                    <input type="number" name="is_tutari" step="0.01" min="0" 
                                           placeholder="Örn: 50000"
                                           class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-secondary outline-none transition">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">İş Tanımı / Talep Detayı <span class="text-red-500">*</span></label>
                                <textarea name="is_tanimi" required rows="5" 
                                          placeholder="Lütfen ihtiyacınızı veya iş talebinizi detaylı açıklayın. Hangi hizmeti almak istediğinizi, zaman çerçevesini ve özel gereksinimlerinizi belirtin."
                                          class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-secondary outline-none transition"></textarea>
                                <p class="text-xs text-gray-500 mt-1">Bu bilgi sadece kurul üyeleri tarafından görüntülenecektir.</p>
                            </div>
                            
                            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                                <div class="flex items-center text-sm text-gray-500">
                                    <i data-lucide="lock" class="w-4 h-4 mr-2 text-green-600"></i>
                                    <span>Gizli Başvuru</span>
                                </div>
                                <button type="submit" class="px-8 py-3 gradient-gold text-primary rounded-xl font-bold hover:shadow-lg transition transform hover:scale-105 flex items-center">
                                    <i data-lucide="send" class="w-5 h-5 mr-2"></i>
                                    Başvuruyu Gönder
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Benim Başvurularım (Bu kurula yaptığım) -->
                <?php if (count($benim_basvurularim) > 0): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                        <h3 class="text-lg font-bold text-primary flex items-center">
                            <i data-lucide="folder" class="w-5 h-5 mr-2"></i>
                            Benim Başvurularım
                        </h3>
                        <span class="px-3 py-1 bg-gray-200 rounded-full text-sm text-gray-700"><?php echo count($benim_basvurularim); ?> Adet</span>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <?php foreach ($benim_basvurularim as $b): ?>
                        <div class="p-4 hover:bg-gray-50 transition">
                            <div class="flex justify-between items-start mb-2">
                                <span class="text-sm text-gray-500"><?php echo $b['tarih_formatli']; ?></span>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $b['durum_class']; ?>">
                                    <?php echo $b['durum_text']; ?>
                                </span>
                            </div>
                            <p class="text-sm text-gray-800 line-clamp-2"><?php echo guvenlik($b['is_tanimi']); ?></p>
                            <?php if ($b['is_tutari'] > 0): ?>
                            <div class="mt-2 text-sm font-semibold text-primary">
                                Tutar: <?php echo number_format($b['is_tutari'], 2, ',', '.'); ?> TL
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <!-- Giriş yapmadan başvuru yapılamaz -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">
                    <div class="w-20 h-20 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="lock" class="w-10 h-10 text-primary"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">İş Başvurusu İçin Giriş Yapın</h3>
                    <p class="text-gray-600 mb-6">Kurul üyelerine iş talebinde bulunmak için lütfen giriş yapın veya üye olun. Başvurularınız şifreli olarak saklanır ve sadece yetkili kişiler görüntüler.</p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="giris.php?redirect=<?php echo urlencode('kurul-detay.php?id=' . $id . '#basvuru-formu'); ?>" class="px-8 py-3 bg-primary text-white rounded-xl font-bold hover:bg-blue-800 transition">
                            Giriş Yap
                        </a>
                        <a href="kayit.php" class="px-8 py-3 border-2 border-primary text-primary rounded-xl font-bold hover:bg-primary hover:text-white transition">
                            Üye Ol
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Gizli Alan: Tüm Başvurular (Sadece Yetkililer) -->
                <?php if ($yetkili): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden border-2 border-primary/20">
                    <div class="p-6 border-b border-gray-100 bg-primary text-white flex justify-between items-center">
                        <div>
                            <h2 class="text-xl font-bold flex items-center">
                                <i data-lucide="shield" class="w-6 h-6 mr-3 text-secondary"></i>
                                Tüm İş Başvuruları
                                <span class="ml-3 px-3 py-1 bg-white/20 rounded-full text-sm"><?php echo count($tum_basvurular); ?></span>
                            </h2>
                            <p class="text-blue-100 text-sm mt-1">Bu alan sadece kurul üyeleri ve yöneticiler içindir.</p>
                        </div>
                        
                        <form method="GET" class="flex gap-2">
                            <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
                            <select name="basvuru_durum" onchange="this.form.submit()" class="px-3 py-2 bg-white/10 border border-white/30 rounded-lg text-sm text-white focus:outline-none">
                                <option value="" class="text-gray-800">Tüm Durumlar</option>
                                <option value="BEKLEMEDE" <?php echo isset($_GET['basvuru_durum']) && $_GET['basvuru_durum'] == 'BEKLEMEDE' ? 'selected' : ''; ?> class="text-gray-800">Beklemede</option>
                                <option value="INCELEMEDE" <?php echo isset($_GET['basvuru_durum']) && $_GET['basvuru_durum'] == 'INCELEMEDE' ? 'selected' : ''; ?> class="text-gray-800">İncelemede</option>
                                <option value="TAMAMLANDI" <?php echo isset($_GET['basvuru_durum']) && $_GET['basvuru_durum'] == 'TAMAMLANDI' ? 'selected' : ''; ?> class="text-gray-800">Tamamlandı</option>
                                <option value="REDDEDILDI" <?php echo isset($_GET['basvuru_durum']) && $_GET['basvuru_durum'] == 'REDDEDILDI' ? 'selected' : ''; ?> class="text-gray-800">Reddedildi</option>
                            </select>
                        </form>
                    </div>
                    
                    <?php if ($tum_basvurular): ?>
                    <div class="divide-y divide-gray-100 max-h-[600px] overflow-y-auto">
                        <?php foreach ($tum_basvurular as $basvuru): ?>
                        <div class="p-6 hover:bg-gray-50 transition border-l-4 border-transparent hover:border-primary">
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center text-gray-600 font-bold mr-3">
                                        <?php echo substr(guvenlik($basvuru['ad_soyad']), 0, 1); ?>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-800"><?php echo guvenlik($basvuru['ad_soyad']); ?></h4>
                                        <div class="text-xs text-gray-500 flex items-center">
                                            <i data-lucide="mail" class="w-3 h-3 mr-1"></i>
                                            <?php echo guvenlik($basvuru['email']); ?>
                                            <?php if ($basvuru['telefon']): ?>
                                            <span class="mx-2">•</span>
                                            <i data-lucide="phone" class="w-3 h-3 mr-1"></i>
                                            <?php echo guvenlik($basvuru['telefon']); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $basvuru['durum_class']; ?>">
                                    <?php echo $basvuru['durum_text']; ?>
                                </span>
                            </div>
                            
                            <div class="bg-gray-50 p-4 rounded-xl mb-3 ml-12">
                                <p class="text-sm text-gray-700 leading-relaxed"><?php echo nl2br(guvenlik($basvuru['is_tanimi'])); ?></p>
                                <?php if ($basvuru['is_tutari'] > 0): ?>
                                <div class="mt-3 pt-3 border-t border-gray-200 flex justify-between items-center">
                                    <span class="text-xs text-gray-500">Tutar:</span>
                                    <span class="font-bold text-primary"><?php echo number_format($basvuru['is_tutari'], 2, ',', '.'); ?> TL</span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex justify-between items-center ml-12">
                                <span class="text-xs text-gray-400"><?php echo $basvuru['tarih_formatli']; ?></span>
                                <div class="flex gap-2">
                                    <a href="admin/is-basvuru-detay.php?id=<?php echo (int)$basvuru['id']; ?>" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-semibold hover:bg-blue-800 transition flex items-center">
                                        <i data-lucide="edit" class="w-4 h-4 mr-2"></i>
                                        Yönet
                                    </a>
                                    <?php if (!empty($basvuru['dosya_yolu'])): ?>
                                    <a href="<?php echo guvenlik($basvuru['dosya_yolu']); ?>" target="_blank" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-semibold hover:bg-gray-50 transition flex items-center text-gray-700">
                                        <i data-lucide="paperclip" class="w-4 h-4 mr-2"></i>
                                        Dosya
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="p-12 text-center text-gray-500">
                        <i data-lucide="inbox" class="w-16 h-16 mx-auto mb-4 text-gray-300"></i>
                        <p class="text-lg font-semibold mb-2">Henüz başvuru yok</p>
                        <p class="text-sm">Bu kurula yapılmış iş başvurusu bulunmamaktadır.</p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- İstatistikler -->
                    <?php if ($istatistik): ?>
                    <div class="grid grid-cols-4 gap-0 border-t border-gray-200 bg-gray-50">
                        <div class="p-4 text-center border-r border-gray-200">
                            <div class="text-2xl font-bold text-primary"><?php echo $istatistik['toplam']; ?></div>
                            <div class="text-xs text-gray-500">Toplam</div>
                        </div>
                        <div class="p-4 text-center border-r border-gray-200">
                            <div class="text-2xl font-bold text-yellow-600"><?php echo $istatistik['bekleyen']; ?></div>
                            <div class="text-xs text-gray-500">Bekleyen</div>
                        </div>
                        <div class="p-4 text-center border-r border-gray-200">
                            <div class="text-2xl font-bold text-blue-600"><?php echo $istatistik['incelemede']; ?></div>
                            <div class="text-xs text-gray-500">İncelemede</div>
                        </div>
                        <div class="p-4 text-center">
                            <div class="text-2xl font-bold text-green-600"><?php echo $istatistik['tamamlanan']; ?></div>
                            <div class="text-xs text-gray-500">Tamamlanan</div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</section>

<?php include 'footer-modern.php'; ?>