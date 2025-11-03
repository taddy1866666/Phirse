// Main JavaScript for Student Site
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    // initScrollAnimations(); // Removed fade animations
    initProductSlider();
    // initFadeInEffects(); // Removed fade effects
    initSmoothScrolling();
    initImageLazyLoading();
    initContactForms();

    console.log('Student site initialized successfully');
});

// Initialize scroll animations
function initScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                
                // Add staggered animation for multiple elements
                if (entry.target.classList.contains('stagger')) {
                    const children = entry.target.children;
                    Array.from(children).forEach((child, index) => {
                        setTimeout(() => {
                            child.classList.add('visible');
                        }, index * 100);
                    });
                }
            }
        });
    }, observerOptions);
    
    // Observe fade-in elements
    document.querySelectorAll('.fade-in').forEach(el => {
        observer.observe(el);
    });
}

// Product slider functionality
function initProductSlider() {
    const slider = document.getElementById('productSlider');
    if (!slider) return;
    
    let isDown = false;
    let startX;
    let scrollLeft;
    let animationFrame;
    
    // Mouse events for desktop
    slider.addEventListener('mousedown', (e) => {
        isDown = true;
        slider.classList.add('grabbing');
        startX = e.pageX - slider.offsetLeft;
        scrollLeft = slider.scrollLeft;
        cancelAnimationFrame(animationFrame);
    });
    
    slider.addEventListener('mouseleave', () => {
        isDown = false;
        slider.classList.remove('grabbing');
    });
    
    slider.addEventListener('mouseup', () => {
        isDown = false;
        slider.classList.remove('grabbing');
    });
    
    slider.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - slider.offsetLeft;
        const walk = (x - startX) * 2;
        slider.scrollLeft = scrollLeft - walk;
    });
    
    // Touch events for mobile
    let touchStartX = 0;
    let touchStartScrollLeft = 0;
    
    slider.addEventListener('touchstart', (e) => {
        touchStartX = e.touches[0].clientX;
        touchStartScrollLeft = slider.scrollLeft;
    }, { passive: true });
    
    slider.addEventListener('touchmove', (e) => {
        const touchX = e.touches[0].clientX;
        const diff = touchStartX - touchX;
        slider.scrollLeft = touchStartScrollLeft + diff;
    }, { passive: true });
    
    // Auto-scroll functionality (optional)
    let autoScrollInterval;
    
    function startAutoScroll() {
        autoScrollInterval = setInterval(() => {
            if (slider.scrollLeft >= slider.scrollWidth - slider.clientWidth) {
                slider.scrollTo({ left: 0, behavior: 'smooth' });
            } else {
                slider.scrollBy({ left: 300, behavior: 'smooth' });
            }
        }, 5000);
    }
    
    function stopAutoScroll() {
        clearInterval(autoScrollInterval);
    }
    
    // Start auto-scroll and pause on hover/touch
    startAutoScroll();
    
    slider.addEventListener('mouseenter', stopAutoScroll);
    slider.addEventListener('mouseleave', startAutoScroll);
    slider.addEventListener('touchstart', stopAutoScroll);
}

// Slider navigation functions (called from HTML)
function slideNext() {
    const slider = document.getElementById('productSlider');
    if (slider) {
        const cardWidth = slider.querySelector('.product-card')?.offsetWidth || 280;
        const gap = 32; // 2rem gap
        slider.scrollBy({ 
            left: cardWidth + gap, 
            behavior: 'smooth' 
        });
    }
}

function slidePrev() {
    const slider = document.getElementById('productSlider');
    if (slider) {
        const cardWidth = slider.querySelector('.product-card')?.offsetWidth || 280;
        const gap = 32; // 2rem gap
        slider.scrollBy({ 
            left: -(cardWidth + gap), 
            behavior: 'smooth' 
        });
    }
}

// Initialize fade-in effects
function initFadeInEffects() {
    // Add fade-in class to elements that should animate
    const elementsToAnimate = [
        '.product-card',
        '.banner-container',
        '.footer'
    ];
    
    elementsToAnimate.forEach(selector => {
        document.querySelectorAll(selector).forEach(el => {
            if (!el.classList.contains('fade-in')) {
                el.classList.add('fade-in');
            }
        });
    });
}

// Initialize smooth scrolling
function initSmoothScrolling() {
    // Add smooth scrolling to all anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            
            // Skip if href is just '#' or empty
            if (!href || href === '#' || href === '#!' || href.length <= 1) {
                return;
            }
            
            e.preventDefault();
            
            try {
                const target = document.querySelector(href);
                if (target) {
                    const headerOffset = 80; // Account for fixed header
                    const elementPosition = target.getBoundingClientRect().top;
                    const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                    
                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });
                }
            } catch (e) {
                // Invalid selector, skip
                console.warn('Invalid selector:', href);
            }
        });
    });
}

// Initialize image lazy loading
function initImageLazyLoading() {
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        img.classList.add('loaded');
                        observer.unobserve(img);
                    }
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            img.classList.add('lazy');
            imageObserver.observe(img);
        });
    }
}

// Initialize contact forms
function initContactForms() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(form)) {
                e.preventDefault();
            }
        });
        
        // Real-time validation
        const inputs = form.querySelectorAll('input, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', () => validateField(input));
            input.addEventListener('input', () => clearFieldError(input));
        });
    });
}

// Form validation
function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], textarea[required]');
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    let message = '';
    
    // Required field validation
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        message = 'This field is required';
    }
    
    // Email validation
    if (field.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
            message = 'Please enter a valid email address';
        }
    }
    
    // Phone validation
    if (field.type === 'tel' && value) {
        const phoneRegex = /^[+]?[\d\s\-\(\)]{10,}$/;
        if (!phoneRegex.test(value)) {
            isValid = false;
            message = 'Please enter a valid phone number';
        }
    }
    
    // Show/hide error
    if (!isValid) {
        showFieldError(field, message);
    } else {
        clearFieldError(field);
    }
    
    return isValid;
}

function showFieldError(field, message) {
    clearFieldError(field); // Clear existing error
    
    field.classList.add('error');
    const errorElement = document.createElement('div');
    errorElement.className = 'field-error';
    errorElement.textContent = message;
    errorElement.style.cssText = `
        color: #ef4444;
        font-size: 0.8rem;
        margin-top: 0.25rem;
        animation: slideDown 0.3s ease;
    `;
    
    field.parentNode.appendChild(errorElement);
}

function clearFieldError(field) {
    field.classList.remove('error');
    const errorElement = field.parentNode.querySelector('.field-error');
    if (errorElement) {
        errorElement.remove();
    }
}

// Utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Loading animation
function showLoading() {
    const loading = document.createElement('div');
    loading.className = 'loading-overlay';
    loading.innerHTML = `
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p>Loading...</p>
        </div>
    `;
    
    loading.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        backdrop-filter: blur(5px);
    `;
    
    const spinner = loading.querySelector('.spinner');
    spinner.style.cssText = `
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #667eea;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-bottom: 1rem;
    `;
    
    // Add spin animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    `;
    document.head.appendChild(style);
    
    document.body.appendChild(loading);
    return loading;
}

function hideLoading() {
    const loading = document.querySelector('.loading-overlay');
    if (loading) {
        loading.style.opacity = '0';
        setTimeout(() => {
            if (loading.parentNode) {
                loading.parentNode.removeChild(loading);
            }
        }, 300);
    }
}

// Product interaction functions
function addToWishlist(productId) {
    // Get existing wishlist from localStorage
    let wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
    
    if (!wishlist.includes(productId)) {
        wishlist.push(productId);
        localStorage.setItem('wishlist', JSON.stringify(wishlist));
        
        // Update UI
        updateWishlistIcon(productId, true);
        showNotification('Added to wishlist!', 'success');
    } else {
        showNotification('Item already in wishlist', 'info');
    }
}

function removeFromWishlist(productId) {
    let wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
    wishlist = wishlist.filter(id => id !== productId);
    localStorage.setItem('wishlist', JSON.stringify(wishlist));
    
    updateWishlistIcon(productId, false);
    showNotification('Removed from wishlist', 'success');
}

function updateWishlistIcon(productId, isInWishlist) {
    const heartIcon = document.querySelector(`[data-product-id="${productId}"] .heart-icon`);
    if (heartIcon) {
        heartIcon.classList.toggle('active', isInWishlist);
        heartIcon.style.color = isInWishlist ? '#ef4444' : '#ccc';
    }
}

// Quick view modal
function showQuickView(productId) {
    // This would typically fetch product details via AJAX
    showNotification('Quick view feature coming soon!', 'info');
}

// Share functionality
function shareProduct(productId, productName) {
    if (navigator.share) {
        navigator.share({
            title: productName,
            text: `Check out this product: ${productName}`,
            url: `${window.location.origin}/product-details.php?id=${productId}`
        }).catch(err => console.log('Error sharing:', err));
    } else {
        // Fallback: copy link to clipboard
        const link = `${window.location.origin}/product-details.php?id=${productId}`;
        navigator.clipboard.writeText(link).then(() => {
            showNotification('Product link copied to clipboard!', 'success');
        }).catch(() => {
            showNotification('Unable to copy link', 'error');
        });
    }
}

// Notification system
function showNotification(message, type = 'info', duration = 3000) {
    // Use the navigation bar notification system if available
    if (window.NavBar && window.NavBar.showNavNotification) {
        window.NavBar.showNavNotification(message, type, duration);
        return;
    }
    
    // Fallback notification system
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    
    const colors = {
        success: '#10b981',
        error: '#ef4444',
        info: '#3b82f6',
        warning: '#f59e0b'
    };
    
    notification.style.cssText = `
        position: fixed;
        top: 90px;
        right: 20px;
        background: ${colors[type] || colors.info};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        z-index: 10001;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        font-size: 0.9rem;
        font-weight: 500;
        max-width: 300px;
    `;
    
    notification.textContent = message;
    document.body.appendChild(notification);
    
    // Show notification
    requestAnimationFrame(() => {
        notification.style.transform = 'translateX(0)';
    });
    
    // Hide after duration
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, duration);
}

// Error handling
window.addEventListener('error', function(e) {
    console.error('JavaScript error:', e.error);
    // Optionally show user-friendly error message
    // showNotification('Something went wrong. Please refresh the page.', 'error');
});

// Performance monitoring (optional)
window.addEventListener('load', function() {
    if (window.performance) {
        const loadTime = window.performance.timing.loadEventEnd - window.performance.timing.navigationStart;
        console.log(`Page loaded in ${loadTime}ms`);
    }
});

// Export functions for global use
window.StudentSite = {
    slideNext,
    slidePrev,
    addToWishlist,
    removeFromWishlist,
    showQuickView,
    shareProduct,
    showNotification,
    showLoading,
    hideLoading
};