// Responsive Menu Toggle Functionality
(function() {
  'use strict';
  
  // Create menu toggle button
  function createMenuToggle() {
    // Check if button already exists
    if (document.getElementById('menuToggleBtn')) {
      return;
    }
    
    const toggle = document.createElement('button');
    toggle.id = 'menuToggleBtn';
    toggle.className = 'menu-toggle-btn';
    toggle.innerHTML = '<i class="fas fa-bars"></i>';
    toggle.setAttribute('aria-label', 'Toggle Menu');
    toggle.type = 'button';
    
    // Add inline styles to ensure visibility in mobile
    toggle.style.display = 'flex';
    toggle.style.position = 'fixed';
    toggle.style.top = '15px';
    toggle.style.right = '15px';
    toggle.style.zIndex = '10000';
    toggle.style.width = '50px';
    toggle.style.height = '50px';
    toggle.style.padding = '12px 15px';
    toggle.style.background = '#2e7d32';
    toggle.style.color = 'white';
    toggle.style.border = 'none';
    toggle.style.borderRadius = '8px';
    toggle.style.cursor = 'pointer';
    toggle.style.alignItems = 'center';
    toggle.style.justifyContent = 'center';
    toggle.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.2)';
    toggle.style.fontSize = '1.2rem';
    toggle.style.visibility = 'visible';
    
    // Hide on desktop, show on mobile
    if (window.innerWidth > 992) {
      toggle.style.display = 'none';
    }
    
    document.body.appendChild(toggle);
    
    // Create overlay
    const overlay = document.createElement('div');
    overlay.className = 'responsive-menu-overlay';
    overlay.id = 'menuOverlay';
    overlay.style.display = 'none';
    overlay.style.position = 'fixed';
    overlay.style.top = '0';
    overlay.style.left = '0';
    overlay.style.width = '100%';
    overlay.style.height = '100%';
    overlay.style.background = 'rgba(0, 0, 0, 0.5)';
    overlay.style.zIndex = '9998';
    overlay.style.opacity = '0';
    overlay.style.transition = 'opacity 0.3s ease';
    document.body.appendChild(overlay);
    
    // Create menu sidebar
    const menu = document.createElement('div');
    menu.className = 'responsive-menu';
    menu.id = 'responsiveMenu';
    menu.style.position = 'fixed';
    menu.style.top = '0';
    menu.style.right = '-100%';
    menu.style.width = '280px';
    menu.style.maxWidth = '85%';
    menu.style.height = '100%';
    menu.style.background = 'white';
    menu.style.zIndex = '9999';
    menu.style.overflowY = 'auto';
    menu.style.transition = 'right 0.3s ease';
    menu.style.boxShadow = '-2px 0 10px rgba(0, 0, 0, 0.1)';
    menu.style.display = 'block';
    
    // Get navigation links from header (even if hidden)
    let headerRight = document.querySelector('.dashboard-header .header-right') || 
                       document.querySelector('.header-bar .header-right') ||
                       document.querySelector('.admin-header .admin-nav');
    
    // If not found, try to find it by checking all possible selectors
    if (!headerRight) {
      const possibleSelectors = [
        '.dashboard-header .header-right',
        '.header-bar .header-right',
        '.admin-header .admin-nav',
        '.admin-nav'
      ];
      
      for (let selector of possibleSelectors) {
        const element = document.querySelector(selector);
        if (element) {
          headerRight = element;
          break;
        }
      }
    }
    
    if (!headerRight) {
      console.warn('Navigation menu not found');
      return; // No navigation found
    }
    
    // Temporarily show to read content
    const originalDisplay = headerRight.style.display;
    headerRight.style.display = 'block';
    headerRight.style.visibility = 'hidden';
    headerRight.style.position = 'absolute';
    
    // Build menu content
    let menuHTML = `
      <div class="responsive-menu-header">
        <h3><i class="fas fa-bars"></i> Menu</h3>
      </div>
      <div class="responsive-menu-body" style="display: flex; flex-direction: column; width: 100%; padding: 0;">
    `;
    
    // Get all navigation links
    const navLinks = headerRight.querySelectorAll('a, .dropdown');
    const currentPage = window.location.pathname.split('/').pop();
    const addedItems = new Set(); // Track added items to prevent duplicates
    
    navLinks.forEach((link, index) => {
      if (link.tagName === 'A') {
        const href = link.getAttribute('href') || '#';
        let text = link.textContent.trim();
        // Remove extra whitespace and newlines
        text = text.replace(/\s+/g, ' ').trim();
        const icon = link.querySelector('i');
        const iconClass = icon ? icon.className : 'fas fa-circle';
        const isActive = href === currentPage || link.classList.contains('active');
        
        // Skip empty links
        if (!text || text === '') return;
        
        // Create unique key for this item
        const itemKey = `${href}_${text}`;
        if (addedItems.has(itemKey)) return; // Skip duplicates
        addedItems.add(itemKey);
        
        menuHTML += `
          <a href="${href}" class="responsive-menu-item ${isActive ? 'active' : ''}" style="display: flex; flex-direction: row; width: 100%; padding: 15px 20px; color: #333; text-decoration: none; border-bottom: 1px solid #f0f0f0; align-items: center; gap: 12px; box-sizing: border-box; background: white;">
            <i class="${iconClass}"></i>
            <span>${text}</span>
          </a>
        `;
      } else if (link.classList.contains('dropdown')) {
        // Handle dropdown (profile menu)
        const dropdownMenu = link.querySelector('.dropdown-menu');
        
        if (dropdownMenu) {
          menuHTML += '<div class="responsive-menu-divider"></div>';
          const dropdownItems = dropdownMenu.querySelectorAll('.dropdown-item');
          let logoutAdded = false; // Track if logout has been added
          
          dropdownItems.forEach(item => {
            let itemText = item.textContent.trim();
            // Remove extra whitespace
            itemText = itemText.replace(/\s+/g, ' ').trim();
            const itemIcon = item.querySelector('i');
            const iconClass = itemIcon ? itemIcon.className : 'fas fa-circle';
            const itemHref = item.getAttribute('href') || '#';
            const isButton = item.tagName === 'BUTTON';
            const dataBsToggle = item.getAttribute('data-bs-toggle') || '';
            const dataBsTarget = item.getAttribute('data-bs-target') || '';
            
            // Skip empty items
            if (!itemText || itemText === '') return;
            
            // Check if this is a logout button/link
            const isLogout = itemText.toLowerCase().includes('logout') || 
                           itemHref.toLowerCase().includes('logout') ||
                           item.classList.contains('text-danger');
            
            // Skip duplicate logout buttons
            if (isLogout && logoutAdded) return;
            if (isLogout) logoutAdded = true;
            
            // Create unique key for this item
            const itemKey = `${itemHref}_${itemText}`;
            if (addedItems.has(itemKey)) return; // Skip duplicates
            addedItems.add(itemKey);
            
            if (isButton) {
              // For buttons, preserve Bootstrap modal functionality
              menuHTML += `
                <button class="responsive-menu-item" ${dataBsToggle ? `data-bs-toggle="${dataBsToggle}"` : ''} ${dataBsTarget ? `data-bs-target="${dataBsTarget}"` : ''} style="width: 100%; text-align: left; border: none; background: white; padding: 15px 20px; cursor: pointer; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid #f0f0f0; color: #333;">
                  <i class="${iconClass}"></i>
                  <span>${itemText}</span>
                </button>
              `;
            } else {
              menuHTML += `
                <a href="${itemHref}" class="responsive-menu-item" style="display: flex; flex-direction: row; width: 100%; padding: 15px 20px; color: #333; text-decoration: none; border-bottom: 1px solid #f0f0f0; align-items: center; gap: 12px; box-sizing: border-box; background: white;">
                  <i class="${iconClass}"></i>
                  <span>${itemText}</span>
                </a>
              `;
            }
          });
        }
      }
    });
    
    menuHTML += '</div>';
    menu.innerHTML = menuHTML;
    document.body.appendChild(menu);
    
    // Restore original display
    headerRight.style.display = originalDisplay;
    headerRight.style.visibility = '';
    headerRight.style.position = '';
    
    // Add event listeners
    toggle.addEventListener('click', function() {
      toggleMenu();
    });
    
    overlay.addEventListener('click', function() {
      closeMenu();
    });
    
    const closeBtn = document.getElementById('menuCloseBtn');
    if (closeBtn) {
      closeBtn.addEventListener('click', function() {
        closeMenu();
      });
    }
    
    // Close menu when clicking on menu items
    const menuItems = menu.querySelectorAll('.responsive-menu-item');
    menuItems.forEach(item => {
      item.addEventListener('click', function(e) {
        // If it's a button with modal, don't close immediately
        if (this.tagName === 'BUTTON' && this.getAttribute('data-bs-toggle') === 'modal') {
          setTimeout(closeMenu, 300);
        } else if (this.getAttribute('href') !== '#' && this.getAttribute('href') !== '') {
          // Close menu when navigating
          setTimeout(closeMenu, 100);
        }
      });
    });
  }
  
  function toggleMenu() {
    const menu = document.getElementById('responsiveMenu');
    const overlay = document.getElementById('menuOverlay');
    const toggle = document.getElementById('menuToggleBtn');
    
    if (menu && overlay && toggle) {
      const isOpen = menu.style.right === '0px';
      
      if (isOpen) {
        // Close menu
        menu.style.right = '-100%';
        overlay.style.display = 'none';
        overlay.style.opacity = '0';
        toggle.style.background = '#2e7d32';
        document.body.style.overflow = '';
      } else {
        // Open menu
        menu.style.right = '0';
        overlay.style.display = 'block';
        overlay.style.opacity = '1';
        toggle.style.background = '#d32f2f';
        document.body.style.overflow = 'hidden';
      }
    }
  }
  
  function closeMenu() {
    const menu = document.getElementById('responsiveMenu');
    const overlay = document.getElementById('menuOverlay');
    const toggle = document.getElementById('menuToggleBtn');
    
    if (menu && overlay && toggle) {
      menu.style.right = '-100%';
      overlay.style.display = 'none';
      overlay.style.opacity = '0';
      toggle.style.background = '#2e7d32';
      document.body.style.overflow = '';
    }
  }
  
  // Initialize on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      createMenuToggle();
      
      // Recreate on window resize
      let resizeTimer;
      window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
          const existingBtn = document.getElementById('menuToggleBtn');
          if (existingBtn) {
            if (window.innerWidth > 992) {
              // Hide on desktop
              existingBtn.style.display = 'none';
              const menu = document.getElementById('responsiveMenu');
              const overlay = document.getElementById('menuOverlay');
              if (menu) menu.style.display = 'none';
              if (overlay) overlay.style.display = 'none';
              document.body.style.overflow = '';
            } else {
              // Show on mobile
              existingBtn.style.display = 'flex';
              const menu = document.getElementById('responsiveMenu');
              const overlay = document.getElementById('menuOverlay');
              if (menu) menu.style.display = 'block';
              if (overlay) overlay.style.display = 'block';
            }
          }
        }, 250);
      });
    });
  } else {
    createMenuToggle();
    
    // Recreate on window resize
    let resizeTimer;
    window.addEventListener('resize', function() {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function() {
        const existingBtn = document.getElementById('menuToggleBtn');
        if (existingBtn) {
          if (window.innerWidth > 992) {
            // Hide on desktop
            existingBtn.style.display = 'none';
            const menu = document.getElementById('responsiveMenu');
            const overlay = document.getElementById('menuOverlay');
            if (menu) menu.style.display = 'none';
            if (overlay) overlay.style.display = 'none';
            document.body.style.overflow = '';
          } else {
            // Show on mobile
            existingBtn.style.display = 'flex';
            const menu = document.getElementById('responsiveMenu');
            const overlay = document.getElementById('menuOverlay');
            if (menu) menu.style.display = 'block';
            if (overlay) overlay.style.display = 'block';
          }
        }
      }, 250);
    });
  }
})();