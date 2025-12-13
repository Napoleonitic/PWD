<?php // includes/footer.php ?>
</main>

<footer class="footer">
    <div class="footer-wrapper">

        <!-- Kolom Kiri -->
        <div class="footer-col brand-box">
            <a href="#" class="logo">
                <img src="public/img/logo-cp.png" alt="CinePals Logo" class="logo-footer">
                <h2 class="logo-text">
                    Cine<span>Pals</span>
                </h2>
            </a>
            <p class="brand-desc">
                Platform terbaik untuk para pecinta film.<br>
                Menyediakan informasi film, jadwal bioskop,<br>
                hingga pemesanan tiket online.
            </p>
        </div>

        <!-- Kolom 1 -->
        <div class="footer-col">
            <h4>NAVIGASI</h4>
            <ul>
                <li><a href= "now_showing.php">Sedang Tayang</a></li>
                <li><a href= "#">Trailer</a></li>
                <li><a href= "about.html">Tentang Kami</a></li>
            </ul>
        </div>

        <!-- Kolom 2 -->
        <div class="footer-col">
            <h4>SUPPORT</h4>
            <ul>
                <li><a href="mailto:help@cinepals.com">Email: help@cinepals.com</a></li>
            </ul>
        </div>

    </div>

    <div class="footer-bottom">
        &copy; <?= date("Y") ?> CinePals – Tugas Besar PWD
    </div>
</footer>

</body>
</html>