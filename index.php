<?php
// index-modern.php - Modern, akıllı ana sayfa
require_once 'config.php';

$sayfa_baslik = 'Hasinder - B2B Ticaret ve İlan Platformu';

// Verileri çek
$kategoriler = $db->query("SELECT * FROM kategoriler ORDER BY ad LIMIT 10")->fetchAll();

$one_cikan_ilanlar = $db->query("
    SELECT i.*, u.ad as uye_ad, k.ad as kategori_ad,
           (SELECT COUNT(*) FROM ilan_resimler WHERE ilan_id = i.id) as ek_resim_sayisi
    FROM ilanlar i
    LEFT JOIN uyeler u ON i.uye_id = u.id
    LEFT JOIN kategoriler k ON i.kategori_id = k.id
    WHERE i.durum = 'AKTIF'
    ORDER BY i.created_at DESC
    LIMIT 6
")->fetchAll();

// Kurullar - Komisyon oranı kaldırıldı, sadece gerekli alanlar çekildi
$kurullar = $db->query("
    SELECT ik.id, ik.kurul_ad, ik.aciklama, ik.durum, ik.olusturma_tarihi, ik.baskan_uye_id,
           u.ad as baskan_ad,
           (SELECT COUNT(*) FROM is_basvurulari WHERE kurul_id = ik.id AND durum = 'BEKLEMEDE') as bekleyen_basvuru,
           (SELECT COUNT(*) FROM kurul_uyeleri WHERE kurul_id = ik.id) as uye_sayisi
    FROM icra_kurullari ik
    LEFT JOIN uyeler u ON ik.baskan_uye_id = u.id
    WHERE ik.durum = 'AKTIF'
    ORDER BY ik.olusturma_tarihi DESC
    LIMIT 4
")->fetchAll();

$istatistikler = [
    'uye' => $db->query("SELECT COUNT(*) FROM uyeler WHERE durum = 'AKTIF'")->fetchColumn(),
    'ilan' => $db->query("SELECT COUNT(*) FROM ilanlar WHERE durum = 'AKTIF'")->fetchColumn(),
    'kurul' => $db->query("SELECT COUNT(*) FROM icra_kurullari WHERE durum = 'AKTIF'")->fetchColumn(),
    'basvuru' => $db->query("SELECT COUNT(*) FROM is_basvurulari")->fetchColumn()
];

include 'header-modern.php';
?>

<!-- Hero Section -->
<section class="relative min-h-[85vh] flex items-center justify-center overflow-hidden gradient-hero">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 40px 40px;"></div>
    </div>
    
    <!-- Floating Elements -->
    <div class="absolute top-20 left-10 w-20 h-20 bg-secondary/30 rounded-full blur-xl animate-float"></div>
    <div class="absolute bottom-20 right-10 w-32 h-32 bg-white/10 rounded-full blur-2xl animate-float" style="animation-delay: 2s;"></div>
    
    <div class="relative z-10 max-w-6xl mx-auto px-4 text-center text-white">
        <div class="inline-flex items-center px-4 py-2 bg-white/10 rounded-full mb-8 border border-white/20 backdrop-blur-sm">
            <span class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></span>
            <span class="text-sm font-medium"><?php echo $istatistikler['uye']; ?> Aktif Üye • <?php echo $istatistikler['ilan']; ?> İlan</span>
        </div>
        
        <h1 class="text-4xl md:text-6xl font-bold mb-6 leading-tight">
            B2B Ticaretin<br>
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-secondary to-yellow-200">Yeni Nesil</span> Adresi
        </h1>
        
        <p class="text-xl text-gray-300 mb-12 max-w-2xl mx-auto">
            İstanbul Hatay Sanayici ve İş İnsanları Platformu. 
            Alım-satım ilanları, iş birlikleri ve profesyonel komisyon hizmetleri.
        </p>
        
        <!-- Smart Search Box -->
        <div class="max-w-3xl mx-auto">
            <form action="ilanlar.php" method="GET" class="relative group">
                <div class="absolute inset-0 bg-secondary/20 rounded-2xl blur-xl group-hover:blur-2xl transition"></div>
                <div class="relative flex flex-col md:flex-row bg-white rounded-2xl p-2 shadow-2xl">
                    <div class="flex-1 flex items-center px-4 py-3 border-b md:border-b-0 md:border-r border-gray-200">
                        <i data-lucide="search" class="w-6 h-6 text-gray-400 mr-3"></i>
                        <input type="text" name="arama" placeholder="Ne arıyorsunuz? (örn: güneş paneli, lojistik...)" 
                               class="w-full bg-transparent outline-none text-gray-800 placeholder-gray-400">
                    </div>
                    
                    <div class="flex items-center px-4 py-3 border-b md:border-b-0 md:border-r border-gray-200 min-w-[200px]">
                        <i data-lucide="map-pin" class="w-6 h-6 text-gray-400 mr-3"></i>
                        <select name="il" class="w-full bg-transparent outline-none text-gray-800 cursor-pointer">
                            <option value="">Tüm Türkiye</option>
                            <option value="İstanbul">İstanbul</option>
                            <option value="Ankara">Ankara</option>
                            <option value="İzmir">İzmir</option>
                            <option value="Hatay">Hatay</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="gradient-gold text-primary font-bold px-8 py-4 rounded-xl hover:shadow-lg transition transform hover:scale-105 flex items-center justify-center space-x-2">
                        <i data-lucide="search" class="w-5 h-5"></i>
                        <span>Ara</span>
                    </button>
                </div>
            </form>
            
            <!-- Quick Tags -->
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <span class="text-sm text-gray-400">Popüler:</span>
                <a href="ilanlar.php?kategori=1" class="px-4 py-1.5 bg-white/10 hover:bg-white/20 rounded-full text-sm transition border border-white/20">Gıda</a>
                <a href="ilanlar.php?kategori=2" class="px-4 py-1.5 bg-white/10 hover:bg-white/20 rounded-full text-sm transition border border-white/20">Tekstil</a>
                <a href="ilanlar.php?arama=lojistik" class="px-4 py-1.5 bg-white/10 hover:bg-white/20 rounded-full text-sm transition border border-white/20">Lojistik</a>
                <a href="ilanlar.php?arama=emlak" class="px-4 py-1.5 bg-white/10 hover:bg-white/20 rounded-full text-sm transition border border-white/20">Emlak</a>
            </div>
        </div>
    </div>
    
    <!-- Scroll Indicator -->
    <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce">
        <i data-lucide="chevron-down" class="w-8 h-8 text-white/50"></i>
    </div>
</section>

<!-- Categories Section -->
<section class="py-8 bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-center space-x-4 overflow-x-auto no-scrollbar pb-4">
            <div class="flex-shrink-0">
                <h3 class="font-bold text-gray-800 mr-4">Kategoriler:</h3>
            </div>
            
            <?php foreach ($kategoriler as $kat): ?>
            <a href="ilanlar.php?kategori=<?php echo $kat['id']; ?>" 
               class="flex-shrink-0 group flex items-center space-x-2 px-6 py-3 bg-gray-50 hover:bg-primary hover:text-white rounded-full transition-all border border-gray-200 hover:border-primary">
                <i data-lucide="tag" class="w-4 h-4"></i>
                <span class="font-medium whitespace-nowrap"><?php echo guvenlik($kat['ad']); ?></span>
            </a>
            <?php endforeach; ?>
            
            <a href="ilanlar.php" class="flex-shrink-0 flex items-center space-x-2 px-6 py-3 text-primary font-medium hover:underline">
                <span>Tümünü Gör</span>
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
        </div>
    </div>
</section>

<!-- Featured Listings -->
<section class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between items-end mb-10">
            <div>
                <h2 class="text-3xl font-bold text-primary mb-2">Öne Çıkan İlanlar</h2>
                <p class="text-gray-600">Son eklenen fırsatları keşfedin</p>
            </div>
            <a href="ilanlar.php" class="hidden md:flex items-center space-x-2 text-primary font-semibold hover:underline">
                <span>Tüm İlanlar</span>
                <i data-lucide="arrow-right" class="w-5 h-5"></i>
            </a>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($one_cikan_ilanlar as $ilan): 
                $ozellikler = json_decode($ilan['ozellikler'] ?? '[]', true);
            ?>
            <div class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 card-hover border border-gray-100 group">
                <!-- Image -->
                <div class="relative h-56 overflow-hidden">
                    <?php if (!empty($ilan['resim']) && file_exists($ilan['resim'])): ?>
                        <img src="<?php echo guvenlik($ilan['resim']); ?>" 
                             alt="<?php echo guvenlik($ilan['baslik']); ?>"
                             class="w-full h-full object-cover transform group-hover:scale-110 transition duration-500">
                    <?php else: ?>
                        <div class="w-full h-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                            <i data-lucide="image" class="w-16 h-16 text-gray-300"></i>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Badges -->
                    <div class="absolute top-4 left-4 flex space-x-2">
                        <span class="px-3 py-1 bg-primary/90 text-white text-xs font-bold rounded-full backdrop-blur-sm">
                            <?php echo guvenlik($ilan['kategori_ad'] ?? 'Genel'); ?>
                        </span>
                        <?php if ($ilan['ek_resim_sayisi'] > 0): ?>
                        <span class="px-3 py-1 bg-black/50 text-white text-xs rounded-full backdrop-blur-sm flex items-center">
                            <i data-lucide="image" class="w-3 h-3 mr-1"></i>
                            <?php echo $ilan['ek_resim_sayisi']; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Price Badge -->
                    <div class="absolute bottom-4 right-4">
                        <div class="gradient-gold text-primary px-4 py-2 rounded-xl font-bold shadow-lg">
                            <?php echo para($ilan['fiyat']); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Content -->
                <div class="p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-2 line-clamp-2 group-hover:text-primary transition">
                        <?php echo guvenlik($ilan['baslik']); ?>
                    </h3>
                    
                    <p class="text-gray-500 text-sm mb-4 line-clamp-2">
                        <?php echo guvenlik(mb_substr($ilan['aciklama'], 0, 100)) . '...'; ?>
                    </p>
                    
                    <!-- Features -->
                    <?php if (!empty($ozellikler)): ?>
                    <div class="flex flex-wrap gap-2 mb-4">
                        <?php foreach (array_slice($ozellikler, 0, 3) as $oz): ?>
                        <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-md">
                            <?php echo guvenlik($oz); ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Footer -->
                    <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                        <div class="flex items-center text-sm text-gray-500">
                            <i data-lucide="user" class="w-4 h-4 mr-1"></i>
                            <span><?php echo guvenlik($ilan['uye_ad'] ?? 'Admin'); ?></span>
                        </div>
                        <a href="ilan.php?id=<?php echo $ilan['id']; ?>" 
                           class="flex items-center space-x-1 text-primary font-semibold hover:underline">
                            <span>Detaylar</span>
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="mt-8 text-center md:hidden">
            <a href="ilanlar.php" class="inline-flex items-center space-x-2 text-primary font-semibold">
                <span>Tüm İlanları Gör</span>
                <i data-lucide="arrow-right" class="w-5 h-5"></i>
            </a>
        </div>
    </div>
</section>

<!-- Kurullar Section - KOMİSYON KALDIRILDI -->
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-primary mb-4">İcra Kurulları</h2>
            <p class="text-gray-600 max-w-2xl mx-auto">
                Profesyonel komisyonlarımız iş süreçlerinizi güvence altına alıyor. 
                Hukuk, mali müşavirlik ve sektörel uzmanlık hizmetleri.
            </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
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
                    <p class="text-sm text-gray-300 line-clamp-2"><?php echo guvenlik($kurul['aciklama'] ?? ''); ?></p>
                </div>
                
                <!-- Content - KOMİSYON KALDIRILDI -->
                <div class="p-6">
                    <!-- Sadece Üye Sayısı -->
                    <div class="flex items-center justify-center p-3 bg-gray-50 rounded-xl mb-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-primary">
                                <?php echo ($kurul['uye_sayisi'] ?? 0) + ($kurul['baskan_ad'] ? 1 : 0); ?>
                            </div>
                            <div class="text-xs text-gray-500">Aktif Kurul Üyesi</div>
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
                    
                    <!-- Button -->
                    <a href="kurul-detay.php?id=<?php echo $kurul['id']; ?>" class="block w-full py-3 bg-primary text-white rounded-xl font-semibold text-center hover:bg-blue-800 transition">
                        İncele →
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="py-16 gradient-hero text-white relative overflow-hidden">
    <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.05\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-20"></div>
    
    <div class="max-w-7xl mx-auto px-4 relative z-10">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            <div class="p-6">
                <div class="text-4xl md:text-5xl font-bold mb-2 text-secondary"><?php echo $istatistikler['uye']; ?>+</div>
                <div class="text-gray-300">Aktif Üye</div>
            </div>
            <div class="p-6">
                <div class="text-4xl md:text-5xl font-bold mb-2 text-secondary"><?php echo $istatistikler['ilan']; ?>+</div>
                <div class="text-gray-300">İlan</div>
            </div>
            <div class="p-6">
                <div class="text-4xl md:text-5xl font-bold mb-2 text-secondary"><?php echo $istatistikler['kurul']; ?></div>
                <div class="text-gray-300">İcra Kurulu</div>
            </div>
            <div class="p-6">
                <div class="text-4xl md:text-5xl font-bold mb-2 text-secondary"><?php echo $istatistikler['basvuru']; ?>+</div>
                <div class="text-gray-300">İş Başvurusu</div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-20 bg-gray-50">
    <div class="max-w-5xl mx-auto px-4 text-center">
        <h2 class="text-3xl md:text-4xl font-bold text-primary mb-6">Hemen İşlemeye Başlayın</h2>
        <p class="text-lg text-gray-600 mb-10 max-w-2xl mx-auto">
            Platformumuza katılın, ilanlarınızı yayınlayın veya profesyonel icra kurullarımızdan hizmet alın.
        </p>
        
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <a href="kayit.php" class="px-8 py-4 gradient-gold text-primary rounded-xl font-bold text-lg hover:shadow-lg transition transform hover:scale-105 flex items-center justify-center space-x-2">
                <i data-lucide="user-plus" class="w-5 h-5"></i>
                <span>Ücretsiz Üye Ol</span>
            </a>
            <a href="ilan-ver.php" class="px-8 py-4 bg-primary text-white rounded-xl font-bold text-lg hover:bg-blue-800 transition flex items-center justify-center space-x-2">
                <i data-lucide="plus-circle" class="w-5 h-5"></i>
                <span>İlan Ver</span>
            </a>
        </div>
    </div>
</section>

<?php include 'footer-modern.php'; ?>