<?php
// DETAYLI HATA RAPORU
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$db   = 'hasinder_hasinder_db';
$user = 'hasinder_hasinder';
$pass = 'Hasinder2024';

echo "<h2>MySQL Bağlantı Testi</h2>";
echo "Kullanıcı: $user<br>";
echo "Veritabanı: $db<br>";
echo "Şifre: " . str_repeat('*', strlen($pass)) . "<br><hr>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    echo "<span style='color:green'>✅ BAŞARILI! Bağlantı kuruldu.</span>";
} catch(PDOException $e) {
    echo "<span style='color:red'>❌ HATA:</span> " . $e->getMessage();
    echo "<hr><b>Muhtemel Nedenler:</b><ul>";
    echo "<li>Şifre yanlış</li>";
    echo "<li>Kullanıcı veritabanına eklenmemiş</li>";
    echo "<li>Veritabanı adı yanlış</li>";
    echo "</ul>";
}
?>