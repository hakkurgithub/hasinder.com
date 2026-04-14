<?php
require_once 'config.php';
echo "Config yüklendi<br>";
echo "Session durumu: " . session_status() . "<br>";
echo "Admin mi?: " . (admin() ? 'Evet' : 'Hayır') . "<br>";
echo "Session uye_id: " . ($_SESSION['uye_id'] ?? 'yok') . "<br>";
echo "Session is_admin: " . ($_SESSION['is_admin'] ?? 'yok') . "<br>";
include 'header.php';
echo "<br>Header yüklendi<br>";
include 'footer.php';
?>