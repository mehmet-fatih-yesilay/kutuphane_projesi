<?php
/**
 * ============================================
 * KİTAP DETAY VE YORUM SAYFASI
 * ============================================
 * Proje: Kitap Sosyal Ağı
 * Dosya: book-detail.php
 * Açıklama: Google Books API'den kitap detayları + Yorum sistemi
 * Kritik Mantık: Yorum yapılırken kitap önce cached_books'a kaydedilir
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
    set_flash('warning', 'Kitap detaylarını görmek için giriş yapmalısınız.');
    redirect('login.php');
}

// ============================================
// KİTAP ID'SİNİ AL VE DOĞRULA
// ============================================

// URL'den kitap ID'sini al
$book_id = isset($_GET['id']) ? sanitize($_GET['id']) : '';

// ID yoksa veya geçersizse hata ver
if (empty($book_id)) {
    set_flash('danger', 'Geçersiz kitap ID.');
    redirect('books.php');
}

// ============================================
// GOOGLE BOOKS API'DEN KİTAP DETAYLARINI ÇEK
// ============================================

$book = null;
$error_message = '';

try {
    // API URL'i (tek kitap detayı)
    $api_url = 'https://www.googleapis.com/books/v1/volumes/' . urlencode($book_id);

    // cURL ile API isteği
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT => 'Kitap Sosyal Agi/1.0'
    ]);

    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if (curl_errno($curl)) {
        $error_message = 'Bağlantı hatası: ' . curl_error($curl);
    }

    curl_close($curl);

    // API yanıtını işle
    if ($http_code === 200 && !empty($response)) {
        $data = json_decode($response, true);

        if (isset($data['volumeInfo'])) {
            $volume_info = $data['volumeInfo'];

            // Kitap bilgilerini düzenle
            $book = [
                'id' => $data['id'],
                'title' => $volume_info['title'] ?? 'Başlık Yok',
                'authors' => isset($volume_info['authors']) ? implode(', ', $volume_info['authors']) : 'Yazar Bilinmiyor',
                'publisher' => $volume_info['publisher'] ?? 'Yayınevi Bilinmiyor',
                'published_date' => $volume_info['publishedDate'] ?? '',
                'description' => $volume_info['description'] ?? 'Açıklama yok.',
                'page_count' => $volume_info['pageCount'] ?? 0,
                'categories' => isset($volume_info['categories']) ? implode(', ', $volume_info['categories']) : '',
                'language' => $volume_info['language'] ?? '',
                'thumbnail' => isset($volume_info['imageLinks']['thumbnail']) ? str_replace('http://', 'https://', $volume_info['imageLinks']['thumbnail']) : 'https://via.placeholder.com/128x192?text=Kapak+Yok',
                'preview_link' => $volume_info['previewLink'] ?? '',
                'info_link' => $volume_info['infoLink'] ?? ''
            ];
        } else {
            $error_message = 'Kitap bulunamadı.';
        }
    } else {
        $error_message = 'Kitap bilgileri yüklenirken hata oluştu.';
    }

} catch (Exception $e) {
    $error_message = 'Bir hata oluştu: ' . $e->getMessage();
    error_log('Book detail error: ' . $e->getMessage());
}

// Kitap bulunamadıysa geri dön
if (!$book) {
    set_flash('danger', $error_message);
    redirect('books.php');
}

// ============================================
// VERİTABANINDAN YORUMLARI ÇEK
// ============================================

$reviews = [];
$average_rating = 0;
$review_count = 0;

try {
    // Bu kitaba yapılmış yorumları çek
    $stmt = $pdo->prepare("
        SELECT 
            r.id,
            r.rating,
            r.comment,
            r.created_at,
            u.id as user_id,
            u.username,
            u.full_name,
            u.avatar
        FROM reviews r
        INNER JOIN users u ON r.user_id = u.id
        WHERE r.book_api_id = ?
        ORDER BY r.created_at DESC
    ");

    $stmt->execute([$book_id]);
    $reviews = $stmt->fetchAll();

    // Yorum sayısı
    $review_count = count($reviews);

    // Ortalama puan hesapla
    if ($review_count > 0) {
        $total_rating = array_sum(array_column($reviews, 'rating'));
        $average_rating = round($total_rating / $review_count, 1);
    }

} catch (PDOException $e) {
    error_log('Reviews fetch error: ' . $e->getMessage());
}

// ============================================
// KULLANICI DAHA ÖNCE YORUM YAPMIŞ MI?
// ============================================

$user_has_reviewed = false;

try {
    $stmt = $pdo->prepare("
        SELECT id FROM reviews 
        WHERE user_id = ? AND book_api_id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $book_id]);
    $user_has_reviewed = ($stmt->fetch() !== false);

} catch (PDOException $e) {
    error_log('User review check error: ' . $e->getMessage());
}

// ============================================
// YORUM GÖNDERME İŞLEMİ
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$user_has_reviewed) {

    // Form verilerini al
    $rating = sanitize_int($_POST['rating'] ?? 0);
    $comment = sanitize($_POST['comment'] ?? '');

    // Validasyon
    $errors = [];

    if (!validate_rating($rating)) {
        $errors[] = 'Puan 1-5 arası olmalıdır.';
    }

    if (empty($comment)) {
        $errors[] = 'Yorum boş bırakılamaz.';
    } elseif (strlen($comment) < 10) {
        $errors[] = 'Yorum en az 10 karakter olmalıdır.';
    }

    // Hata yoksa kaydet
    if (empty($errors)) {

        try {
            // Transaction başlat (atomik işlem)
            $pdo->beginTransaction();

            // ============================================
            // 1. ADIM: KİTABI CACHED_BOOKS'A KAYDET
            // ============================================

            // Kitap daha önce kaydedilmiş mi kontrol et
            $stmt = $pdo->prepare("SELECT id FROM cached_books WHERE api_id = ?");
            $stmt->execute([$book_id]);
            $cached_book = $stmt->fetch();

            // Eğer kitap yoksa kaydet
            if (!$cached_book) {
                $stmt = $pdo->prepare("
                    INSERT INTO cached_books (api_id, title, author, cover_url, cached_at)
                    VALUES (?, ?, ?, ?, NOW())
                ");

                $stmt->execute([
                    $book_id,
                    $book['title'],
                    $book['authors'],
                    $book['thumbnail']
                ]);
            }

            // ============================================
            // 2. ADIM: YORUMU REVIEWS'A KAYDET
            // ============================================

            $stmt = $pdo->prepare("
                INSERT INTO reviews (user_id, book_api_id, rating, comment, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $_SESSION['user_id'],
                $book_id,
                $rating,
                $comment
            ]);

            // Transaction'ı tamamla
            $pdo->commit();

            // Başarı mesajı
            set_flash('success', 'Yorumunuz başarıyla eklendi!');

            // Sayfayı yenile
            redirect('book-detail.php?id=' . urlencode($book_id));

        } catch (PDOException $e) {
            // Hata durumunda transaction'ı geri al
            $pdo->rollBack();

            set_flash('danger', 'Yorum eklenirken bir hata oluştu.');
            error_log('Review insert error: ' . $e->getMessage());
        }

    } else {
        // Hataları göster
        foreach ($errors as $error) {
            set_flash('danger', $error);
        }
    }
}

// Sayfa başlığı
$page_title = htmlspecialchars($book['title']);

// Header'ı dahil et
require_once 'includes/header.php';
?>

<!-- ============================================
     KİTAP DETAYLARI
     ============================================ -->

<div class="card" style="margin-bottom: 40px;">

    <div style="display: flex; gap: 40px; flex-wrap: wrap;">

        <!-- Kitap Kapağı -->
        <div style="flex-shrink: 0;">
            <img src="<?php echo htmlspecialchars($book['thumbnail']); ?>"
                alt="<?php echo htmlspecialchars($book['title']); ?>"
                style="width: 250px; border-radius: 8px; box-shadow: 0 8px 16px rgba(0,0,0,0.2);">

            <!-- Ortalama Puan -->
            <?php if ($review_count > 0): ?>
                <div
                    style="margin-top: 20px; text-align: center; padding: 15px; background-color: #f5f7fa; border-radius: 8px;">
                    <div style="font-size: 2rem; font-weight: bold; color: #20B2AA;">
                        <?php echo $average_rating; ?> / 5
                    </div>
                    <div style="margin: 10px 0;">
                        <?php echo show_stars(round($average_rating)); ?>
                    </div>
                    <div style="color: #7b8794; font-size: 0.9rem;">
                        <?php echo $review_count; ?> yorum
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Kitap Bilgileri -->
        <div style="flex: 1; min-width: 300px;">

            <!-- Başlık -->
            <h1 style="margin: 0 0 15px 0; color: #323f4b; font-size: 2rem;">
                <?php echo htmlspecialchars($book['title']); ?>
            </h1>

            <!-- Yazar -->
            <p style="font-size: 1.2rem; color: #7b8794; margin: 0 0 20px 0;">
                <i class="fas fa-user"></i>
                <?php echo htmlspecialchars($book['authors']); ?>
            </p>

            <!-- Bilgi Tablosu -->
            <table style="width: 100%; margin-bottom: 20px; border-collapse: collapse;">

                <?php if (!empty($book['publisher'])): ?>
                    <tr style="border-bottom: 1px solid #e4e7eb;">
                        <td style="padding: 10px 0; color: #7b8794; font-weight: 600;">Yayınevi:</td>
                        <td style="padding: 10px 0; color: #52606d;">
                            <?php echo htmlspecialchars($book['publisher']); ?>
                        </td>
                    </tr>
                <?php endif; ?>

                <?php if (!empty($book['published_date'])): ?>
                    <tr style="border-bottom: 1px solid #e4e7eb;">
                        <td style="padding: 10px 0; color: #7b8794; font-weight: 600;">Yayın Tarihi:</td>
                        <td style="padding: 10px 0; color: #52606d;">
                            <?php echo htmlspecialchars($book['published_date']); ?>
                        </td>
                    </tr>
                <?php endif; ?>

                <?php if (!empty($book['page_count'])): ?>
                    <tr style="border-bottom: 1px solid #e4e7eb;">
                        <td style="padding: 10px 0; color: #7b8794; font-weight: 600;">Sayfa Sayısı:</td>
                        <td style="padding: 10px 0; color: #52606d;">
                            <?php echo number_format($book['page_count']); ?>
                        </td>
                    </tr>
                <?php endif; ?>

                <?php if (!empty($book['categories'])): ?>
                    <tr style="border-bottom: 1px solid #e4e7eb;">
                        <td style="padding: 10px 0; color: #7b8794; font-weight: 600;">Kategori:</td>
                        <td style="padding: 10px 0; color: #52606d;">
                            <?php echo htmlspecialchars($book['categories']); ?>
                        </td>
                    </tr>
                <?php endif; ?>

                <?php if (!empty($book['language'])): ?>
                    <tr>
                        <td style="padding: 10px 0; color: #7b8794; font-weight: 600;">Dil:</td>
                        <td style="padding: 10px 0; color: #52606d;">
                            <?php echo strtoupper($book['language']); ?>
                        </td>
                    </tr>
                <?php endif; ?>

            </table>

            <!-- Açıklama -->
            <h3 style="color: #323f4b; margin: 20px 0 10px 0;">📖 Açıklama</h3>
            <div style="color: #52606d; line-height: 1.8; text-align: justify;">
                <?php echo nl2br(htmlspecialchars($book['description'])); ?>
            </div>

            <!-- Dış Linkler -->
            <div style="margin-top: 30px; display: flex; gap: 10px; flex-wrap: wrap;">
                <?php if (!empty($book['preview_link'])): ?>
                    <a href="<?php echo htmlspecialchars($book['preview_link']); ?>" target="_blank"
                        class="btn btn-secondary">
                        <i class="fas fa-eye"></i> Önizleme
                    </a>
                <?php endif; ?>

                <?php if (!empty($book['info_link'])): ?>
                    <a href="<?php echo htmlspecialchars($book['info_link']); ?>" target="_blank" class="btn btn-secondary">
                        <i class="fas fa-info-circle"></i> Daha Fazla Bilgi
                    </a>
                <?php endif; ?>
            </div>

        </div>

    </div>

</div>

<!-- Flash Mesajları -->
<?php echo get_flash(); ?>

<!-- ============================================
     YORUM YAPMA FORMU
     ============================================ -->

<?php if (!$user_has_reviewed): ?>

    <div class="card" style="margin-bottom: 40px; background-color: #e7f3ff; border-left: 4px solid #20B2AA;">

        <h3 style="margin: 0 0 20px 0; color: #20B2AA;">
            ✍️ Bu Kitap Hakkında Yorum Yap
        </h3>

        <form method="POST" action="">

            <!-- Puan Seçimi -->
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-star"></i> Puan (1-5)
                </label>

                <div style="display: flex; gap: 10px; font-size: 2rem;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <label style="cursor: pointer;">
                            <input type="radio" name="rating" value="<?php echo $i; ?>" required style="display: none;"
                                class="rating-input">
                            <i class="far fa-star rating-star" data-rating="<?php echo $i; ?>"
                                style="color: #fbbf24; transition: all 0.2s;"></i>
                        </label>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Yorum Metni -->
            <div class="form-group">
                <label for="comment" class="form-label">
                    <i class="fas fa-comment"></i> Yorumunuz
                </label>
                <textarea id="comment" name="comment" class="form-control" rows="5"
                    placeholder="Bu kitap hakkındaki düşüncelerinizi paylaşın... (En az 10 karakter)" required></textarea>
            </div>

            <!-- Gönder Butonu -->
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-paper-plane"></i> Yorumu Gönder
            </button>

        </form>

    </div>

<?php else: ?>

    <div class="card" style="margin-bottom: 40px; background-color: #fff3cd; border-left: 4px solid #f6ad55;">
        <p style="margin: 0; color: #856404;">
            <i class="fas fa-info-circle"></i>
            Bu kitap hakkında zaten yorum yaptınız. Her kitaba sadece bir kez yorum yapabilirsiniz.
        </p>
    </div>

<?php endif; ?>

<!-- ============================================
     YORUMLAR BÖLÜMÜ
     ============================================ -->

<h2 class="page-title" style="text-align: left; margin-bottom: 30px;">
    💬 Yorumlar
    <span class="badge badge-primary" style="margin-left: 10px;">
        <?php echo $review_count; ?>
    </span>
</h2>

<?php if (empty($reviews)): ?>

    <!-- Henüz yorum yoksa -->
    <div class="card" style="text-align: center; padding: 60px 20px;">
        <div style="font-size: 4rem; margin-bottom: 20px; opacity: 0.5;">💭</div>
        <h3 style="color: #7b8794; margin-bottom: 10px;">Henüz yorum yok</h3>
        <p style="color: #9aa5b1;">İlk yorumu yapan siz olun!</p>
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
            <div class="card-body">
                <p style="margin: 0; line-height: 1.6; color: #52606d;">
                    <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                </p>
            </div>

        </div>

    <?php endforeach; ?>

<?php endif; ?>

<!-- Yıldız Seçimi için JavaScript -->
<script>
    // Tüm yıldızları seç
    const stars = document.querySelectorAll('.rating-star');
    const ratingInputs = document.querySelectorAll('.rating-input');

    // Her yıldıza tıklama olayı ekle
    stars.forEach((star, index) => {
        // Tıklama
        star.addEventListener('click', function () {
            const rating = this.getAttribute('data-rating');

            // İlgili radio input'u seç
            ratingInputs[index].checked = true;

            // Tüm yıldızları güncelle
            updateStars(rating);
        });

        // Hover
        star.addEventListener('mouseenter', function () {
            const rating = this.getAttribute('data-rating');
            updateStars(rating);
        });
    });

    // Mouse çıkınca seçili puanı göster
    document.querySelector('form').addEventListener('mouseleave', function () {
        const selectedRating = document.querySelector('.rating-input:checked');
        if (selectedRating) {
            updateStars(selectedRating.value);
        } else {
            updateStars(0);
        }
    });

    // Yıldızları güncelle
    function updateStars(rating) {
        stars.forEach((star, index) => {
            if (index < rating) {
                star.classList.remove('far');
                star.classList.add('fas');
            } else {
                star.classList.remove('fas');
                star.classList.add('far');
            }
        });
    }
</script>

<?php
// Footer'ı dahil et
require_once 'includes/footer.php';
?>