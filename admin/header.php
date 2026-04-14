<?php
// admin/header.php - Admin Header (Ana Siteyle Aynı Tasarım)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($db)) {
    require_once __DIR__ . '/../config.php';
}

if (!function_exists('admin') || !admin()) {
    header('Location: /giris.php');
    exit;
}

$admin_uye_ad = $_SESSION['uye_ad'] ?? 'Admin';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($sayfa_baslik) ? guvenlik($sayfa_baslik) . ' - Hasinder' : 'Admin - Hasinder'; ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1B365D',
                        secondary: '#D4AF37',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
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
            display: inline-flex;
            align-items: center;
            gap: 4px;
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

<!-- Header (Ana Siteyle Aynı) -->
<nav class="hidden md:block fixed top-0 left-0 right-0 z-50 gradient-hero text-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo -->
            <a href="/index.php" class="flex items-center space-x-3 group">
                <div class="w-10 h-10 bg-secondary rounded-lg flex items-center justify-center text-primary font-bold text-xl shadow-lg">
                    H
                </div>
                <div>
                    <span class="font-bold text-xl tracking-tight">HASINDER</span>
                    <span class="text-xs text-secondary ml-2">B2B Ticaret Platformu</span>
                </div>
            </a>
            
            <!-- Menu (Tamamen Aynı) -->
            <div class="flex items-center space-x-1">
                <a href="/index.php" class="nav-link <?php echo $current_page == 'index.php' && !strpos($_SERVER['REQUEST_URI'], '/admin/') ? 'active' : ''; ?>">
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
                
                <!-- Admin Indicator -->
                <a href="/admin/index.php" class="nav-link active" style="background: rgba(220,53,69,0.3); color: #ffcdd2; margin-left: 8px;">
                    <i data-lucide="settings" class="w-4 h-4"></i>
                    Yönetim
                </a>
            </div>
            
            <!-- Sağ Bölüm (Admin Bilgisi + Çıkış) -->
            <div class="flex items-center space-x-3">
                <span class="admin-badge">
                    <i data-lucide="shield" class="w-3 h-3"></i>
                    <?php echo guvenlik($admin_uye_ad); ?>
                </span>
                
                <a href="/panel.php" class="btn-gold">
                    Panelim
                </a>
                
                <a href="/cikis.php" class="btn-outline">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                    Çıkış
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- Mobile Header (Aynı) -->
<div class="md:hidden fixed top-0 left-0 right-0 z-50 gradient-hero text-white shadow-lg">
    <div class="flex justify-between items-center px-4 h-14">
        <a href="/index.php" class="flex items-center space-x-2">
            <div class="w-8 h-8 bg-secondary rounded-lg flex items-center justify-center text-primary font-bold text-lg">H</div>
            <div>
                <span class="font-bold text-lg">HASINDER</span>
                <span class="text-xs text-secondary block -mt-1">Admin Modu</span>
            </div>
        </a>
        <button onclick="toggleMobileMenu()" class="p-2 rounded-lg hover:bg-white/10 transition">
            <i data-lucide="menu" class="w-6 h-6"></i>
        </button>
    </div>
</div>

<!-- Mobile Menu -->
<div id="mobile-menu" class="fixed inset-0 z-50 hidden md:hidden">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="toggleMobileMenu()"></div>
    <div class="absolute right-0 top-0 bottom-0 w-72 bg-white shadow-2xl transform transition-transform translate-x-full" id="mobile-menu-panel">
        <div class="p-5 gradient-hero text-white">
            <div class="flex justify-between items-center mb-4">
                <span class="font-bold text-lg">Menü</span>
                <button onclick="toggleMobileMenu()" class="p-2 hover:bg-white/10 rounded-lg">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                    <i data-lucide="user" class="w-5 h-5"></i>
                </div>
                <div>
                    <p class="font-semibold text-sm"><?php echo guvenlik($admin_uye_ad); ?></p>
                    <span class="admin-badge text-xs">Yönetici</span>
                </div>
            </div>
        </div>
        
        <div class="p-4 flex flex-col space-y-1">
            <a href="/index.php" class="flex items-center space-x-3 p-3 rounded-xl hover:bg-gray-100 text-gray-700">
                <span class="font-medium">Ana Sayfa</span>
            </a>
            <a href="/ilanlar.php" class="flex items-center space-x-3 p-3 rounded-xl hover:bg-gray-100 text-gray-700">
                <span class="font-medium">İlanlar</span>
            </a>
            <a href="/borsa.php" class="flex items-center space-x-3 p-3 rounded-xl hover:bg-gray-100 text-gray-700">
                <span class="font-medium">Borsa</span>
            </a>
            <a href="/platform.php" class="flex items-center space-x-3 p-3 rounded-xl hover:bg-gray-100 text-gray-700">
                <span class="font-medium">Platform</span>
            </a>
            <a href="/haberler.php" class="flex items-center space-x-3 p-3 rounded-xl hover:bg-gray-100 text-gray-700">
                <span class="font-medium">Haberler</span>
            </a>
            <a href="/iletisim.php" class="flex items-center space-x-3 p-3 rounded-xl hover:bg-gray-100 text-gray-700">
                <span class="font-medium">İletişim</span>
            </a>
            <a href="/kurul.php" class="flex items-center space-x-3 p-3 rounded-xl hover:bg-gray-100 text-gray-700">
                <span class="font-medium">Kurullar</span>
            </a>
            
            <div class="border-t border-gray-200 my-2"></div>
            <p class="text-xs text-gray-500 uppercase font-bold mb-2 px-3">Admin</p>
            
            <a href="/admin/index.php" class="flex items-center space-x-3 p-3 rounded-xl bg-red-50 text-red-700 border border-red-200">
                <span class="font-bold">Dashboard</span>
            </a>
            <a href="/admin/ilanlar.php" class="flex items-center space-x-3 p-3 rounded-xl hover:bg-gray-100 text-gray-700">
                <span class="font-medium">İlan Yönetimi</span>
            </a>
            <a href="/admin/uyeler.php" class="flex items-center space-x-3 p-3 rounded-xl hover:bg-gray-100 text-gray-700">
                <span class="font-medium">Üye Yönetimi</span>
            </a>
            <a href="/admin/icra-kurullari.php" class="flex items-center space-x-3 p-3 rounded-xl hover:bg-gray-100 text-gray-700">
                <span class="font-medium">Kurullar</span>
            </a>
            <a href="/admin/is-basvurulari.php" class="flex items-center space-x-3 p-3 rounded-xl hover:bg-gray-100 text-gray-700">
                <span class="font-medium">Başvurular</span>
            </a>
            
            <div class="border-t border-gray-200 my-2"></div>
            
            <a href="/cikis.php" class="flex items-center space-x-3 p-3 rounded-xl text-red-600 hover:bg-red-50">
                <span class="font-medium">Çıkış Yap</span>
            </a>
        </div>
    </div>
</div>

<!-- Spacer for fixed header -->
<div class="h-14 md:h-16"></div>

<script>
    function toggleMobileMenu() {
        const menu = document.getElementById('mobile-menu');
        const panel = document.getElementById('mobile-menu-panel');
        
        if (menu.classList.contains('hidden')) {
            menu.classList.remove('hidden');
            setTimeout(() => panel.classList.remove('translate-x-full'), 10);
        } else {
            panel.classList.add('translate-x-full');
            setTimeout(() => menu.classList.add('hidden'), 300);
        }
    }
    
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>