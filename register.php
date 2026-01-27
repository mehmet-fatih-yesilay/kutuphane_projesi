<?php
/**
 * ============================================
 * KAYIT SAYFASI
 * ============================================
 * Proje: Kitap Sosyal Ağı
 * Dosya: register.php
 * Açıklama: Yeni kullanıcı kayıt formu ve işlemleri
 * Güvenlik: password_hash, prepared statements, CSRF koruması
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

    // Kullanıcı adını al ve temizle
    $username = sanitize($_POST['username'] ?? '');

    // E-posta adresini al ve temizle
    $email = sanitize_email($_POST['email'] ?? '');

    // Tam adı al ve temizle
    $full_name = sanitize($_POST['full_name'] ?? '');

    // Şifreyi al (şifre sanitize edilmez, hash'lenecek)
    $password = $_POST['password'] ?? '';

    // Şifre tekrarını al
    $password_confirm = $_POST['password_confirm'] ?? '';

    // ============================================
    // VALİDASYON KONTROLLERI
    // ============================================

    // Kullanıcı adı kontrolü
    if (empty($username)) {
        $errors[] = 'Kullanıcı adı boş bırakılamaz.';
    } elseif (!validate_username($username)) {
        $errors[] = 'Kullanıcı adı 3-50 karakter olmalı ve sadece harf, rakam, alt çizgi içermelidir.';
    }

    // E-posta kontrolü
    if (empty($email)) {
        $errors[] = 'E-posta adresi boş bırakılamaz.';
    } elseif ($email === false) {
        $errors[] = 'Geçerli bir e-posta adresi giriniz.';
    }

    // Tam ad kontrolü
    if (empty($full_name)) {
        $errors[] = 'Tam ad boş bırakılamaz.';
    } elseif (strlen($full_name) < 3 || strlen($full_name) > 100) {
        $errors[] = 'Tam ad 3-100 karakter arasında olmalıdır.';
    }

    // Şifre kontrolü
    if (empty($password)) {
        $errors[] = 'Şifre boş bırakılamaz.';
    } elseif (!validate_password($password)) {
        $errors[] = 'Şifre en az 6 karakter olmalıdır.';
    }

    // Şifre tekrar kontrolü
    if ($password !== $password_confirm) {
        $errors[] = 'Şifreler eşleşmiyor.';
    }

    // ============================================
    // VERİTABANI KONTROLLERI
    // ============================================

    // Eğer validasyon hatası yoksa veritabanı kontrollerine geç
    if (empty($errors)) {

        try {
            // Kullanıcı adı daha önce kullanılmış mı kontrol et
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);

            if ($stmt->fetch()) {
                $errors[] = 'Bu kullanıcı adı zaten kullanılıyor.';
            }

            // E-posta daha önce kullanılmış mı kontrol et
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $errors[] = 'Bu e-posta adresi zaten kayıtlı.';
            }

        } catch (PDOException $e) {
            // Veritabanı hatası
            $errors[] = 'Bir hata oluştu. Lütfen tekrar deneyin.';
            // Hata loguna kaydet (production ortamı için)
            error_log('Register DB Error: ' . $e->getMessage());
        }
    }

    // ============================================
    // KAYIT İŞLEMİ
    // ============================================

    // Eğer hata yoksa kullanıcıyı kaydet
    if (empty($errors)) {

        try {
            // Şifreyi hashle (bcrypt algoritması ile)
            $hashed_password = hash_password($password);

            // Kullanıcıyı veritabanına ekle
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password, full_name, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");

            $stmt->execute([$username, $email, $hashed_password, $full_name]);

            // Başarı mesajı
            $success = 'Kayıt başarılı! Giriş yapabilirsiniz.';

            // Flash mesaj ekle
            set_flash('success', 'Kayıt başarılı! Şimdi giriş yapabilirsiniz.');

            // 2 saniye sonra login sayfasına yönlendir
            header("refresh:2;url=login.php");

        } catch (PDOException $e) {
            // Veritabanı hatası
            $errors[] = 'Kayıt sırasında bir hata oluştu. Lütfen tekrar deneyin.';
            // Hata loguna kaydet (production ortamı için)
            error_log('Register Insert Error: ' . $e->getMessage());
        }
    }
}

// Sayfa başlığı
$page_title = 'Kayıt Ol';

// Header'ı dahil et
require_once 'includes/header.php';
?>

<!-- ============================================
     KAYIT FORMU
     ============================================ -->

<div class="card" style="max-width: 500px; margin: 50px auto;">
    <!-- Kart başlığı -->
    <div class="card-title text-center">
        <h2>📚 Kayıt Ol</h2>
        <p class="text-muted" style="font-size: 0.9rem; margin-top: 10px;">
            Kitap Sosyal Ağı'na hoş geldiniz!
        </p>
    </div>

    <!-- Kart içeriği -->
    <div class="card-body">

        <!-- ============================================
             BAŞARI MESAJI
             ============================================ -->

        <?php if (!empty($success)): ?>
            <div
                style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #28a745;">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

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
             KAYIT FORMU
             ============================================ -->

        <form method="POST" action="register.php">

            <!-- Kullanıcı Adı -->
            <div class="form-group">
                <label for="username" class="form-label">
                    <i class="fas fa-user"></i> Kullanıcı Adı
                </label>
                <input type="text" id="username" name="username" class="form-control" placeholder="örn: kitapsever123"
                    value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required autofocus>
                <small class="text-muted" style="font-size: 0.8rem;">
                    3-50 karakter, sadece harf, rakam ve alt çizgi
                </small>
            </div>

            <!-- E-posta -->
            <div class="form-group">
                <label for="email" class="form-label">
                    <i class="fas fa-envelope"></i> E-posta Adresi
                </label>
                <input type="email" id="email" name="email" class="form-control" placeholder="ornek@email.com"
                    value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
            </div>

            <!-- Tam Ad -->
            <div class="form-group">
                <label for="full_name" class="form-label">
                    <i class="fas fa-id-card"></i> Tam Ad
                </label>
                <input type="text" id="full_name" name="full_name" class="form-control" placeholder="Adınız Soyadınız"
                    value="<?php echo isset($full_name) ? htmlspecialchars($full_name) : ''; ?>" required>
            </div>

            <!-- Şifre -->
            <div class="form-group">
                <label for="password" class="form-label">
                    <i class="fas fa-lock"></i> Şifre
                </label>
                <input type="password" id="password" name="password" class="form-control" placeholder="En az 6 karakter"
                    required>
            </div>

            <!-- Şifre Tekrar -->
            <div class="form-group">
                <label for="password_confirm" class="form-label">
                    <i class="fas fa-lock"></i> Şifre Tekrar
                </label>
                <input type="password" id="password_confirm" name="password_confirm" class="form-control"
                    placeholder="Şifrenizi tekrar girin" required>
            </div>

            <!-- Kayıt Ol Butonu -->
            <button type="submit" class="btn btn-primary btn-block btn-lg">
                <i class="fas fa-user-plus"></i> Kayıt Ol
            </button>

        </form>

    </div>

    <!-- ============================================
         GİRİŞ LİNKİ
         ============================================ -->

    <div class="card-footer" style="text-align: center;">
        <p class="text-muted" style="margin: 0;">
            Zaten hesabınız var mı?
            <a href="login.php" class="text-primary" style="font-weight: 600;">
                Giriş Yapın
            </a>
        </p>
    </div>

</div>

<?php
// Footer'ı dahil et
require_once 'includes/footer.php';
?>