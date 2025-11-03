
<div class="sidebar" id="sidebar">
<div class="sidebar-header">
  <div class="admin-panel-label">
    <i class="fas fa-user-shield"></i>
    <span class="admin-panel-text">Admin Panel</span>
  </div>
</div>



  <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
  <ul class="sidebar-menu">
    <li><a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
    
    <li class="has-dropdown">
      <a href="#" class="menu-dropdown <?php echo (in_array($current_page, ['registered-sellers.php', 'students-list.php'])) ? 'active' : ''; ?>">
        <i class="fas fa-users-cog"></i>
        <span>User Management</span>
        <i class="fas fa-chevron-down dropdown-icon"></i>
      </a>
      <ul class="dropdown-menu">
        <li><a href="registered-sellers.php" class="<?php echo ($current_page == 'registered-sellers.php') ? 'active' : ''; ?>"><i class="fas fa-building"></i><span>Organizations</span></a></li>
        <li><a href="students-list.php" class="<?php echo ($current_page == 'students-list.php') ? 'active' : ''; ?>"><i class="fas fa-user-graduate"></i><span>Students</span></a></li>
      </ul>
    </li>
    
    <li class="has-dropdown">
      <a href="#" class="menu-dropdown <?php echo (in_array($current_page, ['product-management.php', 'admin-orders.php'])) ? 'active' : ''; ?>">
        <i class="fas fa-shopping-cart"></i>
        <span>Store Management</span>
        <i class="fas fa-chevron-down dropdown-icon"></i>
      </a>
      <ul class="dropdown-menu">
        <li><a href="product-management.php" class="<?php echo ($current_page == 'product-management.php') ? 'active' : ''; ?>"><i class="fas fa-box"></i><span>Products</span></a></li>
        <li><a href="admin-orders.php" class="<?php echo ($current_page == 'admin-orders.php') ? 'active' : ''; ?>"><i class="fas fa-shopping-bag"></i><span>Orders</span></a></li>
      </ul>
    </li>
  
    <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i><span>Log out</span></a></li>
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
.admin-panel-label {
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 16px;
  color: #0f172a;
  transition: all 0.3s ease;
  margin-bottom: 20px;
  height: 60px;
  overflow: hidden;
  border-bottom: 1px solid rgba(15, 23, 42, 0.06);
}
.admin-panel-label i {
  font-size: 20px;
  transition: margin 0.3s ease, transform 0.3s ease;
  margin-right: 0;
}

.admin-panel-text {
  opacity: 0;
  max-width: 0;
  transition: opacity 0.3s ease, max-width 0.3s ease;
  white-space: nowrap;
  overflow: hidden;
}
.sidebar.expanded .admin-panel-label {
  justify-content: flex-start;
  padding-left: 16px;
}

.sidebar.expanded .admin-panel-label i {
  margin-right: 10px;
}

.sidebar.expanded .admin-panel-text {
  opacity: 1;
  max-width: 200px;
}

.sidebar {
  position: fixed;
  top: -10px; /* lowers from top */
  bottom: 70px;
  left: 0;
  height: calc(115vh - 60px); /* trims top and bottom equally */
  width: 65px;
  background: rgba(255,255,255,0.9);
  color: black;
  overflow-x: hidden;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  z-index: 1000;
  box-shadow: 0 20px 40px rgba(0,0,0,0.06);
  border-top-right-radius: 16px;
  border-bottom-right-radius: 16px;
  backdrop-filter: blur(8px);
  border-right: 1px solid rgba(15, 23, 42, 0.06);
}


.sidebar.expanded {
  width: 230px;
}

.sidebar.expanded ~ .main-content {
  margin-left: 220px;
}

.sidebar-header {
  padding: 20px;
  text-align: center;
  transition: all 0.3s ease;
}

.main-content {
  margin-left: 65px;
  transition: margin-left 0.3s ease;
}

.organization-logo {
  width: 24px;
  height: 24px;
  transition: width 0.3s ease, height 0.3s ease;
}

.sidebar.expanded .organization-logo {
  width: 80px;
  height: auto;
}

.organization-name {
  font-size: 14px;
  font-weight: bold;
  color: black;
  margin-top: 10px;
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
  margin: 0;
}

.sidebar-menu li {
  padding: 5px 10px;
  margin-bottom: 8px;
}

.sidebar-menu a {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  color: #0f172a;
  text-decoration: none;
  font-size: 14px;
  border-radius: 8px;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
  border-left: 4px solid transparent;
}

.sidebar-menu a:hover,
.sidebar-menu a.active {
  background: linear-gradient(90deg, rgba(102,126,234,0.12), rgba(118,75,162,0.12));
  font-weight: 700;
  border-left: 4px solid #667eea;
}

.sidebar-menu a:hover { transform: translateX(2px); }

.sidebar-menu i {
  font-size: 16px;
  flex-shrink: 0;
  transition: margin 0.3s ease;
  margin-right: 0;
}
.sidebar-menu span {
  opacity: 0;
  max-width: 0;
  overflow: hidden;
  white-space: nowrap;
  transition: all 0.3s ease;
}

.sidebar.expanded .sidebar-menu span {
  opacity: 1;
  max-width: 200px;
  margin-left: 10px;
  display: inline-block;
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
  transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
  background: rgba(255,255,255,0.5);
  border-radius: 12px;
  margin: 4px 0;
  transform-origin: top;
  transform: scaleY(0);
}

.has-dropdown:hover .dropdown-menu,
.has-dropdown.active .dropdown-menu {
  max-height: 200px;
  opacity: 1;
  padding-top: 4px;
  padding-bottom: 4px;
  transform: scaleY(1);
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
  transition: all 0.3s ease;
  margin: 2px 8px;
  border-radius: 8px;
  display: flex;
  align-items: center;
}

.dropdown-menu a i {
  width: 20px;
  text-align: center;
  margin-right: 10px;
  font-size: 14px;
  opacity: 0.7;
}

.has-dropdown:hover .dropdown-menu a,
.has-dropdown.active .dropdown-menu a {
  opacity: 1;
  transform: translateX(0);
}

.dropdown-menu a:hover {
  background: linear-gradient(90deg, rgba(102,126,234,0.08), rgba(118,75,162,0.08));
}

.dropdown-menu a.active {
  background: linear-gradient(90deg, rgba(102,126,234,0.12), rgba(118,75,162,0.12));
  font-weight: 600;
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
  background: #111827;
  color: #fff;
  border: none;
  padding: 10px;
  font-size: 18px;
  cursor: pointer;
  z-index: 1100;
  box-shadow: 0 10px 25px rgba(0,0,0,0.15);
  border-radius: 10px;
}


</style>

<!-- Script -->
<script>
  
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
  const sidebar = document.getElementById('sidebar');
  const sidebarOverlay = document.getElementById('sidebarOverlay');
  const mobileMenuToggle = document.getElementById('mobileMenuToggle');

  // Hover to expand
  sidebar.addEventListener('mouseenter', () => {
    sidebar.classList.add('expanded');
  });

  sidebar.addEventListener('mouseleave', () => {
    sidebar.classList.remove('expanded');
    // Close dropdowns when sidebar collapses
    document.querySelectorAll('.has-dropdown').forEach(item => {
      if (!item.querySelector('a.active')) {
        item.classList.remove('active');
      }
    });
  });

  // Handle dropdowns
  document.querySelectorAll('.has-dropdown').forEach(dropdown => {
    // Add hover listener to parent
    dropdown.addEventListener('mouseenter', () => {
      if (sidebar.classList.contains('expanded')) {
        // Close other dropdowns
        document.querySelectorAll('.has-dropdown').forEach(item => {
          if (item !== dropdown) {
            item.classList.remove('active');
          }
        });
        dropdown.classList.add('active');
      }
    });
  });

  // Auto-activate dropdown based on current page
  const currentPage = '<?php echo $current_page; ?>';
  if (['registered-sellers.php', 'students-list.php'].includes(currentPage)) {
    const userManagementDropdown = document.querySelector('.has-dropdown:nth-child(2)');
    if (userManagementDropdown) userManagementDropdown.classList.add('active');
  }
  if (['product-management.php', 'admin-orders.php'].includes(currentPage)) {
    const storeManagementDropdown = document.querySelector('.has-dropdown:nth-child(3)');
    if (storeManagementDropdown) storeManagementDropdown.classList.add('active');
  }

  // Mobile toggle
  mobileMenuToggle.addEventListener('click', () => {
    sidebar.classList.toggle('expanded');
    sidebarOverlay.classList.toggle('active');
  });

  sidebarOverlay.addEventListener('click', () => {
    sidebar.classList.remove('expanded');
    sidebarOverlay.classList.remove('active');
  });

  // Auto-close on mobile after clicking a link
  document.querySelectorAll('.sidebar-menu a').forEach(link => {
    link.addEventListener('click', () => {
      if (window.innerWidth <= 768) {
        sidebar.classList.remove('expanded');
        sidebarOverlay.classList.remove('active');
      }
    });
  });
</script>
