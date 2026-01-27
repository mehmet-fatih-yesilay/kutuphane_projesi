<?php
/**
 * ============================================
 * DASHBOARD - DİJİTAL OTAĞ ANA SAYFA
 * ============================================
 * Proje: Kitap Sosyal Ağı
 * Dosya: dashboard.php
 * Yapı: 3 Sütun (Sol: Profil, Orta: Akış, Sağ: Günün Sözü)
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

// Takipçi ve takip edilen sayılarını çek
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

// Son yorumları çek (takip edilenler + kendi yorumlar)
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
            u.avatar
        FROM reviews r
        INNER JOIN users u ON r.user_id = u.id
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

// Popüler kitapları çek (en çok yorum alan)
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

// Günün sözü (rastgele)
$quotes = [
    ["text" => "Okuduğun kitaplar, senin gerçek zenginliğindir.", "author" => "Türk Atasözü"],
    ["text" => "Bir kitap bin dosttan yeğdir.", "author" => "Yunus Emre"],
    ["text" => "İlim Çin'de bile olsa gidiniz alınız.", "author" => "Hz. Muhammed"],
    ["text" => "Kitap okumayan bir millet, yüksek bir medeniyet seviyesine ulaşamaz.", "author" => "Mustafa Kemal Atatürk"],
    ["text" => "Kitaplar, zamanın dalgaları üzerinde yol alan düşüncenin gemileridir.", "author" => "Francis Bacon"]
];
$daily_quote = $quotes[array_rand($quotes)];

// Sayfa başlığı
$page_title = 'Dashboard';

// Header'ı dahil et
require_once 'includes/header.php';
?>

<!-- Dashboard 3 Sütun Layout -->
<div style="display: grid; grid-template-columns: 280px 1fr 320px; gap: var(--space-xl); align-items: start;">

    <!-- ============================================
         SOL SÜTUN - PROFİL ÖZETİ VE MENÜ
         ============================================ -->

    <aside style="position: sticky; top: 100px;">

        <!-- Profil Kartı - Minyatür Sanatı Tarzı -->
        <div class="card"
            style="text-align: center; background: linear-gradient(135deg, var(--soft-parchment) 0%, var(--light-pure) 100%);">

            <!-- Avatar - Altın Çerçeveli -->
            <div style="position: relative; display: inline-block; margin-bottom: var(--space-lg);">
                <img src="uploads/avatars/<?php echo htmlspecialchars($user_avatar); ?>"
                    alt="<?php echo htmlspecialchars($user_full_name); ?>" class="avatar-xl"
                    style="border: 4px solid var(--gold); box-shadow: 0 0 0 3px var(--primary-lal), var(--shadow-gold);">
                <!-- Aktif Durum İşareti -->
                <div
                    style="position: absolute; bottom: 5px; right: 5px; width: 20px; height: 20px; background: #10b981; border: 3px solid var(--light-pure); border-radius: var(--radius-full);">
                </div>
            </div>

            <!-- İsim -->
            <h3 style="margin: 0 0 var(--space-xs) 0; color: var(--secondary-royal);">
                <?php echo htmlspecialchars($user_full_name); ?>
            </h3>

            <!-- Kullanıcı Adı -->
            <p style="margin: 0 0 var(--space-lg) 0; color: var(--text-muted); font-size: var(--text-sm);">
                @
                <?php echo htmlspecialchars($user_name); ?>
            </p>

            <!-- İstatistikler -->
            <div
                style="display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-md); padding: var(--space-lg) 0; border-top: 2px solid var(--border-light); border-bottom: 2px solid var(--border-light);">

                <div>
                    <div style="font-size: var(--text-2xl); font-weight: 700; color: var(--primary-lal);">
                        <?php echo $review_count; ?>
                    </div>
                    <div style="font-size: var(--text-xs); color: var(--text-muted); text-transform: uppercase;">Yorum
                    </div>
                </div>

                <div>
                    <div style="font-size: var(--text-2xl); font-weight: 700; color: var(--accent-metallic);">
                        <?php echo $follower_count; ?>
                    </div>
                    <div style="font-size: var(--text-xs); color: var(--text-muted); text-transform: uppercase;">Takipçi
                    </div>
                </div>

                <div>
                    <div style="font-size: var(--text-2xl); font-weight: 700; color: var(--gold);">
                        <?php echo $following_count; ?>
                    </div>
                    <div style="font-size: var(--text-xs); color: var(--text-muted); text-transform: uppercase;">Takip
                    </div>
                </div>

            </div>

            <!-- Profil Butonu -->
            <a href="profile.php" class="btn btn-secondary btn-block" style="margin-top: var(--space-lg);">
                <i class="fas fa-user-circle"></i> Profilimi Gör
            </a>

        </div>

        <!-- Hızlı Menü -->
        <div class="card" style="margin-top: var(--space-lg); padding: var(--space-lg);">
            <h4 style="margin: 0 0 var(--space-md) 0; font-size: var(--text-lg); color: var(--secondary-royal);">Hızlı
                Erişim</h4>

            <nav style="display: flex; flex-direction: column; gap: var(--space-sm);">
                <a href="books.php"
                    style="padding: var(--space-md); border-radius: var(--radius-md); display: flex; align-items: center; gap: var(--space-sm); transition: all var(--transition-base);">
                    <i class="fas fa-search" style="color: var(--accent-metallic); font-size: var(--text-lg);"></i>
                    <span>Kitap Ara</span>
                </a>
                <a href="profile.php"
                    style="padding: var(--space-md); border-radius: var(--radius-md); display: flex; align-items: center; gap: var(--space-sm); transition: all var(--transition-base);">
                    <i class="fas fa-star" style="color: var(--gold); font-size: var(--text-lg);"></i>
                    <span>Yorumlarım</span>
                </a>
                <a href="explore.php"
                    style="padding: var(--space-md); border-radius: var(--radius-md); display: flex; align-items: center; gap: var(--space-sm); transition: all var(--transition-base);">
                    <i class="fas fa-compass" style="color: var(--primary-lal); font-size: var(--text-lg);"></i>
                    <span>Keşfet</span>
                </a>
            </nav>
        </div>

    </aside>

    <!-- ============================================
         ORTA SÜTUN - AKIŞ (FEED)
         ============================================ -->

    <main style="padding: 0;">

        <!-- Hoş Geldin Mesajı - Zarif ve Entegre -->
        <div
            style="background: linear-gradient(135deg, var(--primary-lal) 0%, var(--accent-metallic) 100%); color: var(--light-pure); padding: var(--space-2xl); border-radius: var(--radius-lg); margin-bottom: var(--space-xl); box-shadow: var(--shadow-lal); position: relative; overflow: hidden;">

            <!-- Arka Plan Deseni -->
            <div
                style="position: absolute; top: 0; right: 0; width: 200px; height: 200px; opacity: 0.1; background-image: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(255,255,255,0.5) 10px, rgba(255,255,255,0.5) 20px);">
            </div>

            <h2 style="margin: 0 0 var(--space-sm) 0; color: var(--light-pure); font-size: var(--text-3xl);">
                Hoş Geldin,
                <?php echo htmlspecialchars(explode(' ', $user_full_name)[0]); ?>! 📚
            </h2>
            <p style="margin: 0; opacity: 0.95; font-size: var(--text-lg);">
                Bugün hangi kitabı keşfedeceksin?
            </p>
        </div>

        <!-- Flash Mesajı -->
        <?php echo get_flash(); ?>

        <!-- Yeni Kitap Keşfet Butonu -->
        <a href="books.php" class="btn btn-accent btn-lg btn-block" style="margin-bottom: var(--space-2xl);">
            <i class="fas fa-book-open"></i> Yeni Kitap Keşfet
        </a>

        <!-- Akış Başlığı -->
        <h3
            style="margin: 0 0 var(--space-xl) 0; color: var(--secondary-royal); display: flex; align-items: center; gap: var(--space-sm);">
            <i class="fas fa-stream" style="color: var(--accent-metallic);"></i>
            Akışın
        </h3>

        <!-- Yorumlar Akışı -->
        <?php if (empty($feed_reviews)): ?>

            <div class="card" style="text-align: center; padding: var(--space-4xl);">
                <div style="font-size: var(--text-6xl); margin-bottom: var(--space-lg); opacity: 0.3;">📖</div>
                <h3 style="color: var(--text-muted);">Akışın Henüz Boş</h3>
                <p style="color: var(--text-muted);">Kitapseverleri takip et ve yorumlarını gör!</p>
                <a href="explore.php" class="btn btn-primary" style="margin-top: var(--space-lg);">
                    <i class="fas fa-users"></i> Kullanıcıları Keşfet
                </a>
            </div>

        <?php else: ?>

            <?php foreach ($feed_reviews as $review): ?>

                <div class="card fade-in">

                    <!-- Kullanıcı Bilgileri -->
                    <div style="display: flex; align-items: center; gap: var(--space-md); margin-bottom: var(--space-lg);">
                        <a href="profile.php?id=<?php echo $review['user_id']; ?>">
                            <img src="uploads/avatars/<?php echo htmlspecialchars($review['avatar']); ?>"
                                alt="<?php echo htmlspecialchars($review['username']); ?>" class="avatar-lg">
                        </a>

                        <div style="flex: 1;">
                            <a href="profile.php?id=<?php echo $review['user_id']; ?>"
                                style="font-weight: 700; color: var(--secondary-royal); font-size: var(--text-lg); display: block;">
                                <?php echo htmlspecialchars($review['full_name']); ?>
                            </a>
                            <div style="color: var(--text-muted); font-size: var(--text-sm);">
                                @
                                <?php echo htmlspecialchars($review['username']); ?> •
                                <?php echo time_ago($review['created_at']); ?>
                            </div>
                        </div>

                        <!-- Yıldız Puanı -->
                        <div>
                            <?php echo show_stars($review['rating']); ?>
                        </div>
                    </div>

                    <!-- Yorum İçeriği -->
                    <div style="margin-bottom: var(--space-lg);">
                        <p style="margin: 0; line-height: 1.8; color: var(--text-secondary); font-size: var(--text-base);">
                            <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                        </p>
                    </div>

                    <!-- Kitap Detayı Linki -->
                    <a href="book-detail.php?id=<?php echo urlencode($review['book_api_id']); ?>"
                        class="btn btn-secondary btn-sm">
                        <i class="fas fa-book"></i> Kitabı Gör
                    </a>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </main>

    <!-- ============================================
         SAĞ SÜTUN - GÜNÜN SÖZÜ VE POPÜLER KİTAPLAR
         ============================================ -->

    <aside style="position: sticky; top: 100px;">

        <!-- Günün Sözü -->
        <div class="card"
            style="background: linear-gradient(135deg, var(--secondary-royal) 0%, var(--secondary-royal-dark) 100%); color: var(--light-pure); position: relative; overflow: hidden;">

            <!-- Altın Süsleme -->
            <div
                style="position: absolute; top: -20px; right: -20px; width: 100px; height: 100px; background: var(--gold); opacity: 0.1; border-radius: var(--radius-full);">
            </div>

            <h4
                style="margin: 0 0 var(--space-lg) 0; color: var(--gold); font-size: var(--text-lg); display: flex; align-items: center; gap: var(--space-sm);">
                <i class="fas fa-quote-left"></i> Günün Sözü
            </h4>

            <p
                style="margin: 0 0 var(--space-md) 0; font-size: var(--text-lg); line-height: 1.8; font-style: italic; color: var(--soft-parchment);">
                "
                <?php echo htmlspecialchars($daily_quote['text']); ?>"
            </p>

            <p style="margin: 0; font-size: var(--text-sm); color: var(--gold); text-align: right;">
                —
                <?php echo htmlspecialchars($daily_quote['author']); ?>
            </p>
        </div>

        <!-- Popüler Kitaplar -->
        <div class="card" style="margin-top: var(--space-lg);">
            <h4
                style="margin: 0 0 var(--space-lg) 0; color: var(--secondary-royal); font-size: var(--text-lg); display: flex; align-items: center; gap: var(--space-sm);">
                <i class="fas fa-fire" style="color: var(--accent-metallic);"></i> Popüler Kitaplar
            </h4>

            <?php if (!empty($popular_books)): ?>
                <div style="display: flex; flex-direction: column; gap: var(--space-md);">
                    <?php foreach ($popular_books as $index => $book): ?>
                        <a href="book-detail.php?id=<?php echo urlencode($book['api_id']); ?>"
                            style="display: flex; gap: var(--space-md); padding: var(--space-sm); border-radius: var(--radius-md); transition: all var(--transition-base);"
                            onmouseover="this.style.background='var(--soft-parchment)'"
                            onmouseout="this.style.background='transparent'">

                            <!-- Sıra Numarası -->
                            <div
                                style="font-size: var(--text-2xl); font-weight: 900; color: var(--primary-lal); font-family: 'Cinzel', serif; min-width: 30px;">
                                <?php echo $index + 1; ?>
                            </div>

                            <!-- Kitap Bilgisi -->
                            <div style="flex: 1; min-width: 0;">
                                <div
                                    style="font-weight: 600; color: var(--secondary-royal); font-size: var(--text-sm); margin-bottom: var(--space-xs); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?php echo htmlspecialchars($book['title']); ?>
                                </div>
                                <div style="font-size: var(--text-xs); color: var(--text-muted);">
                                    <?php echo htmlspecialchars($book['author']); ?>
                                </div>
                                <div style="margin-top: var(--space-xs);">
                                    <?php if ($book['avg_rating']): ?>
                                        <?php echo show_stars(round($book['avg_rating'])); ?>
                                    <?php endif; ?>
                                </div>
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

<style>
    /* Hover efektleri */
    aside nav a:hover {
        background: linear-gradient(135deg, rgba(200, 16, 46, 0.05) 0%, rgba(240, 80, 51, 0.05) 100%);
        transform: translateX(4px);
    }
</style>

<?php
// Footer'ı dahil et
require_once 'includes/footer.php';
?>