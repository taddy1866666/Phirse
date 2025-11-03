// js/product_performance.js
document.addEventListener('DOMContentLoaded', () => {
  const buttons = document.querySelectorAll('#adminProductFilters button');
  const container = document.getElementById('adminProductPerformanceContainer');
  const totalRevEl = document.getElementById('admin_prodTotalRevenue');
  const totalSoldEl = document.getElementById('admin_prodTotalSold');

  async function loadProductPerformance(range = '7days') {
    container.innerHTML = '<p style="color:#888">Loading...</p>';
    const res = await fetch(`product_performance.php?range=${range}`);
    const data = await res.json();

    if (!data.products || data.products.length === 0) {
      container.innerHTML = "<p class='no-data'>No product performance data found.</p>";
      totalRevEl.textContent = '0';
      totalSoldEl.textContent = '0';
      return;
    }

    totalRevEl.textContent = Number(data.total_revenue).toLocaleString(undefined, { minimumFractionDigits: 2 });
    totalSoldEl.textContent = Number(data.total_sold);

    const maxRevenue = Math.max(...data.products.map(p => Number(p.revenue) || 0));
    container.innerHTML = '';

    data.products.forEach((p, idx) => {
      const percent = maxRevenue > 0 ? (Number(p.revenue) / maxRevenue) * 100 : 0;
      const div = document.createElement('div');
      div.className = 'product-row';
      div.innerHTML = `<div class="product-info">
          <div style="display:flex;gap:10px;align-items:center">
            <div style="font-weight:700">${idx + 1}.</div>
            <div class="product-name">${p.name}</div>
          </div>
          <div style="text-align:right">
            <div style="font-weight:700;color:#333">₱${Number(p.revenue).toLocaleString()}</div>
            <div style="font-size:13px;color:#666">${p.total_sold} sold</div>
          </div>
        </div>
        <div class="product-bar">
          <div class="product-bar-fill" style="width:${percent}%;"></div>
        </div>`;
      container.appendChild(div);
    });
  }

  buttons.forEach(btn => {
    btn.addEventListener('click', () => {
      buttons.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      loadProductPerformance(btn.dataset.range);
    });
  });

  loadProductPerformance(document.querySelector('#adminProductFilters button.active').dataset.range);
});
