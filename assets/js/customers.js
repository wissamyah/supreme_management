document.addEventListener('DOMContentLoaded', function() {

    let allCustomers = []; // Add this at the top
    const searchCustomer = document.getElementById('searchCustomer');
    
    const resetCustomerSearch = document.getElementById('resetCustomerSearch');
    resetCustomerSearch.addEventListener('click', function() {
        searchCustomer.value = '';
        filterCustomers();
    });

    // Add event listener for search
    searchCustomer.addEventListener('input', filterCustomers);

    function filterCustomers() {
        let filteredCustomers = [...allCustomers];
        
        // Apply search filter
        if (searchCustomer.value.trim()) {
            const searchTerm = searchCustomer.value.toLowerCase();
            filteredCustomers = filteredCustomers.filter(customer => {
                return customer.name.toLowerCase().includes(searchTerm) ||
                       (customer.company_name && customer.company_name.toLowerCase().includes(searchTerm)) ||
                       customer.phone.includes(searchTerm) ||
                       customer.state.toLowerCase().includes(searchTerm) ||
                       customer.balance.toString().includes(searchTerm);
            });
        }
        
        updateCustomersTable(filteredCustomers);
    }

    const nigerianStates = [
        'Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa', 'Benue', 'Borno',
        'Cross River', 'Delta', 'Ebonyi', 'Edo', 'Ekiti', 'Enugu', 'FCT', 'Gombe', 'Imo',
        'Jigawa', 'Kaduna', 'Kano', 'Katsina', 'Kebbi', 'Kogi', 'Kwara', 'Lagos', 'Nasarawa',
        'Niger', 'Ogun', 'Ondo', 'Osun', 'Oyo', 'Plateau', 'Rivers', 'Sokoto', 'Taraba',
        'Yobe', 'Zamfara'
    ];

    // Initialize form submissions
    initializeForms();
    // Load initial customer data
    loadCustomers();
    // Initialize state dropdown
    populateStateDropdown();

    function populateStateDropdown() {
        const stateSelects = document.querySelectorAll('#state, #editState');
        stateSelects.forEach(select => {
            if (select) {
                nigerianStates.forEach(state => {
                    const option = document.createElement('option');
                    option.value = state;
                    option.textContent = state;
                    select.appendChild(option);
                });
            }
        });
    }

    function initializeForms() {
        // Add Customer Form
        const customerForm = document.getElementById('customerForm');
        if (customerForm) {
            customerForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                
                fetch('../../api/customers/create.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showToast('success', 'Customer created successfully');
                        customerForm.reset();
                        const modal = bootstrap.Modal.getInstance(document.getElementById('addCustomerModal'));
                        modal && modal.hide();
                        loadCustomers();
                    } else {
                        showToast('error', data.message || 'Error creating customer');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('error', 'An error occurred while creating customer');
                });
            });
        }

        // Edit Customer Form
        const editCustomerForm = document.getElementById('editCustomerForm');
        if (editCustomerForm) {
            editCustomerForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const customerData = Object.fromEntries(formData.entries());

                fetch('../../api/customers/update.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(customerData)
                })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showToast('success', 'Customer updated successfully');
                        editCustomerForm.reset();
                        const modal = bootstrap.Modal.getInstance(document.getElementById('editCustomerModal'));
                        modal && modal.hide();
                        loadCustomers();
                    } else {
                        showToast('error', data.message || 'Error updating customer');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('error', 'An error occurred while updating customer');
                });
            });
        }
    }

    function loadCustomers() {
        fetch('../../api/customers/read.php')
            .then(response => response.json())
            .then(data => {
                if (Array.isArray(data)) {
                    allCustomers = data; // Store all customers
                    filterCustomers(); // Apply filters
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Error loading customers');
            });
    }

    function updateCustomersTable(customers) {
        const tbody = document.querySelector('#customersTable tbody');
        if (!tbody) return;
    
        tbody.innerHTML = '';
        
        if (!customers.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <div class="d-flex flex-column align-items-center">
                            <i class="bi bi-people text-muted" style="font-size: 2rem;"></i>
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
            const balanceLabel = customer.balance > 0 ? 'Outstanding' : 'Credit';
            
            tr.innerHTML = `
                <td>${escapeHtml(customer.name)}</td>
                <td>${escapeHtml(customer.company_name || '-')}</td>
                <td>${escapeHtml(customer.phone)}</td>
                <td>${escapeHtml(customer.state)}</td>
                <td>
                    <a href="account.php?id=${customer.id}" 
                       class="d-inline-flex align-items-center gap-2 text-decoration-none balance-link">
                        <span class="badge ${customer.balance > 0 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success'} px-2 py-1">
                            ${balanceLabel}
                        </span>
                        <span class="${balanceClass} fw-medium">₦${formatNumber(Math.abs(customer.balance))}</span>
                    </a>
                </td>
                <td>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-primary edit-customer" data-id="${customer.id}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-customer" data-id="${customer.id}">
                            <i class="bi bi-trash"></i>
                        </button>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-secondary" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="../orders/customer.php?id=${customer.id}">
                                        <i class="bi bi-cart me-2"></i>View Orders
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="account.php?id=${customer.id}">
                                        <i class="bi bi-cash-stack me-2"></i>View Account
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
    
        // Add some CSS to handle hover states
        const style = document.createElement('style');
        style.textContent = `
            .balance-link:hover {
                background-color: rgba(0,0,0,.03);
                border-radius: 4px;
                padding: 4px 8px;
                margin: -4px -8px;
            }
            .balance-link {
                padding: 4px 8px;
                margin: -4px -8px;
                transition: background-color 0.2s ease;
            }
        `;
        document.head.appendChild(style);
    
        // Attach event listeners to new buttons
        attachButtonListeners();
    }

    function attachButtonListeners() {
        // Edit button listeners
        document.querySelectorAll('.edit-customer').forEach(button => {
            button.addEventListener('click', function() {
                const customerId = this.dataset.id;
                fetch(`../../api/customers/read.php?id=${customerId}`)
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(customer => {
                        if (customer.id) {
                            document.getElementById('editId').value = customer.id;
                            document.getElementById('editName').value = customer.name;
                            document.getElementById('editCompanyName').value = customer.company_name || '';
                            document.getElementById('editPhone').value = customer.phone;
                            document.getElementById('editState').value = customer.state;
                            
                            const editModal = new bootstrap.Modal(document.getElementById('editCustomerModal'));
                            editModal.show();
                        } else {
                            showToast('error', 'Error loading customer data');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('error', 'Error loading customer data');
                    });
            });
        });

        // Delete button listeners
        document.querySelectorAll('.delete-customer').forEach(button => {
            button.addEventListener('click', function() {
                const customerId = this.dataset.id;
                
                showConfirmDialog(
                    'Confirm Deletion',
                    'Are you sure you want to delete this customer? This action cannot be undone.',
                    () => {
                        fetch('../../api/customers/delete.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                id: customerId
                            })
                        })
                        .then(response => {
                            if (!response.ok) throw new Error('Network response was not ok');
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                showToast('success', 'Customer deleted successfully');
                                loadCustomers();
                            } else {
                                showToast('error', data.message || 'Error deleting customer');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showToast('error', 'Error deleting customer');
                        });
                    }
                );
            });
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

    // Utility function to escape HTML and prevent XSS
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

