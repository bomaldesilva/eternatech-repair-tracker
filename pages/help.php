<?php

require_once '../includes/session.php';
require_once '../config/database.php';

$page_title = 'Help & Support - ETERNATECH REPAIRS';

include '../includes/header.php';
?>

<style>
.help-hero {
    background: linear-gradient(135deg, var(--accent) 0%, #1976d2 100%);
    color: white;
    padding: 60px 0;
    margin: -16px -16px 40px -16px;
    text-align: center;
}

.help-hero h1 {
    margin: 0 0 10px 0;
    font-size: 36px;
    font-weight: 700;
}

.help-hero p {
    margin: 0 0 30px 0;
    font-size: 18px;
    opacity: 0.9;
}

.help-search {
    max-width: 500px;
    margin: 0 auto;
    position: relative;
}

.help-search input {
    width: 100%;
    padding: 15px 50px 15px 20px;
    border: none;
    border-radius: 25px;
    font-size: 16px;
    background: rgba(255,255,255,0.1);
    color: white;
    backdrop-filter: blur(10px);
}

.help-search input::placeholder {
    color: rgba(255,255,255,0.8);
}

.help-search-icon {
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 20px;
    color: rgba(255,255,255,0.8);
}

.help-categories {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    margin-bottom: 50px;
}

.help-category {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 30px;
    transition: all 0.3s ease;
}

.help-category:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
    border-color: var(--accent);
}

.help-category-icon {
    font-size: 48px;
    margin-bottom: 20px;
    display: block;
}

.help-category h3 {
    color: var(--primary);
    margin: 0 0 15px 0;
    font-size: 24px;
}

.help-category p {
    color: var(--text-secondary);
    margin: 0 0 20px 0;
    line-height: 1.6;
}

.help-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.help-links li {
    margin: 0 0 10px 0;
}

.help-links a {
    color: var(--accent);
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
}

.help-links a:hover {
    color: var(--primary);
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 25px;
    margin-bottom: 50px;
}

.quick-action {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 25px;
    text-align: center;
    transition: all 0.3s ease;
}

.quick-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    border-color: var(--accent);
}

.quick-action-icon {
    font-size: 40px;
    margin-bottom: 15px;
    color: var(--accent);
}

.quick-action h4 {
    color: var(--primary);
    margin: 0 0 10px 0;
    font-size: 18px;
}

.quick-action p {
    color: var(--text-secondary);
    margin: 0 0 20px 0;
    font-size: 14px;
    line-height: 1.5;
}

.contact-section {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 40px;
    text-align: center;
    margin-bottom: 30px;
}

.contact-section h3 {
    color: var(--primary);
    margin: 0 0 15px 0;
    font-size: 28px;
}

.contact-section > p {
    color: var(--text-secondary);
    margin: 0 0 30px 0;
    font-size: 16px;
    line-height: 1.6;
}

.contact-methods {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 25px;
    margin-top: 30px;
}

.contact-method {
    padding: 20px;
    border-radius: 8px;
    background: var(--background);
    border: 1px solid var(--border);
    transition: all 0.3s ease;
}

.contact-method:hover {
    border-color: var(--accent);
    transform: translateY(-1px);
}

.contact-method-icon {
    font-size: 32px;
    color: var(--accent);
    margin-bottom: 15px;
    display: block;
}

.contact-method h4 {
    color: var(--primary);
    margin: 0 0 10px 0;
    font-size: 16px;
}

.contact-method p {
    color: var(--text-secondary);
    margin: 0;
    font-size: 14px;
    line-height: 1.4;
}

.faq-section {
    margin-top: 50px;
}

.faq-section h3 {
    color: var(--primary);
    margin: 0 0 30px 0;
    font-size: 28px;
    text-align: center;
}

.faq-item {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 8px;
    margin-bottom: 15px;
    overflow: hidden;
}

.faq-question {
    padding: 20px;
    cursor: pointer;
    background: var(--background);
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background-color 0.3s ease;
}

.faq-question:hover {
    background: var(--card);
}

.faq-question h4 {
    color: var(--primary);
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.faq-answer {
    padding: 20px;
    color: var(--text-secondary);
    line-height: 1.6;
}

@media (max-width: 768px) {
    .help-hero h1 {
        font-size: 28px;
    }
    
    .help-categories {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .quick-actions {
        grid-template-columns: 1fr;
        gap: 15px;
    }
}
</style>

<main class="container">
    <div class="help-hero">
        <h1>How can we help you?</h1>
        <p>Get answers to your questions and learn how to use our repair services</p>
        <div class="help-search">
            <input type="text" placeholder="Search for help topics..." id="helpSearch">
            <span class="help-search-icon">🔍</span>
        </div>
    </div>

    <div class="quick-actions">
        <div class="quick-action">
            <div class="quick-action-icon">📱</div>
            <h4>Submit New Request</h4>
            <p>Start a new repair request for your device</p>
            <a href="submit_request.php" class="btn btn-small">Submit Request</a>
        </div>
        
        <div class="quick-action">
            <div class="quick-action-icon">🔍</div>
            <h4>Track Your Repair</h4>
            <p>Check the status of your current repairs</p>
            <a href="../track_status.php" class="btn btn-small">Track Status</a>
        </div>
        
        <div class="quick-action">
            <div class="quick-action-icon">📊</div>
            <h4>View Dashboard</h4>
            <p>Access your customer dashboard</p>
            <a href="customer_dashboard.php" class="btn btn-small">Go to Dashboard</a>
        </div>
    </div>

    <div class="help-categories">
        <div class="help-category">
            <span class="help-category-icon">🚀</span>
            <h3>Getting Started</h3>
            <p>Learn the basics of using our repair service and submitting your first request.</p>
            <ul class="help-links">
                <li><a href="#account-setup">→ Setting up your account</a></li>
                <li><a href="#first-request">→ Submitting your first repair request</a></li>
                <li><a href="#device-types">→ Supported device types</a></li>
                <li><a href="#preparation">→ Preparing your device for repair</a></li>
            </ul>
        </div>

        <div class="help-category">
            <span class="help-category-icon">📋</span>
            <h3>Managing Requests</h3>
            <p>Track and manage your repair requests through our customer portal.</p>
            <ul class="help-links">
                <li><a href="#tracking">→ How to track repair status</a></li>
                <li><a href="#editing">→ Editing pending requests</a></li>
                <li><a href="#communication">→ Communicating with technicians</a></li>
                <li><a href="#updates">→ Understanding status updates</a></li>
            </ul>
        </div>

        <div class="help-category">
            <span class="help-category-icon">💰</span>
            <h3>Pricing & Payment</h3>
            <p>Understand our pricing structure and payment options.</p>
            <ul class="help-links">
                <li><a href="#estimates">→ Getting repair estimates</a></li>
                <li><a href="#pricing">→ Understanding our pricing</a></li>
                <li><a href="#payment">→ Payment methods accepted</a></li>
                <li><a href="#warranty">→ Warranty information</a></li>
            </ul>
        </div>
    </div>

    <div class="contact-section">
        <h3>Still need help?</h3>
        <p>Our customer support team is here to assist you with any questions or concerns.</p>
        
        <div class="contact-methods">
            <div class="contact-method">
                <span class="contact-method-icon">☎</span>
                <h4>Phone Support</h4>
                <p>(555) 123-REPAIR<br>Mon-Fri: 9AM-6PM<br>Sat: 10AM-4PM</p>
            </div>
            
            <div class="contact-method">
                <span class="contact-method-icon">✉️</span>
                <h4>Email Support</h4>
                <p>support@eternatech.com<br>Response within 24 hours<br>7 days a week</p>
            </div>
            
            <div class="contact-method">
                <span class="contact-method-icon">💬</span>
                <h4>Live Chat</h4>
                <p>Available on website<br>Mon-Fri: 9AM-8PM<br>Instant responses</p>
            </div>
            
            <div class="contact-method">
                <span class="contact-method-icon">📍</span>
                <h4>Visit Our Store</h4>
                <p>123 Tech Street<br>Digital City, DC 12345<br>Mon-Sat: 9AM-7PM</p>
            </div>
        </div>
    </div>

    <div class="faq-section">
        <h3>Frequently Asked Questions</h3>
        
        <div class="faq-item">
            <div class="faq-question">
                <h4>How long does a typical repair take?</h4>
                <span>+</span>
            </div>
            <div class="faq-answer">
                Repair times vary depending on the device and issue complexity. Simple repairs like screen replacements typically take 2-3 business days, while more complex motherboard repairs may take 5-7 business days. You'll receive regular updates on your repair status.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                <h4>Do you offer warranties on repairs?</h4>
                <span>+</span>
            </div>
            <div class="faq-answer">
                Yes! We provide a 90-day warranty on all parts and labor for our repairs. If you experience any issues related to our repair work within this period, we'll fix it at no additional charge.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                <h4>What payment methods do you accept?</h4>
                <span>+</span>
            </div>
            <div class="faq-answer">
                We accept all major credit cards (Visa, MasterCard, American Express), debit cards, PayPal, and cash payments. Payment is due upon completion of the repair service.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                <h4>Can I track my repair status online?</h4>
                <span>+</span>
            </div>
            <div class="faq-answer">
                Absolutely! Once you submit a repair request, you'll receive a unique tracking number. You can use this number on our website to check real-time updates on your repair progress, from initial diagnosis to completion.
            </div>
        </div>
    </div>
</main>

<script>
document.getElementById('helpSearch').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const categories = document.querySelectorAll('.help-category');
    
    categories.forEach(category => {
        const text = category.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            category.style.display = 'block';
        } else {
            category.style.display = searchTerm ? 'none' : 'block';
        }
    });
});

document.querySelectorAll('.faq-question').forEach(question => {
    question.addEventListener('click', function() {
        const answer = this.nextElementSibling;
        const icon = this.querySelector('span');
        
        if (answer.style.display === 'none' || !answer.style.display) {
            answer.style.display = 'block';
            icon.textContent = '−';
        } else {
            answer.style.display = 'none';
            icon.textContent = '+';
        }
    });
});

document.querySelectorAll('.faq-answer').forEach(answer => {
    answer.style.display = 'none';
});
</script>

<?php include '../includes/footer.php'; ?>
