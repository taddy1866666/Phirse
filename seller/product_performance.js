async function loadProductPerformance() {
    const response = await fetch('product_performance.php');
    const products = await response.json();

    const container = document.getElementById('productPerformanceContainer');
    container.innerHTML = '';

    products.forEach((item, index) => {
        const percent = (item.total_sold / products[0].total_sold) * 100;
        const div = document.createElement('div');
        div.className = 'org-row';
        div.innerHTML = `<div class="org-info">
        <div class="org-name">${index + 1}. ${item.name}</div>
        <div class="category-tag">${item.category || 'Uncategorized'}</div>
        <div class="org-sales">${item.total_sold} sold</div>
      </div>
      <div class="org-bar">
        .org-list .org-bar-fill {
  height: 100%;
  background: linear-gradient(45deg, #FF6B6B, #FF8E8E);
  border-radius: 4px;
  width: var(--target-width);
  transition: width 1s ease-in-out;
}

.category-tag {
  display: inline-block;
  padding: 2px 8px;
  background-color: #f0f0f0;
  border-radius: 12px;
  font-size: 12px;
  color: #666;
  margin-left: 8px;
}
      </div>
      <div class="org-info">
        <span style="font-size: 13px; color:#666;">₱${Number(item.revenue).toLocaleString()}</span>
      </div>
    `;
        container.appendChild(div);
    });
}

document.addEventListener("DOMContentLoaded", () => {
    const filterButtons = document.querySelectorAll("#productPerformanceFilters button");
    const container = document.getElementById("productPerformanceContainer");
    const totalRevenueEl = document.getElementById("prodTotalRevenue");
    const totalSoldEl = document.getElementById("prodTotalSold");

    function loadProductPerformance(range = "7days") {
        fetch(`product_performance.php?range=${range}`)
            .then(res => res.json())
            .then(data => {
                container.innerHTML = "";
                if (data.products.length === 0) {
                    container.innerHTML = "<p class='no-data'>No product performance data found.</p>";
                    totalRevenueEl.textContent = "0";
                    totalSoldEl.textContent = "0";
                    return;
                }

                totalRevenueEl.textContent = data.total_revenue.toLocaleString();
                totalSoldEl.textContent = data.total_sold;

                const maxRevenue = Math.max(...data.products.map(p => p.revenue));
                data.products.forEach(prod => {
                    const percent = (prod.revenue / maxRevenue) * 100;
                    container.innerHTML += `
            <div class="product-row">
              <div class="product-info">
                <span class="product-name">${prod.name}</span>
                <span class="product-sales">₱${prod.revenue.toLocaleString()} (${prod.total_sold} sold)</span>
              </div>
              <div class="product-bar">
                <div class="product-bar-fill" style="--target-width: ${percent}%;"></div>
              </div>
            </div>`;
                });
            });
    }

    filterButtons.forEach(btn => {
        btn.addEventListener("click", () => {
            filterButtons.forEach(b => b.classList.remove("active"));
            btn.classList.add("active");
            loadProductPerformance(btn.dataset.range);
        });
    });

    loadProductPerformance("7days");
});

loadProductPerformance();
