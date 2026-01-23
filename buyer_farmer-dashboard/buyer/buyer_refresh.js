// AJAX auto-refresh when tab becomes visible or window gains focus
(function(){
  const REFRESH_DEBOUNCE_MS = 3000;
  let lastRefresh = 0;

  async function fetchCartCount() {
    try {
      const res = await fetch('cart_count_api.php', {cache: 'no-store'});
      if (!res.ok) return;
      const data = await res.json();
      const badge = document.querySelector('.cart-badge-count');
      if (badge) {
        const n = parseInt(data.count || 0, 10);
        if (n > 0) badge.textContent = n;
        else badge.remove();
      }
    } catch (e) {
      console.debug('cart count fetch failed', e);
    }
  }

  async function fetchProductsAndRender() {
    try {
      const grid = document.getElementById('productGrid');
      if (!grid) return;
      // If server-rendered product cards (with forms/actions) exist, do not overwrite them.
      if (grid.querySelector('.product-card form')) {
        return;
      }
      const res = await fetch('products_api.php', {cache: 'no-store'});
      if (!res.ok) return;
      const items = await res.json();
      // Build minimal product cards (keeps styling simple)
      const html = items.map(p => {
        const name = (p.name || '').replace(/</g,'&lt;');
        const price = (parseFloat(p.price)||0).toFixed(2);
        const img = p.image_path ? `<img src="${p.image_path}" alt="${name}" class="product-thumb">` : '';
        return `
          <div class="product-card">
            <div class="product-media">${img}</div>
            <div class="product-body">
              <div class="product-name">${name}</div>
              <div class="product-price">₹${price}</div>
            </div>
          </div>`;
      }).join('');
      grid.innerHTML = html;
    } catch (e) {
      console.debug('products fetch failed', e);
    }
  }

  async function doRefresh() {
    const now = Date.now();
    if (now - lastRefresh < REFRESH_DEBOUNCE_MS) return;
    lastRefresh = now;
    // Only refresh lightweight data (cart count). Do NOT re-render the product grid
    // to avoid overwriting server-rendered cards and their action buttons.
    fetchCartCount();
  }

  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) doRefresh();
  });
  window.addEventListener('focus', doRefresh);

  // Optional: initial load refresh after DOM is ready
  document.addEventListener('DOMContentLoaded', () => {
    setTimeout(doRefresh, 500);
  });

})();
