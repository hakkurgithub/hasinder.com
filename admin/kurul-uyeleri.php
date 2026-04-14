<?php
require_once '../config.php';

if (!admin()) {
    header('Location: /giris.php');
    exit;
}

$kurul_id = isset($_GET['kurul_id']) ? (int)$_GET['kurul_id'] : 0;

if (!$kurul_id) {
    header('Location: /admin/icra-kurullari.php');
    exit;
}

// Kurul bilgisi
$kurul = $db->prepare("SELECT * FROM icra_kurullari WHERE id = ?");
$kurul->execute([$kurul_id]);
$kurul = $kurul->fetch();

if (!$kurul) {
    header('Location: /admin/icra-kurullari.php');
    exit;
}

// Üye ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['uye_ekle'])) {
    $uye_id = (int)$_POST['uye_id'];
    $yetki = guvenlik($_POST['yetki'] ?? 'UYE');
    
    // Yetki kontrolü
    $gecerli_yetkiler = ['UYE', 'YARDIMCI'];
    if (!in_array($yetki, $gecerli_yetkiler)) {
        $yetki = 'UYE';
    }
    
    try {
        $stmt = $db->prepare("INSERT INTO kurul_uyeleri (kurul_id, uye_id, yetki, atama_tarihi) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$kurul_id, $uye_id, $yetki]);
        bildirim('Üye başarıyla eklendi.', 'success');
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            bildirim('Bu üye zaten kurulda mevcut.', 'danger');
        } else {
            bildirim('Hata: ' . $e->getMessage(), 'danger');
        }
    }
    header("Location: kurul-uyeleri.php?kurul_id=$kurul_id");
    exit;
}

// Üye çıkarma işlemi
if (isset($_GET['sil'])) {
    $uye_id = (int)$_GET['sil'];
    $stmt = $db->prepare("DELETE FROM kurul_uyeleri WHERE kurul_id = ? AND uye_id = ?");
    $stmt->execute([$kurul_id, $uye_id]);
    bildirim('Üye kuruldan çıkarıldı.', 'success');
    header("Location: kurul-uyeleri.php?kurul_id=$kurul_id");
    exit;
}

// Mevcut üyeleri çek - atama_tarihi explicitly eklendi
$uyeler = $db->prepare("
    SELECT ku.kurul_id, ku.uye_id, ku.yetki, ku.atama_tarihi, 
           u.ad, u.email, u.telefon 
    FROM kurul_uyeleri ku 
    JOIN uyeler u ON ku.uye_id = u.id 
    WHERE ku.kurul_id = ? 
    ORDER BY ku.yetki DESC, u.ad ASC
");
$uyeler->execute([$kurul_id]);
$kurul_uyeleri = $uyeler->fetchAll();

// Eklenebilecek üyeleri çek (kurulda olmayan aktif üyeler)
$eklenebilir = $db->prepare("
    SELECT id, ad, email FROM uyeler 
    WHERE durum = 'AKTIF' 
    AND is_admin = 0 
    AND id NOT IN (SELECT uye_id FROM kurul_uyeleri WHERE kurul_id = ?)
    AND id != ?
    ORDER BY ad ASC
");
$eklenebilir->execute([$kurul_id, $kurul['baskan_uye_id'] ?? 0]);
$eklenebilir_uyeler = $eklenebilir->fetchAll();

// Başkan bilgisi çek (SQL Injection düzeltildi)
$baskan_ad = '<span class="text-red-500">Atanmamış</span>';
if (!empty($kurul['baskan_uye_id'])) {
    $baskan_stmt = $db->prepare("SELECT ad FROM uyeler WHERE id = ?");
    $baskan_stmt->execute([$kurul['baskan_uye_id']]);
    $baskan = $baskan_stmt->fetch();
    if ($baskan) {
        $baskan_ad = guvenlik($baskan['ad']);
    }
}

$sayfa_baslik = guvenlik($kurul['kurul_ad']) . ' - Üye Yönetimi';
include '../header-modern.php';
?>

<section class="gradient-hero text-white py-12">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-center text-sm text-gray-300 mb-4">
            <a href="index.php" class="hover:text-white">Admin Panel</a>
            <i data-lucide="chevron-right" class="w-4 h-4 mx-2"></i>
            <a href="icra-kurullari.php" class="hover:text-white">İcra Kurulları</a>
            <i data-lucide="chevron-right" class="w-4 h-4 mx-2"></i>
            <span>Üye Yönetimi</span>
        </div>
        
        <h1 class="text-3xl font-bold mb-2"><?php echo guvenlik($kurul['kurul_ad']); ?></h1>
        <p class="text-gray-300">Kurul üyelerini yönetin ve yetkilerini düzenleyin</p>
    </div>
</section>

<section class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Sol Panel - Üye Ekleme -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sticky top-24">
                    <h2 class="text-xl font-bold text-primary mb-6 flex items-center">
                        <i data-lucide="user-plus" class="w-5 h-5 mr-2"></i>
                        Yeni Üye Ekle
                    </h2>
                    
                    <?php if ($eklenebilir_uyeler): ?>
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Üye Seçin</label>
                            <select name="uye_id" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-secondary outline-none">
                                <option value="">Üye seçin...</option>
                                <?php foreach ($eklenebilir_uyeler as $u): ?>
                                <option value="<?php echo (int)$u['id']; ?>">
                                    <?php echo guvenlik($u['ad']); ?> (<?php echo guvenlik($u['email']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Yetki</label>
                            <select name="yetki" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-secondary outline-none">
                                <option value="UYE">Kurul Üyesi</option>
                                <option value="YARDIMCI">Yardımcı Başkan</option>
                            </select>
                        </div>
                        
                        <button type="submit" name="uye_ekle" class="w-full py-3 gradient-gold text-primary rounded-xl font-bold hover:shadow-lg transition flex items-center justify-center">
                            <i data-lucide="plus" class="w-5 h-5 mr-2"></i>
                            Üyeyi Ekle
                        </button>
                    </form>
                    <?php else: ?>
                    <div class="text-center py-8 bg-gray-50 rounded-xl">
                        <i data-lucide="users" class="w-12 h-12 text-gray-400 mx-auto mb-3"></i>
                        <p class="text-gray-600">Eklenebilecek aktif üye kalmadı.</p>
                        <p class="text-sm text-gray-500 mt-2">Tüm üyeler bu kurulda veya başka kurullarda.</p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-8 pt-6 border-t border-gray-100">
                        <h3 class="font-semibold text-gray-800 mb-3">Kurul Bilgileri</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Başkan:</span>
                                <span class="font-medium"><?php echo $baskan_ad; ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Üye Sayısı:</span>
                                <span class="font-medium"><?php echo count($kurul_uyeleri) + ($kurul['baskan_uye_id'] ? 1 : 0); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sağ Panel - Mevcut Üyeler -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                        <h2 class="text-xl font-bold text-primary flex items-center">
                            <i data-lucide="users" class="w-5 h-5 mr-2"></i>
                            Mevcut Üyeler
                        </h2>
                        <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-sm">
                            <?php echo count($kurul_uyeleri) + ($kurul['baskan_uye_id'] ? 1 : 0); ?> Üye
                        </span>
                    </div>
                    
                    <!-- Başkan -->
                    <?php if (!empty($kurul['baskan_uye_id'])): 
                        $baskan_stmt = $db->prepare("SELECT * FROM uyeler WHERE id = ?");
                        $baskan_stmt->execute([$kurul['baskan_uye_id']]);
                        $baskan_detay = $baskan_stmt->fetch();
                        if ($baskan_detay):
                    ?>
                    <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-yellow-50 to-transparent">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="w-14 h-14 bg-gradient-to-br from-secondary to-yellow-400 rounded-full flex items-center justify-center text-primary font-bold text-xl">
                                    <?php echo substr(guvenlik($baskan_detay['ad']), 0, 1); ?>
                                </div>
                                <div>
                                    <div class="flex items-center">
                                        <h3 class="font-bold text-lg text-gray-800"><?php echo guvenlik($baskan_detay['ad']); ?></h3>
                                        <span class="ml-3 px-3 py-1 bg-secondary text-primary text-xs font-bold rounded-full">BAŞKAN</span>
                                    </div>
                                    <div class="text-sm text-gray-500"><?php echo guvenlik($baskan_detay['email']); ?></div>
                                </div>
                            </div>
                            <a href="icra-kurulu-duzenle.php?id=<?php echo (int)$kurul_id; ?>" class="px-4 py-2 text-sm text-blue-600 hover:bg-blue-50 rounded-lg transition">
                                Değiştir
                            </a>
                        </div>
                    </div>
                    <?php endif; endif; ?>
                    
                    <!-- Diğer Üyeler -->
                    <?php if ($kurul_uyeleri): ?>
                    <div class="divide-y divide-gray-100">
                        <?php foreach ($kurul_uyeleri as $uye): ?>
                        <div class="p-6 flex items-center justify-between hover:bg-gray-50 transition">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 bg-primary rounded-full flex items-center justify-center text-white font-bold">
                                    <?php echo substr(guvenlik($uye['ad']), 0, 1); ?>
                                </div>
                                <div>
                                    <div class="flex items-center">
                                        <h4 class="font-semibold text-gray-800"><?php echo guvenlik($uye['ad']); ?></h4>
                                        <?php if ($uye['yetki'] == 'YARDIMCI'): ?>
                                        <span class="ml-2 px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded">Yardımcı</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-sm text-gray-500"><?php echo guvenlik($uye['email']); ?></div>
                                    <div class="text-xs text-gray-400 mt-1">
                                        <?php if (!empty($uye['atama_tarihi'])): ?>
                                        Üyelik: <?php echo date('d.m.Y', strtotime($uye['atama_tarihi'])); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-2">
                                <form method="POST" action="kurul-uye-yetki.php" class="mr-2">
                                    <input type="hidden" name="kurul_id" value="<?php echo (int)$kurul_id; ?>">
                                    <input type="hidden" name="uye_id" value="<?php echo (int)$uye['uye_id']; ?>">
                                    <select name="yetki" onchange="this.form.submit()" class="text-sm px-3 py-1 border border-gray-200 rounded-lg focus:ring-2 focus:ring-secondary outline-none">
                                        <option value="UYE" <?php echo $uye['yetki'] == 'UYE' ? 'selected' : ''; ?>>Üye</option>
                                        <option value="YARDIMCI" <?php echo $uye['yetki'] == 'YARDIMCI' ? 'selected' : ''; ?>>Yardımcı</option>
                                    </select>
                                </form>
                                
                                <a href="?kurul_id=<?php echo (int)$kurul_id; ?>&sil=<?php echo (int)$uye['uye_id']; ?>" 
                                   onclick="return confirm('Bu üyeyi kuruldan çıkarmak istediğinize emin misiniz?')"
                                   class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" title="Üyeyi Çıkar">
                                    <i data-lucide="user-x" class="w-5 h-5"></i>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="p-12 text-center text-gray-500">
                        <i data-lucide="users" class="w-16 h-16 text-gray-300 mx-auto mb-4"></i>
                        <p>Henüz kurul üyesi yok.</p>
                        <p class="text-sm mt-2">Sol panelden yeni üyeler ekleyebilirsiniz.</p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Yetki Açıklamaları -->
                <div class="mt-6 bg-blue-50 border border-blue-100 rounded-xl p-6">
                    <h4 class="font-bold text-blue-800 mb-3 flex items-center">
                        <i data-lucide="info" class="w-5 h-5 mr-2"></i>
                        Yetki Seviyeleri
                    </h4>
                    <div class="space-y-2 text-sm text-blue-700">
                        <div class="flex items-start">
                            <span class="font-bold w-24">Başkan:</span>
                            <span>Tüm başvuruları görüntüleyebilir, düzenleyebilir ve karar verebilir.</span>
                        </div>
                        <div class="flex items-start">
                            <span class="font-bold w-24">Yardımcı:</span>
                            <span>Başkan adına başvuruları yönetebilir, ancak son kararı başkan verir.</span>
                        </div>
                        <div class="flex items-start">
                            <span class="font-bold w-24">Üye:</span>
                            <span>Başvuruları görüntüleyebilir ve yorum yapabilir.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../footer-modern.php'; ?>