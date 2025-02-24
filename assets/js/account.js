document.addEventListener('DOMContentLoaded', function() {
    // Set default dates to today
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('paymentDate').value = today;
    document.getElementById('creditNoteDate').value = today;

    // Load initial data
    loadCustomerData();
    loadTransactions();

    // Initialize form handlers
    initializeFormHandlers();

    function loadCustomerData() {
        fetch(`../../api/customers/read.php?id=${customerId}`)
            .then(response => response.json())
            .then(customer => {
                if (customer && customer.id) {
                    updateCustomerInfo(customer);
                } else {
                    showToast('error', 'Customer not found');
                    setTimeout(() => {
                        window.location.href = '../../pages/customers/';
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Error loading customer data');
            });
    }

    function updateCustomerInfo(customer) {
        const customerInfo = document.getElementById('customerInfo');
        const balanceClass = customer.balance > 0 ? 'text-danger' : 'text-success';
        const balanceText = customer.balance > 0 ? 'Outstanding Balance' : 'Credit Balance';
        
        customerInfo.innerHTML = `
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h3>${escapeHtml(customer.name)}</h3>
                    ${customer.company_name ? `<p class="mb-1 text-muted">${escapeHtml(customer.company_name)}</p>` : ''}
                    <p class="mb-1"><i class="bi bi-phone me-2"></i>${escapeHtml(customer.phone)}</p>
                    <p class="mb-1"><i class="bi bi-geo-alt me-2"></i>${escapeHtml(customer.state)}</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted mb-1">${balanceText}</p>
                    <h2 class="${balanceClass}">₦${formatNumber(Math.abs(customer.balance))}</h2>
                </div>
            </div>
        `;
    }

    function loadTransactions() {
        fetch(`../../api/customers/read_customer_transactions.php?customer_id=${customerId}`)
            .then(response => response.json())
            .then(transactions => {
                updateTransactionsTable(transactions);
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Error loading transactions');
            });
    }

    function updateTransactionsTable(transactions) {
        const tbody = document.querySelector('#transactionsTable tbody');
        tbody.innerHTML = '';
    
        if (!transactions.length) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center">No transactions found</td></tr>`;
            return;
        }
    
        transactions.forEach(transaction => {
            const tr = document.createElement('tr');
            const amountClass = transaction.amount < 0 ? 'text-success' : 'text-danger';
            const balanceClass = transaction.running_balance > 0 ? 'text-danger' : 'text-success';
            
            tr.innerHTML = `
                <td>${formatDate(transaction.date)}</td>
                <td><span class="badge ${getTransactionBadgeClass(transaction.type)}">${escapeHtml(transaction.type)}</span></td>
                <td>${transaction.type === 'Order' && transaction.reference_id ? 
                    `<button class="btn btn-sm btn-info view-invoice-btn" data-id="${transaction.reference_id}">
                        <i class="bi bi-file-text"></i> View Invoice
                    </button>` : 
                    escapeHtml(transaction.description)}</td>
                <td class="text-end ${amountClass}">₦${formatNumber(Math.abs(transaction.amount))}</td>
                <td class="text-end ${balanceClass}">₦${formatNumber(Math.abs(transaction.running_balance))}</td>
                <td class="text-end">
                    ${transaction.can_delete ? 
                        `<button class="btn btn-sm btn-outline-danger delete-transaction" data-id="${transaction.id}" data-type="${transaction.type}">
                            <i class="bi bi-trash"></i>
                        </button>` : ''}
                </td>
            `;
            tbody.appendChild(tr);
        });
    }
    
    // Add event listener for view invoice buttons
    document.addEventListener('click', function(e) {
        const viewInvoiceBtn = e.target.closest('.view-invoice-btn');
        if (viewInvoiceBtn) {
            const orderId = viewInvoiceBtn.dataset.id;
            fetch(`../../api/customers/read_order.php?id=${orderId}`)
                .then(response => response.json())
                .then(order => {
                    if (order.success === false) {
                        throw new Error(order.message);
                    }
                    displayOrderDetails(order);
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('error', 'Error loading order details');
                });
        }
    });

    function loadOrderDetails(orderId) {
        fetch(`../../api/customers/read_order.php?id=${orderId}`)
            .then(response => response.json())
            .then(order => {
                displayOrderDetails(order);
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Error loading order details');
            });
    }
    
    function getLoadingStatusClass(status) {
        switch (status) {
            case 'Fully Loaded':
                return 'success';
            case 'Partially Loaded':
                return 'warning';
            default:
                return 'secondary';
        }
    }
    
    function displayOrderDetails(order) {
        const modal = document.getElementById('viewOrderModal');
        const details = document.getElementById('orderDetails');
        details.innerHTML = `
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Order Date:</strong> ${formatDate(order.order_date)}<br>
                    <strong>Order #:</strong> ${order.id}<br>
                    <strong>Customer:</strong> ${escapeHtml(order.customer_name)}
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="text-center">Loading Status</th>
                            <th class="text-end">Quantity</th>
                            <th class="text-end">Price</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${order.items.map(item => `
                            <tr>
                                <td>${escapeHtml(item.product_name)}</td>
                                <td class="text-center">
                                    <span class="badge bg-${getLoadingStatusClass(item.loading_status)}">
                                        ${item.loading_status}
                                    </span>
                                </td>
                                <td class="text-end">${formatNumber(item.quantity)}</td>
                                <td class="text-end">₦${formatNumber(item.price)}</td>
                                <td class="text-end">₦${formatNumber(item.quantity * item.price)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-end"><strong>Total Amount:</strong></td>
                            <td class="text-end"><strong>₦${formatNumber(order.total_amount)}</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        `;
    
        // Show modal
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
    }

    function getTransactionBadgeClass(type) {
        switch (type) {
            case 'Order':
                return 'bg-primary';
            case 'Payment':
                return 'bg-success';
            case 'Credit Note':
                return 'bg-warning text-dark';
            default:
                return 'bg-secondary';
        }
    }

    function initializeFormHandlers() {
        // Payment Form Handler
        const paymentForm = document.getElementById('paymentForm');
        paymentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Confirm payment submission
            showConfirmDialog(
                'Confirm Payment',
                `Are you sure you want to record a payment of ₦${formatNumber(this.amount.value)}?`,
                () => submitPayment(new FormData(this))
            );
        });

        // Credit Note Form Handler
        const creditNoteForm = document.getElementById('creditNoteForm');
        creditNoteForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Confirm credit note submission
            showConfirmDialog(
                'Confirm Credit Note',
                `Are you sure you want to create a credit note for ₦${formatNumber(this.amount.value)}?`,
                () => submitCreditNote(new FormData(this))
            );
        });

            // Delete Transaction Handler
            document.addEventListener('click', function(e) {
                const deleteBtn = e.target.closest('.delete-transaction');
                if (deleteBtn) {
                    const transactionId = deleteBtn.dataset.id;
                    const transactionType = deleteBtn.dataset.type;
                    
                    showConfirmDialog(
                        `Delete ${transactionType}`,
                        `Are you sure you want to delete this ${transactionType.toLowerCase()}? This will update the customer's balance.`,
                        () => deleteTransaction(transactionId)
                    );
                }
            });
    }

    function submitPayment(formData) {
        fetch('../../api/customers/record_payment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', 'Payment recorded successfully');
                document.getElementById('paymentForm').reset();
                document.getElementById('paymentDate').value = today;
                const modal = bootstrap.Modal.getInstance(document.getElementById('recordPaymentModal'));
                modal.hide();
                loadCustomerData();
                loadTransactions();
            } else {
                showToast('error', data.message || 'Error recording payment');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Error recording payment');
        });
    }

    function deleteTransaction(transactionId) {
        fetch('../../api/customers/delete_transaction.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ transaction_id: transactionId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', data.message);
                loadCustomerData();
                loadTransactions();
            } else {
                showToast('error', data.message || 'Error deleting transaction');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Error deleting transaction');
        });
    }
    
    function submitCreditNote(formData) {
        fetch('../../api/customers/create_credit_note.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', 'Credit note created successfully');
                document.getElementById('creditNoteForm').reset();
                document.getElementById('creditNoteDate').value = today;
                const modal = bootstrap.Modal.getInstance(document.getElementById('creditNoteModal'));
                modal.hide();
                loadCustomerData();
                loadTransactions();
            } else {
                showToast('error', data.message || 'Error creating credit note');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Error creating credit note');
        });
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
            maximumFractionDigits: 0,
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