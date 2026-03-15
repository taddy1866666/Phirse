<div class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <?php if (!empty($seller_logo)): ?>
      <div class="logo-container">
        <img src="<?php echo htmlspecialchars($seller_logo); ?>" alt="Organization Logo" class="organization-logo">
        <div class="organization-name"><?php echo htmlspecialchars($organization_name ?: 'Organization'); ?></div>
      </div>
    <?php else: ?>
      <h2 class="organization-text"><?php echo htmlspecialchars($organization_name ?: 'Seller Panel'); ?></h2>
    <?php endif; ?>
  </div>

  <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
  <ul class="sidebar-menu">
    <li><a href="seller-dashboard.php" class="<?php echo ($current_page == 'seller-dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
    <li><a href="registered-students.php" class="<?php echo ($current_page == 'registered-students.php') ? 'active' : ''; ?>"><i class="fas fa-users"></i><span>Students</span></a></li>
    
    <!-- Products with Dropdown -->
    <li class="has-dropdown">
      <a href="#" class="menu-dropdown <?php echo (in_array($current_page, ['seller-products.php', 'seller-add-product.php'])) ? 'active' : ''; ?>">
        <i class="fas fa-box"></i>
        <span>Product Management</span>
        <i class="fas fa-chevron-down dropdown-icon"></i>
      </a>
      <ul class="dropdown-menu">
        <li>
          <a href="seller-products.php" class="<?php echo ($current_page == 'seller-products.php') ? 'active' : ''; ?>">
            <div class="menu-icon"><i class="fas fa-list"></i></div>
            <span>All Products</span>
          </a>
        </li>
        <li>
          <a href="seller-add-product.php" class="<?php echo ($current_page == 'seller-add-product.php') ? 'active' : ''; ?>">
            <div class="menu-icon"><i class="fas fa-plus"></i></div>
            <span>Add Product</span>
          </a>
        </li>
      </ul>
    </li>
    
    <li><a href="seller-orders.php" class="<?php echo ($current_page == 'seller-orders.php') ? 'active' : ''; ?>"><i class="fas fa-shopping-cart"></i><span>Orders</span></a></li>
  </ul>
  
  <!-- Bottom Menu Section -->
  <ul class="sidebar-menu sidebar-bottom-menu">
    <li><a href="seller-edit-profile.php" class="<?php echo ($current_page == 'seller-edit-profile.php') ? 'active' : ''; ?>"><i class="fas fa-user-edit"></i><span>Edit Profile</span></a></li>
    <li><a href="seller-logout.php"><i class="fas fa-sign-out-alt"></i><span>Log out</span></a></li>
  </ul>
</div>

<!-- Styles -->
<style>
  body {
    margin: 0;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: linear-gradient(135deg, #eef2ff 0%, #f8fafc 60%, #ffffff 100%);
    transition: opacity 0.3s ease;
  }
  
  body.fade-out {
    opacity: 0;
  }
  
  .sidebar {
    position: fixed;
    top: 20px;
    bottom: 70px;
    left: 0;
    height: calc(100vh - 40px);
    width: 65px;
    background: rgba(255,255,255,0.9);
    color: #0f172a;
    overflow-x: hidden;
    overflow-y: auto;
    transition: width 0.3s ease;
    z-index: 1000;
    box-shadow: 0 20px 40px rgba(0,0,0,0.06);
    border-top-right-radius: 16px;
    border-bottom-right-radius: 16px;
    backdrop-filter: blur(8px);
    border-right: 1px solid rgba(15, 23, 42, 0.06);
    display: flex;
    flex-direction: column;
  }

  .sidebar.expanded {
    width: 220px;
  }

  .sidebar.expanded ~ .main-content {
    margin-left: 220px;
  }

  .sidebar-header {
    padding: 32px 8px 20px;
    text-align: center;
    transition: all 0.3s ease;
    display: flex;
    justify-content: center;
    align-items: center;
    background: transparent;
    border: none;
    box-shadow: none;
  }

  .main-content {
    margin-left: 65px;
    transition: margin-left 0.3s ease;
  }

  .logo-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: 0 8px;
    background: transparent;
    border: none;
    box-shadow: none;
  }

  .organization-logo {
    width: 40px;
    height: 40px;
    transition: all 0.3s ease;
    margin: 0 auto;
    background: none !important;
    border: none !important;
    border-radius: 0 !important;
    box-shadow: none !important;
    object-fit: contain !important;
    mix-blend-mode: multiply;
  }

  .sidebar.expanded .organization-logo {
    width: 80px;
    height: auto;
    margin: 0;
    background: none !important;
    border: none !important;
    border-radius: 0 !important;
    box-shadow: none !important;
    mix-blend-mode: multiply;
  }

  .organization-name {
    font-size: 14px;
    font-weight: 500;
    color: #1e293b;
    margin-top: 16px;
    opacity: 0;
    transition: opacity 0.3s ease;
    white-space: nowrap;
    opacity: 0;
    transition: opacity 0.3s ease;
    white-space: nowrap;
  }

  .sidebar.expanded .organization-name {
    opacity: 1;
  }

  .sidebar-menu {
    list-style: none;
    padding: 0;
    margin: 28px 0 0 0;
    flex: 1;
  }

  .sidebar-bottom-menu {
    list-style: none;
    padding: 0;
    margin: 0;
    border-top: 1px solid rgba(15, 23, 42, 0.1);
    padding-top: 12px;
    padding-bottom: 12px;
  }

  .sidebar-menu > li {
    padding: 4px 10px;
  }

  .sidebar-bottom-menu > li {
    padding: 4px 10px;
  }

  .sidebar-menu a {
    transition: background-color 0.3s ease, padding 0.3s ease, color 0.3s ease;
    display: flex;
    align-items: center;
    padding: 12px 16px;
    color: black;
    text-decoration: none;
    font-size: 14px;
    border-radius: 8px;
    position: relative;
  }

  .sidebar-menu > li > a:hover,
  .sidebar-menu > li > a.active {
    background-color: #e5e5e5;
    font-weight: bold;
    border-left: 4px solid #000000ff;
  }

  .sidebar-menu a:hover {
    transform: translateX(2px);
  }

  .sidebar-menu i {
    width: 20px;
    text-align: center;
    font-size: 16px;
    margin-right: 0;
    flex-shrink: 0;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    color: #0f172a;
  }

  .sidebar-menu span {
    opacity: 0;
    max-width: 0;
    overflow: hidden;
    white-space: nowrap;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    color: #0f172a;
  }

  .sidebar.expanded .sidebar-menu span {
    opacity: 1;
    max-width: 200px;
  }

  /* Dropdown styles */
  .has-dropdown {
    position: relative;
  }

  .dropdown-menu {
    max-height: 0;
    opacity: 0;
    list-style: none;
    padding-left: 20px;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    background: rgba(255,255,255,0.5);
    border-radius: 12px;
    margin: 4px 0;
  }

  .has-dropdown:hover .dropdown-menu,
  .has-dropdown.active .dropdown-menu {
    max-height: 200px;
    opacity: 1;
    padding-top: 4px;
    padding-bottom: 4px;
  }

  .menu-dropdown {
    cursor: pointer;
    justify-content: space-between;
  }

  .menu-dropdown .dropdown-icon {
    margin-left: auto;
    transition: all 0.3s ease;
    opacity: 0;
    width: 0;
    font-size: 12px;
  }

  .sidebar.expanded .menu-dropdown .dropdown-icon {
    opacity: 0.6;
    width: auto;
    margin-left: 8px;
  }

  .has-dropdown:hover .menu-dropdown .dropdown-icon,
  .has-dropdown.active .menu-dropdown .dropdown-icon {
    transform: rotate(180deg);
    opacity: 1;
  }

  .sidebar:not(.expanded) .dropdown-menu {
    display: none !important;
  }

  .dropdown-menu a {
    padding: 10px 16px;
    font-size: 13px;
    opacity: 0;
    transform: translateX(-10px);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    margin: 2px 8px;
    border-radius: 8px;
    display: flex;
    align-items: center;
  }

  .has-dropdown:hover .dropdown-menu a,
  .has-dropdown.active .dropdown-menu a {
    opacity: 1;
    transform: translateX(0);
  }

  /* Add delay for each dropdown item */
  .dropdown-menu li:nth-child(1) a { transition-delay: 0.1s; }
  .dropdown-menu li:nth-child(2) a { transition-delay: 0.15s; }
  
  .dropdown-menu a:hover {
    background: linear-gradient(90deg, rgba(102,126,234,0.08), rgba(118,75,162,0.08));
  }

  .dropdown-menu a.active {
    background: linear-gradient(90deg, rgba(102,126,234,0.12), rgba(118,75,162,0.12));
    font-weight: 600;
  }

  .dropdown-menu a:hover {
    background: linear-gradient(90deg, rgba(102,126,234,0.08), rgba(118,75,162,0.08));
  }

  .dropdown-menu a.active {
    background: linear-gradient(90deg, rgba(102,126,234,0.12), rgba(118,75,162,0.12));
    font-weight: 600;
  }

  .dropdown-menu a i {
    font-size: 14px;
    opacity: 0.7;
    margin-right: 8px;
  }

  .dropdown-menu a:hover i,
  .dropdown-menu a.active i {
    opacity: 1;
  }

  /* Add delay for each dropdown item */
  .dropdown-menu li:nth-child(1) a { transition-delay: 0.1s; }
  .dropdown-menu li:nth-child(2) a { transition-delay: 0.15s; }

  .sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
  }

  .sidebar-overlay.active {
    display: block;
  }

  .sidebar-toggle {
    position: fixed;
    top: 15px;
    left: 15px;
    background-color: #ffffff;
    color: black;
    border: none;
    padding: 10px;
    font-size: 18px;
    cursor: pointer;
    z-index: 1100;
    box-shadow: 0 0 5px rgba(0,0,0,0.1);
  }

  /* Menu icon styles */
  .menu-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    flex-shrink: 0;
  }

  .menu-icon i {
    font-size: 14px;
    opacity: 0.7;
    transition: opacity 0.2s ease;
  }

  .dropdown-menu .menu-icon {
    margin-right: 10px;
  }

  /* Dropdown styles when sidebar is collapsed */
  .sidebar:not(.expanded) .dropdown-menu {
    position: absolute;
    left: 60px;
    top: 0;
    width: 200px;
    background: white;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    border-radius: 8px;
    padding: 8px 0;
    margin: 0;
    max-height: none;
    display: none;
  }

  .sidebar:not(.expanded) .has-dropdown:hover .dropdown-menu {
    display: block;
    opacity: 1;
  }

  /* Icon visibility states */
  .sidebar:not(.expanded) .dropdown-menu .menu-icon i {
    opacity: 1;
  }

  .sidebar.expanded .dropdown-menu .menu-icon i {
    opacity: 0.7;
  }

  .dropdown-menu a:hover .menu-icon i {
    opacity: 1;
  }
</style>

<!-- Script -->
<script>
  const sidebar = document.getElementById('sidebar');
  const sidebarOverlay = document.getElementById('sidebarOverlay');
  const mobileMenuToggle = document.getElementById('mobileMenuToggle');
  const dropdownMenu = document.querySelector('.dropdown-menu');
  const dropdownToggle = document.querySelector('.dropdown-toggle');

  // Hover to expand sidebar
  sidebar.addEventListener('mouseenter', () => {
    sidebar.classList.add('expanded');
  });

  sidebar.addEventListener('mouseleave', () => {
    sidebar.classList.remove('expanded');
  });

  // Handle dropdowns
  document.querySelectorAll('.has-dropdown').forEach(dropdown => {
    // Add hover listener to parent
    dropdown.addEventListener('mouseenter', () => {
      if (sidebar.classList.contains('expanded')) {
        dropdown.classList.add('active');
      }
    });

    // Close dropdown on mouse leave
    dropdown.addEventListener('mouseleave', () => {
      dropdown.classList.remove('active');
    });
  });

  // Auto-activate dropdown if on products page
  const currentPage = '<?php echo $current_page; ?>';
  if (currentPage === 'seller-products.php' || currentPage === 'seller-add-product.php') {
    const productDropdown = document.querySelector('.has-dropdown');
    if (productDropdown) {
      productDropdown.classList.add('active');
    }
  }

  // Ensure dropdowns close when sidebar collapses
  sidebar.addEventListener('mouseleave', () => {
    document.querySelectorAll('.has-dropdown').forEach(item => {
      if (!item.querySelector('a.active')) {
        item.classList.remove('active');
      }
    });
  });

  // Page transition fade effect
  document.querySelectorAll('.sidebar-menu a').forEach(link => {
    link.addEventListener('click', function (e) {
      const href = this.getAttribute('href');
      if (href && href !== '#') {
        e.preventDefault();
        document.body.classList.add('fade-out');
        setTimeout(() => {
          window.location.href = href;
        }, 300);
      }
    });
  });

  // Mobile toggle
  if (mobileMenuToggle) {
    mobileMenuToggle.addEventListener('click', () => {
      sidebar.classList.toggle('expanded');
      sidebarOverlay.classList.toggle('active');
    });
  }

  if (sidebarOverlay) {
    sidebarOverlay.addEventListener('click', () => {
      sidebar.classList.remove('expanded');
      sidebarOverlay.classList.remove('active');
    });
  }

  // Auto-close on mobile after clicking a link
  document.querySelectorAll('.sidebar-menu a').forEach(link => {
    link.addEventListener('click', () => {
      if (window.innerWidth <= 768) {
        sidebar.classList.remove('expanded');
        if (sidebarOverlay) {
          sidebarOverlay.classList.remove('active');
        }
      }
    });
  });
</script>