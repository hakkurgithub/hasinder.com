<?php
require_once '../../config.php';
if (!admin()) { header('Location: /giris.php'); exit; }

$id = isset($_GET['id']) && is_numeric($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) { header('Location: /admin/is-basvurulari.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kaydet'])) {
    $izinli = ['BEKLEMEDE','INCELEMEDE','BASKANA_ILETILDI','TAMAMLANDI','REDDEDILDI'];
    $yd = in_array($_POST['durum'] ?? '', $izinli) ? $_POST['durum'] : 'BEKLEMEDE';
    $db->prepare("UPDATE is_basvurulari SET durum=?,admin_notlari=?,guncelleme_tarihi=NOW() WHERE id=?")
       ->execute([$yd, trim($_POST['admin_notlari'] ?? ''), $id]);
    bildirim('Başvuru güncellendi.', 'success');
    header("Location: is-basvuru-detay.php?id={$id}"); exit;
}

$stmt = $db->prepare("SELECT b.*,ik.kurul_ad,ik.komisyon_orani,u.ad AS baskan_ad,u.email AS baskan_email,u.telefon AS baskan_tel FROM is_basvurulari b LEFT JOIN icra_kurullari ik ON b.kurul_id=ik.id LEFT JOIN uyeler u ON ik.baskan_uye_id=u.id WHERE b.id=?");
$stmt->execute([$id]);
$b = $stmt->fetch();
if (!$b) { bildirim('Başvuru bulunamadı.','danger'); header('Location: /admin/is-basvurulari.php'); exit; }

$komisyon = $b['is_tutari'] * ($b['komisyon_orani'] / 100);
$dr = [
    'BEKLEMEDE'        => ['bg'=>'#fff3cd','c'=>'#856404','l'=>'⏳ Beklemede'],
    'INCELEMEDE'       => ['bg'=>'#cce5ff','c'=>'#004085','l'=>'🔍 İncelemede'],
    'BASKANA_ILETILDI' => ['bg'=>'#d4edda','c'=>'#155724','l'=>'📩 Başkana İletildi'],
    'TAMAMLANDI'       => ['bg'=>'#c3e6cb','c'=>'#0c5460','l'=>'✅ Tamamlandı'],
    'REDDEDILDI'       => ['bg'=>'#f8d7da','c'=>'#721c24','l'=>'❌ Reddedildi'],
];
$d = $dr[$b['durum']] ?? ['bg'=>'#eee','c'=>'#333','l'=>$b['durum']];

include '../header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:10px;">
    <h2 style="color:#1B365D;">📄 Başvuru Detayı — #<?php echo $id; ?></h2>
    <a href="/admin/is-basvurulari.php" class="btn btn-secondary btn-sm">← Listeye Dön</a>
</div>

<?php echo bildirimGoster(); ?>

<!-- Başvuran -->
<div class="card">
    <h3 style="margin-bottom:16px;color:#1B365D;">👤 Başvuran Bilgileri</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
        <div><label style="font-size:11px;color:#888;font-weight:bold;text-transform:uppercase;">Ad Soyad</label><div style="font-size:16px;margin-top:4px;"><?php echo guvenlik($b['ad_soyad']); ?></div></div>
        <div><label style="font-size:11px;color:#888;font-weight:bold;text-transform:uppercase;">E-posta</label><div style="margin-top:4px;"><a href="mailto:<?php echo guvenlik($b['email']); ?>"><?php echo guvenlik($b['email']); ?></a></div></div>
        <div><label style="font-size:11px;color:#888;font-weight:bold;text-transform:uppercase;">Telefon</label>
            <div style="margin-top:4px;">
                <a href="tel:<?php echo guvenlik($b['telefon']); ?>"><?php echo guvenlik($b['telefon']); ?></a>
                &nbsp;<a href="https://wa.me/<?php echo preg_replace('/[^0-9]/','', $b['telefon']); ?>" target="_blank" class="btn btn-sm" style="background:#25d366;color:white;font-size:11px;padding:3px 8px;">WhatsApp</a>
            </div>
        </div>
        <div><label style="font-size:11px;color:#888;font-weight:bold;text-transform:uppercase;">Durum</label>
            <div style="margin-top:4px;"><span style="display:inline-block;padding:5px 12px;border-radius:12px;font-weight:bold;background:<?php echo $d['bg']; ?>;color:<?php echo $d['c']; ?>;"><?php echo $d['l']; ?></span></div>
        </div>
        <div><label style="font-size:11px;color:#888;font-weight:bold;text-transform:uppercase;">Başvuru Tarihi</label><div style="margin-top:4px;"><?php echo date('d.m.Y H:i', strtotime($b['basvuru_tarihi'])); ?></div></div>
        <?php if ($b['guncelleme_tarihi']): ?>
        <div><label style="font-size:11px;color:#888;font-weight:bold;text-transform:uppercase;">Son Güncelleme</label><div style="margin-top:4px;"><?php echo date('d.m.Y H:i', strtotime($b['guncelleme_tarihi'])); ?></div></div>
        <?php endif; ?>
    </div>
</div>

<!-- İş Bilgileri -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px;">
    <div class="card">
        <h3 style="margin-bottom:16px;color:#1B365D;">💼 İş Bilgileri</h3>
        <div style="margin-bottom:14px;"><label style="font-size:11px;color:#888;font-weight:bold;text-transform:uppercase;">Kurul</label><div style="margin-top:4px;font-size:16px;"><?php echo guvenlik($b['kurul_ad']); ?></div></div>
        <?php if ($b['baskan_ad']): ?>
        <div style="margin-bottom:14px;">
            <label style="font-size:11px;color:#888;font-weight:bold;text-transform:uppercase;">Kurul Başkanı</label>
            <div style="margin-top:4px;"><?php echo guvenlik($b['baskan_ad']); ?>
            <?php if ($b['baskan_tel']): ?>
                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/','', $b['baskan_tel']); ?>" target="_blank" class="btn btn-sm" style="background:#25d366;color:white;font-size:11px;padding:3px 8px;margin-left:8px;">Başkana WhatsApp</a>
            <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        <div><label style="font-size:11px;color:#888;font-weight:bold;text-transform:uppercase;">İş Tutarı</label><div style="font-size:24px;font-weight:bold;color:#1B365D;margin-top:4px;"><?php echo para($b['is_tutari']); ?></div></div>
        <?php if ($b['dosya_yolu']): ?>
        <div style="margin-top:14px;"><a href="/<?php echo guvenlik($b['dosya_yolu']); ?>" target="_blank" class="btn btn-info">📎 Belgeyi Görüntüle</a></div>
        <?php endif; ?>
    </div>
    <div class="card" style="display:flex;align-items:center;justify-content:center;text-align:center;">
        <div style="background:#fff8e1;border:2px dashed #ffc107;border-radius:10px;padding:24px;width:100%;">
            <div style="font-size:12px;color:#888;margin-bottom:6px;">Tahmini Komisyon (🔒 Gizli)</div>
            <div style="font-size:26px;font-weight:bold;color:#e65100;"><?php echo para($komisyon); ?></div>
            <div style="font-size:12px;color:#888;margin-top:6px;">%<?php echo number_format($b['komisyon_orani'], 2); ?> oran</div>
        </div>
    </div>
</div>

<!-- İş Tanımı -->
<div class="card">
    <h3 style="margin-bottom:14px;color:#1B365D;">📝 İş Tanımı</h3>
    <div style="background:#f9f9f9;border-radius:6px;padding:18px;line-height:1.8;white-space:pre-wrap;"><?php echo guvenlik($b['is_tanimi']); ?></div>
</div>

<!-- Admin İşlemleri -->
<div class="card">
    <h3 style="margin-bottom:16px;color:#1B365D;">🛠️ Admin İşlemleri</h3>
    <form method="POST">
        <div class="form-group">
            <label class="form-label">Durum Güncelle</label>
            <select name="durum" class="form-control">
                <?php foreach ($dr as $dVal=>$dInfo): ?>
                    <option value="<?php echo $dVal; ?>" <?php echo $b['durum']===$dVal?'selected':''; ?>><?php echo $dInfo['l']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Admin Notları (Ziyaretçiye gösterilmez)</label>
            <textarea name="admin_notlari" class="form-control" rows="4" placeholder="İç notlarınız..."><?php echo guvenlik($b['admin_notlari'] ?? ''); ?></textarea>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button type="submit" name="kaydet" class="btn btn-success">💾 Kaydet</button>
            <a href="/admin/is-basvurulari.php" class="btn btn-secondary">← Listeye Dön</a>
            <?php if ($b['baskan_email']): ?>
            <a href="mailto:<?php echo guvenlik($b['baskan_email']); ?>?subject=Başvuru+%23<?php echo $id; ?>&body=Sayın+<?php echo urlencode($b['baskan_ad']); ?>,%0A%0A<?php echo urlencode($b['ad_soyad']); ?>+adlı+başvurucu+incelemeniz+için+iletilmiştir.%0ATutar:+<?php echo urlencode(para($b['is_tutari'])); ?>"
               class="btn btn-primary">📧 Başkana E-posta</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php include '../footer.php'; ?>
