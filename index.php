<?php
/**
 * ============================================
 * ANA SAYFA
 * ============================================
 * Proje: Kitap Sosyal Ağı
 * Dosya: index.php
 * Açıklama: Giriş yapmamış kullanıcılar için hero bölümü,
 *           Giriş yapmış kullanıcılar için yorum akışı
 * ============================================
 */

// Veritabanı bağlantısını dahil et
require_once 'includes/db.php';

// Yardımcı fonksiyonları dahil et
require_once 'includes/functions.php';

// Session'ı başlat
session_start();

// Kullanıcı giriş yapmış mı kontrol et
$is_logged_in = isset($_SESSION['user_id']);

// ============================================
// GİRİŞ YAPMIŞ KULLANICILAR İÇİN VERİ ÇEK
// ============================================

$reviews = [];

if ($is_logged_in) {
    try {
        // Son eklenen yorumları çek (kullanıcı bilgileriyle birlikte)
        // LIMIT 10: Sadece son 10 yorumu göster
        $stmt = $pdo->prepare("
            SELECT 
                r.id,
                r.book_api_id,
                r.rating,
                r.comment,
                r.created_at,
                u.id as user_id,
                u.username,
                u.full_name,
                u.avatar
            FROM reviews r
            INNER JOIN users u ON r.user_id = u.id
            ORDER BY r.created_at DESC
            LIMIT 10
        ");

        $stmt->execute();
        $reviews = $stmt->fetchAll();

    } catch (PDOException $e) {
        // Hata durumunda boş array kalsın
        error_log('Index reviews fetch error: ' . $e->getMessage());
    }
}

// Sayfa başlığı
$page_title = 'Ana Sayfa';

// Header'ı dahil et
require_once 'includes/header.php';
?>

<!-- ============================================
     GİRİŞ YAPMAMIŞ KULLANICILAR İÇİN HERO BÖLÜMÜ
     ============================================ -->

<?php if (!$is_logged_in): ?>

    <!-- Hero Section -->
    <div
        style="background: linear-gradient(135deg, #20B2AA 0%, #1a8f89 100%); color: white; padding: 80px 20px; text-align: center; border-radius: 12px; margin-bottom: 40px; box-shadow: 0 10px 30px rgba(32, 178, 170, 0.3);">

        <!-- Ana Başlık -->
        <h1 style="font-size: 3rem; margin-bottom: 20px; font-weight: bold;">
            📚 Kitap Sosyal Ağı'na Hoş Geldiniz!
        </h1>

        <!-- Alt Başlık -->
        <p
            style="font-size: 1.3rem; margin-bottom: 40px; opacity: 0.95; max-width: 700px; margin-left: auto; margin-right: auto; line-height: 1.6;">
            Kitapları keşfedin, yorumlarınızı paylaşın, kitapsever arkadaşlarınızla bağlantı kurun.
        </p>

        <!-- CTA Butonları -->
        <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
            <a href="register.php" class="btn btn-lg"
                style="background-color: white; color: #20B2AA; font-weight: 600; padding: 15px 40px;">
                <i class="fas fa-user-plus"></i> Hemen Kayıt Ol
            </a>
            <a href="login.php" class="btn btn-lg"
                style="background-color: rgba(255,255,255,0.2); color: white; border: 2px solid white; font-weight: 600; padding: 15px 40px;">
                <i class="fas fa-sign-in-alt"></i> Giriş Yap
            </a>
        </div>

    </div>

    <!-- ============================================
         ÖZELLİKLER BÖLÜMÜ
         ============================================ -->

    <div class="grid" style="margin-top: 60px;">

        <!-- Özellik 1: Kitap Keşfet -->
        <div class="card" style="text-align: center;">
            <div style="font-size: 3rem; margin-bottom: 20px;">📖</div>
            <h3 class="card-title" style="color: #20B2AA;">Kitap Keşfet</h3>
            <p class="card-body">
                Google Books API ile milyonlarca kitabı keşfedin. Yeni okumalar bulun, favorilerinizi kaydedin.
            </p>
        </div>

        <!-- Özellik 2: Yorum Yap -->
        <div class="card" style="text-align: center;">
            <div style="font-size: 3rem; margin-bottom: 20px;">⭐</div>
            <h3 class="card-title" style="color: #20B2AA;">Yorum Yap</h3>
            <p class="card-body">
                Okuduğunuz kitaplar hakkında düşüncelerinizi paylaşın. 1-5 yıldız verin, detaylı yorumlar yazın.
            </p>
        </div>

        <!-- Özellik 3: Arkadaş Edin -->
        <div class="card" style="text-align: center;">
            <div style="font-size: 3rem; margin-bottom: 20px;">👥</div>
            <h3 class="card-title" style="color: #20B2AA;">Arkadaş Edin</h3>
            <p class="card-body">
                Benzer kitap zevkine sahip kişileri takip edin. Arkadaşlarınızın yorumlarını görün.
            </p>
        </div>

    </div>

    <!-- ============================================
         İSTATİSTİKLER BÖLÜMÜ
         ============================================ -->

    <div class="card"
        style="margin-top: 60px; background: linear-gradient(135deg, #f5f7fa 0%, #e4e7eb 100%); text-align: center;">
        <h2 style="color: #20B2AA; margin-bottom: 30px;">📊 Platform İstatistikleri</h2>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 30px;">

            <?php
            // Toplam kullanıcı sayısı
            $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

            // Toplam yorum sayısı
            $review_count = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();

            // Toplam takip sayısı
            $follow_count = $pdo->query("SELECT COUNT(*) FROM follows")->fetchColumn();
            ?>

            <!-- Kullanıcı Sayısı -->
            <div>
                <div style="font-size: 2.5rem; font-weight: bold; color: #20B2AA;">
                    <?php echo number_format($user_count); ?>
                </div>
                <div style="color: #7b8794; margin-top: 10px;">Kayıtlı Kullanıcı</div>
            </div>

            <!-- Yorum Sayısı -->
            <div>
                <div style="font-size: 2.5rem; font-weight: bold; color: #20B2AA;">
                    <?php echo number_format($review_count); ?>
                </div>
                <div style="color: #7b8794; margin-top: 10px;">Kitap Yorumu</div>
            </div>

            <!-- Takip Sayısı -->
            <div>
                <div style="font-size: 2.5rem; font-weight: bold; color: #20B2AA;">
                    <?php echo number_format($follow_count); ?>
                </div>
                <div style="color: #7b8794; margin-top: 10px;">Takip İlişkisi</div>
            </div>

        </div>
    </div>

<?php else: ?>

    <!-- ============================================
         GİRİŞ YAPMIŞ KULLANICILAR İÇİN YORUM AKIŞI
         ============================================ -->

    <!-- Hoş Geldin Mesajı -->
    <div class="card"
        style="background: linear-gradient(135deg, #20B2AA 0%, #1a8f89 100%); color: white; text-align: center; margin-bottom: 30px;">
        <h2 style="margin: 0;">
            👋 Hoş geldin, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!
        </h2>
        <p style="margin: 10px 0 0 0; opacity: 0.9;">
            Son yorumları keşfet veya yeni bir kitap ara
        </p>
    </div>

    <!-- Flash Mesajı Göster -->
    <?php echo get_flash(); ?>

    <!-- Hızlı Aksiyonlar -->
    <div style="display: flex; gap: 15px; margin-bottom: 30px; flex-wrap: wrap;">
        <a href="search.php" class="btn btn-primary">
            <i class="fas fa-search"></i> Kitap Ara
        </a>
        <a href="profile.php" class="btn btn-secondary">
            <i class="fas fa-user"></i> Profilim
        </a>
        <a href="explore.php" class="btn btn-secondary">
            <i class="fas fa-compass"></i> Keşfet
        </a>
    </div>

    <!-- Yorum Akışı Başlığı -->
    <h2 class="page-title" style="text-align: left; margin-bottom: 30px;">
        📖 Son Yorumlar
    </h2>

    <!-- ============================================
         YORUM LİSTESİ
         ============================================ -->

    <?php if (empty($reviews)): ?>

        <!-- Henüz yorum yoksa -->
        <div class="card" style="text-align: center; padding: 60px 20px;">
            <div style="font-size: 4rem; margin-bottom: 20px; opacity: 0.5;">📚</div>
            <h3 style="color: #7b8794; margin-bottom: 10px;">Henüz yorum yok</h3>
            <p style="color: #9aa5b1;">İlk yorumu yapan siz olun!</p>
            <a href="search.php" class="btn btn-primary" style="margin-top: 20px;">
                <i class="fas fa-search"></i> Kitap Ara
            </a>
        </div>

    <?php else: ?>

        <!-- Yorumları listele -->
        <?php foreach ($reviews as $review): ?>

            <div class="card" style="margin-bottom: 20px;">

                <!-- Kullanıcı Bilgileri -->
                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">

                    <!-- Avatar -->
                    <a href="profile.php?id=<?php echo $review['user_id']; ?>">
                        <img src="uploads/avatars/<?php echo htmlspecialchars($review['avatar']); ?>"
                            alt="<?php echo htmlspecialchars($review['username']); ?>" class="avatar avatar-lg">
                    </a>

                    <!-- İsim ve Zaman -->
                    <div style="flex: 1;">
                        <a href="profile.php?id=<?php echo $review['user_id']; ?>"
                            style="font-weight: 600; color: #323f4b; font-size: 1.1rem;">
                            <?php echo htmlspecialchars($review['full_name']); ?>
                        </a>
                        <div style="color: #9aa5b1; font-size: 0.9rem;">
                            @<?php echo htmlspecialchars($review['username']); ?> •
                            <?php echo time_ago($review['created_at']); ?>
                        </div>
                    </div>

                    <!-- Yıldız Puanı -->
                    <div>
                        <?php echo show_stars($review['rating']); ?>
                    </div>

                </div>

                <!-- Yorum İçeriği -->
                <div class="card-body">
                    <p style="margin: 0; line-height: 1.6; color: #52606d;">
                        <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                    </p>
                </div>

                <!-- Kitap ID (Geliştirme aşamasında göster) -->
                <div class="card-footer">
                    <small class="text-muted">
                        <i class="fas fa-book"></i>
                        Kitap ID: <?php echo htmlspecialchars($review['book_api_id']); ?>
                    </small>
                </div>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>

<?php endif; ?>

<?php
// Footer'ı dahil et
require_once 'includes/footer.php';
?>