<?php
/**
 * ============================================
 * HEADER - CLEAN DARK NAVBAR
 * ============================================
 * Proje: Kitap Sosyal Ağı
 * Dosya: includes/header.php
 * Tasarım: Clean Dark & Vibrant Accents
 * ============================================
 */

// Session kontrolü (eğer başlatılmamışsa başlat)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kullanıcı giriş yapmış mı kontrol et
$is_logged_in = isset($_SESSION['user_id']);

// Eğer giriş yapmışsa kullanıcı bilgilerini al
if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $user_name = $_SESSION['username'];
    $user_full_name = $_SESSION['full_name'];
    $user_avatar = $_SESSION['avatar'];
}

// Aktif sayfa tespiti için mevcut sayfayı al
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
         META TAGS
         ============================================ -->

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kitap Sosyal Ağı - Kitapseverlerin buluşma noktası">
    <meta name="author" content="Kitap Sosyal Ağı">

    <!-- ============================================
         TITLE
         ============================================ -->

    <title>
        <?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - Kitap Sosyal Ağı' : 'Kitap Sosyal Ağı'; ?>
    </title>

    <!-- ============================================
         CSS
         ============================================ -->

    <!-- Ana CSS Dosyası -->
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- ============================================
         FONT AWESOME (İkonlar)
         ============================================ -->

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>

<body>
    <!-- ============================================
         HEADER - CLEAN DARK NAVBAR
         ============================================ -->

    <header>
        <nav>
            <!-- ============================================
                 LOGO - "LONELY EYE" GRADIENT
                 ============================================ -->

            <!-- Logo - Deep Blue to Baby Blue Gradient with Icon -->
            <a href="<?php echo $is_logged_in ? 'dashboard.php' : 'index.php'; ?>" class="logo">
                <i class="fas fa-book brand-icon"></i>
                Lonely Eye
            </a>

            <!-- ============================================
                 MOBİL MENÜ BUTONU
                 ============================================ -->

            <!-- Hamburger menü butonu (mobilde görünür) -->
            <button class="menu-toggle" id="menuToggle" aria-label="Menüyü Aç/Kapat">
                <i class="fas fa-bars"></i>
            </button>

            <!-- ============================================
                 NAVİGASYON MENÜSÜ
                 ============================================ -->

            <ul class="nav-menu" id="navMenu">
                <?php if ($is_logged_in): ?>
                    <!-- Kullanıcı giriş yapmışsa bu menüyü göster -->

                    <!-- Dashboard -->
                    <li>
                        <a href="dashboard.php" class="<?php echo isActive('dashboard.php'); ?>">
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

                    <!-- Kullanıcı Avatar -->
                    <li style="margin-left: var(--space-md);">
                        <a href="profile.php"
                            style="display: flex; align-items: center; gap: var(--space-sm); padding: var(--space-xs) var(--space-md);">
                            <img src="uploads/avatars/<?php echo htmlspecialchars($user_avatar); ?>"
                                alt="<?php echo htmlspecialchars($user_name); ?>" class="avatar">
                            <span style="font-weight: 700; color: var(--text-primary);">
                                <?php echo htmlspecialchars($user_name); ?>
                            </span>
                        </a>
                    </li>

                    <!-- Çıkış Yap -->
                    <li>
                        <a href="logout.php" class="btn btn-danger btn-sm" style="margin-left: var(--space-sm);">
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

                    <!-- Giriş Yap - Purple -->
                    <li style="margin-left: var(--space-xl);">
                        <a href="login.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Giriş Yap</span>
                        </a>
                    </li>

                    <!-- Kayıt Ol - Baby Blue -->
                    <li>
                        <a href="register.php" class="btn btn-gold btn-sm">
                            <i class="fas fa-user-plus"></i>
                            <span>Kayıt Ol</span>
                        </a>
                    </li>

                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <!-- ============================================
         MAIN CONTENT BAŞLANGIÇ
         ============================================ -->

    <!-- Main content buradan başlıyor, her sayfada devam edecek -->
    <main>