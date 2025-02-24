document.addEventListener('DOMContentLoaded', function() {
    let productionTrendChart = null;
    let categoryPieChart = null;
    const reportForm = document.getElementById('reportForm');
    const printBtn = document.getElementById('printReport');
    const exportBtn = document.getElementById('exportExcel');

    // Set default dates (last 30 days)
    const today = new Date();
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(today.getDate() - 30);
    document.getElementById('startDate').value = thirtyDaysAgo.toISOString().split('T')[0];
    document.getElementById('endDate').value = today.toISOString().split('T')[0];

    // Initialize event listeners
    reportForm.addEventListener('submit', handleReportSubmission);
    printBtn.addEventListener('click', handlePrint);
    exportBtn.addEventListener('click', handleExport);

    // Load products for filter dropdown
    loadProducts();
    // Load initial report data
    loadReportData();

    function loadProducts() {
        fetch('../../api/inventory/read_products.php')
            .then(response => response.json())
            .then(products => {
                if (Array.isArray(products)) {
                    const productFilter = document.getElementById('productFilter');
                    products.forEach(product => {
                        const option = document.createElement('option');
                        option.value = product.id;
                        option.textContent = product.name;
                        productFilter.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Error loading products');
            });
    }

    function handleReportSubmission(e) {
        e.preventDefault();
        loadReportData();
    }

    function loadReportData() {
        const formData = new FormData(reportForm);
        const params = new URLSearchParams(formData);

        fetch(`../../api/inventory/get_production_report.php?${params}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateDashboard(data);
                    updateCharts(data);
                    updateDetailedTable(data.details);
                } else {
                    showToast('error', data.message || 'Error loading report data');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Error loading report data');
            });
    }

    function updateDashboard(data) {
        document.getElementById('totalProduction').textContent = 
            formatNumber(data.summary.total) + ' Bags';
        document.getElementById('headRiceProduction').textContent = 
            formatNumber(data.summary.head_rice) + ' Bags';
        document.getElementById('byProductProduction').textContent = 
            formatNumber(data.summary.by_product) + ' Bags';
    }

    function updateCharts(data) {
        // Production Trend Chart
        if (productionTrendChart) {
            productionTrendChart.destroy();
        }

        const trendCtx = document.getElementById('productionTrendChart').getContext('2d');
        productionTrendChart = new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: data.trend.dates,
                datasets: [{
                    label: 'Daily Production',
                    data: data.trend.quantities,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Quantity (Bags)'
                        }
                    }
                }
            }
        });

        // Category Pie Chart
        if (categoryPieChart) {
            categoryPieChart.destroy();
        }

        const pieCtx = document.getElementById('categoryPieChart').getContext('2d');
        categoryPieChart = new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: ['Head Rice', 'By-product'],
                datasets: [{
                    data: [data.summary.head_rice, data.summary.by_product],
                    backgroundColor: [
                        'rgb(54, 162, 235)',
                        'rgb(255, 99, 132)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
    }

    function updateDetailedTable(details) {
        const tbody = document.querySelector('#detailedReportTable tbody');
        tbody.innerHTML = '';
        let runningTotal = 0;

        if (!details.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-4">
                        <div class="d-flex flex-column align-items-center">
                            <i class="bi bi-inbox text-muted" style="font-size: 2rem;"></i>
                            <h5 class="mt-2 text-muted">No production records found</h5>
                            <p class="text-muted">Try adjusting your filters</p>
                        </div>
                    </td>
                </tr>
            `;
            document.getElementById('tableTotalQuantity').textContent = '0 Bags';
            return;
        }

        let totalQuantity = 0;
        details.forEach(record => {
            runningTotal += parseFloat(record.quantity);
            totalQuantity += parseFloat(record.quantity);
            
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${formatDate(record.production_date)}</td>
                <td>${escapeHtml(record.product_name)}</td>
                <td>
                    <span class="badge bg-${record.category === 'Head Rice' ? 'primary' : 'secondary'}">
                        ${escapeHtml(record.category)}
                    </span>
                </td>
                <td class="text-end">${formatNumber(record.quantity)}</td>
                <td class="text-end">${formatNumber(runningTotal)}</td>
            `;
            tbody.appendChild(tr);
        });

        document.getElementById('tableTotalQuantity').textContent = 
            formatNumber(totalQuantity) + ' Bags';
    }

    function handlePrint() {
        // Update print area content
        document.getElementById('printDateRange').textContent = 
            `Period: ${formatDate(document.getElementById('startDate').value)} to ${formatDate(document.getElementById('endDate').value)}`;
        
        const filters = [];
        const product = document.getElementById('productFilter');
        const category = document.getElementById('categoryFilter');
        
        if (product.value) filters.push(`Product: ${product.options[product.selectedIndex].text}`);
        if (category.value) filters.push(`Category: ${category.value}`);
        
        document.getElementById('printFilters').textContent = filters.join(' | ');
        document.getElementById('printGeneratedDate').textContent = new Date().toLocaleString('en-NG');

        // Clone table for printing
        const printableTable = document.getElementById('detailedReportTable').cloneNode(true);
        document.getElementById('printableTable').innerHTML = '';
        document.getElementById('printableTable').appendChild(printableTable);

        // Print
        window.print();
    }

    function handleExport() {
        const wb = XLSX.utils.book_new();
        
        // Get the table and convert to worksheet
        const table = document.getElementById('detailedReportTable');
        const ws = XLSX.utils.table_to_sheet(table);
        
        // Add the worksheet to the workbook
        XLSX.utils.book_append_sheet(wb, ws, 'Production Report');
        
        // Generate filename with current date
        const fileName = `Production_Report_${new Date().toISOString().split('T')[0]}.xlsx`;
        
        // Save the file
        XLSX.writeFile(wb, fileName);
    }

    // Utility Functions
    function formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('en-NG', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    function formatNumber(num) {
        return parseFloat(num).toLocaleString('en-NG', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
            useGrouping: true
        });
    }

    function escapeHtml(unsafe) {
        if (!unsafe) return '';
        return unsafe
            .toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
});