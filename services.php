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
    <title>Our Services - EternaTech Repairs</title>
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container">
        <div class="content-wrapper">
            <h1>Our Services</h1>
            
            <div class="services-grid">
                <div class="service-card">
                    <h2>Computer Repairs</h2>
                    <p>Reliable fixes for desktops and PCs — from hardware failures to software issues, we keep your system running smoothly.</p>
                </div>
                
                <div class="service-card">
                    <h2>Laptop Repairs</h2>
                    <p>From broken screens to overheating and power issues, our laptop repair service gets you back to work in no time.</p>
                </div>
                
                <div class="service-card">
                    <h2>Mobile Repairs</h2>
                    <p>Fast and affordable smartphone repairs, including cracked screens, battery replacements, and performance issues.</p>
                </div>
                
                <div class="service-card">
                    <h2>Tablet Repairs</h2>
                    <p>Expert solutions for tablets of all brands — screen repairs, charging problems, and system diagnostics done right.</p>
                </div>
            </div>
            
            <section class="detailed-services">
                <h2>Detailed Service Categories</h2>
                
                <div class="service-category">
                    <h3>Hardware Repairs</h3>
                    <ul>
                        <li>Motherboard diagnosis and repair</li>
                        <li>RAM and storage upgrades</li>
                        <li>Power supply replacement</li>
                        <li>Fan and cooling system repairs</li>
                        <li>Port and connector repairs</li>
                        <li>Component replacement and installation</li>
                    </ul>
                </div>
                
                <div class="service-category">
                    <h3>Screen and Display Services</h3>
                    <ul>
                        <li>LCD and LED screen replacement</li>
                        <li>Touch screen repairs</li>
                        <li>Display calibration and testing</li>
                        <li>Backlight repairs</li>
                        <li>Digitizer replacement</li>
                    </ul>
                </div>
                
                <div class="service-category">
                    <h3>Battery and Power Solutions</h3>
                    <ul>
                        <li>Battery replacement and testing</li>
                        <li>Charging port repairs</li>
                        <li>Power adapter diagnosis</li>
                        <li>Battery optimization</li>
                        <li>Power management troubleshooting</li>
                    </ul>
                </div>
                
                <div class="service-category">
                    <h3>Software and System Services</h3>
                    <ul>
                        <li>Operating system installation and repair</li>
                        <li>Virus and malware removal</li>
                        <li>Data backup and recovery</li>
                        <li>System optimization</li>
                        <li>Driver installation and updates</li>
                        <li>Software troubleshooting</li>
                    </ul>
                </div>
                
                <div class="service-category">
                    <h3>Diagnostic Services</h3>
                    <ul>
                        <li>Comprehensive system diagnostics</li>
                        <li>Performance testing and analysis</li>
                        <li>Hardware compatibility checks</li>
                        <li>Problem identification and assessment</li>
                        <li>Repair cost estimation</li>
                        <li>Pre-purchase device inspection</li>
                    </ul>
                </div>
                
                <div class="service-category">
                    <h3>Warranty and Quality Assurance</h3>
                    <ul>
                        <li>30-day warranty on all repairs</li>
                        <li>Quality tested parts and components</li>
                        <li>Professional workmanship guarantee</li>
                        <li>Follow-up support and assistance</li>
                        <li>Transparent pricing with no hidden fees</li>
                    </ul>
                </div>
                
                <div class="service-category">
                    <h3>Emergency and Express Services</h3>
                    <ul>
                        <li>Same-day repair for urgent cases</li>
                        <li>Express service for business clients</li>
                        <li>On-site repair for larger systems</li>
                        <li>Remote diagnostic support</li>
                        <li>Priority scheduling for critical repairs</li>
                    </ul>
                </div>
            </section>
            
            <div class="cta-section">
                <h2>Ready to Get Your Device Fixed?</h2>
                <p>Contact us today to discuss your repair needs and get a free diagnostic assessment!</p>
                <a href="contact_us.php" class="btn btn-primary">Contact Us</a>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>
