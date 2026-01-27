<?php
/**
 * ============================================
 * YARDIMCI FONKSİYONLAR
 * ============================================
 * Proje: Kitap Sosyal Ağı
 * Dosya: includes/functions.php
 * Açıklama: Projede kullanılacak genel yardımcı fonksiyonlar
 * İçerik: Güvenlik, veri temizleme, validasyon fonksiyonları
 * ============================================
 */

// ============================================
// GÜVENLİK FONKSİYONLARI
// ============================================

/**
 * Gelen veriyi temizler (sanitize)
 * XSS (Cross-Site Scripting) saldırılarına karşı koruma sağlar
 * 
 * @param string $data Temizlenecek veri
 * @return string Temizlenmiş veri
 * 
 * Kullanım:
 * $clean_name = sanitize($_POST['name']);
 */
function sanitize($data)
{
    // Eğer veri boşsa boş string döndür
    if (empty($data)) {
        return '';
    }

    // Başındaki ve sonundaki boşlukları kaldır
    $data = trim($data);

    // Ters slash'leri kaldır (magic quotes için)
    // Örnek: O\'Reilly -> O'Reilly
    $data = stripslashes($data);

    // HTML özel karakterlerini dönüştür (XSS koruması)
    // Örnek: <script> -> &lt;script&gt;
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');

    // Temizlenmiş veriyi döndür
    return $data;
}

/**
 * E-posta adresini temizler ve doğrular
 * 
 * @param string $email Temizlenecek e-posta
 * @return string|false Geçerliyse temiz e-posta, değilse false
 * 
 * Kullanım:
 * $clean_email = sanitize_email($_POST['email']);
 * if ($clean_email === false) {
 *     echo "Geçersiz e-posta!";
 * }
 */
function sanitize_email($email)
{
    // Başındaki ve sonundaki boşlukları kaldır
    $email = trim($email);

    // E-posta filtresini uygula (geçersiz karakterleri kaldır)
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);

    // E-posta formatını doğrula
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Geçerliyse küçük harfe çevir ve döndür
        return strtolower($email);
    } else {
        // Geçersizse false döndür
        return false;
    }
}

/**
 * URL'yi temizler ve doğrular
 * 
 * @param string $url Temizlenecek URL
 * @return string|false Geçerliyse temiz URL, değilse false
 * 
 * Kullanım:
 * $clean_url = sanitize_url($_POST['website']);
 */
function sanitize_url($url)
{
    // Başındaki ve sonundaki boşlukları kaldır
    $url = trim($url);

    // URL filtresini uygula
    $url = filter_var($url, FILTER_SANITIZE_URL);

    // URL formatını doğrula
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        // Geçerliyse döndür
        return $url;
    } else {
        // Geçersizse false döndür
        return false;
    }
}

/**
 * Sayısal veriyi temizler ve doğrular
 * 
 * @param mixed $number Temizlenecek sayı
 * @return int|false Geçerliyse integer, değilse false
 * 
 * Kullanım:
 * $user_id = sanitize_int($_GET['id']);
 */
function sanitize_int($number)
{
    // Integer filtresini uygula
    $number = filter_var($number, FILTER_SANITIZE_NUMBER_INT);

    // Integer formatını doğrula
    if (filter_var($number, FILTER_VALIDATE_INT) !== false) {
        // Geçerliyse integer'a çevir ve döndür
        return (int) $number;
    } else {
        // Geçersizse false döndür
        return false;
    }
}

/**
 * Şifreyi güvenli şekilde hashler (şifreler)
 * 
 * @param string $password Hashlenecek şifre
 * @return string Hashlenmiş şifre
 * 
 * Kullanım:
 * $hashed_password = hash_password($_POST['password']);
 */
function hash_password($password)
{
    // PHP'nin yerleşik password_hash fonksiyonunu kullan
    // PASSWORD_DEFAULT: En güncel ve güvenli algoritmayı kullanır (şu an bcrypt)
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Şifreyi doğrular (hash ile karşılaştırır)
 * 
 * @param string $password Kullanıcının girdiği şifre
 * @param string $hash Veritabanındaki hashlenmiş şifre
 * @return bool Eşleşiyorsa true, değilse false
 * 
 * Kullanım:
 * if (verify_password($_POST['password'], $user['password'])) {
 *     echo "Giriş başarılı!";
 * }
 */
function verify_password($password, $hash)
{
    // PHP'nin yerleşik password_verify fonksiyonunu kullan
    return password_verify($password, $hash);
}

// ============================================
// VALİDASYON FONKSİYONLARI
// ============================================

/**
 * Kullanıcı adını doğrular
 * Kurallar: 3-50 karakter, sadece harf, rakam, alt çizgi
 * 
 * @param string $username Doğrulanacak kullanıcı adı
 * @return bool Geçerliyse true, değilse false
 * 
 * Kullanım:
 * if (!validate_username($_POST['username'])) {
 *     echo "Geçersiz kullanıcı adı!";
 * }
 */
function validate_username($username)
{
    // Uzunluk kontrolü (3-50 karakter)
    if (strlen($username) < 3 || strlen($username) > 50) {
        return false;
    }

    // Regex kontrolü: sadece harf, rakam, alt çizgi
    // ^: başlangıç, $: bitiş, [a-zA-Z0-9_]: izin verilen karakterler, +: bir veya daha fazla
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        return false;
    }

    // Tüm kontroller geçtiyse true döndür
    return true;
}

/**
 * Şifreyi doğrular
 * Kurallar: Minimum 6 karakter
 * 
 * @param string $password Doğrulanacak şifre
 * @return bool Geçerliyse true, değilse false
 * 
 * Kullanım:
 * if (!validate_password($_POST['password'])) {
 *     echo "Şifre en az 6 karakter olmalıdır!";
 * }
 */
function validate_password($password)
{
    // Uzunluk kontrolü (minimum 6 karakter)
    if (strlen($password) < 6) {
        return false;
    }

    // Daha güçlü şifre için ek kontroller eklenebilir:
    // - En az bir büyük harf
    // - En az bir küçük harf
    // - En az bir rakam
    // - En az bir özel karakter

    // Örnek güçlü şifre kontrolü (opsiyonel):
    // if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
    //     return false;
    // }

    return true;
}

/**
 * Puan (rating) değerini doğrular
 * Kurallar: 1-5 arası tam sayı
 * 
 * @param int $rating Doğrulanacak puan
 * @return bool Geçerliyse true, değilse false
 * 
 * Kullanım:
 * if (!validate_rating($_POST['rating'])) {
 *     echo "Puan 1-5 arası olmalıdır!";
 * }
 */
function validate_rating($rating)
{
    // Integer'a çevir
    $rating = (int) $rating;

    // 1-5 arası kontrol et
    return ($rating >= 1 && $rating <= 5);
}

// ============================================
// YARDIMCI FONKSİYONLAR
// ============================================

/**
 * Kullanıcıyı belirtilen sayfaya yönlendirir
 * 
 * @param string $page Yönlendirilecek sayfa
 * @return void
 * 
 * Kullanım:
 * redirect('dashboard.php');
 */
function redirect($page)
{
    // HTTP header ile yönlendirme yap
    header("Location: $page");

    // Scripti durdur (yönlendirmeden sonra kod çalışmasın)
    exit();
}

/**
 * Flash mesaj oluşturur (session'da saklanır, bir kez gösterilir)
 * 
 * @param string $type Mesaj tipi (success, error, warning, info)
 * @param string $message Mesaj içeriği
 * @return void
 * 
 * Kullanım:
 * set_flash('success', 'Kayıt başarılı!');
 */
function set_flash($type, $message)
{
    // Session'ı başlat (başlatılmamışsa)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Flash mesajı session'a kaydet
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Flash mesajı gösterir ve siler
 * 
 * @return string HTML formatında flash mesaj
 * 
 * Kullanım:
 * echo get_flash();
 */
function get_flash()
{
    // Session'ı başlat (başlatılmamışsa)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Eğer flash mesaj varsa
    if (isset($_SESSION['flash'])) {
        // Mesajı al
        $flash = $_SESSION['flash'];

        // Session'dan sil (bir kez gösterilsin)
        unset($_SESSION['flash']);

        // HTML formatında döndür
        $html = '<div class="alert alert-' . $flash['type'] . '">';
        $html .= htmlspecialchars($flash['message']);
        $html .= '</div>';

        return $html;
    }

    // Flash mesaj yoksa boş string döndür
    return '';
}

/**
 * Tarihi Türkçe formatında gösterir
 * 
 * @param string $date Tarih (MySQL formatı: Y-m-d H:i:s)
 * @return string Türkçe formatlanmış tarih
 * 
 * Kullanım:
 * echo format_date($review['created_at']); // "27 Ocak 2026, 09:42"
 */
function format_date($date)
{
    // Türkçe ay isimleri
    $months = [
        1 => 'Ocak',
        2 => 'Şubat',
        3 => 'Mart',
        4 => 'Nisan',
        5 => 'Mayıs',
        6 => 'Haziran',
        7 => 'Temmuz',
        8 => 'Ağustos',
        9 => 'Eylül',
        10 => 'Ekim',
        11 => 'Kasım',
        12 => 'Aralık'
    ];

    // Tarihi parçalara ayır
    $timestamp = strtotime($date);
    $day = date('d', $timestamp);
    $month = $months[(int) date('m', $timestamp)];
    $year = date('Y', $timestamp);
    $time = date('H:i', $timestamp);

    // Formatlanmış tarihi döndür
    return "$day $month $year, $time";
}

/**
 * Göreceli zaman gösterir (örn: "2 saat önce")
 * 
 * @param string $date Tarih (MySQL formatı: Y-m-d H:i:s)
 * @return string Göreceli zaman
 * 
 * Kullanım:
 * echo time_ago($review['created_at']); // "2 saat önce"
 */
function time_ago($date)
{
    // Şimdiki zaman ile verilen zaman arasındaki farkı hesapla
    $timestamp = strtotime($date);
    $diff = time() - $timestamp;

    // Saniye cinsinden farkları kontrol et
    if ($diff < 60) {
        return 'Az önce';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' dakika önce';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' saat önce';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' gün önce';
    } elseif ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return $weeks . ' hafta önce';
    } else {
        // 1 aydan eski ise tam tarihi göster
        return format_date($date);
    }
}

/**
 * Metni kısaltır (excerpt)
 * 
 * @param string $text Kısaltılacak metin
 * @param int $limit Karakter limiti (varsayılan: 150)
 * @return string Kısaltılmış metin
 * 
 * Kullanım:
 * echo excerpt($review['comment'], 100); // "Bu kitap çok güzeldi..."
 */
function excerpt($text, $limit = 150)
{
    // Eğer metin limitten kısaysa olduğu gibi döndür
    if (strlen($text) <= $limit) {
        return $text;
    }

    // Metni kısalt ve sonuna "..." ekle
    return substr($text, 0, $limit) . '...';
}

/**
 * Yıldız rating'i HTML olarak gösterir
 * 
 * @param int $rating Puan (1-5)
 * @return string HTML formatında yıldızlar
 * 
 * Kullanım:
 * echo show_stars($review['rating']); // ★★★★☆
 */
function show_stars($rating)
{
    $html = '';

    // Dolu yıldızlar
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            // Dolu yıldız
            $html .= '<i class="fas fa-star" style="color: #fbbf24;"></i>';
        } else {
            // Boş yıldız
            $html .= '<i class="far fa-star" style="color: #fbbf24;"></i>';
        }
    }

    return $html;
}

// ============================================
// FONKSİYONLAR DOSYASI SONU
// ============================================
// Bu dosya diğer PHP dosyalarında şu şekilde dahil edilir:
// require_once 'includes/functions.php';
// ============================================
?>