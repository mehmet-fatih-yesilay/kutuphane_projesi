<?php
/**
 * ============================================
 * KİTAP ARAMA SAYFASI
 * ============================================
 * Proje: Kitap Sosyal Ağı
 * Dosya: books.php
 * Açıklama: Google Books API ile kitap arama
 * API: https://www.googleapis.com/books/v1/volumes
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
    set_flash('warning', 'Kitap aramak için giriş yapmalısınız.');
    redirect('login.php');
}

// ============================================
// ARAMA SORGUSU VE API İSTEĞİ
// ============================================

// Arama terimi (GET parametresinden al)
$search_query = isset($_GET['q']) ? sanitize($_GET['q']) : '';

// Arama sonuçları için değişken
$books = [];
$error_message = '';
$total_results = 0;

// Eğer arama yapılmışsa API'ye istek gönder
if (!empty($search_query)) {

    try {
        // ============================================
        // GOOGLE BOOKS API ENDPOINT
        // ============================================

        // API URL'i oluştur
        // q: arama terimi
        // maxResults: maksimum sonuç sayısı (40)
        // printType: sadece kitaplar (books)
        // langRestrict: Türkçe ve İngilizce kitaplar
        $api_url = 'https://www.googleapis.com/books/v1/volumes?q=' . urlencode($search_query) . '&maxResults=40&printType=books';

        // ============================================
        // cURL İLE API İSTEĞİ
        // ============================================

        // cURL oturumu başlat
        $curl = curl_init();

        // cURL seçeneklerini ayarla
        curl_setopt_array($curl, [
            CURLOPT_URL => $api_url,              // İstek yapılacak URL
            CURLOPT_RETURNTRANSFER => true,       // Sonucu string olarak döndür
            CURLOPT_FOLLOWLOCATION => true,       // Yönlendirmeleri takip et
            CURLOPT_TIMEOUT => 10,                // Timeout süresi (10 saniye)
            CURLOPT_SSL_VERIFYPEER => true,       // SSL sertifikasını doğrula
            CURLOPT_USERAGENT => 'Kitap Sosyal Agi/1.0' // User agent
        ]);

        // API isteğini gönder
        $response = curl_exec($curl);

        // HTTP durum kodunu al
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // cURL hatası var mı kontrol et
        if (curl_errno($curl)) {
            $error_message = 'Bağlantı hatası: ' . curl_error($curl);
            error_log('Google Books API cURL Error: ' . curl_error($curl));
        }

        // cURL oturumunu kapat
        curl_close($curl);

        // ============================================
        // API YANITI İŞLE
        // ============================================

        // HTTP 200 OK ise sonuçları işle
        if ($http_code === 200 && !empty($response)) {

            // JSON'u PHP dizisine çevir
            $data = json_decode($response, true);

            // Toplam sonuç sayısı
            $total_results = $data['totalItems'] ?? 0;

            // Eğer sonuç varsa işle
            if ($total_results > 0 && isset($data['items'])) {

                // Her kitap için
                foreach ($data['items'] as $item) {

                    // Kitap bilgilerini al
                    $volume_info = $item['volumeInfo'] ?? [];

                    // Kitap ID'si
                    $book_id = $item['id'] ?? '';

                    // Kitap başlığı
                    $title = $volume_info['title'] ?? 'Başlık Yok';

                    // Yazarlar (array olarak gelir, virgülle birleştir)
                    $authors = isset($volume_info['authors']) ? implode(', ', $volume_info['authors']) : 'Yazar Bilinmiyor';

                    // Kapak resmi (thumbnail)
                    // Eğer yoksa varsayılan bir resim kullan
                    $thumbnail = $volume_info['imageLinks']['thumbnail'] ?? 'https://via.placeholder.com/128x192?text=Kapak+Yok';

                    // HTTPS kullan (Google bazen HTTP döndürüyor)
                    $thumbnail = str_replace('http://', 'https://', $thumbnail);

                    // Açıklama (kısa)
                    $description = $volume_info['description'] ?? '';

                    // Yayın tarihi
                    $published_date = $volume_info['publishedDate'] ?? '';

                    // Sayfa sayısı
                    $page_count = $volume_info['pageCount'] ?? 0;

                    // Dil
                    $language = $volume_info['language'] ?? '';

                    // Kitap bilgilerini diziye ekle
                    $books[] = [
                        'id' => $book_id,
                        'title' => $title,
                        'authors' => $authors,
                        'thumbnail' => $thumbnail,
                        'description' => $description,
                        'published_date' => $published_date,
                        'page_count' => $page_count,
                        'language' => $language
                    ];
                }
            }

        } else {
            // API hatası
            $error_message = 'Kitaplar yüklenirken bir hata oluştu. Lütfen tekrar deneyin.';
            error_log('Google Books API Error: HTTP ' . $http_code);
        }

    } catch (Exception $e) {
        // Genel hata
        $error_message = 'Bir hata oluştu: ' . $e->getMessage();
        error_log('Books search error: ' . $e->getMessage());
    }
}

// Sayfa başlığı
$page_title = 'Kitap Ara';

// Header'ı dahil et
require_once 'includes/header.php';
?>

<!-- ============================================
     ARAMA BÖLÜMÜ
     ============================================ -->

<div class="card"
    style="margin-bottom: 40px; background: linear-gradient(135deg, #20B2AA 0%, #1a8f89 100%); color: white;">

    <!-- Başlık -->
    <h1 style="margin: 0 0 20px 0; text-align: center;">
        🔍 Kitap Ara
    </h1>

    <!-- Arama Formu -->
    <form method="GET" action="books.php" style="max-width: 600px; margin: 0 auto;">

        <div style="display: flex; gap: 10px;">

            <!-- Arama Input -->
            <input type="text" name="q" class="form-control" placeholder="Kitap adı, yazar veya ISBN girin..."
                value="<?php echo htmlspecialchars($search_query); ?>" required
                style="flex: 1; font-size: 1.1rem; padding: 15px 20px;">

            <!-- Ara Butonu -->
            <button type="submit" class="btn btn-lg"
                style="background-color: white; color: #20B2AA; font-weight: 600; padding: 15px 30px;">
                <i class="fas fa-search"></i> Ara
            </button>

        </div>

    </form>

    <!-- Arama İpuçları -->
    <div style="text-align: center; margin-top: 20px; opacity: 0.9; font-size: 0.9rem;">
        <p style="margin: 0;">
            💡 İpucu: "Harry Potter", "Orhan Pamuk", "9780747532699" gibi aramalar yapabilirsiniz
        </p>
    </div>

</div>

<!-- ============================================
     ARAMA SONUÇLARI
     ============================================ -->

<?php if (!empty($search_query)): ?>

    <!-- Sonuç Sayısı Başlığı -->
    <div style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
        <h2 style="margin: 0; color: #323f4b;">
            "
            <?php echo htmlspecialchars($search_query); ?>" için sonuçlar
        </h2>
        <span class="badge badge-primary" style="font-size: 1rem; padding: 8px 16px;">
            <?php echo number_format($total_results); ?> sonuç
        </span>
    </div>

    <!-- Hata Mesajı -->
    <?php if (!empty($error_message)): ?>
        <div class="card" style="background-color: #f8d7da; color: #721c24; border-left: 4px solid #dc3545;">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- Sonuç Yok -->
    <?php if (empty($books) && empty($error_message)): ?>
        <div class="card" style="text-align: center; padding: 60px 20px;">
            <div style="font-size: 4rem; margin-bottom: 20px; opacity: 0.5;">📚</div>
            <h3 style="color: #7b8794; margin-bottom: 10px;">Sonuç Bulunamadı</h3>
            <p style="color: #9aa5b1;">Farklı bir arama terimi deneyin.</p>
        </div>
    <?php endif; ?>

    <!-- Kitap Listesi (Grid) -->
    <?php if (!empty($books)): ?>

        <div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));">

            <?php foreach ($books as $book): ?>

                <!-- Kitap Kartı -->
                <a href="book-detail.php?id=<?php echo urlencode($book['id']); ?>" style="text-decoration: none; color: inherit;">

                    <div class="card"
                        style="height: 100%; display: flex; flex-direction: column; transition: transform 0.3s ease, box-shadow 0.3s ease;">

                        <!-- Kitap Kapağı -->
                        <div style="text-align: center; padding: 20px; background-color: #f5f7fa; border-radius: 8px 8px 0 0;">
                            <img src="<?php echo htmlspecialchars($book['thumbnail']); ?>"
                                alt="<?php echo htmlspecialchars($book['title']); ?>"
                                style="max-width: 100%; height: 200px; object-fit: contain; border-radius: 4px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                        </div>

                        <!-- Kitap Bilgileri -->
                        <div style="padding: 20px; flex: 1; display: flex; flex-direction: column;">

                            <!-- Başlık -->
                            <h3 style="margin: 0 0 10px 0; font-size: 1.1rem; color: #323f4b; line-height: 1.4;">
                                <?php echo htmlspecialchars(excerpt($book['title'], 60)); ?>
                            </h3>

                            <!-- Yazar -->
                            <p style="margin: 0 0 10px 0; color: #7b8794; font-size: 0.9rem;">
                                <i class="fas fa-user"></i>
                                <?php echo htmlspecialchars(excerpt($book['authors'], 50)); ?>
                            </p>

                            <!-- Ek Bilgiler -->
                            <div
                                style="margin-top: auto; padding-top: 10px; border-top: 1px solid #e4e7eb; font-size: 0.85rem; color: #9aa5b1;">

                                <?php if (!empty($book['published_date'])): ?>
                                    <span style="margin-right: 10px;">
                                        <i class="fas fa-calendar"></i>
                                        <?php echo htmlspecialchars(substr($book['published_date'], 0, 4)); ?>
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($book['page_count'])): ?>
                                    <span>
                                        <i class="fas fa-file-alt"></i>
                                        <?php echo number_format($book['page_count']); ?> sayfa
                                    </span>
                                <?php endif; ?>

                            </div>

                        </div>

                        <!-- Detay Butonu -->
                        <div style="padding: 0 20px 20px 20px;">
                            <div class="btn btn-primary btn-block" style="text-align: center;">
                                <i class="fas fa-eye"></i> Detayları Gör
                            </div>
                        </div>

                    </div>

                </a>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

<?php else: ?>

    <!-- İlk Yükleme - Popüler Aramalar Öner -->
    <div class="card" style="text-align: center; padding: 60px 20px;">
        <div style="font-size: 4rem; margin-bottom: 20px;">📖</div>
        <h3 style="color: #323f4b; margin-bottom: 20px;">Kitap Aramaya Başlayın</h3>
        <p style="color: #7b8794; margin-bottom: 30px;">Yukarıdaki arama çubuğunu kullanarak milyonlarca kitabı keşfedin</p>

        <!-- Popüler Aramalar -->
        <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
            <a href="?q=Harry+Potter" class="btn btn-secondary">Harry Potter</a>
            <a href="?q=Orhan+Pamuk" class="btn btn-secondary">Orhan Pamuk</a>
            <a href="?q=1984" class="btn btn-secondary">1984</a>
            <a href="?q=Sabahattin+Ali" class="btn btn-secondary">Sabahattin Ali</a>
            <a href="?q=Suç+ve+Ceza" class="btn btn-secondary">Suç ve Ceza</a>
        </div>
    </div>

<?php endif; ?>

<!-- Hover Efekti için CSS -->
<style>
    .card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
    }
</style>

<?php
// Footer'ı dahil et
require_once 'includes/footer.php';
?>