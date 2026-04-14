<?php
if (!isset($db)) {
    require_once '../config.php';
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasinder - B2B Ticaret Platformu</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; color: #333; line-height: 1.6; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        header { background: #1B365D; border-bottom: 3px solid #D4AF37; padding: 15px 0; position: sticky; top: 0; z-index: 1000; }
        .header-content { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .logo { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .logo-icon { width: 42px; height: 42px; background: #D4AF37; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #1B365D; font-weight: 900; font-size: 22px; }
        .logo-text h1 { font-size: 22px; margin: 0; color: #fff; letter-spacing: 1px; }
        .logo-text span { font-size: 11px; color: #aaa; }
        nav { display: flex; gap: 5px; flex-wrap: wrap; }
        nav a { color: #ddd; text-decoration: none; padding: 7px 13px; border-radius: 5px; transition: all 0.2s; font-size: 14px; }
        nav a:hover { color: #D4AF37; background: rgba(212,175,55,0.12); }
        .user-menu { display: flex; align-items: center; gap: 12px; }
        .btn { padding: 8px 16px; border-radius: 5px; text-decoration: none; font-weight: 600; transition: all 0.2s; border: none; cursor: pointer; display: inline-block; font-size: 14px; }
        .btn-primary { background: #D4AF37; color: #1B365D; }
        .btn-primary:hover { background: #c4a030; }
        .btn-outline { border: 2px solid #D4AF37; color: #D4AF37; background: transparent; }
        .btn-outline:hover { background: #D4AF37; color: #1B365D; }
        .btn-danger  { background: #dc3545; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-info    { background: #17a2b8; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-sm { padding: 5px 10px; font-size: 12px; }
        main { padding: 40px 0; min-height: calc(100vh - 280px); }
        /* Kart */
        .card { background: #fff; border: 1px solid #e0e0e0; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 6px rgba(0,0,0,0.07); }
        .card-header { border-bottom: 1px solid #eee; padding-bottom: 14px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .card-title { color: #1B365D; font-size: 20px; margin: 0; font-weight: bold; }
        /* Grid */
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
        .text-center { text-align: center; }
        .mb-4 { margin-bottom: 20px; }
        /* İlan kartları */
        .ilan-card { position: relative; }
        .ilan-resim { width: 100%; height: 200px; object-fit: cover; border-radius: 8px; margin-bottom: 10px; }
        .ilan-resim-yok { width: 100%; height: 200px; background: #f0f0f0; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #bbb; margin-bottom: 10px; border: 2px dashed #ddd; font-size: 14px; }
        .admin-buttons { position: absolute; top: 10px; right: 10px; z-index: 10; display: flex; gap: 5px; }
        /* Form */
        .form-group { margin-bottom: 18px; }
        .form-label { display: block; margin-bottom: 6px; font-weight: 600; color: #444; font-size: 14px; }
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid #ccc; border-radius: 6px; font-size: 15px; font-family: inherit; box-sizing: border-box; }
        .form-control:focus { outline: none; border-color: #D4AF37; box-shadow: 0 0 0 3px rgba(212,175,55,0.15); }
        /* Tablo */
        table { width: 100%; border-collapse: collapse; }
        th { background: #1B365D; color: white; padding: 12px 14px; text-align: left; font-size: 13px; }
        td { padding: 12px 14px; border-bottom: 1px solid #f0f0f0; font-size: 14px; vertical-align: middle; }
        tr:hover td { background: #fafafa; }
        /* Badge */
        .badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; display: inline-block; }
        .badge-aktif     { background: #d4edda; color: #155724; }
        .badge-pasif     { background: #f8d7da; color: #721c24; }
        .badge-beklemede { background: #fff3cd; color: #856404; }
        /* Footer */
        footer { background: #1B365D; color: #aaa; text-align: center; padding: 25px 20px; margin-top: 40px; border-top: 3px solid #D4AF37; }
        footer a { color: #D4AF37; text-decoration: none; }
        /* Yardımcı */
        h1, h2, h3 { color: #1B365D; }
        p { color: #666; }
        .mt-4 { margin-top: 20px; }
        @media(max-width: 768px) {
            nav { display: none; }
            .admin-buttons { position: static; margin-bottom: 10px; }
        }
    </style>
</head>
<body>
<header>
    <div class="container">
        <div class="header-content">
            <a href="/index.php" class="logo">
                <div class="logo-icon">H</div>
                <div class="logo-text">
                    <h1>HASINDER</h1>
                    <span>B2B Ticaret Platformu</span>
                </div>
            </a>

            <nav>
                <a href="/index.php">Ana Sayfa</a>
                <a href="/ilanlar.php">İlanlar</a>
                <a href="/panel.php">Borsa</a>
                <a href="/hakkimizda.php">Platform</a>
                <a href="/haberler.php">Haberler</a>
                <a href="/iletisim.php">İletişim</a>
                <?php if (admin()): ?>
                    <a href="/admin/index.php" style="color:#D4AF37;font-weight:bold;">⚙️ Yönetim</a>
                    <a href="/admin/icra-kurullari.php" style="color:#D4AF37;">🏛️ Kurullar</a>
                    <a href="/admin/is-basvurulari.php" style="color:#D4AF37;">📑 Başvurular</a>
                    <a href="/ilan-ver.php" style="color:#28a745;font-weight:bold;">+ İlan Ver</a>
                <?php else: ?>
                    <a href="/is-basvuru-formu.php" style="color:#D4AF37;font-weight:bold;">📋 İş Başvurusu</a>
                <?php endif; ?>
            </nav>

            <div class="user-menu">
                <?php if (yetkili()): ?>
                    <span style="color:#D4AF37;font-weight:600;"><?php echo guvenlik($_SESSION['ad']); ?></span>
                    <?php if (admin()): ?>
                        <span class="badge badge-aktif">Admin</span>
                    <?php endif; ?>
                    <a href="/cikis.php" class="btn btn-outline">Çıkış</a>
                <?php else: ?>
                    <a href="/giris.php" class="btn btn-outline">Giriş</a>
                    <a href="/kayit.php" class="btn btn-primary">Kayıt Ol</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>
<main class="container">
    <?php echo bildirimGoster(); ?>
