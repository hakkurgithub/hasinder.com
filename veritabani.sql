-- ============================================================
-- HASINDER B2B PLATFORM - TAM VERİTABANI
-- phpMyAdmin > hasinder_webuser > SQL sekmesine yapıştırın
-- ============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- 1. ÜYELER
CREATE TABLE IF NOT EXISTS `uyeler` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `ad`         VARCHAR(100) NOT NULL,
  `email`      VARCHAR(100) NOT NULL UNIQUE,
  `telefon`    VARCHAR(20) DEFAULT '',
  `sifre`      VARCHAR(255) NOT NULL,
  `durum`      ENUM('BEKLEMEDE','AKTIF','PASIF') DEFAULT 'BEKLEMEDE',
  `is_admin`   TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. KATEGORİLER
CREATE TABLE IF NOT EXISTS `kategoriler` (
  `id`       INT AUTO_INCREMENT PRIMARY KEY,
  `ad`       VARCHAR(100) NOT NULL,
  `aciklama` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. İLANLAR
CREATE TABLE IF NOT EXISTS `ilanlar` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `baslik`      VARCHAR(255) NOT NULL,
  `aciklama`    TEXT,
  `fiyat`       DECIMAL(12,2) NOT NULL DEFAULT 0,
  `kategori_id` INT DEFAULT NULL,
  `uye_id`      INT NOT NULL,
  `resim`       VARCHAR(255) DEFAULT '',
  `durum`       ENUM('BEKLEMEDE','AKTIF','PASIF') DEFAULT 'BEKLEMEDE',
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`kategori_id`) REFERENCES `kategoriler`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`uye_id`)      REFERENCES `uyeler`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. İCRA KURULLARI
CREATE TABLE IF NOT EXISTS `icra_kurullari` (
  `id`                  INT AUTO_INCREMENT PRIMARY KEY,
  `kurul_ad`            VARCHAR(255) NOT NULL,
  `aciklama`            TEXT,
  `komisyon_orani`      DECIMAL(5,2) NOT NULL DEFAULT 20.00,
  `baskan_uye_id`       INT DEFAULT NULL,
  `durum`               ENUM('AKTIF','PASIF') NOT NULL DEFAULT 'AKTIF',
  `olusturma_tarihi`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `guncellenme_tarihi`  DATETIME DEFAULT NULL,
  FOREIGN KEY (`baskan_uye_id`) REFERENCES `uyeler`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. İŞ BAŞVURULARI
CREATE TABLE IF NOT EXISTS `is_basvurulari` (
  `id`                INT AUTO_INCREMENT PRIMARY KEY,
  `ad_soyad`          VARCHAR(255) NOT NULL,
  `email`             VARCHAR(255) NOT NULL,
  `telefon`           VARCHAR(20) NOT NULL,
  `kurul_id`          INT NOT NULL,
  `is_tanimi`         TEXT NOT NULL,
  `is_tutari`         DECIMAL(15,2) NOT NULL,
  `dosya_yolu`        VARCHAR(500) DEFAULT NULL,
  `durum`             ENUM('BEKLEMEDE','INCELEMEDE','BASKANA_ILETILDI','TAMAMLANDI','REDDEDILDI') NOT NULL DEFAULT 'BEKLEMEDE',
  `admin_notlari`     TEXT DEFAULT NULL,
  `basvuru_tarihi`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `guncelleme_tarihi` DATETIME DEFAULT NULL,
  FOREIGN KEY (`kurul_id`) REFERENCES `icra_kurullari`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET foreign_key_checks = 1;

-- ============================================================
-- BAŞLANGIÇ VERİLERİ
-- ============================================================

-- Admin kullanıcısı (şifre: admin123)
INSERT IGNORE INTO `uyeler` (`ad`, `email`, `telefon`, `sifre`, `durum`, `is_admin`)
VALUES ('Admin', 'kurt.hakki@gmail.com', '+905333715577',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'AKTIF', 1);

-- Kategoriler
INSERT IGNORE INTO `kategoriler` (`id`, `ad`, `aciklama`) VALUES
(1, 'Gıda',       'Yiyecek ve içecek ürünleri'),
(2, 'Tekstil',    'Kumaş, giyim ve tekstil ürünleri'),
(3, 'Kimya',      'Kimyasal ürünler ve hammaddeler'),
(4, 'İnşaat',     'İnşaat malzemeleri ve demir'),
(5, 'Elektronik', 'Elektronik cihazlar ve parçalar'),
(6, 'Ziraat',     'Tarım ürünleri ve hayvancılık'),
(7, 'Plastik',    'Plastik ürünler ve hammaddeler'),
(8, 'Mobilya',    'Mobilya ve ahşap ürünler');

-- Örnek İcra Kurulu
INSERT IGNORE INTO `icra_kurullari` (`id`, `kurul_ad`, `aciklama`, `komisyon_orani`, `durum`)
VALUES (1, 'İstanbul İcra Kurulu', 'İstanbul bölgesi iş başvuruları', 20.00, 'AKTIF');
