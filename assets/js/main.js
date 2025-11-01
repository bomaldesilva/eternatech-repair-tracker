/**
 * Minimal JavaScript utilities for RepairShop
 * No AJAX - only UI enhancements
 */

// Mobile Menu Functions
function toggleMobileMenu() {
    const menuBtn = document.querySelector('.mobile-menu-btn');
    const overlay = document.querySelector('.mobile-nav-overlay');
    const mobileNav = document.querySelector('.mobile-nav');
    
    menuBtn.classList.toggle('active');
    overlay.classList.toggle('active');
    mobileNav.classList.toggle('active');
    
    // Prevent body scroll when menu is open
    if (mobileNav.classList.contains('active')) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
}

function closeMobileMenu() {
    const menuBtn = document.querySelector('.mobile-menu-btn');
    const overlay = document.querySelector('.mobile-nav-overlay');
    const mobileNav = document.querySelector('.mobile-nav');
    
    menuBtn.classList.remove('active');
    overlay.classList.remove('active');
    mobileNav.classList.remove('active');
    document.body.style.overflow = '';
}

// Close mobile menu when clicking on a link
document.addEventListener('DOMContentLoaded', function() {
    const mobileNavLinks = document.querySelectorAll('.mobile-nav-links a');
    mobileNavLinks.forEach(link => {
        link.addEventListener('click', closeMobileMenu);
    });
    
    // Close menu on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeMobileMenu();
        }
    });
});

// Set current year in footer
document.addEventListener('DOMContentLoaded', function() {
    // Set current year
    const yearElements = document.querySelectorAll('#year');
    yearElements.forEach(el => el.textContent = new Date().getFullYear());
    
    // Auto-hide success/error messages after 5 seconds
    const messages = document.querySelectorAll('.msg.success, .msg.error');
    messages.forEach(msg => {
        setTimeout(() => {
            msg.style.opacity = '0';
            setTimeout(() => {
                if (msg.parentNode) {
                    msg.remove();
                }
            }, 300);
        }, 5000);
    });
    
    // Add loading states to form buttons
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                const originalText = submitBtn.textContent;
                submitBtn.dataset.originalText = originalText;
                submitBtn.textContent = 'Loading...';
                submitBtn.disabled = true;
                
                // Re-enable after 10 seconds as fallback
                setTimeout(() => {
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                }, 10000);
            }
        });
    });
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
        button.dataset.originalText = button.textContent;
        button.textContent = 'Loading...';
    } else {
        button.disabled = false;
        button.textContent = button.dataset.originalText || 'Submit';
    }
}

// Simple modal functions
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
    }
}

function hideModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
});

// Escape key to close modals
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const openModals = document.querySelectorAll('.modal[style*="block"]');
        openModals.forEach(modal => {
            modal.style.display = 'none';
        });
    }
});

// Simple confirmation dialogs
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Auto-format phone numbers (basic)
function formatPhone(input) {
    let value = input.value.replace(/\D/g, '');
    if (value.length >= 6) {
        value = value.slice(0, 3) + '-' + value.slice(3, 6) + '-' + value.slice(6, 10);
    } else if (value.length >= 3) {
        value = value.slice(0, 3) + '-' + value.slice(3);
    }
    input.value = value;
}

// Add event listeners for phone formatting
document.addEventListener('DOMContentLoaded', function() {
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', () => formatPhone(input));
    });
});

// Simple search functionality for tables/lists
function filterTable(input, tableId) {
    const filter = input.value.toLowerCase();
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr, .data-card');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
}

// Copy to clipboard functionality
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showNotification('Copied to clipboard!', 'success');
        });
    } else {
        // Fallback for older browsers
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        showNotification('Copied to clipboard!', 'success');
    }
}

// Simple notification system
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `msg ${type}`;
    notification.textContent = message;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.maxWidth = '300px';
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    }, 3000);
}

// Print functionality
function printPage() {
    window.print();
}

// Simple form reset with confirmation
function resetForm(formId) {
    if (confirm('Are you sure you want to reset the form? All entered data will be lost.')) {
        const form = document.getElementById(formId);
        if (form) {
            form.reset();
            // Clear any validation styling
            const fields = form.querySelectorAll('input, select, textarea');
            fields.forEach(field => {
                field.style.borderColor = '';
            });
        }
    }
}
