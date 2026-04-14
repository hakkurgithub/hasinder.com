<?php
require_once 'config.php';

if (!isset($_SESSION['uye_id'])) {
    header('Location: giris.php');
    exit;
}

$uye_id = $_SESSION['uye_id'];

// Üye bilgileri
$stmt = $db->prepare("SELECT * FROM uyeler WHERE id = ?");
$stmt->execute([$uye_id]);
$uye = $stmt->fetch();

// Benim ilanlarım
$ilanlarim = $db->prepare("SELECT i.*, k.ad as kategori_ad,
    (SELECT COUNT(*) FROM ilan_resimler WHERE ilan_id = i.id) as resim_sayisi
    FROM ilanlar i 
    LEFT JOIN kategoriler k ON i.kategori_id = k.id 
    WHERE i.uye_id = ? 
    ORDER BY i.created_at DESC");
$ilanlarim->execute([$uye_id]);
$ilanlar = $ilanlarim->fetchAll();

// Benim iş başvurularım (Gizli - sadece kendi görebilir)
$basvurularim = $db->prepare("SELECT b.*, ik.kurul_ad, DATE_FORMAT(b.basvuru_tarihi, '%d.%m.%Y') as tarih
    FROM is_basvurulari b 
    JOIN icra_kurullari ik ON b.kurul_id = ik.id 
    WHERE b.email = ? OR b.id IN (SELECT id FROM is_basvurulari WHERE email = ?)
    ORDER BY b.basvuru_tarihi DESC");
$basvurularim->execute([$uye['email'], $uye['email']]);
$basvurular = $basvurularim->fetchAll();

// Admin mi?
$is_admin = admin();

// Kurul başkanlıklarım (varsa)
$baskanliklar = [];
$kurul_uyelikler = [];
if (!$is_admin) {
    $baskan_stmt = $db->prepare("SELECT * FROM icra_kurullari WHERE baskan_uye_id = ? AND durum = 'AKTIF'");
    $baskan_stmt->execute([$uye_id]);
    $baskanliklar = $baskan_stmt->fetchAll();
    
    $uye_stmt = $db->prepare("SELECT ku.*, ik.kurul_ad FROM kurul_uyeleri ku JOIN icra_kurullari ik ON ku.kurul_id = ik.id WHERE ku.uye_id = ?");
    $uye_stmt->execute([$uye_id]);
    $kurul_uyelikler = $uye_stmt->fetchAll();
}

$sayfa_baslik = 'Dashboard - ' . $uye['ad'];
include 'header-modern.php';
?>

<section class="gradient-hero text-white py-12">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center text-3xl font-bold backdrop-blur-sm">
                    <?php echo substr($uye['ad'], 0, 1); ?>
                </div>
                <div>
                    <h1 class="text-3xl font-bold mb-1">Hoş Geldiniz, <?php echo guvenlik($uye['ad']); ?></h1>
                    <p class="text-gray-300 flex items-center">
                        <i data-lucide="shield" class="w-4 h-4 mr-2"></i>
                        <?php echo $is_admin ? 'Sistem Yöneticisi' : 'Üye'; ?>
                    </p>
                </div>
            </div>
            
            <div class="hidden md:flex gap-3">
                <a href="ilan-ver.php" class="px-6 py-3 gradient-gold text-primary rounded-xl font-bold hover:shadow-lg transition flex items-center">
                    <i data-lucide="plus" class="w-5 h-5 mr-2"></i>
                    Yeni İlan
                </a>
                <a href="is-basvuru-formu.php" class="px-6 py-3 bg-white/10 border border-white/30 rounded-xl font-bold hover:bg-white/20 transition flex items-center">
                    <i data-lucide="briefcase" class="w-5 h-5 mr-2"></i>
                    İş Başvurusu
                </a>
            </div>
        </div>
    </div>
</section>

<section class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4">
        <!-- Hızlı Erişim Kartları -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-12">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 text-center card-hover">
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mx-auto mb-3 text-blue-600">
                    <i data-lucide="file-text" class="w-6 h-6"></i>
                </div>
                <div class="text-2xl font-bold text-primary mb-1"><?php echo count($ilanlar); ?></div>
                <div class="text-sm text-gray-500">İlanım</div>
            </div>
            
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 text-center card-hover">
                <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center mx-auto mb-3 text-orange-600">
                    <i data-lucide="send" class="w-6 h-6"></i>
                </div>
                <div class="text-2xl font-bold text-primary mb-1"><?php echo count($basvurular); ?></div>
                <div class="text-sm text-gray-500">İş Başvurum</div>
            </div>
            
            <?php if (!$is_admin): ?>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 text-center card-hover">
                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mx-auto mb-3 text-purple-600">
                    <i data-lucide="crown" class="w-6 h-6"></i>
                </div>
                <div class="text-2xl font-bold text-primary mb-1"><?php echo count($baskanliklar); ?></div>
                <div class="text-sm text-gray-500">Başkanlığım</div>
            </div>
            
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 text-center card-hover">
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mx-auto mb-3 text-green-600">
                    <i data-lucide="users" class="w-6 h-6"></i>
                </div>
                <div class="text-2xl font-bold text-primary mb-1"><?php echo count($kurul_uyelikler); ?></div>
                <div class="text-sm text-gray-500">Kurul Üyeliğim</div>
            </div>
            <?php else: ?>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 text-center card-hover">
                <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center mx-auto mb-3 text-red-600">
                    <i data-lucide="settings" class="w-6 h-6"></i>
                </div>
                <div class="text-2xl font-bold text-primary mb-1">Admin</div>
                <div class="text-sm text-gray-500">Panel Erişimi</div>
            </div>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- İlanlarım -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                    <h2 class="text-xl font-bold text-primary flex items-center">
                        <i data-lucide="file-text" class="w-5 h-5 mr-2"></i>
                        İlanlarım
                    </h2>
                    <a href="ilan-ver.php" class="text-sm text-primary font-semibold hover:underline">Yeni İlan +</a>
                </div>
                
                <?php if ($ilanlar): ?>
                <div class="divide-y divide-gray-100 max-h-[500px] overflow-y-auto">
                    <?php foreach ($ilanlar as $ilan): ?>
                    <div class="p-4 hover:bg-gray-50 transition flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden flex-shrink-0">
                                <?php if ($ilan['resim'] && file_exists($ilan['resim'])): ?>
                                <img src="<?php echo $ilan['resim']; ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                <i data-lucide="image" class="w-6 h-6 text-gray-400"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800 line-clamp-1"><?php echo guvenlik($ilan['baslik']); ?></h4>
                                <div class="flex items-center text-sm text-gray-500 mt-1">
                                    <span class="px-2 py-0.5 bg-gray-100 rounded text-xs mr-2"><?php echo guvenlik($ilan['kategori_ad']); ?></span>
                                    <span class="<?php echo $ilan['durum'] == 'AKTIF' ? 'text-green-600' : 'text-orange-600'; ?> font-medium text-xs">
                                        <?php echo $ilan['durum']; ?>
                                    </span>
                                </div>
                                <div class="text-primary font-bold text-sm mt-1"><?php echo para($ilan['fiyat']); ?></div>
                            </div>
                        </div>
                        <div class="flex flex-col gap-2">
                            <a href="ilan-duzenle.php?id=<?php echo $ilan['id']; ?>" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg" title="Düzenle">
                                <i data-lucide="edit" class="w-4 h-4"></i>
                            </a>
                            <a href="ilan.php?id=<?php echo $ilan['id']; ?>" target="_blank" class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg" title="Görüntüle">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="p-12 text-center text-gray-500">
                    <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                    <p>Henüz ilanınız yok.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- İş Başvurularım (Gizli - Sadece Kendi Görür) -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                    <h2 class="text-xl font-bold text-primary flex items-center">
                        <i data-lucide="send" class="w-5 h-5 mr-2"></i>
                        İş Başvurularım
                        <span class="ml-2 px-2 py-1 bg-gray-100 text-gray-600 rounded text-xs"><?php echo count($basvurular); ?></span>
                    </h2>
                    <a href="is-basvuru-formu.php" class="text-sm text-primary font-semibold hover:underline">Yeni Başvuru +</a>
                </div>
                
                <?php if ($basvurular): ?>
                <div class="divide-y divide-gray-100 max-h-[500px] overflow-y-auto">
                    <?php foreach ($basvurular as $basvuru): ?>
                    <div class="p-4 hover:bg-gray-50 transition">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <h4 class="font-semibold text-gray-800"><?php echo guvenlik($basvuru['kurul_ad']); ?></h4>
                                <div class="text-sm text-gray-500"><?php echo $basvuru['tarih']; ?></div>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold 
                                <?php 
                                switch($basvuru['durum']) {
                                    case 'BEKLEMEDE': echo 'bg-yellow-100 text-yellow-700'; break;
                                    case 'INCELEMEDE': echo 'bg-blue-100 text-blue-700'; break;
                                    case 'TAMAMLANDI': echo 'bg-green-100 text-green-700'; break;
                                    case 'REDDEDILDI': echo 'bg-red-100 text-red-700'; break;
                                }
                                ?>">
                                <?php echo str_replace('_', ' ', $basvuru['durum']); ?>
                            </span>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg mb-2">
                            <div class="text-xs text-gray-500 mb-1">İş Tanımı:</div>
                            <p class="text-sm text-gray-700 line-clamp-2"><?php echo guvenlik($basvuru['is_tanimi']); ?></p>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-primary font-bold text-sm"><?php echo para($basvuru['is_tutari']); ?></span>
                            <a href="admin/is-basvuru-detay.php?id=<?php echo $basvuru['id']; ?>" class="text-sm text-blue-600 hover:underline">Detaylar →</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="p-12 text-center text-gray-500">
                    <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                    <p>Henüz iş başvurunuz yok.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Kurul Başkanlıklarım / Üyeliklerim -->
        <?php if (!$is_admin && (count($baskanliklar) > 0 || count($kurul_uyelikler) > 0)): ?>
        <div class="mt-8">
            <h2 class="text-xl font-bold text-primary mb-6">Kurul Yetkilerim</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php foreach ($baskanliklar as $kurul): ?>
                <div class="bg-gradient-to-br from-primary to-blue-800 text-white rounded-2xl p-6 shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <i data-lucide="crown" class="w-6 h-6 text-secondary mr-3"></i>
                            <div>
                                <div class="text-xs text-blue-200 uppercase tracking-wider">Başkanlık</div>
                                <h3 class="font-bold text-lg"><?php echo guvenlik($kurul['kurul_ad']); ?></h3>
                            </div>
                        </div>
                        <span class="px-3 py-1 bg-white/20 rounded-full text-xs">Yönetim Paneli</span>
                    </div>
                    <p class="text-blue-100 text-sm mb-4"><?php echo guvenlik(substr($kurul['aciklama'], 0, 100)); ?>...</p>
                    <a href="kurul-detay.php?id=<?php echo $kurul['id']; ?>" class="inline-flex items-center px-4 py-2 bg-white text-primary rounded-lg font-semibold text-sm hover:bg-gray-100 transition">
                        Başvuruları Yönet
                        <i data-lucide="arrow-right" class="w-4 h-4 ml-2"></i>
                    </a>
                </div>
                <?php endforeach; ?>
                
                <?php foreach ($kurul_uyelikler as $uyelik): ?>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                    <div class="flex items-center mb-4">
                        <i data-lucide="users" class="w-6 h-6 text-primary mr-3"></i>
                        <div>
                            <div class="text-xs text-gray-500 uppercase tracking-wider">Kurul Üyesi</div>
                            <h3 class="font-bold text-lg text-gray-800"><?php echo guvenlik($uyelik['kurul_ad']); ?></h3>
                        </div>
                    </div>
                    <a href="kurul-detay.php?id=<?php echo $uyelik['kurul_id']; ?>" class="inline-flex items-center text-primary font-semibold text-sm hover:underline">
                        Başvuruları Görüntüle
                        <i data-lucide="arrow-right" class="w-4 h-4 ml-1"></i>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'footer-modern.php'; ?>