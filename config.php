<?php
if (defined('CONFIG_LOADED')) return;
define('CONFIG_LOADED', true);

// UTF-8
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

// Hata raporlama (geliştirme - canlıda 0 yapın)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
    session_start();
}

// ─── VERİTABANI ───────────────────────────────────────────────────────────────
$db_host = 'localhost';
$db_name = 'hasinder_webuser';
$db_user = 'hasinder_webuser';
$db_pass = 'Hasinder2024Web';   // ← kendi şifrenizle değiştirin

try {
    $db = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_general_ci",
    ]);
    $pdo = $db;
    $GLOBALS['db']  = $db;
    $GLOBALS['pdo'] = $pdo;
} catch (PDOException $e) {
    error_log("DB Hatası: " . $e->getMessage());
    die("<h2 style='font-family:Arial;color:red;text-align:center;padding:50px;'>Veritabanı bağlantı hatası. Lütfen config.php ayarlarını kontrol edin.</h2>");
}

// ─── SABIT TANIMLAR ──────────────────────────────────────────────────────────
define('SITE_URL',       'https://www.hasinder.com');
define('SITE_ADI',       'Hasinder - B2B Platform');
define('ADMIN_EMAIL',    'kurt.hakki@gmail.com');
define('ADMIN_WHATSAPP', '+905333715577');
define('MAX_FILE_SIZE',  5 * 1024 * 1024);
define('IZINLI_UZANTILAR', ['jpg','jpeg','png','pdf','doc','docx']);

date_default_timezone_set('Europe/Istanbul');

// ─── FONKSİYONLAR ─────────────────────────────────────────────────────────────

function guvenlik($veri) {
    return htmlspecialchars(trim((string)$veri), ENT_QUOTES, 'UTF-8');
}

function yetkili() {
    return isset($_SESSION['uye_id']) && isset($_SESSION['durum']) && $_SESSION['durum'] === 'AKTIF';
}

function admin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

function yetkiKontrol(array $roller = ['admin']) {
    if (!yetkili()) {
        header('Location: ' . SITE_URL . '/giris.php');
        exit;
    }
    if (in_array('admin', $roller) && !admin()) {
        header('Location: ' . SITE_URL . '/giris.php');
        exit;
    }
}

function bildirim($mesaj, $tip = 'success') {
    $_SESSION['bildirim'] = ['mesaj' => $mesaj, 'tip' => $tip];
}

function bildirimGoster() {
    if (!isset($_SESSION['bildirim'])) return '';
    $tip   = $_SESSION['bildirim']['tip'];
    $mesaj = guvenlik($_SESSION['bildirim']['mesaj']);
    unset($_SESSION['bildirim']);
    $bg    = $tip === 'success' ? '#d4edda' : '#f8d7da';
    $color = $tip === 'success' ? '#155724' : '#721c24';
    return "<div style='padding:14px 18px;margin-bottom:20px;border-radius:6px;background:{$bg};color:{$color};font-weight:600;'>{$mesaj}</div>";
}

function para($tutar) {
    return number_format((float)$tutar, 2, ',', '.') . ' TL';
}

function site_url($yol = '') {
    return SITE_URL . '/' . ltrim($yol, '/');
}

// ─── BİLDİRİM FONKSİYONLARI ──────────────────────────────────────────────────

function mailGonder($kime, $konu, $icerik) {
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SITE_ADI . " <" . ADMIN_EMAIL . ">\r\n";
    return @mail($kime, '=?UTF-8?B?' . base64_encode($konu) . '?=', $icerik, $headers);
}

function whatsappGonder($telefon, $mesaj) {
    // CallMeBot'tan API key alın: https://www.callmebot.com/blog/free-api-whatsapp-messages/
    $apiKey  = 'CALLMEBOT_API_KEY';
    $telefon = preg_replace('/[^0-9]/', '', $telefon);
    $url = "https://api.callmebot.com/whatsapp.php?phone={$telefon}&text=" . urlencode($mesaj) . "&apikey={$apiKey}";
    $ch  = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
    $r = curl_exec($ch);
    curl_close($ch);
    return $r;
}

function sendAdminNotification($mesaj, $telefon = ADMIN_WHATSAPP, $email = ADMIN_EMAIL) {
    mailGonder($email, 'Hasinder Bildirimi', '<p>' . nl2br(guvenlik($mesaj)) . '</p>');
    whatsappGonder($telefon, '[Hasinder] ' . $mesaj);
}
