<?php
require_once '../../config.php';
if (!admin()) { header('Location: /giris.php'); exit; }

// Hızlı durum güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['durum_guncelle'])) {
    $izinli = ['BEKLEMEDE','INCELEMEDE','BASKANA_ILETILDI','TAMAMLANDI','REDDEDILDI'];
    $yd = $_POST['yeni_durum'] ?? '';
    if (in_array($yd, $izinli)) {
        $db->prepare("UPDATE is_basvurulari SET durum=?,guncelleme_tarihi=NOW() WHERE id=?")
           ->execute([$yd, intval($_POST['basvuru_id'])]);
        bildirim('Durum güncellendi.','success');
    }
    $qs = isset($_GET['kurul_id']) ? '?kurul_id=' . intval($_GET['kurul_id']) : '';
    header('Location: is-basvurulari.php' . $qs); exit;
}

// Filtreler
$fKurul = isset($_GET['kurul_id']) && is_numeric($_GET['kurul_id']) ? intval($_GET['kurul_id']) : 0;
$fDurum = $_GET['durum'] ?? '';
$fArama = trim($_GET['arama'] ?? '');

$where  = ['1=1']; $params = [];
if ($fKurul) { $where[] = 'b.kurul_id=?'; $params[] = $fKurul; }
if ($fDurum) { $where[] = 'b.durum=?';    $params[] = $fDurum; }
if ($fArama) { $where[] = '(b.ad_soyad LIKE ? OR b.email LIKE ? OR b.telefon LIKE ?)'; $q="%$fArama%"; $params[]=$q;$params[]=$q;$params[]=$q; }

// SQL'den komisyon_orani kaldırıldı
$stmt = $db->prepare("SELECT b.*,ik.kurul_ad,u.ad AS baskan_ad FROM is_basvurulari b LEFT JOIN icra_kurullari ik ON b.kurul_id=ik.id LEFT JOIN uyeler u ON ik.baskan_uye_id=u.id WHERE " . implode(' AND ',$where) . " ORDER BY b.basvuru_tarihi DESC");
$stmt->execute($params);
$basvurular = $stmt->fetchAll();

$kurullar = $db->query("SELECT id,kurul_ad FROM icra_kurullari ORDER BY kurul_ad")->fetchAll();

$ist = $db->query("SELECT COUNT(*) AS toplam, SUM(CASE WHEN durum='BEKLEMEDE' THEN 1 ELSE 0 END) AS beklemede, SUM(CASE WHEN durum='TAMAMLANDI' THEN 1 ELSE 0 END) AS tamamlandi, SUM(is_tutari) AS toplam_tutar FROM is_basvurulari")->fetch();

$dr = [
    'BEKLEMEDE'        => ['bg'=>'#fff3cd','c'=>'#856404','l'=>'⏳ Beklemede'],
    'INCELEMEDE'       => ['bg'=>'#cce5ff','c'=>'#004085','l'=>'🔍 İncelemede'],
    'BASKANA_ILETILDI' => ['bg'=>'#d4edda','c'=>'#155724','l'=>'📩 Başkana İletildi'],
    'TAMAMLANDI'       => ['bg'=>'#c3e6cb','c'=>'#0c5460','l'=>'✅ Tamamlandı'],
    'REDDEDILDI'       => ['bg'=>'#f8d7da','c'=>'#721c24','l'=>'❌ Reddedildi'],
];

include '../header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:10px;">
    <h2 style="color:#1B365D;">📑 İş Başvuruları</h2>
    <a href="/admin/index.php" class="btn btn-secondary btn-sm">← Dashboard</a>
</div>

<!-- İstatistik -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:14px;margin-bottom:22px;">
    <div class="card text-center"><div style="font-size:26px;font-weight:bold;color:#1B365D;"><?php echo $ist['toplam']; ?></div><div style="font-size:12px;color:#888;">Toplam</div></div>
    <div class="card text-center"><div style="font-size:26px;font-weight:bold;color:#856404;"><?php echo $ist['beklemede']; ?></div><div style="font-size:12px;color:#888;">Beklemede</div></div>
    <div class="card text-center"><div style="font-size:26px;font-weight:bold;color:#28a745;"><?php echo $ist['tamamlandi']; ?></div><div style="font-size:12px;color:#888;">Tamamlandı</div></div>
    <div class="card text-center"><div style="font-size:20px;font-weight:bold;color:#1B365D;"><?php echo number_format((float)$ist['toplam_tutar'],0,',','.'); ?> ₺</div><div style="font-size:12px;color:#888;">Toplam Tutar</div></div>
</div>

<!-- Filtre -->
<form method="GET" class="card" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;padding:16px;margin-bottom:20px;">
    <div>
        <label class="form-label">Kurul</label>
        <select name="kurul_id" class="form-control" style="min-width:160px;">
            <option value="">Tümü</option>
            <?php foreach ($kurullar as $k): ?>
                <option value="<?php echo $k['id']; ?>" <?php echo $fKurul==$k['id']?'selected':''; ?>><?php echo guvenlik($k['kurul_ad']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label class="form-label">Durum</label>
        <select name="durum" class="form-control">
            <option value="">Tümü</option>
            <?php foreach ($dr as $d=>$info): ?>
                <option value="<?php echo $d; ?>" <?php echo $fDurum===$d?'selected':''; ?>><?php echo $info['l']; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label class="form-label">Ara</label>
        <input type="text" name="arama" class="form-control" value="<?php echo guvenlik($fArama); ?>" placeholder="Ad / e-posta / tel">
    </div>
    <button type="submit" class="btn btn-primary">Filtrele</button>
    <a href="is-basvurulari.php" class="btn btn-secondary">Temizle</a>
</form>

<?php echo bildirimGoster(); ?>

<?php if (!$basvurular): ?>
    <div class="card" style="text-align:center;padding:50px;color:#aaa;">Başvuru bulunamadı.</div>
<?php else: ?>
<p style="color:#888;font-size:13px;margin-bottom:10px;"><?php echo count($basvurular); ?> başvuru</p>
<div style="overflow-x:auto;">
<table>
    <thead><tr><th>#</th><th>Başvuran</th><th>Kurul</th><th>Tutar</th><th>Durum</th><th>Tarih</th><th>Hızlı Durum</th><th>Detay</th></tr></thead>
    <tbody>
    <?php foreach ($basvurular as $b):
        $d = $dr[$b['durum']] ?? ['bg'=>'#eee','c'=>'#333','l'=>$b['durum']];
    ?>
    <tr>
        <td style="color:#999;">#<?php echo $b['id']; ?></td>
        <td>
            <strong><?php echo guvenlik($b['ad_soyad']); ?></strong><br>
            <small><?php echo guvenlik($b['email']); ?></small><br>
            <small><?php echo guvenlik($b['telefon']); ?></small>
        </td>
        <td><?php echo guvenlik($b['kurul_ad']); ?></td>
        <td><strong><?php echo para($b['is_tutari']); ?></strong></td>
        <td><span style="display:inline-block;padding:4px 10px;border-radius:12px;font-size:12px;font-weight:600;background:<?php echo $d['bg']; ?>;color:<?php echo $d['c']; ?>;"><?php echo $d['l']; ?></span></td>
        <td style="font-size:12px;color:#888;white-space:nowrap;"><?php echo date('d.m.Y H:i', strtotime($b['basvuru_tarihi'])); ?></td>
        <td>
            <form method="POST" style="display:flex;gap:6px;align-items:center;">
                <input type="hidden" name="basvuru_id" value="<?php echo $b['id']; ?>">
                <select name="yeni_durum" class="form-control" style="padding:5px;font-size:12px;">
                    <?php foreach ($dr as $d2=>$i2): ?>
                        <option value="<?php echo $d2; ?>" <?php echo $b['durum']===$d2?'selected':''; ?>><?php echo $i2['l']; ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="durum_guncelle" class="btn btn-success btn-sm">✔</button>
            </form>
        </td>
        <td><a href="is-basvuru-detay.php?id=<?php echo $b['id']; ?>" class="btn btn-primary btn-sm">📄 Detay</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

<?php include '../footer.php'; ?>