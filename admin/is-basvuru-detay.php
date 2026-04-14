<?php
require_once '../config.php';

if (!admin()) {
    header('Location: /giris.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header('Location: /admin/is-basvurulari.php');
    exit;
}

// Başvuru bilgilerini çek (komisyon_orani kaldırıldı)
$stmt = $db->prepare("
    SELECT b.*, 
           ik.kurul_ad, ik.aciklama as kurul_aciklama,
           u.ad as basvuran_ad, u.email as basvuran_email, u.telefon as basvuran_telefon
    FROM is_basvurulari b
    LEFT JOIN icra_kurullari ik ON b.kurul_id = ik.id
    LEFT JOIN uyeler u ON b.uye_id = u.id
    WHERE b.id = ?
");
$stmt->execute([$id]);
$basvuru = $stmt->fetch();

if (!$basvuru) {
    header('Location: /admin/is-basvurulari.php');
    exit;
}

// Durum güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['durum_guncelle'])) {
    $yeni_durum = guvenlik($_POST['durum']);
    $notlar = guvenlik($_POST['admin_notlari']);
    
    $stmt = $db->prepare("UPDATE is_basvurulari SET durum = ?, admin_notlari = ?, guncelleme_tarihi = NOW() WHERE id = ?");
    $stmt->execute([$yeni_durum, $notlar, $id]);
    
    header("Location: is-basvuru-detay.php?id=$id&ok=1");
    exit;
}

$sayfa_baslik = 'İş Başvuru Detayı #' . $id;
include '../header-modern.php';
?>

<section class="gradient-hero text-white py-12">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-center text-sm text-gray-300 mb-4">
            <a href="index.php" class="hover:text-white">Admin Panel</a>
            <i data-lucide="chevron-right" class="w-4 h-4 mx-2"></i>
            <a href="is-basvurulari.php" class="hover:text-white">İş Başvuruları</a>
            <i data-lucide="chevron-right" class="w-4 h-4 mx-2"></i>
            <span>Detay</span>
        </div>
        <h1 class="text-3xl font-bold">İş Başvuru Detayı #<?php echo $id; ?></h1>
    </div>
</section>

<section class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4">
        <?php if (isset($_GET['ok'])): ?>
        <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-xl">
            Başvuru durumu başarıyla güncellendi.
        </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Sol: Başvuru Bilgileri -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                    <h2 class="text-xl font-bold text-primary mb-4">Başvuru Bilgileri</h2>
                    
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <div class="text-sm text-gray-500">Ad Soyad</div>
                            <div class="font-semibold text-lg"><?php echo guvenlik($basvuru['ad_soyad']); ?></div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">E-Posta</div>
                            <div class="font-semibold"><?php echo guvenlik($basvuru['email']); ?></div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Telefon</div>
                            <div class="font-semibold"><?php echo guvenlik($basvuru['telefon'] ?? '-'); ?></div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Başvuru Tarihi</div>
                            <div class="font-semibold"><?php echo date('d.m.Y H:i', strtotime($basvuru['basvuru_tarihi'])); ?></div>
                        </div>
                    </div>
                    
                    <div class="p-4 bg-gray-50 rounded-xl mb-4">
                        <div class="text-sm text-gray-500 mb-2">İş Tanımı</div>
                        <p class="text-gray-800 leading-relaxed"><?php echo nl2br(guvenlik($basvuru['is_tanimi'])); ?></p>
                    </div>
                    
                    <?php if ($basvuru['is_tutari'] > 0): ?>
                    <div class="flex justify-between items-center p-4 bg-blue-50 rounded-xl">
                        <span class="text-gray-600">İş Tutarı</span>
                        <span class="text-xl font-bold text-primary"><?php echo number_format($basvuru['is_tutari'], 2, ',', '.'); ?> TL</span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Durum Güncelleme Formu -->
                <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                    <h2 class="text-xl font-bold text-primary mb-4">Durum Güncelle</h2>
                    <form method="POST">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Yeni Durum</label>
                                <select name="durum" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-secondary outline-none">
                                    <option value="BEKLEMEDE" <?php echo $basvuru['durum'] == 'BEKLEMEDE' ? 'selected' : ''; ?>>Beklemede</option>
                                    <option value="INCELEMEDE" <?php echo $basvuru['durum'] == 'INCELEMEDE' ? 'selected' : ''; ?>>İncelemede</option>
                                    <option value="TAMAMLANDI" <?php echo $basvuru['durum'] == 'TAMAMLANDI' ? 'selected' : ''; ?>>Tamamlandı</option>
                                    <option value="REDDEDILDI" <?php echo $basvuru['durum'] == 'REDDEDILDI' ? 'selected' : ''; ?>>Reddedildi</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Admin Notları</label>
                            <textarea name="admin_notlari" rows="4" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-secondary outline-none"><?php echo guvenlik($basvuru['admin_notlari'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" name="durum_guncelle" class="px-6 py-3 bg-primary text-white rounded-xl font-bold hover:bg-blue-800 transition">
                            Güncelle
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Sağ: Kurul Bilgileri (Komisyon kaldırıldı) -->
            <div class="space-y-6">
                <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                    <h3 class="text-lg font-bold text-primary mb-4">Başvurulan Kurul</h3>
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center mr-3">
                            <i data-lucide="shield" class="w-6 h-6 text-primary"></i>
                        </div>
                        <div>
                            <div class="font-bold"><?php echo guvenlik($basvuru['kurul_ad']); ?></div>
                            <div class="text-sm text-gray-500">İcra Kurulu</div>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 mb-4"><?php echo guvenlik($basvuru['kurul_aciklama']); ?></p>
                    
                    <!-- KOMİSYON ORANI KALDIRILDI -->
                    
                    <a href="kurul-uyeleri.php?kurul_id=<?php echo $basvuru['kurul_id']; ?>" class="block w-full py-2 border border-primary text-primary rounded-lg text-center font-semibold hover:bg-primary hover:text-white transition">
                        Kurul Üyelerini Gör
                    </a>
                </div>
                
                <!-- Başvuran Üye Bilgisi (varsa) -->
                <?php if ($basvuru['basvuran_ad']): ?>
                <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                    <h3 class="text-lg font-bold text-primary mb-4">Başvuran Üye</h3>
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center text-gray-600 font-bold mr-3">
                            <?php echo substr($basvuru['basvuran_ad'], 0, 1); ?>
                        </div>
                        <div>
                            <div class="font-bold"><?php echo guvenlik($basvuru['basvuran_ad']); ?></div>
                            <div class="text-sm text-gray-500"><?php echo guvenlik($basvuru['basvuran_email']); ?></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include '../footer-modern.php'; ?>