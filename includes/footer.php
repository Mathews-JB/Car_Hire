<footer class="footer-main">
    <div class="container">
        <div class="footer-grid">
            <!-- Brand & Info -->
            <div class="footer-section">
                <a href="index.php" class="logo" style="font-size: 1.5rem; margin-bottom: 20px; display: inline-block;">Car Hire</a>
                <p style="color: rgba(255,255,255,0.6); font-size: 0.9rem; line-height: 1.6; margin-bottom: 20px;"> Zambia's premier car rental service. Experience luxury, reliability, and the freedom to explore our beautiful nation in style.</p>
                <div class="social-links" style="display: flex; gap: 12px;">
                    <a href="#" style="width: 36px; height: 36px; background: rgba(255,255,255,0.05); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; transition: 0.3s; border: 1px solid rgba(255,255,255,0.1);"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" style="width: 36px; height: 36px; background: rgba(255,255,255,0.05); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; transition: 0.3s; border: 1px solid rgba(255,255,255,0.1);"><i class="fab fa-instagram"></i></a>
                    <a href="#" style="width: 36px; height: 36px; background: rgba(255,255,255,0.05); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; transition: 0.3s; border: 1px solid rgba(255,255,255,0.1);"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>

            <!-- Grouped Links: 2x2 Grid -->
            <div class="footer-links-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                <!-- Quick Links -->
                <div class="footer-section">
                    <h4 style="color: white; font-weight: 700; margin-bottom: 20px; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px;">Quick Explore</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 12px;"><a href="our-fleet.php" style="color: rgba(255,255,255,0.5); text-decoration: none; font-size: 0.85rem; transition: 0.3s;" onmouseover="this.style.color='var(--accent-color)'" onmouseout="this.style.color='rgba(255,255,255,0.5)'">View Fleet</a></li>
                        <li style="margin-bottom: 12px;"><a href="index.php#search" style="color: rgba(255,255,255,0.5); text-decoration: none; font-size: 0.85rem; transition: 0.3s;" onmouseover="this.style.color='var(--accent-color)'" onmouseout="this.style.color='rgba(255,255,255,0.5)'">Search Vehicles</a></li>
                        <li style="margin-bottom: 12px;"><a href="register.php" style="color: rgba(255,255,255,0.5); text-decoration: none; font-size: 0.85rem; transition: 0.3s;" onmouseover="this.style.color='var(--accent-color)'" onmouseout="this.style.color='rgba(255,255,255,0.5)'">Join Membership</a></li>
                    </ul>
                </div>

                <!-- Legal & Support -->
                <div class="footer-section">
                    <h4 style="color: white; font-weight: 700; margin-bottom: 20px; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px;">Legal & Trust</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 12px;"><a href="terms.php" style="color: rgba(255,255,255,0.5); text-decoration: none; font-size: 0.85rem; transition: 0.3s;" onmouseover="this.style.color='var(--accent-color)'" onmouseout="this.style.color='rgba(255,255,255,0.5)'">Terms</a></li>
                        <li style="margin-bottom: 12px;"><a href="privacy.php" style="color: rgba(255,255,255,0.5); text-decoration: none; font-size: 0.85rem; transition: 0.3s;" onmouseover="this.style.color='var(--accent-color)'" onmouseout="this.style.color='rgba(255,255,255,0.5)'">Privacy</a></li>
                        <li style="margin-bottom: 12px;"><a href="support-tickets.php" style="color: rgba(255,255,255,0.5); text-decoration: none; font-size: 0.85rem; transition: 0.3s;" onmouseover="this.style.color='var(--accent-color)'" onmouseout="this.style.color='rgba(255,255,255,0.5)'">Support Hub</a></li>
                    </ul>
                </div>
            </div>

            <!-- Contact -->
            <div class="footer-section">
                <h4 style="color: white; font-weight: 700; margin-bottom: 20px; font-size: 1rem; text-transform: uppercase; letter-spacing: 1px;">Contact Us</h4>
                <div class="footer-contact-item" style="margin-bottom: 15px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); padding: 12px 20px; border-radius: 12px; display: flex; align-items: center; gap: 12px;">
                    <i class="fas fa-phone" style="color: var(--accent-color);"></i>
                    <span style="color: white; font-size: 0.85rem; font-weight: 600;">+260 970 000 000</span>
                </div>
                <div class="footer-contact-item" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); padding: 12px 20px; border-radius: 12px; display: flex; align-items: center; gap: 12px;">
                    <i class="fas fa-envelope" style="color: var(--accent-color);"></i>
                    <span style="color: white; font-size: 0.85rem; font-weight: 600;">info@CarHire.zm</span>
                </div>
            </div>
        </div>

        <div class="footer-bottom" style="margin-top: 50px; padding-top: 30px; border-top: 1px solid rgba(255,255,255,0.05); text-align: center;">
            <p style="color: rgba(255,255,255,0.3); font-size: 0.8rem; letter-spacing: 1px;">&copy; <?php echo date('Y'); ?> CAR HIRE. ALL RIGHTS RESERVED.</p>
        </div>
    </div>
</footer>

<style>
.footer-main {
    background: rgba(20, 20, 20, 0.6) !important;
    backdrop-filter: blur(40px) saturate(180%);
    -webkit-backdrop-filter: blur(40px) saturate(180%);
    border-top: 1px solid rgba(255,255,255,0.1);
    padding: 80px 0 40px;
}
.footer-grid {
    display: grid;
    grid-template-columns: 1.5fr 2fr 1.2fr;
    gap: 50px;
}
.social-links a:hover {
    background: var(--accent-vibrant) !important;
    border-color: var(--accent-vibrant) !important;
    transform: translateY(-3px);
}

@media (max-width: 991px) {
    .footer-grid {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 600px) {
    .footer-main { padding: 40px 15px 110px; }
    .footer-grid {
        grid-template-columns: 1fr;
        gap: 30px;
        text-align: center;
    }
    .footer-section {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .footer-contact-item { width: 100%; justify-content: center; }
    .social-links { justify-content: center; }
}
</style>
