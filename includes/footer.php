    <footer class="container footer">
        <div class="footer-content">
            <div class="footer-brand">
                <img src="<?php echo $base_path; ?>assets/images/logo.svg" alt="ETERNATECH REPAIRS Logo" class="footer-logo">
                <div class="footer-text">
                    <span class="footer-name">ETERNATECH</span>
                    <span class="footer-subtitle">REPAIRS</span>
                </div>
            </div>
            <div class="footer-links">
                <a href="/newproject/about_us.php">About Us</a>
                <a href="/newproject/services.php">Services</a>
                <a href="/newproject/contact_us.php">Contact Us</a>
                <a href="/newproject/privacy_policy.php">Privacy Policy</a>
            </div>
            <div class="footer-copyright">
                © <?php echo date('Y'); ?> ETERNATECH REPAIRS. All rights reserved.
            </div>
        </div>
    </footer>

    
    <script>
        // Set current year
        document.addEventListener('DOMContentLoaded', function() {
            const yearElements = document.querySelectorAll('#year');
            yearElements.forEach(el => el.textContent = new Date().getFullYear());
        });

        // Form validation helper
        function validateForm(form) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#f44336';
                    isValid = false;
                } else {
                    field.style.borderColor = '';
                }
            });
            
            return isValid;
        }

        // Show/hide loading state
        function setLoading(button, loading = true) {
            if (loading) {
                button.disabled = true;
                button.textContent = 'Loading...';
            } else {
                button.disabled = false;
                button.textContent = button.dataset.originalText || 'Submit';
            }
        }

        // Auto-hide messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const messages = document.querySelectorAll('.msg.success, .msg.error');
            messages.forEach(msg => {
                setTimeout(() => {
                    msg.style.opacity = '0';
                    setTimeout(() => msg.remove(), 300);
                }, 5000);
            });
        });
    </script>
</body>
</html>
