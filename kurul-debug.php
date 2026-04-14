<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Başladı</h1>";

require_once 'config.php';
echo "<p>✅ Config yüklendi</p>";

$sayfa_baslik = 'Test';
echo "<p>✅ Değişken atandı</p>";

try {
    $kategoriler = $db->query("SELECT * FROM kategoriler ORDER BY ad LIMIT 10")->fetchAll();
    echo "<p>✅ Kategoriler çekildi: " . count($kategoriler) . " adet</p>";
} catch (Exception $e) {
    echo "<p>❌ Kategori hatası: " . $e->getMessage() . "</p>";
}

try {
    $one_cikan_ilanlar = $db->query("SELECT * FROM ilanlar WHERE durum = 'AKTIF' LIMIT 1")->fetchAll();
    echo "<p>✅ İlanlar çekildi</p>";
} catch (Exception $e) {
    echo "<p>❌ İlan hatası: " . $e->getMessage() . "</p>";
}

try {
    $kurullar = $db->query("SELECT * FROM icra_kurullari WHERE durum = 'AKTIF' LIMIT 1")->fetchAll();
    echo "<p>✅ Kurullar çekildi: " . count($kurullar) . " adet</p>";
} catch (Exception $e) {
    echo "<p>❌ Kurul hatası: " . $e->getMessage() . "</p>";
}

echo "<p>Şimdi header-modern.php yükleniyor...</p>";
include 'header-modern.php';
echo "<p>✅ Header yüklendi</p>";

echo "<p>HTML içerik burada...</p>";

include 'footer-modern.php';
echo "<p>✅ Footer yüklendi</p>";