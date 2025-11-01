<?php
session_start();
require_once 'config/database.php';
require_once 'includes/session.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - EternaTech Repairs</title>
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container">
        <div class="content-wrapper">
            <h1>Contact Us</h1>
            <p class="page-description">Get in touch with EternaTech Repairs — we're here to help with all your device repair needs.</p>
            
            <div class="contact-content">
                <section class="contact-info">
                    <h2>Get in Touch</h2>
                    <div class="contact-details">
                        <div class="contact-item">
                            <h3>Phone</h3>
                            <p><a href="tel:+94112223344">+94 11 222 3344</a></p>
                        </div>
                        
                        <div class="contact-item">
                            <h3>Email</h3>
                            <p><a href="mailto:info@eternatech.com">info@eternatech.com</a></p>
                        </div>
                        
                        <div class="contact-item">
                            <h3>Address</h3>
                            <p>123, Tech Street<br>Colombo, Sri Lanka</p>
                        </div>
                    </div>
                </section>
                
                <section class="business-hours">
                    <h2>Business Hours</h2>
                    <div class="hours-list">
                        <div class="hours-item">
                            <strong>Monday - Friday:</strong> 9:00 AM - 6:00 PM
                        </div>
                        <div class="hours-item">
                            <strong>Saturday:</strong> 9:00 AM - 4:00 PM
                        </div>
                        <div class="hours-item">
                            <strong>Sunday:</strong> Closed
                        </div>
                    </div>
                </section>
                
                <section class="contact-form-section">
                    <h2>Send Us a Message</h2>
                    <form class="contact-form" method="POST" action="">
                        <div class="form-group">
                            <label for="name">Your Name *</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone">
                        </div>
                        
                        <div class="form-group">
                            <label for="device_type">Device Type</label>
                            <select id="device_type" name="device_type">
                                <option value="">Select device type</option>
                                <option value="computer">Computer/Desktop</option>
                                <option value="laptop">Laptop</option>
                                <option value="smartphone">Smartphone</option>
                                <option value="tablet">Tablet</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject">Subject *</label>
                            <input type="text" id="subject" name="subject" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="message">Message *</label>
                            <textarea id="message" name="message" rows="5" required placeholder="Please describe your device issue or inquiry..."></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>
                    
                    <?php
                    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                        $name = htmlspecialchars($_POST['name'] ?? '');
                        $email = htmlspecialchars($_POST['email'] ?? '');
                        $phone = htmlspecialchars($_POST['phone'] ?? '');
                        $device_type = htmlspecialchars($_POST['device_type'] ?? '');
                        $subject = htmlspecialchars($_POST['subject'] ?? '');
                        $message = htmlspecialchars($_POST['message'] ?? '');
                        
                        if (!empty($name) && !empty($email) && !empty($subject) && !empty($message)) {
                            try {
                                // Save message to database
                                $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, phone, device_type, subject, message) VALUES (?, ?, ?, ?, ?, ?)");
                                $stmt->execute([$name, $email, $phone, $device_type, $subject, $message]);
                                
                                echo '<div class="alert alert-success">Thank you for your message! We will get back to you within 24 hours. Reference ID: #' . $pdo->lastInsertId() . '</div>';
                            } catch (PDOException $e) {
                                echo '<div class="alert alert-error">Sorry, there was an error sending your message. Please try again or call us directly.</div>';
                            }
                        } else {
                            echo '<div class="alert alert-error">Please fill in all required fields.</div>';
                        }
                    }
                    ?>
                </section>
                
                <section class="emergency-contact">
                    <h2>Emergency Repairs</h2>
                    <p>For urgent repair needs outside business hours, please call our emergency line:</p>
                    <p class="emergency-phone"><strong>Emergency: +94 11 222 3344</strong></p>
                    <p><em>Additional charges may apply for emergency and after-hours services.</em></p>
                </section>
                
                <section class="quick-links">
                    <h2>Quick Links</h2>
                    <div class="links-grid">
                        <a href="services.php" class="link-card">
                            <h3>Our Services</h3>
                            <p>View all repair services we offer</p>
                        </a>
                        
                        <a href="about_us.php" class="link-card">
                            <h3>About Us</h3>
                            <p>Learn more about EternaTech Repairs</p>
                        </a>
                        
                        <a href="submit_request.php" class="link-card">
                            <h3>Submit Repair Request</h3>
                            <p>Start your repair process online</p>
                        </a>
                        
                        <a href="track_status.php" class="link-card">
                            <h3>Track Your Repair</h3>
                            <p>Check the status of your device</p>
                        </a>
                    </div>
                </section>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>
