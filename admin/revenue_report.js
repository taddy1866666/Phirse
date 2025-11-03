// Revenue Report with Chart
(function() {
    const canvas = document.getElementById('adminRevenueChart');
    const ctx = canvas.getContext('2d');
    const filterButtons = document.querySelectorAll('#adminRevenueFilters button');
    
    let revenueChart = null;

    function loadRevenueReport(range = '7days') {
        // Update active button
        filterButtons.forEach(btn => {
            if (btn.dataset.range === range) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        fetch(`get_revenue_report.php?range=${range}`)
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    console.error('Revenue report error:', data.error);
                    return;
                }

                const labels = data.labels || [];
                const revenues = data.revenues || [];
                const totalRevenue = data.total || 0;
                const avgRevenue = data.average || 0;
                const change = data.change || 0;

                // Update stats
                document.getElementById('admin_totalRevenue').textContent = '₱' + totalRevenue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                document.getElementById('admin_avgRevenue').textContent = '₱' + avgRevenue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                
                const changeElement = document.getElementById('admin_change');
                changeElement.textContent = (change > 0 ? '+' : '') + change.toFixed(2) + '%';
                changeElement.style.color = change >= 0 ? '#28a745' : '#dc3545';

                // Destroy existing chart
                if (revenueChart) {
                    revenueChart.destroy();
                }

                // Create new chart
                revenueChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Revenue (₱)',
                            data: revenues,
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            pointBackgroundColor: '#667eea',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointHoverBackgroundColor: '#764ba2',
                            pointHoverBorderColor: '#fff',
                            pointHoverBorderWidth: 3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    font: {
                                        size: 13,
                                        weight: 'bold'
                                    },
                                    color: '#1a1a1a',
                                    padding: 15
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                padding: 12,
                                titleFont: {
                                    size: 14,
                                    weight: 'bold'
                                },
                                bodyFont: {
                                    size: 13
                                },
                                cornerRadius: 8,
                                displayColors: false,
                                callbacks: {
                                    label: function(context) {
                                        return '₱' + context.parsed.y.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)',
                                    drawBorder: false
                                },
                                ticks: {
                                    font: {
                                        size: 11,
                                        weight: '600'
                                    },
                                    color: '#666',
                                    callback: function(value) {
                                        return '₱' + value.toLocaleString('en-US');
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false,
                                    drawBorder: false
                                },
                                ticks: {
                                    font: {
                                        size: 11,
                                        weight: '600'
                                    },
                                    color: '#666',
                                    maxRotation: 45,
                                    minRotation: 0
                                }
                            }
                        },
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        }
                    }
                });
            })
            .catch(err => {
                console.error('Error loading revenue report:', err);
            });
    }

    // Load initial data
    loadRevenueReport('7days');

    // Add click events to filter buttons
    filterButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            loadRevenueReport(this.dataset.range);
        });
    });
})();