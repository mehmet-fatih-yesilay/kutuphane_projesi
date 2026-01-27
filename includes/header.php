<?php
/**
 * ============================================
 * HEADER - NAVİGASYON BARI
 * ============================================
 * Proje: Kitap Sosyal Ağı
 * Dosya: includes/header.php
 * Açıklama: Tüm sayfalarda kullanılacak üst kısım (header)
 * İçerik: Session başlatma, navigasyon menüsü, logo
 * ============================================
 */

// ============================================
// SESSION BAŞLATMA
// ============================================

// Eğer session başlatılmamışsa başlat
// session_status() fonksiyonu session durumunu kontrol eder
// PHP_SESSION_NONE = session başlatılmamış demektir
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Session'ı başlat
}

// ============================================
// KULLANICI GİRİŞ KONTROLÜ
// ============================================

// Kullanıcı giriş yapmış mı kontrol et
// $_SESSION['user_id'] varsa kullanıcı giriş yapmış demektir
$is_logged_in = isset($_SESSION['user_id']);

// Kullanıcı bilgilerini al (giriş yapmışsa)
$user_name = $is_logged_in ? $_SESSION['username'] : '';
$user_avatar = $is_logged_in ? $_SESSION['avatar'] : 'default-avatar.png';

// ============================================
// AKTİF SAYFA TESPİTİ
// ============================================

// Şu anki sayfanın dosya adını al (örn: index.php, dashboard.php)
$current_page = basename($_SERVER['PHP_SELF']);

// Aktif sayfa için CSS class'ı ekleyen yardımcı fonksiyon
// Kullanım: <?php echo isActive('index.php'); 
function isActive($page)
{
    global $current_page; // Global değişkeni kullan
    return ($current_page === $page) ? 'active' : ''; // Eşitse 'active' döndür
}

?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <!-- ============================================
         META ETİKETLERİ
         ============================================ -->

    <!-- Karakter seti (Türkçe karakter desteği için UTF-8) -->
    <meta charset="UTF-8">

    <!-- Responsive tasarım için viewport ayarı -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Tarayıcı uyumluluğu (IE için) -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <!-- Sayfa açıklaması (SEO için) -->
    <meta name="description" content="Kitap Sosyal Ağı - Kitapları keşfet, yorum yap, arkadaşlarınla paylaş">

    <!-- Anahtar kelimeler (SEO için) -->
    <meta name="keywords" content="kitap, sosyal ağ, okuma, yorum, öneri">

    <!-- Yazar bilgisi -->
    <meta name="author" content="Kitap Sosyal Ağı">

    <!-- Sayfa başlığı (her sayfada değişebilir) -->
    <title>
        <?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Kitap Sosyal Ağı
    </title>

    <!-- ============================================
         CSS DOSYALARI
         ============================================ -->

    <!-- Ana stil dosyası -->
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- Google Fonts (opsiyonel - daha güzel fontlar için) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome (ikonlar için - opsiyonel) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <!-- ============================================
         HEADER - PREMIUM GLASSMORPHISM NAVBAR
         ============================================ -->

    <header>
        <nav>
            <!-- ============================================
                 LOGO - GRADIENT TEXT
                 ============================================ -->

            <!-- Logo - Ana sayfaya link (Gradient efektli) -->
            <a href="index.php" class="logo">
                Kitap Sosyal Ağı
            </a>

            <!-- ============================================
                 MOBİL MENÜ BUTONU
                 ============================================ -->

            <!-- Hamburger menü butonu (mobilde görünür) -->
            <button class="menu-toggle" id="menuToggle" aria-label="Menüyü Aç/Kapat">
                <i class="fas fa-bars"></i>
            </button>

            <!-- ============================================
                 NAVİGASYON MENÜSÜ - PREMIUM DESIGN
                 ============================================ -->

            <ul class="nav-menu" id="navMenu">
                <?php if ($is_logged_in): ?>
                    <!-- Kullanıcı giriş yapmışsa bu menüyü göster -->

                    <!-- Ana Sayfa -->
                    <li>
                        <a href="index.php" class="<?php echo isActive('index.php'); ?>">
                            <i class="fas fa-home"></i>
                            <span>Ana Sayfa</span>
                        </a>
                    </li>

                    <!-- Kitaplar -->
                    <li>
                        <a href="books.php" class="<?php echo isActive('books.php'); ?>">
                            <i class="fas fa-book"></i>
                            <span>Kitaplar</span>
                        </a>
                    </li>

                    <!-- Keşfet -->
                    <li>
                        <a href="explore.php" class="<?php echo isActive('explore.php'); ?>">
                            <i class="fas fa-compass"></i>
                            <span>Keşfet</span>
                        </a>
                    </li>

                    <!-- Profil -->
                    <li>
                        <a href="profile.php" class="<?php echo isActive('profile.php'); ?>">
                            <i class="fas fa-user-circle"></i>
                            <span>Profil</span>
                        </a>
                    </li>

                    <!-- Kullanıcı Avatar (Sadece mobilde gizli) -->
                    <li style="margin-left: 8px;">
                        <a href="profile.php" style="display: flex; align-items: center; gap: 10px; padding: 6px 12px;">
                            <img src="uploads/avatars/<?php echo htmlspecialchars($user_avatar); ?>"
                                alt="<?php echo htmlspecialchars($user_name); ?>" class="avatar">
                            <span style="font-weight: 600; color: var(--anthracite);">
                                <?php echo htmlspecialchars($user_name); ?>
                            </span>
                        </a>
                    </li>

                    <!-- Çıkış Yap -->
                    <li>
                        <a href="logout.php" class="btn btn-danger btn-sm" style="margin-left: 8px;">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Çıkış</span>
                        </a>
                    </li>

                <?php else: ?>
                    <!-- Kullanıcı giriş yapmamışsa bu menüyü göster -->

                    <!-- Ana Sayfa -->
                    <li>
                        <a href="index.php" class="<?php echo isActive('index.php'); ?>">
                            <i class="fas fa-home"></i>
                            <span>Ana Sayfa</span>
                        </a>
                    </li>

                    <!-- Hakkında -->
                    <li>
                        <a href="about.php" class="<?php echo isActive('about.php'); ?>">
                            <i class="fas fa-info-circle"></i>
                            <span>Hakkında</span>
                        </a>
                    </li>

                    <!-- Giriş Yap -->
                    <li style="margin-left: 16px;">
                        <a href="login.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Giriş Yap</span>
                        </a>
                    </li>

                    <!-- Kayıt Ol -->
                    <li>
                        <a href="register.php" class="btn btn-accent btn-sm">
                            <i class="fas fa-user-plus"></i>
                            <span>Kayıt Ol</span>
                        </a>
                    </li>

                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <!-- ============================================
         MAIN CONTENT BAŞLANGICI
         ============================================ -->

    <!-- Ana içerik her sayfada buradan başlar -->
    <main>
        <!-- Sayfa içeriği buraya gelecek -->