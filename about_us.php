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
    <title>About Us - EternaTech Repairs</title>
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container">
        <div class="content-wrapper">
            <h1>About Us</h1>
            
            <section class="about-intro">
                <p>At EternaTech Repairs, we bring your computers, phones, tablets, and other electronic devices back to life. From cracked screens and faulty motherboards to power issues and more, our skilled technicians provide fast, reliable, and affordable repairs.</p>
                
                <p>With years of experience and the latest diagnostic tools, we deliver precise, professional care for both personal gadgets and business hardware. Your tech deserves the best — and that's exactly what we deliver.</p>
            </section>
            
            <section class="services-overview">
                <h2>Our Services</h2>
                <div class="services-list">
                    <ul>
                        <li>Computer and laptop repairs</li>
                        <li>Smartphone and tablet repairs</li>
                        <li>Hardware diagnostics and troubleshooting</li>
                        <li>Screen replacements and repairs</li>
                        <li>Motherboard and component repairs</li>
                        <li>Power supply and battery issues</li>
                        <li>Data recovery services</li>
                        <li>Preventive maintenance</li>
                    </ul>
                </div>
            </section>
            
            <section class="why-choose-us">
                <h2>Why Choose EternaTech Repairs</h2>
                <div class="features-grid">
                    <div class="feature">
                        <h3>Experienced Technicians</h3>
                        <p>Certified professionals with years of experience</p>
                    </div>
                    
                    <div class="feature">
                        <h3>Fast Turnaround</h3>
                        <p>Quick repair times to get you back up and running</p>
                    </div>
                    
                    <div class="feature">
                        <h3>Competitive Pricing</h3>
                        <p>Transparent and affordable pricing for all services</p>
                    </div>
                    
                    <div class="feature">
                        <h3>Quality Parts</h3>
                        <p>Only genuine and high-quality replacement components</p>
                    </div>
                    
                    <div class="feature">
                        <h3>Comprehensive Warranty</h3>
                        <p>Full warranty coverage on all repairs and services</p>
                    </div>
                    
                    <div class="feature">
                        <h3>Professional Service</h3>
                        <p>Excellent customer service and support</p>
                    </div>
                    
                    <div class="feature">
                        <h3>Advanced Equipment</h3>
                        <p>State-of-the-art diagnostic and repair tools</p>
                    </div>
                    
                    <div class="feature">
                        <h3>Trusted by Many</h3>
                        <p>Reliable service for individuals and businesses</p>
                    </div>
                </div>
            </section>
            
            <section class="contact-info">
                <h2>Contact Information</h2>
                <div class="contact-details">
                    <p><strong>EternaTech Repairs</strong></p>
                    <p>Phone: +94 11 222 3344</p>
                    <p>Email: info@eternatech.com</p>
                    <p>Address: 123, Tech Street, Colombo, Sri Lanka</p>
                </div>
            </section>
            
            <section class="business-hours">
                <h2>Business Hours</h2>
                <div class="hours-table">
                    <p><strong>Monday - Friday:</strong> 9:00 AM - 6:00 PM</p>
                    <p><strong>Saturday:</strong> 9:00 AM - 4:00 PM</p>
                    <p><strong>Sunday:</strong> Closed</p>
                </div>
            </section>
            
            <section class="mission">
                <h2>Our Mission</h2>
                <p>To provide exceptional electronic device repair services that restore functionality, extend device lifespan, and deliver outstanding value to our customers through expert craftsmanship and reliable service.</p>
            </section>
            
            <div class="cta-section">
                <h2>Need a Repair?</h2>
                <p>Get in touch with us today for professional device repair services!</p>
                <a href="contact_us.php" class="btn btn-primary">Contact Us</a>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>
