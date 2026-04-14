<?php
require_once '../config.php';

if (!yetkili()) {
    bildirim('Borsa paneline erişmek için giriş yapın.', 'danger');
    header('Location: /giris.php');
    exit;
}

// Borsa verileri (statik örnek - ileride DB'den çekilebilir)
$borsa_verileri = [
    ['urun' => 'Domates Salçası',   'fiyat' => 2400.00, 'degisim' => '+5.2%',  'degisim_pozitif' => true,  'adet' => 150,  'kategori' => 'Gıda'],
    ['urun' => 'Tekstil Kumaş',     'fiyat' => 45.50,   'degisim' => '-2.1%',  'degisim_pozitif' => false, 'adet' => 2000, 'kategori' => 'Tekstil'],
    ['urun' => 'İnşaat Demiri',     'fiyat' => 18500.00,'degisim' => '+1.8%',  'degisim_pozitif' => true,  'adet' => 500,  'kategori' => 'İnşaat'],
    ['urun' => 'Kimyasal Hammadde', 'fiyat' => 3200.00, 'degisim' => '-0.5%',  'degisim_pozitif' => false, 'adet' => 300,  'kategori' => 'Kimya'],
    ['urun' => 'Elektronik Parça',  'fiyat' => 850.00,  'degisim' => '+3.4%',  'degisim_pozitif' => true,  'adet' => 1200, 'kategori' => 'Elektronik'],
    ['urun' => 'Buğday',            'fiyat' => 4200.00, 'degisim' => '+0.9%',  'degisim_pozitif' => true,  'adet' => 5000, 'kategori' => 'Ziraat'],
    ['urun' => 'Plastik Granül',    'fiyat' => 1750.00, 'degisim' => '-1.2%',  'degisim_pozitif' => false, 'adet' => 800,  'kategori' => 'Plastik'],
    ['urun' => 'Mobilya Kaplaması', 'fiyat' => 620.00,  'degisim' => '+2.1%',  'degisim_pozitif' => true,  'adet' => 450,  'kategori' => 'Mobilya'],
];

// Aktif ilanlardan istatistik
$stats = [
    'aktif_ilan'    => $db->query("SELECT COUNT(*) FROM ilanlar WHERE durum='AKTIF'")->fetchColumn(),
    'bekleyen_ilan' => $db->query("SELECT COUNT(*) FROM ilanlar WHERE durum='BEKLEMEDE'")->fetchColumn(),
    'bugun'         => $db->query("SELECT COUNT(*) FROM ilanlar WHERE DATE(created_at)=CURDATE()")->fetchColumn(),
    'toplam_uye'    => $db->query("SELECT COUNT(*) FROM uyeler WHERE durum='AKTIF'")->fetchColumn(),
];

include 'header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:10px;">
    <div>
        <h1 style="color:#1B365D;font-size:28px;margin-bottom:4px;">📈 TİB BORSA <span style="color:#D4AF37;">PANELİ</span></h1>
        <p style="color:#888;font-size:13px;">Otonom Ticaret ve İş Birliği Platformu — <?php echo date('d.m.Y H:i'); ?></p>
    </div>
    <div style="display:flex;gap:10px;">
        <?php if (admin()): ?>
            <a href="/ilan-ver.php" class="btn btn-success">+ Yeni İlan/Emir</a>
            <a href="/admin/index.php" class="btn btn-primary">⚙️ Yönetim</a>
        <?php else: ?>
            <a href="/ilan-ver.php" class="btn btn-success">+ Emir Ver</a>
        <?php endif; ?>
    </div>
</div>

<!-- İstatistik Kartları -->
<div class="grid mb-4" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
    <div class="card text-center" style="background:#1B365D;color:white;">
        <h3 style="font-size:32px;color:#D4AF37;"><?php echo $stats['aktif_ilan']; ?></h3>
        <p style="color:#aaa;font-size:13px;">Aktif Emir/İlan</p>
    </div>
    <div class="card text-center" style="background:#28a745;color:white;">
        <h3 style="font-size:32px;"><?php echo $stats['toplam_uye']; ?></h3>
        <p style="font-size:13px;">Aktif Üye</p>
    </div>
    <div class="card text-center" style="background:#ffc107;color:#333;">
        <h3 style="font-size:32px;"><?php echo $stats['bekleyen_ilan']; ?></h3>
        <p style="font-size:13px;">Bekleyen</p>
    </div>
    <div class="card text-center" style="background:#17a2b8;color:white;">
        <h3 style="font-size:32px;"><?php echo $stats['bugun']; ?></h3>
        <p style="font-size:13px;">Bugün Eklenen</p>
    </div>
</div>

<!-- Borsa Tablosu -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">📊 Güncel Piyasa Verileri</h2>
        <span style="font-size:13px;color:#888;">Son güncelleme: <?php echo date('H:i'); ?></span>
    </div>
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Ürün</th>
                    <th>Kategori</th>
                    <th style="text-align:right;">Fiyat (TL)</th>
                    <th style="text-align:right;">Değişim</th>
                    <th style="text-align:right;">Hacim</th>
                    <th style="text-align:center;">İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($borsa_verileri as $v): ?>
                <tr>
                    <td><strong style="color:#1B365D;"><?php echo guvenlik($v['urun']); ?></strong></td>
                    <td><span class="badge badge-aktif"><?php echo guvenlik($v['kategori']); ?></span></td>
                    <td style="text-align:right;font-weight:bold;"><?php echo number_format($v['fiyat'], 2, ',', '.'); ?> ₺</td>
                    <td style="text-align:right;font-weight:bold;color:<?php echo $v['degisim_pozitif'] ? '#28a745' : '#dc3545'; ?>;">
                        <?php echo $v['degisim_pozitif'] ? '▲' : '▼'; ?> <?php echo $v['degisim']; ?>
                    </td>
                    <td style="text-align:right;color:#666;"><?php echo number_format($v['adet']); ?> adet</td>
                    <td style="text-align:center;">
                        <a href="/ilan-ver.php" class="btn btn-primary btn-sm">Emir Ver</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Son İlanlar -->
<?php
$son_ilanlar = $db->query("
    SELECT i.*, u.ad as uye_ad, k.ad as kategori_ad
    FROM ilanlar i
    LEFT JOIN uyeler u ON i.uye_id = u.id
    LEFT JOIN kategoriler k ON i.kategori_id = k.id
    WHERE i.durum = 'AKTIF'
    ORDER BY i.created_at DESC LIMIT 4
")->fetchAll();
?>
<?php if ($son_ilanlar): ?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title">🆕 Son Aktif İlanlar</h2>
        <a href="/ilanlar.php" class="btn btn-primary btn-sm">Tümünü Gör</a>
    </div>
    <div class="grid">
        <?php foreach ($son_ilanlar as $ilan): ?>
        <div class="card" style="margin-bottom:0;">
            <h3 style="color:#D4AF37;font-size:15px;margin-bottom:6px;"><?php echo guvenlik($ilan['baslik']); ?></h3>
            <p style="font-size:12px;margin-bottom:8px;"><?php echo guvenlik(mb_substr($ilan['aciklama'], 0, 80)); ?>...</p>
            <div style="display:flex;justify-content:space-between;">
                <strong style="color:#1B365D;"><?php echo para($ilan['fiyat']); ?></strong>
                <span class="badge badge-aktif"><?php echo guvenlik($ilan['kategori_ad'] ?? ''); ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php include 'footer.php'; ?>
