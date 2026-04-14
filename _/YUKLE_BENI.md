# HASINDER - KURULUM (3 ADIM)

## ADIM 1 — VERİTABANI
phpMyAdmin > hasinder_webuser > SQL sekmesi
`veritabani.sql` içeriğini yapıştır > Git

## ADIM 2 — DOSYALARI YÜKLE
- `config.php` → `/home/hasinder/config.php`  (public_html DIŞI!)
- `public_html/` içindeki HERŞEYİ → `/home/hasinder/public_html/` içine yükle

## ADIM 3 — KLASÖR İZNİ
`public_html/uploads/` klasörüne izin: 755
`public_html/uploads/is_basvurulari/` klasörüne izin: 755

---

## Giriş Bilgileri
- URL: https://www.hasinder.com
- Admin: kurt.hakki@gmail.com
- Şifre: admin123  ← GİRİŞ YAPINCA DEĞİŞTİRİN!

## Dosya Yapısı
```
/home/hasinder/
├── config.php                  ← public_html DIŞINDA
└── public_html/
    ├── index.php
    ├── giris.php
    ├── kayit.php
    ├── cikis.php
    ├── ilanlar.php
    ├── panel.php
    ├── ilan-ver.php
    ├── ilan-duzenle.php
    ├── ilan-sil.php
    ├── hakkimizda.php
    ├── haberler.php
    ├── iletisim.php
    ├── iletisim-gonder.php
    ├── is-basvuru-formu.php
    ├── header.php
    ├── footer.php
    ├── admin/
    │   ├── index.php
    │   ├── uyeler.php
    │   ├── ilanlar.php
    │   ├── kategoriler.php
    │   ├── icra-kurullari.php
    │   ├── icra-kurulu-duzenle.php
    │   ├── is-basvurulari.php
    │   └── is-basvuru-detay.php
    └── uploads/
        └── is_basvurulari/
```
