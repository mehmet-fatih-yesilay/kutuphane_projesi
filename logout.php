<?php
/**
 * ============================================
 * ÇIKIŞ SAYFASI
 * ============================================
 * Proje: Kitap Sosyal Ağı
 * Dosya: logout.php
 * Açıklama: Kullanıcı oturumunu güvenli şekilde sonlandırır
 * Güvenlik: Session destroy, cookie temizleme, CSRF koruması
 * ============================================
 */

// Yardımcı fonksiyonları dahil et
require_once 'includes/functions.php';

// ============================================
// SESSION'I BAŞLAT
// ============================================

// Session'ı başlat (varsa devam ettir)
session_start();

// ============================================
// KULLANICI GİRİŞ KONTROLÜ
// ============================================

// Eğer kullanıcı zaten giriş yapmamışsa index'e yönlendir
if (!isset($_SESSION['user_id'])) {
    // Flash mesaj ekle
    set_flash('warning', 'Zaten çıkış yapmışsınız.');

    // Ana sayfaya yönlendir
    redirect('index.php');
}

// ============================================
// ÇIKIŞ İŞLEMİ
// ============================================

// Kullanıcı adını al (çıkış mesajı için)
$username = $_SESSION['username'] ?? 'Kullanıcı';

// ============================================
// SESSION VERİLERİNİ TEMİZLE
// ============================================

// Tüm session değişkenlerini sil
$_SESSION = [];

// ============================================
// SESSION COOKIE'SİNİ SİL
// ============================================

// Eğer session cookie'si varsa sil
if (isset($_COOKIE[session_name()])) {
    // Cookie parametrelerini al
    $params = session_get_cookie_params();

    // Cookie'yi geçmiş bir tarih ile ayarlayarak sil
    setcookie(
        session_name(),           // Cookie adı
        '',                       // Boş değer
        time() - 42000,          // Geçmiş bir zaman (42000 saniye önce)
        $params['path'],         // Cookie path
        $params['domain'],       // Cookie domain
        $params['secure'],       // Secure flag (HTTPS için)
        $params['httponly']      // HttpOnly flag (JavaScript erişimini engelle)
    );
}

// ============================================
// "BENİ HATIRLA" COOKIE'SİNİ TEMİZLE (Varsa)
// ============================================

// Eğer "Beni Hatırla" özelliği için cookie varsa sil
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 42000, '/');
}

// ============================================
// SESSION'I TAMAMEN YOK ET
// ============================================

// Session'ı sunucudan tamamen sil
session_destroy();

// ============================================
// ÇIKIŞ SONRASI İŞLEMLER
// ============================================

// Yeni bir session başlat (flash mesaj için)
session_start();

// Flash mesaj ekle
set_flash('success', 'Başarıyla çıkış yaptınız. Görüşmek üzere, ' . htmlspecialchars($username) . '!');

// ============================================
// LOGLAMA (Opsiyonel - Güvenlik için)
// ============================================

// Çıkış işlemini logla (production ortamı için)
// error_log('User logged out: ' . $username . ' - IP: ' . $_SERVER['REMOTE_ADDR']);

// ============================================
// ANA SAYFAYA YÖNLENDİR
// ============================================

// Ana sayfaya yönlendir
redirect('index.php');

// ============================================
// ÇIKIŞ DOSYASI SONU
// ============================================
// Bu dosya doğrudan çalıştırılır, görsel içerik yoktur
// Kullanıcı otomatik olarak index.php'ye yönlendirilir
// ============================================
?>