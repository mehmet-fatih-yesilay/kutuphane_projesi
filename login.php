<?php
/**
 * ============================================
 * GİRİŞ SAYFASI
 * ============================================
 * Proje: Kitap Sosyal Ağı
 * Dosya: login.php
 * Açıklama: Kullanıcı giriş formu ve kimlik doğrulama
 * Güvenlik: password_verify, prepared statements, session hijacking koruması
 * ============================================
 */

// Veritabanı bağlantısını dahil et
require_once 'includes/db.php';

// Yardımcı fonksiyonları dahil et
require_once 'includes/functions.php';

// Session'ı başlat
session_start();

// Eğer kullanıcı zaten giriş yapmışsa dashboard'a yönlendir
if (isset($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

// Hata ve başarı mesajları için değişkenler
$errors = [];
$success = '';

// ============================================
// FORM GÖNDERİLDİYSE İŞLE
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ============================================
    // FORM VERİLERİNİ AL VE TEMİZLE
    // ============================================

    // Kullanıcı adı veya e-posta (ikisi de kabul edilir)
    $login_identifier = sanitize($_POST['login_identifier'] ?? '');

    // Şifre (sanitize edilmez, doğrudan verify edilecek)
    $password = $_POST['password'] ?? '';

    // "Beni Hatırla" checkbox'ı
    $remember_me = isset($_POST['remember_me']);

    // ============================================
    // VALİDASYON KONTROLLERI
    // ============================================

    // Kullanıcı adı/e-posta kontrolü
    if (empty($login_identifier)) {
        $errors[] = 'Kullanıcı adı veya e-posta boş bırakılamaz.';
    }

    // Şifre kontrolü
    if (empty($password)) {
        $errors[] = 'Şifre boş bırakılamaz.';
    }

    // ============================================
    // KULLANICI DOĞRULAMA
    // ============================================

    // Eğer validasyon hatası yoksa giriş işlemine geç
    if (empty($errors)) {

        try {
            // Kullanıcıyı veritabanından bul (username veya email ile)
            // LOWER() fonksiyonu ile büyük/küçük harf duyarsız arama
            $stmt = $pdo->prepare("
                SELECT id, username, email, password, full_name, avatar 
                FROM users 
                WHERE LOWER(username) = LOWER(?) OR LOWER(email) = LOWER(?)
                LIMIT 1
            ");

            $stmt->execute([$login_identifier, $login_identifier]);
            $user = $stmt->fetch();

            // ============================================
            // ŞİFRE DOĞRULAMA
            // ============================================

            // Kullanıcı bulunduysa ve şifre doğruysa
            if ($user && verify_password($password, $user['password'])) {

                // ============================================
                // SESSION HİJACKING KORUMALARI
                // ============================================

                // Session ID'yi yenile (session fixation saldırılarına karşı)
                session_regenerate_id(true);

                // ============================================
                // SESSION VERİLERİNİ AYARLA
                // ============================================

                // Kullanıcı ID'sini session'a kaydet
                $_SESSION['user_id'] = $user['id'];

                // Kullanıcı adını session'a kaydet
                $_SESSION['username'] = $user['username'];

                // E-posta adresini session'a kaydet
                $_SESSION['email'] = $user['email'];

                // Tam adı session'a kaydet
                $_SESSION['full_name'] = $user['full_name'];

                // Avatar'ı session'a kaydet
                $_SESSION['avatar'] = $user['avatar'];

                // Giriş zamanını session'a kaydet (güvenlik için)
                $_SESSION['login_time'] = time();

                // IP adresini session'a kaydet (session hijacking tespiti için)
                $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];

                // User agent'ı session'a kaydet (session hijacking tespiti için)
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];

                // ============================================
                // "BENİ HATIRLA" ÖZELLİĞİ
                // ============================================

                if ($remember_me) {
                    // Cookie ömrünü 30 gün olarak ayarla (30 * 24 * 60 * 60 saniye)
                    $cookie_lifetime = 30 * 24 * 60 * 60;

                    // Session cookie parametrelerini al
                    $cookie_params = session_get_cookie_params();

                    // Cookie'yi 30 gün için ayarla
                    setcookie(
                        session_name(),
                        session_id(),
                        time() + $cookie_lifetime,
                        $cookie_params['path'],
                        $cookie_params['domain'],
                        $cookie_params['secure'],
                        $cookie_params['httponly']
                    );
                } else {
                    // "Beni Hatırla" seçilmediyse tarayıcı kapanınca session sonlansın
                    // (Varsayılan davranış, ek bir şey yapmaya gerek yok)
                }

                // ============================================
                // SON GİRİŞ TARİHİNİ GÜNCELLE (Opsiyonel)
                // ============================================

                // Kullanıcının son giriş tarihini güncelle
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET last_login = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$user['id']]);

                // ============================================
                // BAŞARILI GİRİŞ - YÖNLENDİRME
                // ============================================

                // Flash mesaj ekle
                set_flash('success', 'Hoş geldiniz, ' . $user['full_name'] . '!');

                // Dashboard'a yönlendir
                redirect('dashboard.php');

            } else {
                // Kullanıcı bulunamadı veya şifre yanlış
                // Güvenlik için spesifik hata verme (brute force saldırılarını zorlaştırır)
                $errors[] = 'Kullanıcı adı/e-posta veya şifre hatalı.';

                // Başarısız giriş denemesini logla (opsiyonel)
                error_log('Failed login attempt for: ' . $login_identifier);
            }

        } catch (PDOException $e) {
            // Veritabanı hatası
            $errors[] = 'Bir hata oluştu. Lütfen tekrar deneyin.';
            // Hata loguna kaydet (production ortamı için)
            error_log('Login DB Error: ' . $e->getMessage());
        }
    }
}

// Sayfa başlığı
$page_title = 'Giriş Yap';

// Header'ı dahil et
require_once 'includes/header.php';
?>

<!-- ============================================
     GİRİŞ FORMU
     ============================================ -->

<div class="card" style="max-width: 500px; margin: 50px auto;">
    <!-- Kart başlığı -->
    <div class="card-title text-center">
        <h2>🔐 Giriş Yap</h2>
        <p class="text-muted" style="font-size: 0.9rem; margin-top: 10px;">
            Hesabınıza giriş yapın
        </p>
    </div>

    <!-- Kart içeriği -->
    <div class="card-body">

        <!-- ============================================
             FLASH MESAJI (Kayıt başarılı vb.)
             ============================================ -->

        <?php echo get_flash(); ?>

        <!-- ============================================
             HATA MESAJLARI
             ============================================ -->

        <?php if (!empty($errors)): ?>
            <div
                style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #dc3545;">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Hata!</strong>
                <ul style="margin: 10px 0 0 20px; padding: 0;">
                    <?php foreach ($errors as $error): ?>
                        <li>
                            <?php echo htmlspecialchars($error); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- ============================================
             GİRİŞ FORMU
             ============================================ -->

        <form method="POST" action="login.php">

            <!-- Kullanıcı Adı veya E-posta -->
            <div class="form-group">
                <label for="login_identifier" class="form-label">
                    <i class="fas fa-user"></i> Kullanıcı Adı veya E-posta
                </label>
                <input type="text" id="login_identifier" name="login_identifier" class="form-control"
                    placeholder="Kullanıcı adınız veya e-posta adresiniz"
                    value="<?php echo isset($login_identifier) ? htmlspecialchars($login_identifier) : ''; ?>" required
                    autofocus>
            </div>

            <!-- Şifre -->
            <div class="form-group">
                <label for="password" class="form-label">
                    <i class="fas fa-lock"></i> Şifre
                </label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Şifreniz"
                    required>
            </div>

            <!-- Beni Hatırla ve Şifremi Unuttum -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <!-- Beni Hatırla Checkbox -->
                <label style="display: flex; align-items: center; cursor: pointer;">
                    <input type="checkbox" name="remember_me" id="remember_me"
                        style="margin-right: 8px; cursor: pointer;">
                    <span style="font-size: 0.9rem;">Beni Hatırla</span>
                </label>

                <!-- Şifremi Unuttum Linki -->
                <a href="forgot-password.php" class="text-primary" style="font-size: 0.9rem;">
                    Şifremi Unuttum?
                </a>
            </div>

            <!-- Giriş Yap Butonu -->
            <button type="submit" class="btn btn-primary btn-block btn-lg">
                <i class="fas fa-sign-in-alt"></i> Giriş Yap
            </button>

        </form>

    </div>

    <!-- ============================================
         KAYIT LİNKİ
         ============================================ -->

    <div class="card-footer" style="text-align: center;">
        <p class="text-muted" style="margin: 0;">
            Hesabınız yok mu?
            <a href="register.php" class="text-primary" style="font-weight: 600;">
                Kayıt Olun
            </a>
        </p>
    </div>

</div>

<!-- ============================================
     BİLGİLENDİRME KUTUSU
     ============================================ -->

<div class="card"
    style="max-width: 500px; margin: 20px auto; background-color: #e7f3ff; border-left: 4px solid #20B2AA;">
    <div class="card-body">
        <h4 style="color: #20B2AA; margin-bottom: 10px;">
            <i class="fas fa-info-circle"></i> Bilgi
        </h4>
        <p style="margin: 0; font-size: 0.9rem; color: #333;">
            <strong>Test Hesabı:</strong><br>
            Kullanıcı Adı: <code>test_user</code><br>
            Şifre: <code>123456</code>
        </p>
    </div>
</div>

<?php
// Footer'ı dahil et
require_once 'includes/footer.php';
?>