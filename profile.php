<?php
/**
 * ============================================
 * PROFİL SAYFASI
 * ============================================
 * Proje: Kitap Sosyal Ağı
 * Dosya: profile.php
 * Açıklama: Kullanıcı profil bilgileri, yorumları ve takip özelliği
 * ============================================
 */

// Veritabanı bağlantısını dahil et
require_once 'includes/db.php';

// Yardımcı fonksiyonları dahil et
require_once 'includes/functions.php';

// Session'ı başlat
session_start();

// Kullanıcı giriş yapmamışsa login sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    set_flash('warning', 'Profil sayfasını görüntülemek için giriş yapmalısınız.');
    redirect('login.php');
}

// ============================================
// GÖRÜNTÜLENECEK PROFİLİ BELİRLE
// ============================================

// URL'den profil ID'sini al (yoksa kendi profilini göster)
$profile_user_id = isset($_GET['id']) ? sanitize_int($_GET['id']) : $_SESSION['user_id'];

// Geçersiz ID kontrolü
if ($profile_user_id === false) {
    set_flash('danger', 'Geçersiz kullanıcı ID.');
    redirect('index.php');
}

// Kendi profilini mi görüntülüyor kontrol et
$is_own_profile = ($profile_user_id == $_SESSION['user_id']);

// ============================================
// KULLANICI BİLGİLERİNİ ÇEK
// ============================================

try {
    // Kullanıcı bilgilerini veritabanından çek
    $stmt = $pdo->prepare("
        SELECT id, username, email, full_name, bio, avatar, created_at
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$profile_user_id]);
    $profile_user = $stmt->fetch();

    // Kullanıcı bulunamadıysa hata ver
    if (!$profile_user) {
        set_flash('danger', 'Kullanıcı bulunamadı.');
        redirect('index.php');
    }

} catch (PDOException $e) {
    set_flash('danger', 'Bir hata oluştu.');
    error_log('Profile user fetch error: ' . $e->getMessage());
    redirect('index.php');
}

// ============================================
// TAKİP DURUMUNU KONTROL ET
// ============================================

$is_following = false;

if (!$is_own_profile) {
    try {
        // Bu kullanıcıyı takip ediyor muyum?
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM follows 
            WHERE follower_id = ? AND followed_id = ?
        ");

        $stmt->execute([$_SESSION['user_id'], $profile_user_id]);
        $is_following = ($stmt->fetchColumn() > 0);

    } catch (PDOException $e) {
        error_log('Follow check error: ' . $e->getMessage());
    }
}

// ============================================
// TAKİPÇİ VE TAKİP EDİLEN SAYILARINI ÇEK
// ============================================

try {
    // Takipçi sayısı (bu kullanıcıyı kaç kişi takip ediyor)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE followed_id = ?");
    $stmt->execute([$profile_user_id]);
    $follower_count = $stmt->fetchColumn();

    // Takip edilen sayısı (bu kullanıcı kaç kişiyi takip ediyor)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?");
    $stmt->execute([$profile_user_id]);
    $following_count = $stmt->fetchColumn();

} catch (PDOException $e) {
    $follower_count = 0;
    $following_count = 0;
    error_log('Follow count error: ' . $e->getMessage());
}

// ============================================
// KULLANICININ YORUMLARINI ÇEK
// ============================================

try {
    // Kullanıcının tüm yorumlarını çek (en yeniden eskiye)
    $stmt = $pdo->prepare("
        SELECT 
            id,
            book_api_id,
            rating,
            comment,
            created_at
        FROM reviews
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");

    $stmt->execute([$profile_user_id]);
    $user_reviews = $stmt->fetchAll();

    // Yorum sayısı
    $review_count = count($user_reviews);

} catch (PDOException $e) {
    $user_reviews = [];
    $review_count = 0;
    error_log('User reviews fetch error: ' . $e->getMessage());
}

// ============================================
// TAKİP ET / TAKİPTEN ÇIKAR İŞLEMİ
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && !$is_own_profile) {

    $action = $_POST['action'];

    try {
        if ($action === 'follow' && !$is_following) {
            // Takip et
            $stmt = $pdo->prepare("
                INSERT INTO follows (follower_id, followed_id, created_at)
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$_SESSION['user_id'], $profile_user_id]);

            set_flash('success', htmlspecialchars($profile_user['full_name']) . ' takip edildi!');

        } elseif ($action === 'unfollow' && $is_following) {
            // Takipten çıkar
            $stmt = $pdo->prepare("
                DELETE FROM follows 
                WHERE follower_id = ? AND followed_id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $profile_user_id]);

            set_flash('success', htmlspecialchars($profile_user['full_name']) . ' takipten çıkarıldı.');
        }

        // Sayfayı yenile (takip durumunu güncelle)
        redirect('profile.php?id=' . $profile_user_id);

    } catch (PDOException $e) {
        set_flash('danger', 'Bir hata oluştu.');
        error_log('Follow action error: ' . $e->getMessage());
    }
}

// Sayfa başlığı
$page_title = htmlspecialchars($profile_user['full_name']) . ' - Profil';

// Header'ı dahil et
require_once 'includes/header.php';
?>

<!-- ============================================
     PROFİL BAŞLIĞI
     ============================================ -->

<div class="card" style="margin-bottom: 30px;">

    <!-- Profil Bilgileri -->
    <div style="display: flex; align-items: center; gap: 30px; flex-wrap: wrap;">

        <!-- Avatar -->
        <div>
            <img src="uploads/avatars/<?php echo htmlspecialchars($profile_user['avatar']); ?>"
                alt="<?php echo htmlspecialchars($profile_user['full_name']); ?>" class="avatar avatar-xl"
                style="width: 150px; height: 150px;">
        </div>

        <!-- Kullanıcı Bilgileri -->
        <div style="flex: 1; min-width: 250px;">

            <!-- İsim ve Kullanıcı Adı -->
            <h1 style="margin: 0 0 10px 0; color: #323f4b;">
                <?php echo htmlspecialchars($profile_user['full_name']); ?>
            </h1>

            <p style="color: #7b8794; font-size: 1.1rem; margin: 0 0 15px 0;">
                @
                <?php echo htmlspecialchars($profile_user['username']); ?>
            </p>

            <!-- Biyografi -->
            <?php if (!empty($profile_user['bio'])): ?>
                <p style="color: #52606d; margin: 0 0 20px 0; line-height: 1.6;">
                    <?php echo nl2br(htmlspecialchars($profile_user['bio'])); ?>
                </p>
            <?php else: ?>
                <p style="color: #9aa5b1; font-style: italic; margin: 0 0 20px 0;">
                    Henüz biyografi eklenmemiş.
                </p>
            <?php endif; ?>

            <!-- İstatistikler -->
            <div style="display: flex; gap: 30px; margin-bottom: 20px; flex-wrap: wrap;">

                <!-- Yorum Sayısı -->
                <div>
                    <span style="font-weight: bold; font-size: 1.3rem; color: #40C4FF;">
                        <?php echo $review_count; ?>
                    </span>
                    <span style="color: #7b8794; margin-left: 5px;">Yorum</span>
                </div>

                <!-- Takipçi Sayısı -->
                <div>
                    <span style="font-weight: bold; font-size: 1.3rem; color: #40C4FF;">
                        <?php echo $follower_count; ?>
                    </span>
                    <span style="color: #7b8794; margin-left: 5px;">Takipçi</span>
                </div>

                <!-- Takip Edilen Sayısı -->
                <div>
                    <span style="font-weight: bold; font-size: 1.3rem; color: #40C4FF;">
                        <?php echo $following_count; ?>
                    </span>
                    <span style="color: #7b8794; margin-left: 5px;">Takip</span>
                </div>

            </div>

            <!-- Üyelik Tarihi -->
            <p style="color: #9aa5b1; font-size: 0.9rem; margin: 0;">
                <i class="fas fa-calendar-alt"></i>
                Üyelik:
                <?php echo format_date($profile_user['created_at']); ?>
            </p>

        </div>

        <!-- Takip Et Butonu (Başkasının profilindeyse) -->
        <?php if (!$is_own_profile): ?>
            <div>
                <form method="POST" action="">
                    <?php if ($is_following): ?>
                        <!-- Takipten Çıkar Butonu -->
                        <input type="hidden" name="action" value="unfollow">
                        <button type="submit" class="btn btn-secondary btn-lg">
                            <i class="fas fa-user-minus"></i> Takipten Çıkar
                        </button>
                    <?php else: ?>
                        <!-- Takip Et Butonu -->
                        <input type="hidden" name="action" value="follow">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-user-plus"></i> Takip Et
                        </button>
                    <?php endif; ?>
                </form>
            </div>
        <?php else: ?>
            <!-- Profili Düzenle Butonu (Kendi profilindeyse) -->
            <div>
                <a href="edit-profile.php" class="btn btn-secondary btn-lg">
                    <i class="fas fa-edit"></i> Profili Düzenle
                </a>
            </div>
        <?php endif; ?>

    </div>

</div>

<!-- Flash Mesajı Göster -->
<?php echo get_flash(); ?>

<!-- ============================================
     KULLANICININ YORUMLARI
     ============================================ -->

<h2 class="page-title" style="text-align: left; margin-bottom: 30px;">
    📚
    <?php echo $is_own_profile ? 'Yorumlarım' : htmlspecialchars($profile_user['full_name']) . ' - Yorumları'; ?>
    <span class="badge badge-primary" style="margin-left: 10px;">
        <?php echo $review_count; ?>
    </span>
</h2>

<?php if (empty($user_reviews)): ?>

    <!-- Henüz yorum yoksa -->
    <div class="card" style="text-align: center; padding: 60px 20px;">
        <div style="font-size: 4rem; margin-bottom: 20px; opacity: 0.5;">📖</div>
        <h3 style="color: #7b8794; margin-bottom: 10px;">
            <?php echo $is_own_profile ? 'Henüz yorum yapmadınız' : 'Henüz yorum yok'; ?>
        </h3>
        <p style="color: #9aa5b1;">
            <?php echo $is_own_profile ? 'İlk yorumunuzu yapmak için bir kitap arayın!' : 'Bu kullanıcı henüz yorum yapmamış.'; ?>
        </p>
        <?php if ($is_own_profile): ?>
            <a href="search.php" class="btn btn-primary" style="margin-top: 20px;">
                <i class="fas fa-search"></i> Kitap Ara
            </a>
        <?php endif; ?>
    </div>

<?php else: ?>

    <!-- Yorumları listele -->
    <?php foreach ($user_reviews as $review): ?>

        <div class="card" style="margin-bottom: 20px;">

            <!-- Yorum Başlığı -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">

                <!-- Tarih -->
                <div style="color: #9aa5b1; font-size: 0.9rem;">
                    <i class="fas fa-clock"></i>
                    <?php echo time_ago($review['created_at']); ?>
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

            <!-- Kitap Bilgisi -->
            <div class="card-footer">
                <small class="text-muted">
                    <i class="fas fa-book"></i>
                    Kitap ID:
                    <?php echo htmlspecialchars($review['book_api_id']); ?>
                </small>

                <?php if ($is_own_profile): ?>
                    <!-- Kendi yorumuysa düzenle/sil butonları -->
                    <div style="float: right;">
                        <a href="edit-review.php?id=<?php echo $review['id']; ?>" class="btn btn-sm btn-secondary">
                            <i class="fas fa-edit"></i> Düzenle
                        </a>
                        <a href="delete-review.php?id=<?php echo $review['id']; ?>" class="btn btn-sm btn-danger"
                            onclick="return confirm('Bu yorumu silmek istediğinizden emin misiniz?')">
                            <i class="fas fa-trash"></i> Sil
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        </div>

    <?php endforeach; ?>

<?php endif; ?>

<?php
// Footer'ı dahil et
require_once 'includes/footer.php';
?>