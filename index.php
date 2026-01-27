<?php
/**
 * ============================================
 * INDEX - SADECE VİTRİN SAYFASI
 * ============================================
 * Proje: Kitap Sosyal Ağı
 * Dosya: index.php
 * Açıklama: Giriş yapmamış kullanıcılar için basit landing page
 * ============================================
 */

// Session'ı başlat
session_start();

// Eğer kullanıcı zaten giriş yapmışsa dashboard'a yönlendir
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Veritabanı bağlantısını dahil et
require_once 'includes/db.php';

// Yardımcı fonksiyonları dahil et
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitap Sosyal Ağı</title>

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body style="
    background: #000000;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    margin: 0;
    padding: var(--space-xl);
">

    <!-- Ana Kart -->
    <div style="
        text-align: center;
        max-width: 500px;
        width: 100%;
    ">

        <!-- Logo İkon -->
        <div style="
            font-size: 80px;
            margin-bottom: var(--space-2xl);
            filter: drop-shadow(0 4px 24px rgba(212, 175, 55, 0.5));
        ">
            📚
        </div>

        <!-- Başlık - Altın Rengi -->
        <h1 style="
            font-family: 'Cinzel', serif;
            font-size: var(--text-5xl);
            font-weight: 900;
            color: var(--gold);
            margin: 0 0 var(--space-lg) 0;
            letter-spacing: 0.02em;
            text-shadow: 0 2px 16px rgba(212, 175, 55, 0.3);
        ">
            Kitap Sosyal Ağı
        </h1>

        <!-- Alt Başlık -->
        <p style="
            font-size: var(--text-lg);
            color: rgba(255, 255, 255, 0.7);
            margin: 0 0 var(--space-3xl) 0;
        ">
            Kitapseverlerin buluşma noktası
        </p>

        <!-- Flash Mesajı -->
        <?php echo get_flash(); ?>

        <!-- Butonlar -->
        <div style="
            display: flex;
            flex-direction: column;
            gap: var(--space-md);
        ">

            <!-- Giriş Yap -->
            <a href="login.php" class="btn btn-primary btn-lg btn-block" style="
                font-size: var(--text-xl);
                padding: var(--space-lg) var(--space-2xl);
            ">
                <i class="fas fa-sign-in-alt"></i>
                Giriş Yap
            </a>

            <!-- Kayıt Ol -->
            <a href="register.php" class="btn btn-gold btn-lg btn-block" style="
                font-size: var(--text-xl);
                padding: var(--space-lg) var(--space-2xl);
            ">
                <i class="fas fa-user-plus"></i>
                Kayıt Ol
            </a>

        </div>

    </div>

</body>

</html>