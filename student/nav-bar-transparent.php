<?php
// Check if user is logged in
$isLoggedIn = isset($_SESSION['student_id']);
$userName = $isLoggedIn ? ($_SESSION['student_name'] ?? 'Student') : '';
$userInitial = $isLoggedIn ? strtoupper(substr($userName, 0, 1)) : '';
?>

<nav class="navbar" id="navbar">
    <div class="nav-container">
        <!-- Navigation Menu (Left) -->
        <ul class="nav-menu" id="nav-menu">
            <button class="nav-menu-close" id="nav-menu-close">
                <i class="fas fa-times"></i>
            </button>
            <li class="nav-item">
                <a href="index.php" class="nav-link">Home</a>
            </li>
            <li class="nav-item">
                <a href="organizations.php" class="nav-link">Organizations</a>
            </li>
            <li class="nav-item">
                <a href="products.php" class="nav-link">Products</a>
            </li>
        </ul>

        <!-- Logo Section (Center) -->
        <div class="nav-logo">
            <a href="index.php" class="logo-text">PHIRSE</a>
        </div>

        <!-- Navigation Actions (Right) -->
        <div class="nav-actions">
            <?php if ($isLoggedIn): ?>
                <!-- User Dropdown -->
                <div class="user-dropdown">
                    <div class="user-avatar" title="<?php echo htmlspecialchars($userName); ?>">
                        <?php echo $userInitial; ?>
                    </div>
                    <div class="user-menu">
                        <a href="profile.php">
                            <i class="fas fa-user"></i> My Profile
                        </a>
                        <a href="myorders.php">
                            <i class="fas fa-shopping-bag"></i> My Orders
                        </a>
                    
                        <div class="divider"></div>
                        <a href="settings.php">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                        <a href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>

                <!-- Notification Bell -->
                <a href="notifications.php" class="nav-icon" title="Order Notifications" id="notification-bell">
                    <i class="fas fa-bell"></i>
                    <span class="notification-count" id="notification-count">0</span>
                </a>

                <!-- Cart Icon -->
                <a href="myorders.php" class="nav-icon" title="My Orders" id="cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count" id="cart-count">0</span>
                </a>

                <!-- Search Icon -->
                <a href="#" class="nav-icon" title="Search" id="search-icon-trigger">
                    <i class="fas fa-search"></i>
                </a>

            <?php else: ?>
                <!-- Login Button -->
                <a href="login.php" class="nav-btn secondary"><i class="fas fa-sign-in-alt"></i></a>
                
                <!-- Search Icon -->
                <a href="#" class="nav-icon" title="Search" id="search-icon-trigger-guest">
                    <i class="fas fa-search"></i>
                </a>
            <?php endif; ?>
        </div>

        <!-- Mobile Menu Toggle removed (mobile nav now provided by include) -->
    </div>
    
    <!-- Mobile Menu Overlay -->
    <div class="nav-menu-overlay" id="nav-menu-overlay"></div>
</nav>

<!-- Search Modal -->
<div class="search-modal-overlay" id="searchModalOverlay">
    <div class="search-modal-container">
        <div class="search-modal-header">
            <h2>Search</h2>
            <button class="search-modal-close" id="searchModalClose">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="search-modal-body">
            <div class="search-input-wrapper">
                <i class="fas fa-search search-input-icon"></i>
                <input type="text" id="searchModalInput" class="search-modal-input" placeholder="Search products, organizations..." autocomplete="off">
            </div>
            <div id="searchResults" class="search-results-area">
                <div class="search-placeholder">
                    <i class="fas fa-search"></i>
                    <p>Start typing to search...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Search Modal functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchIconTrigger = document.getElementById('search-icon-trigger');
    const searchIconTriggerGuest = document.getElementById('search-icon-trigger-guest');
    const searchModalOverlay = document.getElementById('searchModalOverlay');
    const searchModalClose = document.getElementById('searchModalClose');
    const searchModalInput = document.getElementById('searchModalInput');
    const searchResults = document.getElementById('searchResults');
    
    let searchTimeout;
    
    function openSearchModal(e) {
        e.preventDefault();
        searchModalOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        setTimeout(() => {
            searchModalInput.focus();
        }, 100);
    }
    
    function closeSearchModal() {
        searchModalOverlay.classList.remove('active');
        document.body.style.overflow = '';
        searchModalInput.value = '';
        searchResults.innerHTML = '<div class="search-placeholder"><i class="fas fa-search"></i><p>Start typing to search...</p></div>';
    }
    
    if (searchIconTrigger) {
        searchIconTrigger.addEventListener('click', openSearchModal);
    }
    
    if (searchIconTriggerGuest) {
        searchIconTriggerGuest.addEventListener('click', openSearchModal);
    }
    
    if (searchModalClose) {
        searchModalClose.addEventListener('click', closeSearchModal);
    }
    
    // Close on ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && searchModalOverlay.classList.contains('active')) {
            closeSearchModal();
        }
    });
    
    // Close on backdrop click
    searchModalOverlay.addEventListener('click', function(e) {
        if (e.target === searchModalOverlay) {
            closeSearchModal();
        }
    });
    
    // Live search
    searchModalInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length === 0) {
            searchResults.innerHTML = '<div class="search-placeholder"><i class="fas fa-search"></i><p>Start typing to search...</p></div>';
            return;
        }
        
        if (query.length < 2) {
            searchResults.innerHTML = '<div class="search-placeholder"><i class="fas fa-search"></i><p>Type at least 2 characters...</p></div>';
            return;
        }
        
        searchResults.innerHTML = '<div class="search-loading"><i class="fas fa-spinner fa-spin"></i><p>Searching...</p></div>';
        
        searchTimeout = setTimeout(() => {
            fetch(`search.php?q=${encodeURIComponent(query)}`)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const resultsContainer = doc.querySelector('.search-results-container');
                    
                    if (resultsContainer) {
                        searchResults.innerHTML = resultsContainer.innerHTML;
                    } else {
                        searchResults.innerHTML = '<div class="search-placeholder"><i class="fas fa-search"></i><p>No results found</p></div>';
                    }
                })
                .catch(error => {
                    searchResults.innerHTML = '<div class="search-placeholder"><i class="fas fa-exclamation-triangle"></i><p>Error loading results</p></div>';
                    console.error('Search error:', error);
                });
        }, 500);
    });
    
    // Update cart count
    const cartCountElement = document.getElementById('cart-count');
    if (cartCountElement) {
        const cartCount = localStorage.getItem('cartCount') || '0';
        cartCountElement.textContent = cartCount;
        if (cartCount === '0') {
            cartCountElement.style.display = 'none';
        } else {
            cartCountElement.style.display = 'flex';
        }
    }
    
    // Update notification count
    const notificationCountElement = document.getElementById('notification-count');
    if (notificationCountElement) {
        fetch('get-notification-count.php')
            .then(response => response.json())
            .then(data => {
                const count = data.count || 0;
                notificationCountElement.textContent = count;
                if (count === 0) {
                    notificationCountElement.style.display = 'none';
                } else {
                    notificationCountElement.style.display = 'flex';
                }
            })
            .catch(error => {
                console.error('Error fetching notification count:', error);
                notificationCountElement.style.display = 'none';
            });
    }
    
    // Set active nav link
    function setActiveNavLink() {
        const currentPage = window.location.pathname.split('/').pop() || 'index.php';
        const navLinks = document.querySelectorAll('.nav-menu .nav-link');
        
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            link.classList.remove('active');
            
            if (href === currentPage || 
                (currentPage === '' && href === 'index.php') ||
                (currentPage === 'index.php' && href === 'index.php') ||
                (currentPage === '/' && href === 'index.php')) {
                link.classList.add('active');
            }
        });
    }
    
    // Run after a small delay to ensure DOM is ready
    setTimeout(setActiveNavLink, 100);
});
</script>

