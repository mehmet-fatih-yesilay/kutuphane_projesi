<?php
/**
 * ============================================
 * DASHBOARD - ANA AKIŞ SAYFASI
 * ============================================
 * Proje: Kitap Sosyal Ağı
 * Dosya: dashboard.php
 * Yapı: 3 Sütun Twitter/X Düzeni
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
    set_flash('warning', 'Dashboard\'a erişmek için giriş yapmalısınız.');
    redirect('login.php');
}

// Kullanıcı bilgilerini al
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['username'];
$user_full_name = $_SESSION['full_name'];
$user_avatar = $_SESSION['avatar'];

// İstatistikleri çek
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE followed_id = ?");
    $stmt->execute([$user_id]);
    $follower_count = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?");
    $stmt->execute([$user_id]);
    $following_count = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $review_count = $stmt->fetchColumn();
} catch (PDOException $e) {
    $follower_count = 0;
    $following_count = 0;
    $review_count = 0;
}

// Akış - Takip edilenlerin yorumları
try {
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
            u.avatar,
            cb.title as book_title,
            cb.author as book_author,
            cb.cover_url as book_cover
        FROM reviews r
        INNER JOIN users u ON r.user_id = u.id
        LEFT JOIN cached_books cb ON r.book_api_id = cb.api_id
        WHERE r.user_id IN (
            SELECT followed_id FROM follows WHERE follower_id = ?
            UNION
            SELECT ?
        )
        ORDER BY r.created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$user_id, $user_id]);
    $feed_reviews = $stmt->fetchAll();
} catch (PDOException $e) {
    $feed_reviews = [];
}

// Popüler kitaplar
try {
    $stmt = $pdo->query("
        SELECT 
            cb.api_id,
            cb.title,
            cb.author,
            cb.cover_url,
            COUNT(r.id) as review_count,
            AVG(r.rating) as avg_rating
        FROM cached_books cb
        LEFT JOIN reviews r ON cb.api_id = r.book_api_id
        GROUP BY cb.id
        ORDER BY review_count DESC
        LIMIT 5
    ");
    $popular_books = $stmt->fetchAll();
} catch (PDOException $e) {
    $popular_books = [];
}

// Sayfa başlığı
$page_title = 'Ana Sayfa';

// Header'ı dahil et
require_once 'includes/header.php';
?>

<!-- 3 Sütunlu Twitter/X Düzeni -->
<div style="
    display: grid;
    grid-template-columns: 260px 1fr 320px;
    gap: var(--space-xl);
    max-width: 1400px;
    margin: 0 auto;
    align-items: start;
">

    <!-- ============================================
         SOL SÜTUN - PROFİL VE MENÜ
         ============================================ -->

    <aside style="position: sticky; top: 100px;">

        <!-- Profil Kartı -->
        <div class="card" style="padding: var(--space-lg); text-align: center;">

            <!-- Avatar -->
            <a href="profile.php">
                <img src="uploads/avatars/<?php echo htmlspecialchars($user_avatar); ?>"
                    alt="<?php echo htmlspecialchars($user_full_name); ?>" class="avatar-xl"
                    style="margin-bottom: var(--space-md);">
            </a>

            <!-- İsim -->
            <h3 style="margin: 0 0 var(--space-xs) 0; font-size: var(--text-lg); color: var(--secondary-royal);">
                <?php echo htmlspecialchars($user_full_name); ?>
            </h3>

            <!-- Kullanıcı Adı -->
            <p style="margin: 0 0 var(--space-lg) 0; color: var(--text-muted); font-size: var(--text-sm);">
                @<?php echo htmlspecialchars($user_name); ?>
            </p>

            <!-- İstatistikler -->
            <div style="
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: var(--space-sm);
                padding: var(--space-md) 0;
                border-top: 1px solid var(--border-light);
                border-bottom: 1px solid var(--border-light);
            ">
                <div>
                    <div style="font-size: var(--text-xl); font-weight: 700; color: var(--primary-lal);">
                        <?php echo $review_count; ?>
                    </div>
                    <div style="font-size: var(--text-xs); color: var(--text-muted);">Yorum</div>
                </div>

                <div>
                    <div style="font-size: var(--text-xl); font-weight: 700; color: var(--accent-metallic);">
                        <?php echo $follower_count; ?>
                    </div>
                    <div style="font-size: var(--text-xs); color: var(--text-muted);">Takipçi</div>
                </div>

                <div>
                    <div style="font-size: var(--text-xl); font-weight: 700; color: var(--gold);">
                        <?php echo $following_count; ?>
                    </div>
                    <div style="font-size: var(--text-xs); color: var(--text-muted);">Takip</div>
                </div>
            </div>

            <!-- Profil Butonu -->
            <a href="profile.php" class="btn btn-secondary btn-sm btn-block" style="margin-top: var(--space-md);">
                <i class="fas fa-user"></i> Profilim
            </a>
        </div>

        <!-- Menü -->
        <nav style="margin-top: var(--space-lg); display: flex; flex-direction: column; gap: var(--space-xs);">
            <a href="dashboard.php" class="btn btn-secondary btn-block" style="justify-content: flex-start;">
                <i class="fas fa-home"></i> Ana Sayfa
            </a>
            <a href="books.php" class="btn btn-secondary btn-block" style="justify-content: flex-start;">
                <i class="fas fa-search"></i> Kitap Ara
            </a>
            <a href="explore.php" class="btn btn-secondary btn-block" style="justify-content: flex-start;">
                <i class="fas fa-compass"></i> Keşfet
            </a>
            <a href="profile.php" class="btn btn-secondary btn-block" style="justify-content: flex-start;">
                <i class="fas fa-star"></i> Yorumlarım
            </a>
        </nav>

    </aside>

    <!-- ============================================
         ORTA SÜTUN - KİTAP AKIŞI
         ============================================ -->

    <main style="padding: 0;">

        <!-- Flash Mesajı -->
        <?php echo get_flash(); ?>

        <!-- Yeni Kitap Keşfet Butonu -->
        <a href="books.php" class="btn btn-accent btn-lg btn-block" style="margin-bottom: var(--space-xl);">
            <i class="fas fa-book-open"></i> Yeni Kitap Keşfet
        </a>

        <!-- Akış Başlığı -->
        <h3 style="margin: 0 0 var(--space-lg) 0; color: var(--secondary-royal);">
            <i class="fas fa-stream" style="color: var(--accent-metallic);"></i>
            Akışın
        </h3>

        <!-- Yorumlar -->
        <?php if (empty($feed_reviews)): ?>

            <div class="card" style="text-align: center; padding: var(--space-3xl);">
                <div style="font-size: var(--text-5xl); margin-bottom: var(--space-lg); opacity: 0.3;">📖</div>
                <h3 style="color: var(--text-muted);">Akışın Boş</h3>
                <p style="color: var(--text-muted);">Kitapseverleri takip et!</p>
                <a href="explore.php" class="btn btn-primary" style="margin-top: var(--space-lg);">
                    <i class="fas fa-users"></i> Kullanıcıları Keşfet
                </a>
            </div>

        <?php else: ?>

            <?php foreach ($feed_reviews as $review): ?>

                <div class="card" style="margin-bottom: var(--space-lg);">

                    <!-- Kullanıcı Bilgileri -->
                    <div style="display: flex; gap: var(--space-md); margin-bottom: var(--space-md);">
                        <a href="profile.php?id=<?php echo $review['user_id']; ?>">
                            <img src="uploads/avatars/<?php echo htmlspecialchars($review['avatar']); ?>"
                                alt="<?php echo htmlspecialchars($review['username']); ?>" class="avatar-lg">
                        </a>

                        <div style="flex: 1;">
                            <a href="profile.php?id=<?php echo $review['user_id']; ?>"
                                style="font-weight: 700; color: var(--secondary-royal); font-size: var(--text-base);">
                                <?php echo htmlspecialchars($review['full_name']); ?>
                            </a>
                            <div style="color: var(--text-muted); font-size: var(--text-sm);">
                                @<?php echo htmlspecialchars($review['username']); ?> •
                                <?php echo time_ago($review['created_at']); ?>
                            </div>
                        </div>

                        <!-- Puan -->
                        <div>
                            <?php echo show_stars($review['rating']); ?>
                        </div>
                    </div>

                    <!-- Yorum -->
                    <p style="margin: 0 0 var(--space-md) 0; line-height: 1.7; color: var(--text-secondary);">
                        <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                    </p>

                    <!-- Kitap Bilgisi (Eğer varsa) -->
                    <?php if ($review['book_title']): ?>
                        <div style="
                            display: flex;
                            gap: var(--space-md);
                            padding: var(--space-md);
                            background: var(--soft-parchment);
                            border-radius: var(--radius-md);
                            border-left: 3px solid var(--accent-metallic);
                        ">
                            <?php if ($review['book_cover']): ?>
                                <img src="<?php echo htmlspecialchars($review['book_cover']); ?>"
                                    alt="<?php echo htmlspecialchars($review['book_title']); ?>"
                                    style="width: 60px; height: 90px; object-fit: cover; border-radius: var(--radius-sm);">
                            <?php endif; ?>

                            <div style="flex: 1;">
                                <div style="font-weight: 600; color: var(--secondary-royal); margin-bottom: var(--space-xs);">
                                    <?php echo htmlspecialchars($review['book_title']); ?>
                                </div>
                                <div style="font-size: var(--text-sm); color: var(--text-muted);">
                                    <?php echo htmlspecialchars($review['book_author']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Detay Linki -->
                    <a href="book-detail.php?id=<?php echo urlencode($review['book_api_id']); ?>"
                        class="btn btn-secondary btn-sm" style="margin-top: var(--space-md);">
                        <i class="fas fa-book"></i> Kitabı Gör
                    </a>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </main>

    <!-- ============================================
         SAĞ SÜTUN - ÖNERİLER
         ============================================ -->

    <aside style="position: sticky; top: 100px;">

        <!-- Popüler Kitaplar -->
        <div class="card">
            <h4 style="margin: 0 0 var(--space-lg) 0; color: var(--secondary-royal); font-size: var(--text-lg);">
                <i class="fas fa-fire" style="color: var(--accent-metallic);"></i> Popüler Kitaplar
            </h4>

            <?php if (!empty($popular_books)): ?>
                <div style="display: flex; flex-direction: column; gap: var(--space-md);">
                    <?php foreach ($popular_books as $index => $book): ?>
                        <a href="book-detail.php?id=<?php echo urlencode($book['api_id']); ?>" style="
                            display: flex;
                            gap: var(--space-md);
                            padding: var(--space-sm);
                            border-radius: var(--radius-md);
                            transition: background var(--transition-base);
                        " onmouseover="this.style.background='var(--soft-parchment)'"
                            onmouseout="this.style.background='transparent'">

                            <!-- Sıra -->
                            <div style="
                                font-size: var(--text-2xl);
                                font-weight: 900;
                                color: var(--primary-lal);
                                font-family: 'Cinzel', serif;
                                min-width: 30px;
                            ">
                                <?php echo $index + 1; ?>
                            </div>

                            <!-- Bilgi -->
                            <div style="flex: 1; min-width: 0;">
                                <div style="
                                    font-weight: 600;
                                    color: var(--secondary-royal);
                                    font-size: var(--text-sm);
                                    margin-bottom: var(--space-xs);
                                    overflow: hidden;
                                    text-overflow: ellipsis;
                                    white-space: nowrap;
                                ">
                                    <?php echo htmlspecialchars($book['title']); ?>
                                </div>
                                <div style="font-size: var(--text-xs); color: var(--text-muted);">
                                    <?php echo htmlspecialchars($book['author']); ?>
                                </div>
                                <?php if ($book['avg_rating']): ?>
                                    <div style="margin-top: var(--space-xs); font-size: var(--text-xs);">
                                        <?php echo show_stars(round($book['avg_rating'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: var(--text-muted); font-size: var(--text-sm); text-align: center;">
                    Henüz popüler kitap yok
                </p>
            <?php endif; ?>
        </div>

    </aside>

</div>

<?php
// Footer'ı dahil et
require_once 'includes/footer.php';
?>