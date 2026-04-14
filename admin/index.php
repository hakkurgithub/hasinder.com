<?php
require_once '../../config.php';
if (!admin()) { header('Location: /giris.php'); exit; }

$stats = [
    'toplam_ilan'    => $db->query("SELECT COUNT(*) FROM ilanlar")->fetchColumn(),
    'aktif_ilan'     => $db->query("SELECT COUNT(*) FROM ilanlar WHERE durum='AKTIF'")->fetchColumn(),
    'bekleyen_ilan'  => $db->query("SELECT COUNT(*) FROM ilanlar WHERE durum='BEKLEMEDE'")->fetchColumn(),
    'toplam_uye'     => $db->query("SELECT COUNT(*) FROM uyeler")->fetchColumn(),
    'bekleyen_uye'   => $db->query("SELECT COUNT(*) FROM uyeler WHERE durum='BEKLEMEDE'")->fetchColumn(),
    'toplam_basvuru' => $db->query("SELECT COUNT(*) FROM is_basvurulari")->fetchColumn(),
    'yeni_basvuru'   => $db->query("SELECT COUNT(*) FROM is_basvurulari WHERE durum='BEKLEMEDE'")->fetchColumn(),
];

$ilanlar = $db->query("SELECT i.*, u.ad as uye_ad FROM ilanlar i LEFT JOIN uyeler u ON i.uye_id=u.id ORDER BY i.created_at DESC LIMIT 8")->fetchAll();
$uyeler  = $db->query("SELECT * FROM uyeler ORDER BY created_at DESC LIMIT 5")->fetchAll();

include '../header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:10px;">
    <h2 style="color:#1B365D;font-size:26px;">⚙️ Admin Dashboard</h2>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="/admin/haberler.php" class="btn btn-info">📰 Haber Yönetimi</a>
        <a href="/ilan-ver.php" class="btn btn-success">+ Yeni İlan</a>
        <a href="/admin/icra-kurullari.php" class="btn btn-primary">🏛️ Kurullar</a>
        <a href="/admin/is-basvurulari.php" class="btn btn-info">📑 Başvurular <?php if ($stats['yeni_basvuru'] > 0): ?><span style="background:#dc3545;color:white;padding:1px 6px;border-radius:10px;font-size:11px;margin-left:4px;"><?php echo $stats['yeni_basvuru']; ?></span><?php endif; ?></a>
        <a href="/index.php" class="btn btn-secondary">Siteye Dön</a>
    </div>
</div>

<!-- İstatistik Kartları -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:28px;">
    <div class="card text-center" style="background:#1B365D;color:white;">
        <h3 style="font-size:32px;color:#D4AF37;"><?php echo $stats['toplam_ilan']; ?></h3>
        <p style="color:#aaa;font-size:13px;">Toplam İlan</p>
    </div>
    <div class="card text-center" style="background:#28a745;color:white;">
        <h3 style="font-size:32px;"><?php echo $stats['aktif_ilan']; ?></h3>
        <p style="font-size:13px;">Aktif İlan</p>
    </div>
    <div class="card text-center" style="background:#ffc107;color:#333;">
        <h3 style="font-size:32px;"><?php echo $stats['bekleyen_ilan']; ?></h3>
        <p style="font-size:13px;">Bekleyen İlan</p>
    </div>
    <div class="card text-center" style="background:#17a2b8;color:white;">
        <h3 style="font-size:32px;"><?php echo $stats['toplam_uye']; ?></h3>
        <p style="font-size:13px;">Toplam Üye</p>
    </div>
    <div class="card text-center" style="background:#dc3545;color:white;">
        <h3 style="font-size:32px;"><?php echo $stats['bekleyen_uye']; ?></h3>
        <p style="font-size:13px;">Onay Bekleyen</p>
    </div>
    <div class="card text-center" style="background:#6f42c1;color:white;">
        <h3 style="font-size:32px;"><?php echo $stats['yeni_basvuru']; ?></h3>
        <p style="font-size:13px;">Yeni Başvuru</p>
    </div>
</div>

<!-- Hızlı Erişim -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:28px;">
    <a href="/admin/uyeler.php"      class="card" style="text-decoration:none;text-align:center;padding:18px;">👥 Üye Yönetimi</a>
    <a href="/admin/ilanlar.php"     class="card" style="text-decoration:none;text-align:center;padding:18px;">📦 İlan Yönetimi</a>
    <a href="/admin/kategoriler.php" class="card" style="text-decoration:none;text-align:center;padding:18px;">🏷️ Kategoriler</a>
    <a href="/admin/icra-kurullari.php" class="card" style="text-decoration:none;text-align:center;padding:18px;">🏛️ İcra Kurulları</a>
    <a href="/admin/is-basvurulari.php" class="card" style="text-decoration:none;text-align:center;padding:18px;">📑 İş Başvuruları</a>
</div>

<!-- Son İlanlar -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Son İlanlar</h3>
        <a href="/admin/ilanlar.php" class="btn btn-primary btn-sm">Tümü</a>
    </div>
    <div style="overflow-x:auto;">
    <table>
        <thead><tr><th>Başlık</th><th>Fiyat</th><th>Durum</th><th>Tarih</th><th>İşlem</th></tr></thead>
        <tbody>
        <?php foreach ($ilanlar as $ilan): ?>
        <tr>
            <td><?php echo guvenlik($ilan['baslik']); ?></td>
            <td><?php echo para($ilan['fiyat']); ?></td>
            <td><span class="badge badge-<?php echo strtolower($ilan['durum']); ?>"><?php echo $ilan['durum']; ?></span></td>
            <td style="font-size:12px;color:#888;"><?php echo date('d.m.Y', strtotime($ilan['created_at'])); ?></td>
            <td>
                <a href="/ilan-duzenle.php?id=<?php echo $ilan['id']; ?>" class="btn btn-info btn-sm">Düzenle</a>
                <a href="/ilan-sil.php?id=<?php echo $ilan['id']; ?>"    class="btn btn-danger btn-sm" onclick="return confirm('Silinsin mi?')">Sil</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- Son Üyeler -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Son Üyeler</h3>
        <a href="/admin/uyeler.php" class="btn btn-primary btn-sm">Tümü</a>
    </div>
    <div style="overflow-x:auto;">
    <table>
        <thead><tr><th>Ad</th><th>E-posta</th><th>Durum</th><th>İşlem</th></tr></thead>
        <tbody>
        <?php foreach ($uyeler as $uye): ?>
        <tr>
            <td><?php echo guvenlik($uye['ad']); ?></td>
            <td><?php echo guvenlik($uye['email']); ?></td>
            <td><span class="badge badge-<?php echo strtolower($uye['durum']); ?>"><?php echo $uye['durum']; ?></span></td>
            <td>
                <?php if ($uye['durum'] === 'BEKLEMEDE'): ?>
                    <a href="/admin/uyeler.php?onay=1&id=<?php echo $uye['id']; ?>" class="btn btn-success btn-sm">Onayla</a>
                    <a href="/admin/uyeler.php?onay=0&id=<?php echo $uye['id']; ?>" class="btn btn-danger btn-sm">Reddet</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<?php include '../footer.php'; ?>
