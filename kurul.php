<?php
require_once 'config.php';

$sayfa_baslik = 'İcra Kurulları - Hasinder';

// Filtreler
$arama = isset($_GET['arama']) ? guvenlik($_GET['arama']) : '';
$durum = isset($_GET['durum']) ? guvenlik($_GET['durum']) : 'AKTIF';

// SQL hazırlama - sadece gerekli alanlar
$sql = "SELECT ik.*, u.ad as baskan_ad, u.email as baskan_email,
        (SELECT COUNT(*) FROM is_basvurulari WHERE kurul_id = ik.id AND durum = 'BEKLEMEDE') as bekleyen_basvuru,
        (SELECT COUNT(*) FROM kurul_uyeleri WHERE kurul_id = ik.id) as uye_sayisi
        FROM icra_kurullari ik
        LEFT JOIN uyeler u ON ik.baskan_uye_id = u.id
        WHERE 1=1";

$params = [];

if ($arama) {
    $sql .= " AND (ik.kurul_ad LIKE ? OR ik.aciklama LIKE ?)";
    $params[] = "%$arama%";
    $params[] = "%$arama%";
}

if ($durum) {
    $sql .= " AND ik.durum = ?";
    $params[] = $durum;
}

$sql .= " ORDER BY ik.olusturma_tarihi DESC";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $kurullar = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}

// İstatistikler
$toplam_kurul = $db->query("SELECT COUNT(*) FROM icra_kurullari WHERE durum = 'AKTIF'")->fetchColumn();
$aktif_basvuru = $db->query("SELECT COUNT(*) FROM is_basvurulari WHERE durum = 'BEKLEMEDE'")->fetchColumn();

include 'header-modern.php';
?>

<section class="gradient-hero text-white py-20">
    <div class="max-w-7xl mx-auto px-4 text-center">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">İcra Kurulları</h1>
        <p class="text-xl text-gray-300 max-w-2xl mx-auto">
            Profesyonel komisyonlarımız ile iş süreçlerinizi güvence altına alın.
        </p>
    </div>
</section>

<section class="py-8 bg-white border-b border-gray-200 sticky top-20 z-30 shadow-sm">
    <div class="max-w-7xl mx-auto px-4">
        <form method="GET" class="flex flex-col md:flex-row gap-4">
            <div class="flex-1 relative">
                <i data-lucide="search" class="absolute left-4 top-3.5 w-5 h-5 text-gray-400"></i>
                <input type="text" name="arama" value="<?php echo $arama; ?>" 
                       placeholder="Kurul ara..." 
                       class="w-full pl-12 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-secondary outline-none">
            </div>
            <select name="durum" onchange="this.form.submit()" class="px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl">
                <option value="AKTIF" <?php echo $durum == 'AKTIF' ? 'selected' : ''; ?>>Aktif</option>
                <option value="PASIF" <?php echo $durum == 'PASIF' ? 'selected' : ''; ?>>Pasif</option>
            </select>
            <button type="submit" class="px-8 py-3 bg-primary text-white rounded-xl font-semibold">Filtrele</button>
        </form>
        
        <div class="mt-4 flex items-center justify-between text-sm text-gray-600">
            <span>Toplam <?php echo count($kurullar); ?> kurul bulundu</span>
            <div class="flex gap-6">
                <span class="flex items-center"><span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span><?php echo $toplam_kurul; ?> Aktif</span>
                <span class="flex items-center"><span class="w-2 h-2 bg-orange-500 rounded-full mr-2"></span><?php echo $aktif_basvuru; ?> Bekleyen Başvuru</span>
            </div>
        </div>
    </div>
</section>

<section class="py-12 bg-gray-50 min-h-[60vh]">
    <div class="max-w-7xl mx-auto px-4">
        <?php if ($kurullar): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($kurullar as $kurul): ?>
            <div class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 overflow-hidden group card-hover">
                <!-- Header -->
                <div class="gradient-hero p-6 text-white relative">
                    <?php if ($kurul['bekleyen_basvuru'] > 0): ?>
                    <div class="absolute top-4 right-4 bg-red-500 text-white w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm animate-pulse" title="Bekleyen Başvuru">
                        <?php echo $kurul['bekleyen_basvuru']; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mb-4 backdrop-blur-sm">
                        <i data-lucide="shield" class="w-8 h-8 text-secondary"></i>
                    </div>
                    
                    <h3 class="text-xl font-bold mb-2 line-clamp-1"><?php echo guvenlik($kurul['kurul_ad']); ?></h3>
                    <div class="flex items-center text-sm text-gray-300">
                        <i data-lucide="calendar" class="w-4 h-4 mr-1"></i>
                        <span><?php echo date('d.m.Y', strtotime($kurul['olusturma_tarihi'])); ?></span>
                    </div>
                </div>
                
                <!-- Content -->
                <div class="p-6">
                    <p class="text-gray-600 mb-6 line-clamp-3 text-sm leading-relaxed h-[60px]">
                        <?php echo guvenlik($kurul['aciklama'] ?: 'Açıklama bulunmuyor.'); ?>
                    </p>
                    
                    <!-- Stats - Komisyon kaldırıldı, sadece üye sayısı -->
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="bg-gray-50 p-3 rounded-xl text-center">
                            <div class="text-2xl font-bold text-primary"><?php echo $kurul['uye_sayisi'] + ($kurul['baskan_ad'] ? 1 : 0); ?></div>
                            <div class="text-xs text-gray-500">Kurul Üyesi</div>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-xl text-center">
                            <div class="text-2xl font-bold text-primary"><?php echo $kurul['bekleyen_basvuru']; ?></div>
                            <div class="text-xs text-gray-500">Bekleyen İş</div>
                        </div>
                    </div>
                    
                    <!-- Başkan Info -->
                    <?php if ($kurul['baskan_ad']): ?>
                    <div class="flex items-center p-3 bg-blue-50 rounded-xl mb-4">
                        <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center text-white mr-3">
                            <i data-lucide="user" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Kurul Başkanı</div>
                            <div class="font-semibold text-primary text-sm"><?php echo guvenlik($kurul['baskan_ad']); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Action Buttons -->
                    <div class="flex gap-3">
                        <a href="kurul-detay.php?id=<?php echo $kurul['id']; ?>" class="flex-1 py-3 bg-primary text-white rounded-xl font-semibold text-center hover:bg-blue-800 transition flex items-center justify-center space-x-2">
                            <span>Detaylar</span>
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </a>
                        
                        <?php if (isset($_SESSION['uye_id'])): ?>
                        <a href="is-basvuru-formu.php?kurul=<?php echo $kurul['id']; ?>" class="flex-1 py-3 border-2 border-secondary text-secondary rounded-xl font-semibold text-center hover:bg-secondary hover:text-primary transition">
                            Başvur
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Footer Badge -->
                <div class="px-6 py-3 bg-gray-50 border-t border-gray-100 flex items-center justify-between text-sm">
                    <span class="flex items-center text-gray-600">
                        <i data-lucide="lock" class="w-4 h-4 mr-1"></i>
                        Gizli Başvuru
                    </span>
                    <span class="<?php echo $kurul['durum'] == 'AKTIF' ? 'text-green-600' : 'text-red-600'; ?> font-semibold flex items-center">
                        <span class="w-2 h-2 <?php echo $kurul['durum'] == 'AKTIF' ? 'bg-green-500' : 'bg-red-500'; ?> rounded-full mr-2"></span>
                        <?php echo $kurul['durum'] == 'AKTIF' ? 'Aktif' : 'Pasif'; ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-20">
            <div class="w-24 h-24 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-6">
                <i data-lucide="search-x" class="w-12 h-12 text-gray-400"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Kurul Bulunamadı</h3>
            <p class="text-gray-600">Arama kriterlerinize uygun kurul bulunmuyor.</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'footer-modern.php'; ?>