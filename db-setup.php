<?php
require_once 'config.php';

echo "<h2>Hasinder Veritabanı Güncelleme</h2>";

try {
    // 1. Önce tabloyu Foreign Key olmadan oluştur
    $db->exec("DROP TABLE IF EXISTS `kurul_uyeleri`");
    
    $db->exec("CREATE TABLE `kurul_uyeleri` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `kurul_id` INT NOT NULL,
        `uye_id` INT NOT NULL,
        `yetki` ENUM('UYE', 'YARDIMCI') DEFAULT 'UYE',
        `atama_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_kurul` (`kurul_id`),
        INDEX `idx_uye` (`uye_id`),
        UNIQUE KEY `unique_kurul_uye` (`kurul_id`, `uye_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    echo "✅ kurul_uyeleri tablosu oluşturuldu<br>";
    
    // 2. Şimdi Foreign Key'leri ayrıca ekle
    try {
        $db->exec("ALTER TABLE `kurul_uyeleri` 
                   ADD CONSTRAINT `fk_kurul` 
                   FOREIGN KEY (`kurul_id`) REFERENCES `icra_kurullari`(`id`) 
                   ON DELETE CASCADE");
        echo "✅ Kurul FK eklendi<br>";
    } catch (PDOException $e) {
        echo "⚠️ Kurul FK hatası (önemli değil): " . $e->getMessage() . "<br>";
    }
    
    try {
        $db->exec("ALTER TABLE `kurul_uyeleri` 
                   ADD CONSTRAINT `fk_uye` 
                   FOREIGN KEY (`uye_id`) REFERENCES `uyeler`(`id`) 
                   ON DELETE CASCADE");
        echo "✅ Üye FK eklendi<br>";
    } catch (PDOException $e) {
        echo "⚠️ Üye FK hatası (önemli değil): " . $e->getMessage() . "<br>";
    }
    
    echo "<hr><h3>Sonuç:</h3>";
    echo "Tablo oluşturuldu. Foreign Key'ler referans tablolar uyumlu değilse bile tablo çalışır.<br>";
    echo "<a href='/kurul.php' style='display:inline-block;margin-top:10px;padding:10px 20px;background:#1B365D;color:white;text-decoration:none;border-radius:5px;'>Kurullar Sayfasına Git</a>";
    
} catch (PDOException $e) {
    echo "❌ Hata: " . $e->getMessage();
}
?>