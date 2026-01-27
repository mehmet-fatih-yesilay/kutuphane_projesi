-- ============================================
-- KİTAP SOSYAL AĞI VERİTABANI ŞEMASI
-- Proje: Kitap Sosyal Ağı
-- Veritabanı: MySQL
-- Karakter Seti: UTF-8
-- ============================================

-- Eğer veritabanı varsa sil ve yeniden oluştur
DROP DATABASE IF EXISTS kitap_sosyal_agi;

-- Veritabanını UTF-8 karakter seti ile oluştur
CREATE DATABASE kitap_sosyal_agi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Oluşturulan veritabanını kullan
USE kitap_sosyal_agi;

-- ============================================
-- KULLANICILAR TABLOSU
-- Sistemdeki tüm kullanıcı bilgilerini saklar
-- ============================================
CREATE TABLE users (
    -- Benzersiz kullanıcı kimliği (otomatik artan)
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Kullanıcı adı (benzersiz, boş olamaz, 3-50 karakter)
    username VARCHAR(50) NOT NULL UNIQUE,
    
    -- E-posta adresi (benzersiz, boş olamaz)
    email VARCHAR(100) NOT NULL UNIQUE,
    
    -- Şifrelenmiş parola (bcrypt/password_hash ile)
    password VARCHAR(255) NOT NULL,
    
    -- Kullanıcının tam adı
    full_name VARCHAR(100) NOT NULL,
    
    -- Kullanıcı biyografisi (kısa tanıtım metni)
    bio TEXT DEFAULT NULL,
    
    -- Profil resmi yolu (varsayılan: default-avatar.png)
    avatar VARCHAR(255) DEFAULT 'default-avatar.png',
    
    -- Kayıt tarihi (otomatik olarak şu anki zaman)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Performans için username üzerinde index
    INDEX idx_username (username),
    
    -- Performans için email üzerinde index
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ÖNBELLEKLENEN KİTAPLAR TABLOSU
-- Google Books API'den çekilen kitapları saklar
-- Her API çağrısında tekrar sorgu yapılmasını önler
-- ============================================
CREATE TABLE cached_books (
    -- Benzersiz önbellek kimliği (otomatik artan)
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Google Books API'deki kitap ID'si (benzersiz)
    api_id VARCHAR(100) NOT NULL UNIQUE,
    
    -- Kitap başlığı
    title VARCHAR(255) NOT NULL,
    
    -- Yazar adı (birden fazla yazar varsa virgülle ayrılmış)
    author VARCHAR(255) DEFAULT NULL,
    
    -- Kitap kapak resmi URL'i
    cover_url VARCHAR(500) DEFAULT NULL,
    
    -- Önbelleğe alınma tarihi
    cached_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Performans için api_id üzerinde index
    INDEX idx_api_id (api_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- YORUMLAR TABLOSU
-- Kullanıcıların kitaplar hakkında yaptığı yorumları saklar
-- ============================================
CREATE TABLE reviews (
    -- Benzersiz yorum kimliği (otomatik artan)
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Yorumu yapan kullanıcının ID'si
    user_id INT NOT NULL,
    
    -- Yorumlanan kitabın Google Books API ID'si
    book_api_id VARCHAR(100) NOT NULL,
    
    -- Kullanıcının verdiği puan (1-5 arası)
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    
    -- Yorum metni
    comment TEXT NOT NULL,
    
    -- Yorum tarihi (otomatik olarak şu anki zaman)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Kullanıcı silinirse yorumları da sil (CASCADE)
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Performans için user_id üzerinde index
    INDEX idx_user_id (user_id),
    
    -- Performans için book_api_id üzerinde index
    INDEX idx_book_api_id (book_api_id),
    
    -- Bir kullanıcı aynı kitaba sadece bir kez yorum yapabilir
    UNIQUE KEY unique_user_book (user_id, book_api_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TAKİP İLİŞKİLERİ TABLOSU
-- Kullanıcılar arası takip ilişkilerini saklar
-- ============================================
CREATE TABLE follows (
    -- Takip eden kullanıcının ID'si
    follower_id INT NOT NULL,
    
    -- Takip edilen kullanıcının ID'si
    followed_id INT NOT NULL,
    
    -- Takip tarihi (otomatik olarak şu anki zaman)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Birincil anahtar (takipçi + takip edilen kombinasyonu benzersiz)
    PRIMARY KEY (follower_id, followed_id),
    
    -- Takip eden kullanıcı silinirse ilişkiyi sil
    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Takip edilen kullanıcı silinirse ilişkiyi sil
    FOREIGN KEY (followed_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Performans için follower_id üzerinde index
    INDEX idx_follower (follower_id),
    
    -- Performans için followed_id üzerinde index
    INDEX idx_followed (followed_id),
    
    -- Kullanıcı kendini takip edemez kontrolü
    CHECK (follower_id != followed_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TEST VERİLERİ (İsteğe bağlı - geliştirme için)
-- ============================================

-- Test kullanıcısı ekle (şifre: 123456)
INSERT INTO users (username, email, password, full_name, bio) VALUES
('test_user', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test Kullanıcı', 'Kitap tutkunuyum!');

-- ============================================
-- VERİTABANI ŞEMASI TAMAMLANDI
-- ============================================