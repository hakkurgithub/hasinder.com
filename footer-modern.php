<!-- Modern Footer -->
</main>

<!-- Bottom Navigation (Mobile Only) - Instagram Style -->
<nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 pb-safe z-50 shadow-[0_-5px_20px_rgba(0,0,0,0.1)]">
    <div class="flex justify-around items-center h-16 relative">
        <a href="/index-modern.php" class="flex flex-col items-center justify-center w-full h-full text-gray-400 hover:text-primary transition <?php echo basename($_SERVER['PHP_SELF']) == 'index-modern.php' ? 'text-primary' : ''; ?>">
            <i data-lucide="home" class="w-6 h-6 mb-1"></i>
            <span class="text-[10px] font-medium">Ana Sayfa</span>
        </a>
        
        <a href="/smart-search.php" class="flex flex-col items-center justify-center w-full h-full text-gray-400 hover:text-primary transition">
            <i data-lucide="compass" class="w-6 h-6 mb-1"></i>
            <span class="text-[10px] font-medium">Keşfet</span>
        </a>
        
        <!-- Floating Action Button -->
        <a href="/ilan-ver.php" class="absolute -top-6 left-1/2 transform -translate-x-1/2 w-14 h-14 bg-gradient-to-r from-secondary to-yellow-400 rounded-full flex items-center justify-center text-primary shadow-lg hover:scale-110 transition-transform border-4 border-gray-50">
            <i data-lucide="plus" class="w-7 h-7"></i>
        </a>
        
        <a href="/kurul.php" class="flex flex-col items-center justify-center w-full h-full text-gray-400 hover:text-primary transition">
            <i data-lucide="award" class="w-6 h-6 mb-1"></i>
            <span class="text-[10px] font-medium">Kurullar</span>
        </a>
        
        <a href="<?php echo isset($_SESSION['uye_id']) ? '/panel.php' : '/giris.php'; ?>" class="flex flex-col items-center justify-center w-full h-full text-gray-400 hover:text-primary transition <?php echo basename($_SERVER['PHP_SELF']) == 'panel.php' ? 'text-primary' : ''; ?>">
            <i data-lucide="user" class="w-6 h-6 mb-1"></i>
            <span class="text-[10px] font-medium">Profil</span>
        </a>
    </div>
</nav>

<!-- Desktop Footer -->
<footer class="hidden md:block bg-primary text-white mt-20 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-r from-primary to-blue-900 opacity-90"></div>
    <div class="absolute -top-24 -right-24 w-96 h-96 bg-secondary/20 rounded-full blur-3xl"></div>
    <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
    
    <div class="relative max-w-7xl mx-auto px-4 py-16">
        <div class="grid grid-cols-4 gap-12">
            <div class="col-span-1">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="w-12 h-12 bg-secondary rounded-xl flex items-center justify-center text-primary font-bold text-2xl">H</div>
                    <div>
                        <span class="font-bold text-2xl">HASINDER</span>
                    </div>
                </div>
                <p class="text-gray-300 text-sm leading-relaxed mb-6">
                    İstanbul Hatay Sanayici ve İş İnsanları Yatırım ve İşbirliği Platformu. B2B ticaretin yeni nesil adresi.
                </p>
                <div class="flex space-x-4">
                    <a href="#" class="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center hover:bg-secondary hover:text-primary transition">
                        <i data-lucide="instagram" class="w-5 h-5"></i>
                    </a>
                    <a href="#" class="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center hover:bg-secondary hover:text-primary transition">
                        <i data-lucide="linkedin" class="w-5 h-5"></i>
                    </a>
                    <a href="#" class="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center hover:bg-secondary hover:text-primary transition">
                        <i data-lucide="twitter" class="w-5 h-5"></i>
                    </a>
                </div>
            </div>
            
            <div>
                <h4 class="font-bold text-lg mb-6 text-secondary">Hızlı Linkler</h4>
                <ul class="space-y-3 text-gray-300">
                    <li><a href="/ilanlar.php" class="hover:text-white transition flex items-center"><i data-lucide="chevron-right" class="w-4 h-4 mr-2"></i>İlanlar</a></li>
                    <li><a href="/kurul.php" class="hover:text-white transition flex items-center"><i data-lucide="chevron-right" class="w-4 h-4 mr-2"></i>İcra Kurulları</a></li>
                    <li><a href="/is-basvuru-formu.php" class="hover:text-white transition flex items-center"><i data-lucide="chevron-right" class="w-4 h-4 mr-2"></i>İş Başvurusu</a></li>
                    <li><a href="/haberler.php" class="hover:text-white transition flex items-center"><i data-lucide="chevron-right" class="w-4 h-4 mr-2"></i>Haberler</a></li>
                </ul>
            </div>
            
            <div>
                <h4 class="font-bold text-lg mb-6 text-secondary">Komisyonlar</h4>
                <ul class="space-y-3 text-gray-300">
                    <li><a href="/kurul.php" class="hover:text-white transition">Hukuk Komisyonu</a></li>
                    <li><a href="/kurul.php" class="hover:text-white transition">Mali Müşavirler</a></li>
                    <li><a href="/kurul.php" class="hover:text-white transition">Emlak Değerleme</a></li>
                    <li><a href="/kurul.php" class="hover:text-white transition">Lojistik</a></li>
                </ul>
            </div>
            
            <div>
                <h4 class="font-bold text-lg mb-6 text-secondary">İletişim</h4>
                <ul class="space-y-4 text-gray-300">
                    <li class="flex items-start space-x-3">
                        <i data-lucide="map-pin" class="w-5 h-5 mt-0.5 text-secondary"></i>
                        <span>İstanbul / Türkiye</span>
                    </li>
                    <li class="flex items-center space-x-3">
                        <i data-lucide="phone" class="w-5 h-5 text-secondary"></i>
                        <span>+90 533 371 55 77</span>
                    </li>
                    <li class="flex items-center space-x-3">
                        <i data-lucide="mail" class="w-5 h-5 text-secondary"></i>
                        <span>kurt.hakki@gmail.com</span>
                    </li>
                </ul>
                
                <div class="mt-6 p-4 bg-white/10 rounded-xl">
                    <p class="text-sm font-semibold mb-2">Mobil Uygulama</p>
                    <p class="text-xs text-gray-400 mb-3">Çok yakında App Store ve Google Play'de!</p>
                    <div class="flex space-x-2">
                        <div class="h-8 bg-black/30 rounded flex-1"></div>
                        <div class="h-8 bg-black/30 rounded flex-1"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="border-t border-white/10 mt-12 pt-8 flex justify-between items-center text-sm text-gray-400">
            <p>&copy; 2026 Hasinder. Tüm hakları saklıdır.</p>
            <div class="flex space-x-6">
                <a href="#" class="hover:text-white">Gizlilik Politikası</a>
                <a href="#" class="hover:text-white">Kullanım Şartları</a>
            </div>
        </div>
    </div>
</footer>

<script>
    // Re-initialize Lucide icons for dynamically added content
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>

</body>
</html>