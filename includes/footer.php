<?php
require_once __DIR__ . '/functions.php';
?>
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>About Us</h3>
                <p>Artisan Alley is your destination for unique, handcrafted items from talented artisans around the world.</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="<?php echo url('/index.php'); ?>">Home</a></li>
                    <li><a href="<?php echo url('/search.php'); ?>">Explore</a></li>
                    <li><a href="<?php echo url('/about.php'); ?>">About Us</a></li>
                    <li><a href="<?php echo url('/contact.php'); ?>">Contact</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Connect With Us</h3>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-pinterest"></i></a>
                </div>
                <form action="<?php echo url('/subscribe.php'); ?>" method="POST" class="newsletter-form">
                    <h4>Subscribe to Our Newsletter</h4>
                    <input type="email" name="email" placeholder="Enter your email" required>
                    <button type="submit" class="cta-button">Subscribe</button>
                </form>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Artisan Alley. All rights reserved.</p>
        </div>
    </footer>
    <script src="<?php echo asset('/js/main.js'); ?>"></script>
</body>
</html>
