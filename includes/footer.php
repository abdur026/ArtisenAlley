<footer>
        <p>&copy; <?php echo date("Y"); ?> Artisan Alley. All rights reserved.</p>
    </footer>
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>About Us</h3>
                <ul>
                    <li><a href="<?php echo url('/about.php'); ?>">Our Story</a></li>
                    <li><a href="<?php echo url('/mission.php'); ?>">Mission</a></li>
                    <li><a href="<?php echo url('/community.php'); ?>">Artisan Community</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Customer Service</h3>
                <ul>
                    <li><a href="<?php echo url('/contact.php'); ?>">Contact Us</a></li>
                    <li><a href="<?php echo url('/shipping.php'); ?>">Shipping Policy</a></li>
                    <li><a href="<?php echo url('/returns.php'); ?>">Returns & Exchanges</a></li>
                    <li><a href="<?php echo url('/faq.php'); ?>">FAQ</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Connect With Us</h3>
                <ul>
                    <li><a href="#"><i class="fab fa-facebook"></i> Facebook</a></li>
                    <li><a href="#"><i class="fab fa-instagram"></i> Instagram</a></li>
                    <li><a href="#"><i class="fab fa-pinterest"></i> Pinterest</a></li>
                    <li><a href="#"><i class="fab fa-twitter"></i> Twitter</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Newsletter</h3>
                <p>Subscribe to get updates on new artisans and special offers!</p>
                <form action="<?php echo url('/subscribe.php'); ?>" method="POST" class="newsletter-form">
                    <input type="email" name="email" placeholder="Enter your email" required>
                    <button type="submit">Subscribe</button>
                </form>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Artisan Alley. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
