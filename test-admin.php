<?php
require_once 'config.php';
echo "Session ID: " . session_id() . "<br>";
echo "Uye ID: " . ($_SESSION['uye_id'] ?? 'yok') . "<br>";
echo "Is Admin: " . ($_SESSION['is_admin'] ?? 'yok') . "<br>";
echo "Admin() fonksiyonu: " . (admin() ? 'Evet' : 'Hayır') . "<br>";
echo "<hr><a href='index.php'>Ana Sayfa</a> | <a href='cikis.php'>Çıkış Yap</a>";
?>