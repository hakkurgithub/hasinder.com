<?php
// header-modern.php - Mavi Admin Tarzı Header

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($db)) {
    require_once __DIR__ . '/config.php';
}

$is_admin_user = isset($_SESSION['uye_id']) && !empty($_SESSION['uye_id']) && function_exists('admin') && admin();
$current_page = basename($_SERVER['PHP_SELF']);

if (!isset($sayfa_baslik)) {
    $sayfa_baslik = 'Hasinder - B2B Ticaret Platformu';
} else {
    $sayfa_baslik = guvenlik($sayfa_baslik) . ' - Hasinder';
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $sayfa_baslik; ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        body { font-family: 'Inter', sans-serif; background: #f3f4f6; }
        
        .gradient-hero {
            background: linear-gradient(135deg, #1B365D 0%, #2E4A7F 50%, #1e3a8a 100%);
        }
        
        .nav-link {
            color: rgba(255,255,255,0.9);
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }
        
        .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: #D4AF37;
        }
        
        .nav-link.active {
            background: rgba(212, 175, 55, 0.2);
            color: #D4AF37;
        }
        
        .admin-badge {
            background: linear-gradient(135deg, #D4AF37 0%, #F4D03F 100%);
            color: #1B365D;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }
        
        .btn-gold {
            background: linear-gradient(135deg, #D4AF37 0%, #F4D03F 100%);
            color: #1B365D;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-gold:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.4);
        }
        
        .btn-outline {
            background: transparent;
            color: white;
            border: 2px solid rgba(255,255,255,0.3);
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-outline:hover {
            border-color: #D4AF37;
            color: #D4AF37;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 antialiased">

<!-- Mavi Header (Admin ile Aynı) -->
<nav class="hidden md:block fixed top-0 left-0 right-0 z-50 gradient-hero text-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo -->
            <a href="/index.php" class="flex items-center space-x-3 group">
                <div class="w-10 h-10 bg-secondary rounded-lg flex items-center justify-center text-primary font-bold text-xl shadow-lg" style="background: #D4AF37; color: #1B365D;">
                    H
                </div>
                <div>
                    <span class="font-bold text-xl tracking-tight">HASINDER</span>
                    <span class="text-xs ml-2" style="color: #D4AF37;">B2B Ticaret Platformu</span>
                </div>
            </a>
            
            <!-- Menu (Ortada) -->
            <div class="flex items-center space-x-1">
                <a href="/index.php" class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                    Ana Sayfa
                </a>
                <a href="/ilanlar.php" class="nav-link <?php echo $current_page == 'ilanlar.php' ? 'active' : ''; ?>">
                    İlanlar
                </a>
                <a href="/borsa.php" class="nav-link <?php echo $current_page == 'borsa.php' ? 'active' : ''; ?>">
                    Borsa
                </a>
                <a href="/platform.php" class="nav-link <?php echo $current_page == 'platform.php' ? 'active' : ''; ?>">
                    Platform
                </a>
                <a href="/haberler.php" class="nav-link <?php echo $current_page == 'haberler.php' ? 'active' : ''; ?>">
                    Haberler
                </a>
                <a href="/iletisim.php" class="nav-link <?php echo $current_page == 'iletisim.php' ? 'active' : ''; ?>">
                    İletişim
                </a>
                <a href="/kurul.php" class="nav-link <?php echo $current_page == 'kurul.php' ? 'active' : ''; ?>">
                    Kurullar
                </a>
            </div>
            
            <!-- Sağ Bölüm -->
            <div class="flex items-center space-x-3">
                <?php if ($is_admin_user): ?>
                    <span class="admin-badge">
                        Admin
                    </span>
                    <a href="/admin/index.php" class="btn-gold" style="font-size: 12px; padding: 6px 12px;">
                        Yönetim
                    </a>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['uye_id']) && !empty($_SESSION['uye_id'])): ?>
                    <a href="/panel.php" class="btn-gold">
                        Panelim
                    </a>
                    <a href="/cikis.php" class="btn-outline">
                        Çıkış
                    </a>
                <?php else: ?>
                    <a href="/giris.php" class="nav-link">Giriş</a>
                    <a href="/kayit.php" class="btn-gold">Üye Ol</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Mobile Header -->
<div class="md:hidden fixed top-0 left-0 right-0 z-50 gradient-hero text-white shadow-lg">
    <div class="flex justify-between items-center px-4 h-14">
        <a href="/index.php" class="flex items-center space-x-2">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-lg" style="background: #D4AF37; color: #1B365D;">H</div>
            <div>
                <span class="font-bold text-lg">HASINDER</span>
            </div>
        </a>
        <button onclick="toggleMobileMenu()" class="p-2 rounded-lg hover:bg-white/10 transition">
            <i data-lucide="menu" class="w-6 h-6"></i>
        </button>
    </div>
</div>

<!-- Spacer -->
<div class="h-14 md:h-16"></div>

<script>
    function toggleMobileMenu() {
        // Mobile menu logic
    }
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>