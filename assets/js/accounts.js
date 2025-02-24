document.addEventListener('DOMContentLoaded', function() {
    let allCustomers = [];
    let currentSort = { column: 'name', direction: 'asc' };
    
    // Initialize search and table
    const searchCustomer = document.getElementById('searchCustomer');
    searchCustomer.addEventListener('input', filterCustomers);
    
    // Initialize sorting
    document.querySelectorAll('.sortable').forEach(header => {
        header.addEventListener('click', () => {
            const column = header.dataset.sort;
            if (currentSort.column === column) {
                currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort.column = column;
                currentSort.direction = 'asc';
            }
            filterCustomers();
        });
    });

    // Load initial data
    loadCustomers();

    function loadCustomers() {
        fetch('../../api/customers/read.php')
            .then(response => response.json())
            .then(customers => {
                if (Array.isArray(customers)) {
                    allCustomers = customers;
                    updateKPIs(customers);
                    filterCustomers();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Error loading customer data');
            });
    }

    function updateKPIs(customers) {
        let totalReceivables = 0;
        let totalLiability = 0;

        customers.forEach(customer => {
            if (customer.balance > 0) {
                totalReceivables += customer.balance;
            } else {
                totalLiability += Math.abs(customer.balance);
            }
        });

        document.getElementById('totalReceivables').textContent = `₦${formatNumber(totalReceivables)}`;
        document.getElementById('totalLiability').textContent = `₦${formatNumber(totalLiability)}`;
    }

    function filterCustomers() {
        let filteredCustomers = [...allCustomers];
        
        // Apply search filter
        if (searchCustomer.value.trim()) {
            const searchTerm = searchCustomer.value.toLowerCase();
            filteredCustomers = filteredCustomers.filter(customer => 
                customer.name.toLowerCase().includes(searchTerm) ||
                (customer.company_name && customer.company_name.toLowerCase().includes(searchTerm))
            );
        }
        
        // Apply sorting
        filteredCustomers.sort((a, b) => {
            let comparison = 0;
            if (currentSort.column === 'name') {
                comparison = a.name.localeCompare(b.name);
            } else if (currentSort.column === 'balance') {
                comparison = a.balance - b.balance;
            }
            return currentSort.direction === 'asc' ? comparison : -comparison;
        });
        
        updateBalancesTable(filteredCustomers);
    }

    function updateBalancesTable(customers) {
        const tbody = document.querySelector('#balancesTable tbody');
        tbody.innerHTML = '';
        
        if (!customers.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="3" class="text-center py-4">
                        <div class="d-flex flex-column align-items-center">
                            <i class="bi bi-search text-muted" style="font-size: 2rem;"></i>
                            <h5 class="mt-2 text-muted">No customers found</h5>
                            <p class="text-muted">Try adjusting your search criteria</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        customers.forEach(customer => {
            const tr = document.createElement('tr');
            const balanceClass = customer.balance > 0 ? 'text-danger' : 'text-success';
            const balanceStatus = customer.balance > 0 ? 'Outstanding' : 'Credit';
            const statusBadgeClass = customer.balance > 0 ? 
                'bg-danger-subtle text-danger' : 'bg-success-subtle text-success';
            
            tr.innerHTML = `
                <td>
                    <a href="account.php?id=${customer.id}" class="text-decoration-none">
                        ${escapeHtml(customer.name)}
                        ${customer.company_name ? 
                            `<small class="text-muted d-block">${escapeHtml(customer.company_name)}</small>` 
                            : ''}
                    </a>
                </td>
                <td class="text-end ${balanceClass} fw-medium">
                    ₦${formatNumber(Math.abs(customer.balance))}
                </td>
                <td class="text-center">
                    <span class="badge ${statusBadgeClass} px-2 py-1">
                        ${balanceStatus}
                    </span>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    // Utility function to format numbers with commas
    function formatNumber(num) {
        return parseFloat(num).toLocaleString('en-NG', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
            useGrouping: true
        });
    }

    // Utility function to escape HTML
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