<?php
/**
 * ============================================
 * VERİTABANI BAĞLANTI DOSYASI
 * ============================================
 * Proje: Kitap Sosyal Ağı
 * Dosya: includes/db.php
 * Açıklama: PDO kullanarak MySQL veritabanına güvenli bağlantı sağlar
 * Karakter Seti: UTF-8
 * ============================================
 */

// ============================================
// VERİTABANI BAĞLANTI BİLGİLERİ
// ============================================

// Veritabanı sunucu adresi (localhost = yerel sunucu)
define('DB_HOST', 'localhost');

// Veritabanı adı
define('DB_NAME', 'kitap_sosyal_agi');

// Veritabanı kullanıcı adı (XAMPP varsayılan: root)
define('DB_USER', 'root');

// Veritabanı şifresi (XAMPP varsayılan: boş)
define('DB_PASS', '');

// Karakter seti (Türkçe karakter desteği için UTF-8)
define('DB_CHARSET', 'utf8mb4');

// ============================================
// PDO BAĞLANTI İŞLEMİ
// ============================================

try {
    // DSN (Data Source Name) oluştur
    // Format: mysql:host=sunucu;dbname=veritabanı;charset=karakter_seti
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

    // PDO seçeneklerini tanımla (güvenlik ve performans için)
    $options = [
        // Hata modunu exception (istisna) olarak ayarla
        // Bu sayede hataları try-catch ile yakalayabiliriz
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

        // Varsayılan fetch modunu associative array (ilişkisel dizi) yap
        // Sonuçları $row['kolon_adi'] şeklinde kullanabiliriz
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

        // Emulated prepared statements'ı kapat (gerçek prepared statements kullan)
        // SQL injection saldırılarına karşı daha güvenli
        PDO::ATTR_EMULATE_PREPARES => false,

        // Kalıcı bağlantı kullanma (her istekte yeni bağlantı aç)
        // Paylaşımlı hosting ortamlarında sorun çıkarabilir
        PDO::ATTR_PERSISTENT => false,

        // Bağlantı timeout süresi (saniye cinsinden)
        PDO::ATTR_TIMEOUT => 5
    ];

    // PDO nesnesi oluştur ve veritabanına bağlan
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    // Bağlantı başarılı mesajı (geliştirme aşamasında kullanılabilir)
    // Canlı ortamda bu satırı kaldırın veya yorum satırı yapın
    // echo "Veritabanı bağlantısı başarılı!";

} catch (PDOException $e) {
    // ============================================
    // HATA YAKALAMA VE RAPORLAMA
    // ============================================

    // Hata mesajını kullanıcı dostu formatta hazırla
    $error_message = "Veritabanı Bağlantı Hatası: " . $e->getMessage();

    // Hata kodunu al
    $error_code = $e->getCode();

    // Hatanın oluştuğu dosya ve satır numarasını al
    $error_file = $e->getFile();
    $error_line = $e->getLine();

    // Geliştirme ortamında detaylı hata göster
    // Canlı ortamda bu bloku kaldırın ve sadece log kayıt edin
    echo "<!DOCTYPE html>";
    echo "<html lang='tr'>";
    echo "<head>";
    echo "<meta charset='UTF-8'>";
    echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
    echo "<title>Veritabanı Hatası</title>";
    echo "<style>";
    echo "body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }";
    echo ".error-box { background-color: #fff; border-left: 5px solid #20B2AA; padding: 20px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }";
    echo "h2 { color: #20B2AA; margin-top: 0; }";
    echo "p { color: #333; line-height: 1.6; }";
    echo ".error-details { background-color: #f9f9f9; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px; }";
    echo "</style>";
    echo "</head>";
    echo "<body>";
    echo "<div class='error-box'>";
    echo "<h2>⚠️ Veritabanı Bağlantı Hatası</h2>";
    echo "<p><strong>Hata Mesajı:</strong> " . htmlspecialchars($error_message) . "</p>";
    echo "<p><strong>Hata Kodu:</strong> " . htmlspecialchars($error_code) . "</p>";
    echo "<div class='error-details'>";
    echo "<strong>Dosya:</strong> " . htmlspecialchars($error_file) . "<br>";
    echo "<strong>Satır:</strong> " . htmlspecialchars($error_line);
    echo "</div>";
    echo "<p style='margin-top: 20px; color: #666; font-size: 14px;'>";
    echo "💡 <strong>Çözüm Önerileri:</strong><br>";
    echo "1. XAMPP'in çalıştığından emin olun (Apache ve MySQL)<br>";
    echo "2. Veritabanı adının doğru olduğunu kontrol edin<br>";
    echo "3. database.sql dosyasını phpMyAdmin'de çalıştırın<br>";
    echo "4. Kullanıcı adı ve şifrenin doğru olduğunu kontrol edin";
    echo "</p>";
    echo "</div>";
    echo "</body>";
    echo "</html>";

    // Hata loguna kaydet (production ortamı için)
    // error_log($error_message, 3, __DIR__ . '/../logs/db_errors.log');

    // Scripti durdur (bağlantı olmadan devam edilemez)
    exit();
}

// ============================================
// YARDIMCI FONKSİYONLAR
// ============================================

/**
 * Veritabanı bağlantısını test eder
 * @return bool Bağlantı başarılıysa true, değilse false
 */
function testConnection()
{
    global $pdo;
    try {
        // Basit bir sorgu çalıştırarak bağlantıyı test et
        $pdo->query("SELECT 1");
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Veritabanı bağlantısını kapatır
 * @return void
 */
function closeConnection()
{
    global $pdo;
    // PDO bağlantısını null yaparak kapat
    $pdo = null;
}

// ============================================
// BAĞLANTI DOSYASI SONU
// ============================================
// Bu dosya diğer PHP dosyalarında şu şekilde dahil edilir:
// require_once 'includes/db.php';
// ============================================
?>