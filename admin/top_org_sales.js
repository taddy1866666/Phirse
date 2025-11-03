// Top Organization Sales with Professional Bar Chart
(function() {
    const container = document.getElementById('adminTopOrgsContainer');
    const filterSelect = document.getElementById('adminTopOrgsFilter');
    let chart = null;

    function loadTopOrgs(range = 'current') {
        container.innerHTML = `<div style="display: flex; align-items: center; justify-content: center; height: 400px;">
            <p style="color:#718096;">Loading...</p>
        </div>`;

        fetch(`get_top_orgs.php?range=${range}`)
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    container.innerHTML = `<div style="display: flex; align-items: center; justify-content: center; height: 400px;">
                        <p style="color:#dc2626;">Error: ${data.error}</p>
                    </div>`;
                    return;
                }

                if (!data || data.length === 0) {
                    container.innerHTML = `<div style="display: flex; align-items: center; justify-content: center; height: 400px;">
                        <p style="color:#718096;">No organization sales data available.</p>
                    </div>`;
                    return;
                }

                // Prepare data for chart
                const labels = data.map(org => org.organization || 'Unknown');
                const sales = data.map(org => parseFloat(org.total_sales));
                
                // Create canvas if it doesn't exist
                if (!document.getElementById('orgsChart')) {
                    container.innerHTML = '<canvas id="orgsChart" style="max-height: 400px;"></canvas>';
                }

                const ctx = document.getElementById('orgsChart').getContext('2d');

                // Destroy existing chart if it exists
                if (chart) {
                    chart.destroy();
                }

                // Create new chart
                chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Total Sales (₱)',
                            data: sales,
                            backgroundColor: [
                                'rgba(251, 191, 36, 0.8)',    // Gold for rank 1
                                'rgba(148, 163, 184, 0.8)',   // Silver for rank 2
                                'rgba(251, 146, 60, 0.8)',    // Bronze for rank 3
                                'rgba(102, 126, 234, 0.6)',   // Purple for others
                                'rgba(102, 126, 234, 0.5)',
                                'rgba(102, 126, 234, 0.4)',
                                'rgba(102, 126, 234, 0.3)',
                                'rgba(102, 126, 234, 0.25)',
                                'rgba(102, 126, 234, 0.2)',
                                'rgba(102, 126, 234, 0.15)'
                            ],
                            borderColor: [
                                'rgba(251, 191, 36, 1)',
                                'rgba(148, 163, 184, 1)',
                                'rgba(251, 146, 60, 1)',
                                'rgba(102, 126, 234, 1)',
                                'rgba(102, 126, 234, 1)',
                                'rgba(102, 126, 234, 1)',
                                'rgba(102, 126, 234, 1)',
                                'rgba(102, 126, 234, 1)',
                                'rgba(102, 126, 234, 1)',
                                'rgba(102, 126, 234, 1)'
                            ],
                            borderWidth: 2,
                            borderRadius: 8,
                            hoverBackgroundColor: [
                                'rgba(251, 191, 36, 1)',
                                'rgba(148, 163, 184, 1)',
                                'rgba(251, 146, 60, 1)',
                                'rgba(102, 126, 234, 0.8)',
                                'rgba(102, 126, 234, 0.7)',
                                'rgba(102, 126, 234, 0.6)',
                                'rgba(102, 126, 234, 0.5)',
                                'rgba(102, 126, 234, 0.45)',
                                'rgba(102, 126, 234, 0.4)',
                                'rgba(102, 126, 234, 0.35)'
                            ],
                            hoverBorderWidth: 3
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                padding: 12,
                                titleFont: { size: 14, weight: 'bold' },
                                bodyFont: { size: 13 },
                                cornerRadius: 8,
                                displayColors: false,
                                callbacks: {
                                    label: function(context) {
                                        return '₱' + context.parsed.x.toLocaleString('en-US', {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2
                                        });
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                grid: {
                                    color: '#f1f5f9',
                                    drawBorder: false
                                },
                                ticks: {
                                    font: { size: 12, weight: '600' },
                                    color: '#718096',
                                    callback: function(value) {
                                        return '₱' + value.toLocaleString('en-US');
                                    }
                                }
                            },
                            y: {
                                grid: {
                                    display: false,
                                    drawBorder: false
                                },
                                ticks: {
                                    font: { size: 13, weight: '600' },
                                    color: '#1a202c',
                                    padding: 12
                                }
                            }
                        }
                    }
                });
            })
            .catch(err => {
                console.error('Error loading top orgs:', err);
                container.innerHTML = `<div style="display: flex; align-items: center; justify-content: center; height: 400px;">
                    <p style="color:#dc2626;">Failed to load data.</p>
                </div>`;
            });
    }

    // Load on page load
    loadTopOrgs('current');

    // Filter change event
    filterSelect.addEventListener('change', function() {
        loadTopOrgs(this.value);
    });
})();