// Navigation Bar Transparent JavaScript
document.addEventListener('DOMContentLoaded', function() {
    initNavigation();
    initScrollEffects();
    initMobileMenu();
    initSearch();
    initDropdowns();
    initUserMenu();
});

// Initialize navigation functionality
function initNavigation() {
    const navbar = document.querySelector('.navbar');
    
    if (!navbar) return;
    
    // Add smooth scroll behavior
    document.documentElement.style.scrollBehavior = 'smooth';
    
    // Active link highlighting is now handled in nav-bar-transparent.php
    
    // Handle window resize
    window.addEventListener('resize', handleWindowResize);
}

// Initialize scroll effects
function initScrollEffects() {
    const navbar = document.querySelector('.navbar');
    let lastScrollY = window.scrollY;
    let ticking = false;
    
    // Check if we're on index page
    const currentPage = window.location.pathname.split('/').pop() || 'index.php';
    const isIndexPage = currentPage === 'index.php' || currentPage === '' || currentPage === '/';
    
    // Add transparent class only on index page at top
    if (isIndexPage && window.scrollY <= 50) {
        navbar.classList.add('navbar-transparent');
    }

    function updateNavbar() {
        const currentScrollY = window.scrollY;
        
        // Handle transparent class for index page
        if (isIndexPage) {
            if (currentScrollY > 50) {
                navbar.classList.remove('navbar-transparent');
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.add('navbar-transparent');
                navbar.classList.remove('scrolled');
            }
        } else {
            // For other pages, just add scrolled class
            if (currentScrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        }

        // Hide/show navbar on scroll (optional)
        if (currentScrollY > lastScrollY && currentScrollY > 100) {
            // Scrolling down
            navbar.style.transform = 'translateY(-100%)';
        } else {
            // Scrolling up
            navbar.style.transform = 'translateY(0)';
        }

        lastScrollY = currentScrollY;
        ticking = false;
    }

    function requestNavbarUpdate() {
        if (!ticking) {
            requestAnimationFrame(updateNavbar);
            ticking = true;
        }
    }

    window.addEventListener('scroll', requestNavbarUpdate, { passive: true });
}

// Initialize mobile menu
function initMobileMenu() {
    const mobileToggle = document.querySelector('.mobile-toggle');
    const navMenu = document.querySelector('.nav-menu');
    const navMenuOverlay = document.querySelector('.nav-menu-overlay');
    const navMenuClose = document.querySelector('.nav-menu-close');
    const navLinks = document.querySelectorAll('.nav-link');
    
    if (!mobileToggle || !navMenu) return;

    // Mobile menu toggle
    mobileToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        toggleMobileMenu();
    });

    // Close button
    if (navMenuClose) {
        navMenuClose.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeMobileMenu();
        });
    }

    // Close menu when clicking on overlay
    if (navMenuOverlay) {
        navMenuOverlay.addEventListener('click', function(e) {
            if (e.target === navMenuOverlay) {
                closeMobileMenu();
            }
        });
    }

    // Close menu when clicking on links
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                closeMobileMenu();
            }
        });
    });

    // Handle escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && navMenu.classList.contains('active')) {
            closeMobileMenu();
        }
    });
}

function toggleMobileMenu() {
    const mobileToggle = document.querySelector('.mobile-toggle');
    const navMenu = document.querySelector('.nav-menu');
    const navMenuOverlay = document.querySelector('.nav-menu-overlay');
    
    // Toggle classes if elements exist (mobileToggle may be removed)
    if (mobileToggle) mobileToggle.classList.toggle('active');
    if (navMenu) navMenu.classList.toggle('active');
    if (navMenuOverlay) navMenuOverlay.classList.toggle('active');
    document.body.classList.toggle('menu-open');

    // Update aria attributes for accessibility when elements exist
    const isOpen = navMenu && navMenu.classList.contains('active');
    if (mobileToggle) mobileToggle.setAttribute('aria-expanded', isOpen);
    if (navMenu) navMenu.setAttribute('aria-hidden', !isOpen);
}

function closeMobileMenu() {
    const mobileToggle = document.querySelector('.mobile-toggle');
    const navMenu = document.querySelector('.nav-menu');
    const navMenuOverlay = document.querySelector('.nav-menu-overlay');
    
    if (mobileToggle) mobileToggle.classList.remove('active');
    if (navMenu) navMenu.classList.remove('active');
    if (navMenuOverlay) navMenuOverlay.classList.remove('active');
    document.body.classList.remove('menu-open');

    // Update aria attributes safely
    if (mobileToggle) mobileToggle.setAttribute('aria-expanded', false);
    if (navMenu) navMenu.setAttribute('aria-hidden', true);
}

// Initialize search functionality
function initSearch() {
    const searchInput = document.querySelector('.search-input');
    const searchIcon = document.querySelector('.search-icon');
    
    if (!searchInput) return;

    // Search on enter key
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            performSearch();
        }
    });

    // Search on icon click
    if (searchIcon) {
        searchIcon.addEventListener('click', function(e) {
            e.preventDefault();
            performSearch();
        });
    }

    // Search suggestions (optional - can be expanded)
    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            showSearchSuggestions(this.value);
        }, 300);
    });

    // Close suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.search-container')) {
            hideSearchSuggestions();
        }
    });
}

function performSearch() {
    const searchInput = document.querySelector('.search-input');
    const query = searchInput.value.trim();
    
    if (query) {
        // Hide mobile menu if open
        closeMobileMenu();
        
        // Redirect to search results
        window.location.href = `search.php?q=${encodeURIComponent(query)}`;
    } else {
        // Show all products if empty search
        window.location.href = 'products.php';
    }
}

function showSearchSuggestions(query) {
    if (!query || query.length < 2) {
        hideSearchSuggestions();
        return;
    }

    // This would typically make an AJAX call to get suggestions
    // For now, we'll show a simple dropdown
    const searchContainer = document.querySelector('.search-container');
    let suggestions = document.querySelector('.search-suggestions');
    
    if (!suggestions) {
        suggestions = document.createElement('div');
        suggestions.className = 'search-suggestions';
        suggestions.style.cssText = `
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            max-height: 200px;
            overflow-y: auto;
            margin-top: 5px;
        `;
        searchContainer.appendChild(suggestions);
    }

    // Sample suggestions (replace with actual data)
    const sampleSuggestions = [
        'T-Shirts', 'Hoodies', 'Organizations', 'Merchandise',
        'Academic Organizations', 'Sports Club', 'Cultural Events'
    ].filter(item => item.toLowerCase().includes(query.toLowerCase()));

    if (sampleSuggestions.length > 0) {
        suggestions.innerHTML = sampleSuggestions.map(suggestion => 
            `<div class="suggestion-item" onclick="selectSuggestion('${suggestion}')">${suggestion}</div>`
        ).join('');
        
        // Style suggestion items
        const items = suggestions.querySelectorAll('.suggestion-item');
        items.forEach(item => {
            item.style.cssText = `
                padding: 0.75rem 1rem;
                cursor: pointer;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
                transition: background 0.2s ease;
            `;
            item.addEventListener('mouseenter', () => {
                item.style.background = 'rgba(102, 126, 234, 0.1)';
            });
            item.addEventListener('mouseleave', () => {
                item.style.background = 'transparent';
            });
        });
        
        suggestions.style.display = 'block';
    } else {
        hideSearchSuggestions();
    }
}

function selectSuggestion(suggestion) {
    const searchInput = document.querySelector('.search-input');
    searchInput.value = suggestion;
    hideSearchSuggestions();
    performSearch();
}

function hideSearchSuggestions() {
    const suggestions = document.querySelector('.search-suggestions');
    if (suggestions) {
        suggestions.style.display = 'none';
    }
}

// Initialize dropdown menus
function initDropdowns() {
    const dropdowns = document.querySelectorAll('.dropdown');
    
    dropdowns.forEach(dropdown => {
        const menu = dropdown.querySelector('.dropdown-menu');
        if (!menu) return;

        // Handle hover for desktop
        dropdown.addEventListener('mouseenter', () => {
            if (window.innerWidth > 768) {
                showDropdown(dropdown);
            }
        });

        dropdown.addEventListener('mouseleave', () => {
            if (window.innerWidth > 768) {
                hideDropdown(dropdown);
            }
        });

        // Handle click for mobile
        const link = dropdown.querySelector('.nav-link');
        link.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                toggleDropdown(dropdown);
            }
        });
    });
}

function showDropdown(dropdown) {
    const menu = dropdown.querySelector('.dropdown-menu');
    menu.style.opacity = '1';
    menu.style.visibility = 'visible';
    menu.style.transform = 'translateY(0)';
}

function hideDropdown(dropdown) {
    const menu = dropdown.querySelector('.dropdown-menu');
    menu.style.opacity = '0';
    menu.style.visibility = 'hidden';
    menu.style.transform = 'translateY(10px)';
}

function toggleDropdown(dropdown) {
    const menu = dropdown.querySelector('.dropdown-menu');
    const isVisible = menu.style.opacity === '1';
    
    if (isVisible) {
        hideDropdown(dropdown);
    } else {
        showDropdown(dropdown);
    }
}

// Initialize user menu
function initUserMenu() {
    const userDropdown = document.querySelector('.user-dropdown');
    if (!userDropdown) return;

    const userAvatar = userDropdown.querySelector('.user-avatar');
    const userMenu = userDropdown.querySelector('.user-menu');

    // Handle hover for desktop
    userDropdown.addEventListener('mouseenter', () => {
        if (window.innerWidth > 768) {
            showUserMenu();
        }
    });

    userDropdown.addEventListener('mouseleave', () => {
        if (window.innerWidth > 768) {
            hideUserMenu();
        }
    });

    // Handle click for mobile
    userAvatar.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleUserMenu();
    });

    // Close when clicking outside
    document.addEventListener('click', (e) => {
        if (!userDropdown.contains(e.target)) {
            hideUserMenu();
        }
    });
}

function showUserMenu() {
    const userMenu = document.querySelector('.user-menu');
    if (userMenu) {
        userMenu.style.opacity = '1';
        userMenu.style.visibility = 'visible';
        userMenu.style.transform = 'translateY(0)';
    }
}

function hideUserMenu() {
    const userMenu = document.querySelector('.user-menu');
    if (userMenu) {
        userMenu.style.opacity = '0';
        userMenu.style.visibility = 'hidden';
        userMenu.style.transform = 'translateY(10px)';
    }
}

function toggleUserMenu() {
    const userMenu = document.querySelector('.user-menu');
    if (!userMenu) return;
    
    const isVisible = userMenu.style.opacity === '1';
    if (isVisible) {
        hideUserMenu();
    } else {
        showUserMenu();
    }
}

// Update active navigation link - DISABLED (handled in nav-bar-transparent.php)
/*
function updateActiveNavLink() {
    const currentPage = window.location.pathname.split('/').pop() || 'index.php';
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        const linkPage = href ? href.split('/').pop() : '';
        
        if (linkPage === currentPage || 
            (currentPage === 'index.php' && (href === 'index.php' || href === '/')) ||
            (currentPage === '' && (href === 'index.php' || href === '/'))) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
}
*/

// Handle window resize
function handleWindowResize() {
    // Close mobile menu on resize to desktop
    if (window.innerWidth > 768) {
        closeMobileMenu();
        hideSearchSuggestions();
    }
    
    // Active link highlighting is now handled in nav-bar-transparent.php
}

// Utility functions for cart management
function updateCartCount(count) {
    const cartCountElement = document.querySelector('.cart-count');
    if (cartCountElement) {
        cartCountElement.textContent = count || '0';
        cartCountElement.style.display = (count > 0) ? 'flex' : 'none';
        
        // Add animation
        cartCountElement.style.transform = 'scale(1.2)';
        setTimeout(() => {
            cartCountElement.style.transform = 'scale(1)';
        }, 200);
    }
}

// Notification system
function showNavNotification(message, type = 'info', duration = 3000) {
    const notification = document.createElement('div');
    notification.className = `nav-notification nav-notification-${type}`;
    
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

// Export functions for use in other scripts
window.NavBar = {
    updateCartCount,
    showNavNotification,
    performSearch,
    closeMobileMenu
};
