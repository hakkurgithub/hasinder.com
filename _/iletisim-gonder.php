<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /iletisim.php');
    exit;
}

$ad    = trim($_POST['ad']    ?? '');
$email = trim($_POST['email'] ?? '');
$mesaj = trim($_POST['mesaj'] ?? '');

// E-posta gönder
$konu   = 'Hasinder.com - Yeni İletişim Mesajı';
$icerik = "<h2>Hasinder İletişim Formu</h2>
<p><strong>Gönderen:</strong> " . guvenlik($ad) . "</p>
<p><strong>E-posta:</strong> " . guvenlik($email) . "</p>
<p><strong>Mesaj:</strong></p>
<p>" . nl2br(guvenlik($mesaj)) . "</p>
<p><strong>Tarih:</strong> " . date('d.m.Y H:i') . "</p>";

mailGonder(ADMIN_EMAIL, $konu, $icerik);

$whatsapp_mesaj = urlencode("Hasinder İletişim:\nAd: {$ad}\nEmail: {$email}\nMesaj: {$mesaj}");
$whatsapp_link  = "https://wa.me/905333715577?text={$whatsapp_mesaj}";
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Mesaj Gönderildi | Hasinder</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f5f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .box { background: white; padding: 50px 40px; border-radius: 12px; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.1); max-width: 500px; width: 90%; }
        .icon { font-size: 64px; margin-bottom: 20px; }
        h2 { color: #1B365D; margin-bottom: 12px; }
        p  { color: #666; margin-bottom: 8px; }
        .btn { display: inline-block; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 700; margin: 6px; }
        .btn-wa   { background: #25D366; color: white; }
        .btn-home { background: #1B365D; color: white; }
    </style>
</head>
<body>
<div class="box">
    <div class="icon">✅</div>
    <h2>Mesajınız Gönderildi!</h2>
    <p><strong><?php echo guvenlik($ad); ?></strong>, mesajınız başarıyla alındı.</p>
    <p>En kısa sürede sizinle iletişime geçeceğiz.</p>
    <div style="margin-top:25px;">
        <a href="<?php echo $whatsapp_link; ?>" target="_blank" class="btn btn-wa">💬 WhatsApp'tan Yaz</a>
        <a href="/index.php" class="btn btn-home">Ana Sayfaya Dön</a>
    </div>
</div>
</body>
</html>
