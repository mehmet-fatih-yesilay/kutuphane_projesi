<!-- ============================================
         MAIN CONTENT BİTİŞİ
         ============================================ -->

</main>
<!-- Ana içerik burada bitiyor -->

<!-- ============================================
         FOOTER - ALT BİLGİ
         ============================================ -->

<footer>
    <!-- ============================================
             TELİF HAKKI VE BİLGİLER
             ============================================ -->

    <div class="footer-content">
        <!-- Telif hakkı yazısı -->
        <p>
            &copy;
            <?php echo date('Y'); ?>
            <strong>Kitap Sosyal Ağı</strong>.
            Tüm hakları saklıdır.
        </p>

        <!-- Ayırıcı -->
        <p style="margin: 10px 0; color: #7b8794;">•</p>

        <!-- Ek bilgiler -->
        <p style="font-size: 0.875rem; color: #9aa5b1;">
            Kitapları keşfet, yorum yap, arkadaşlarınla paylaş 📚
        </p>

        <!-- ============================================
                 FOOTER LİNKLERİ (Opsiyonel)
                 ============================================ -->

        <div style="margin-top: 15px; display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
            <!-- Hakkımızda linki -->
            <a href="about.php" style="font-size: 0.875rem;">
                Hakkımızda
            </a>

            <!-- Gizlilik Politikası linki -->
            <a href="privacy.php" style="font-size: 0.875rem;">
                Gizlilik Politikası
            </a>

            <!-- Kullanım Koşulları linki -->
            <a href="terms.php" style="font-size: 0.875rem;">
                Kullanım Koşulları
            </a>

            <!-- İletişim linki -->
            <a href="contact.php" style="font-size: 0.875rem;">
                İletişim
            </a>
        </div>

        <!-- ============================================
                 SOSYAL MEDYA İKONLARI (Opsiyonel)
                 ============================================ -->

        <div style="margin-top: 20px; display: flex; gap: 15px; justify-content: center;">
            <!-- Facebook -->
            <a href="#" aria-label="Facebook" style="font-size: 1.25rem;">
                <i class="fab fa-facebook"></i>
            </a>

            <!-- Twitter -->
            <a href="#" aria-label="Twitter" style="font-size: 1.25rem;">
                <i class="fab fa-twitter"></i>
            </a>

            <!-- Instagram -->
            <a href="#" aria-label="Instagram" style="font-size: 1.25rem;">
                <i class="fab fa-instagram"></i>
            </a>

            <!-- GitHub -->
            <a href="#" aria-label="GitHub" style="font-size: 1.25rem;">
                <i class="fab fa-github"></i>
            </a>
        </div>

        <!-- ============================================
                 GELİŞTİRİCİ BİLGİSİ (Opsiyonel)
                 ============================================ -->

        <p style="margin-top: 20px; font-size: 0.75rem; color: #7b8794;">
            Made with <span style="color: #20B2AA;">❤️</span> by Senior Backend Developer
        </p>
    </div>
</footer>

<!-- ============================================
         JAVASCRIPT DOSYALARI
         ============================================ -->

<!-- Ana JavaScript dosyası (mobil menü toggle için) -->
<script>
    /**
     * ============================================
     * MOBİL MENÜ TOGGLE FONKSİYONU
     * ============================================
     * Hamburger menü butonuna tıklandığında
     * navigasyon menüsünü aç/kapat
     */

    // Menü toggle butonunu seç
    const menuToggle = document.getElementById('menuToggle');

    // Navigasyon menüsünü seç
    const navMenu = document.getElementById('navMenu');

    // Eğer buton varsa (sayfa yüklendiyse)
    if (menuToggle && navMenu) {
        // Butona tıklama event listener ekle
        menuToggle.addEventListener('click', function () {
            // Menüye 'active' class'ını ekle/çıkar (toggle)
            navMenu.classList.toggle('active');

            // Hamburger ikonunu değiştir (bars ↔ times)
            const icon = this.querySelector('i');
            if (navMenu.classList.contains('active')) {
                // Menü açıksa X ikonu göster
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                // Menü kapalıysa hamburger ikonu göster
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });

        // Menü dışına tıklandığında menüyü kapat
        document.addEventListener('click', function (event) {
            // Tıklanan element menü veya buton değilse
            if (!navMenu.contains(event.target) && !menuToggle.contains(event.target)) {
                // Menüyü kapat
                navMenu.classList.remove('active');

                // İkonu hamburger'a çevir
                const icon = menuToggle.querySelector('i');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
    }

    /**
     * ============================================
     * SAYFA YÜKLENME ANİMASYONU
     * ============================================
     * Sayfa yüklendiğinde içeriğe fade-in efekti ekle
     */

    // Sayfa tamamen yüklendiğinde
    window.addEventListener('load', function () {
        // Main elementi seç
        const main = document.querySelector('main');

        // Eğer main element varsa
        if (main) {
            // Fade-in class'ını ekle
            main.classList.add('fade-in');
        }
    });
</script>

<!-- Ek JavaScript dosyası (varsa) -->
<!-- <script src="assets/js/main.js"></script> -->

</body>

</html>