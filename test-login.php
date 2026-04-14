<?php
require_once 'config.php';

echo "<h2>Hasinder Login Debug</h2><hr>";

// 1. Veritabanı bağlantı kontrolü
echo "<h3>1. Veritabanı Bağlantısı</h3>";
try {
    $test = $db->query("SELECT 1");
    echo "✅ Bağlantı OK<br>";
} catch (Exception $e) {
    echo "❌ Bağlantı Hatası: " . $e->getMessage() . "<br>";
    exit;
}

// 2. Kullanıcı var mı?
echo "<h3>2. Admin Kullanıcısı Kontrolü</h3>";
$stmt = $db->prepare("SELECT * FROM uyeler WHERE email = ?");
$stmt->execute(['kurt.hakki@gmail.com']);
$uye = $stmt->fetch();

if ($uye) {
    echo "✅ Kullanıcı bulundu<br>";
    echo "ID: " . $uye['id'] . "<br>";
    echo "Ad: " . $uye['ad'] . "<br>";
    echo "Email: " . $uye['email'] . "<br>";
    echo "Durum: " . $uye['durum'] . "<br>";
    echo "Admin: " . ($uye['is_admin'] ? 'Evet' : 'Hayır') . "<br>";
    echo "<b>DB'deki Şifre (Hash):</b> " . substr($uye['sifre'], 0, 50) . "...<br>";
    echo "Hash Uzunluğu: " . strlen($uye['sifre']) . "<br>";
} else {
    echo "❌ Kullanıcı BULUNAMADI!<br>";
    exit;
}

// 3. Şifre testi
echo "<h3>3. Şifre Doğrulama Testi</h3>";
$test_sifre = 'admin123';
echo "Test Şifresi: $test_sifre<br>";

// password_verify testi
$verify_result = password_verify($test_sifre, $uye['sifre']);
echo "password_verify Sonucu: " . ($verify_result ? '✅ TRUE' : '❌ FALSE') . "<br>";

// Hash bilgisi
echo "<h3>4. Hash Bilgisi</h3>";
$hash_info = password_get_info($uye['sifre']);
echo "Algoritma: " . $hash_info['algo'] . "<br>";
echo "Algo Adı: " . $hash_info['algoName'] . "<br>";

// 5. Yeni hash oluştur (eğer uyuşmuyorsa bu hash'i kullanacağız)
echo "<h3>5. Yeni Hash Oluştur (admin123 için)</h3>";
$yeni_hash = password_hash('admin123', PASSWORD_DEFAULT);
echo "Yeni Hash: $yeni_hash<br>";
echo "<b>BU HASH'İ KOPYALAYIN:</b> <code>$yeni_hash</code><br>";

// 6. Eğer verify çalışmazsa alternatif test
echo "<h3>6. Alternatif Test</h3>";
if (!$verify_result) {
    echo "Şifre uyuşmuyor! Sebepler:<br>";
    echo "- DB'deki hash bozuk olabilir<br>";
    echo "- Farklı şifreleme algoritması kullanılmış olabilir<br>";
    echo "- SQL import sırasında hash kırılmış olabilir<br>";
    
    // Manuel hash kontrolü
    echo "<hr><b>ÇÖZÜM:</b> Aşağıdaki SQL'i phpMyAdmin'de çalıştırın:<br>";
    echo "<pre>";
    echo "UPDATE uyeler SET sifre = '$yeni_hash' WHERE email = 'kurt.hakki@gmail.com';";
    echo "</pre>";
}

echo "<hr><a href='giris.php'>Giriş Sayfasına Git</a>";
?>