let revenueChart;

async function loadRevenue(filter = 'month') {
  const response = await fetch(`revenue_report.php?filter=${filter}`);
  const data = await response.json();

  const labels = data.map(item => item.label);
  const values = data.map(item => Number(item.total));

  // Compute average revenue to color bars dynamically
  const average = values.reduce((a, b) => a + b, 0) / (values.length || 1);

  const colors = values.map(v => {
    if (v === 0) return 'rgba(180, 180, 180, 0.5)'; // gray for no revenue
    if (v >= average) return 'rgba(52, 199, 89, 0.9)'; // green above average
    return 'rgba(220, 53, 69, 0.8)'; // red below average
  });

  const ctx = document.getElementById('revenueChart').getContext('2d');

  if (revenueChart) revenueChart.destroy();

  revenueChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Revenue (₱)',
        data: values,
        backgroundColor: rgba(23, 162, 184, 1),
        borderRadius: 5,
        borderSkipped: false
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          ticks: { color: '#444', font: { size: 12 } },
          grid: { color: '#eee' },
        },
        x: {
          ticks: { color: '#444', font: { size: 12 } },
          grid: { display: false },
        }
      },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#333',
          titleColor: '#fff',
          bodyColor: '#fff',
          callbacks: {
            label: ctx => `₱${Number(ctx.raw).toLocaleString()}`
          }
        },
        annotation: {
          annotations: {
            avgLine: {
              type: 'line',
              yMin: average,
              yMax: average,
              borderColor: '#17a2b8',
              borderWidth: 1.5,
              borderDash: [5, 5],
              label: {
                enabled: true,
                content: `Avg ₱${average.toFixed(0)}`,
                position: 'start',
                backgroundColor: '#094a54ff',
                color: '#17a2b8'
              }
            }
          }
        }
      },
      animation: {
        duration: 800,
        easing: 'easeOutQuart'
      }
    },
    plugins: [Chart.registry.getPlugin('annotation')]
  });
}

// 🔄 Auto-load current month on start
loadRevenue('month');
