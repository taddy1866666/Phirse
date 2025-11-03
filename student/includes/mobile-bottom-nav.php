<?php
// Mobile bottom navigation include
// Usage: include 'includes/mobile-bottom-nav.php';
?>

<style>
/* Mobile-only bottom navigation bar */
.mobile-bottom-nav {
    display: none; /* Hidden by default on desktop */
}

/* Show only on mobile screens */
@media (max-width: 768px) {
    /* Hide existing mobile-toggle (from nav-bar-transparent) on mobile as requested */
    .mobile-toggle, #mobile-toggle {
        display: none !important;
    }

    .mobile-bottom-nav {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        height: 64px;
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: space-around;
        box-shadow: 0 -6px 20px rgba(0,0,0,0.08);
        z-index: 9999;
        border-top: 1px solid #eee;
    }

    .mobile-bottom-nav a {
        color: #333333;
        text-decoration: none;
        font-weight: 700;
        font-size: 0.9rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
    }

    .mobile-bottom-nav a .icon {
        font-size: 18px;
        line-height: 1;
    }

    .mobile-bottom-nav a.active {
        color: #667eea;
    }

    /* Add bottom padding to main content so nav doesn't overlap */
    main {
        padding-bottom: 84px;
    }
}
</style>

<nav class="mobile-bottom-nav" aria-label="Mobile navigation">
    <a href="index.php" class="mobile-nav-link" id="mb-home">
        <span class="icon"><i class="fas fa-home"></i></span>
        <span class="label">Home</span>
    </a>
    <a href="organizations.php" class="mobile-nav-link" id="mb-orgs">
        <span class="icon"><i class="fas fa-store"></i></span>
        <span class="label">Organizations</span>
    </a>
    <a href="products.php" class="mobile-nav-link" id="mb-products">
        <span class="icon"><i class="fas fa-shopping-bag"></i></span>
        <span class="label">Products</span>
    </a>
</nav>

<script>
// Highlight mobile bottom nav active link
(function() {
    try {
        const path = window.location.pathname.split('/').pop();
        const homeLink = document.getElementById('mb-home');
        const orgsLink = document.getElementById('mb-orgs');
        const productsLink = document.getElementById('mb-products');

        if (!path || path === '' || path === 'index.php' || path === 'index.html') {
            homeLink && homeLink.classList.add('active');
        } else if (path.includes('organization') || path === 'organizations.php' || path.includes('organization-product')) {
            orgsLink && orgsLink.classList.add('active');
        } else if (path.includes('product') || path === 'products.php' || path.includes('product-details')) {
            productsLink && productsLink.classList.add('active');
        }
    } catch (e) {
        // fail silently
    }
})();
</script>
