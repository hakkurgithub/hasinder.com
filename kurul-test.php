<?php
require_once 'config.php';

echo "<h2>Kurul Sistemi Test</h2>";

// 1. Tablo var mı?
$tables = $db->query("SHOW TABLES LIKE 'icra_kurullari'")->fetchAll();
echo "<p>icra_kurullari tablosu: " . (count($tables) ? "✅ VAR" : "❌ YOK") . "</p>";

$tables2 = $db->query("SHOW TABLES LIKE 'kurul_uyeleri'")->fetchAll();
echo "<p>kurul_uyeleri tablosu: " . (count($tables2) ? "✅ VAR" : "❌ YOK") . "</p>";

// 2. Kayıt var mı?
try {
    $count = $db->query("SELECT COUNT(*) FROM icra_kurullari")->fetchColumn();
    echo "<p>Toplam Kurul: <strong>$count</strong></p>";
    
    if ($count > 0) {
        $kurullar = $db->query("SELECT id, kurul_ad, durum FROM icra_kurullari LIMIT 5")->fetchAll();
        echo "<h3>İlk 5 Kurul:</h3><ul>";
        foreach ($kurullar as $k) {
            echo "<li>#{$k['id']} - {$k['kurul_ad']} ({$k['durum']})</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color:red'>❗ HİÇ KURUL KAYDI YOK!</p>";
        echo "<p>Admin panelinden kurul eklemeniz gerekiyor: <a href='/admin/icra-kurullari.php'>Kurul Yönetimi</a></p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red'>SQL Hatası: " . $e->getMessage() . "</p>";
}

// 3. Session kontrolü
echo "<hr><h3>Oturum Bilgisi:</h3>";
print_r($_SESSION);