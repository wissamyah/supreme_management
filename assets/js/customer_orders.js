document.addEventListener('DOMContentLoaded', function() {
    let allOrders = [];
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');
    const searchOrder = document.getElementById('searchOrder');
    const resetFilters = document.getElementById('resetFilters');
    const orderForm = document.getElementById('orderForm');
    const addItemBtn = document.getElementById('addItemBtn');
    const orderItems = document.getElementById('orderItems');
    const editOrderItems = document.getElementById('editOrderItems');

    // Set default dates
    const today = new Date();
    const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate());
    startDate.value = lastMonth.toISOString().split('T')[0];
    endDate.value = today.toISOString().split('T')[0];
    document.getElementById('orderDate').value = today.toISOString().split('T')[0];

    // Initialize event listeners
    startDate.addEventListener('change', filterOrders);
    endDate.addEventListener('change', filterOrders);
    searchOrder.addEventListener('input', filterOrders);
    resetFilters.addEventListener('click', resetAllFilters);

    // Load initial data
    loadCustomerDetails();
    loadCustomerOrders();

    // Add Item Button Click Handler
    addItemBtn.addEventListener('click', function() {
        addOrderItemRow(orderItems);
    });

    // Add Edit Item Button Handler
    document.getElementById('addEditItemBtn').addEventListener('click', function() {
        addOrderItemRow(editOrderItems, true);
    });

    function addOrderItemRow(container, isEdit = false) {
        const itemRow = document.createElement('div');
        itemRow.className = 'row mb-3 order-item';
        const prefix = isEdit ? 'edit_' : '';
        
        itemRow.innerHTML = `
            <div class="col-md-4">
                <select class="form-select product-select" name="${prefix}product_id[]" required>
                    <option value="">Select Product</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="number" class="form-control quantity" name="${prefix}quantity[]" min="1" placeholder="Quantity" required>
            </div>
            <div class="col-md-3">
                <input type="number" class="form-control price" name="${prefix}price[]" step="0.01" placeholder="Price" required>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger remove-item">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;

        container.appendChild(itemRow);

        // Load products into dropdown
        const productSelect = itemRow.querySelector('.product-select');
        loadProductsIntoDropdown(productSelect);

        // Add handlers for product selection
        productSelect.addEventListener('change', function() {
            handleProductSelection(this);
        });

        // Add calculation listeners
        const inputs = itemRow.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('input', () => calculateTotal(isEdit));
        });

        itemRow.querySelector('.remove-item').addEventListener('click', function() {
            itemRow.remove();
            calculateTotal(isEdit);
        });
    }

    function loadProductsIntoDropdown(selectElement, selectedProductId = null) {
        fetch('../../api/inventory/read_products.php?include_price=true')
            .then(response => response.json())
            .then(products => {
                selectElement.innerHTML = '<option value="">Select Product</option>';
                products.forEach(product => {
                    const option = document.createElement('option');
                    option.value = product.id;
                    option.textContent = `${product.name} (${product.physical_stock - product.booked_stock} available)`;
                    option.dataset.price = product.price;
                    option.dataset.availableStock = product.physical_stock - product.booked_stock;
                    if (selectedProductId && parseInt(product.id) === parseInt(selectedProductId)) {
                        option.selected = true;
                    }
                    selectElement.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error loading products:', error);
                showToast('error', 'Error loading products');
            });
    }
    
    function handleProductSelection(selectElement) {
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const itemRow = selectElement.closest('.order-item');
        const priceInput = itemRow.querySelector('.price');
        const quantityInput = itemRow.querySelector('.quantity');
    
        if (selectedOption && selectedOption.value) {
            const availableStock = parseFloat(selectedOption.dataset.availableStock) || 0;
            const defaultPrice = parseFloat(selectedOption.dataset.price) || 0;
    
            if (availableStock <= 0) {
                showToast('warning', 'This product is out of stock');
                selectElement.value = '';
                priceInput.value = '';
                quantityInput.value = '';
                return;
            }
    
            if (!priceInput.value) {
                priceInput.value = defaultPrice.toFixed(2);
            }
            quantityInput.max = availableStock;
            priceInput.readOnly = false;
        } else {
            priceInput.value = '';
            quantityInput.value = '';
            quantityInput.removeAttribute('max');
            priceInput.readOnly = true;
        }
        
        calculateTotal(selectElement.name.startsWith('edit_'));
    }


    // Calculate total amount
    function calculateTotal(isEdit = false) {
        const container = isEdit ? editOrderItems : orderItems;
        let total = 0;
        
        container.querySelectorAll('.order-item').forEach(item => {
            const quantity = parseFloat(item.querySelector('.quantity').value) || 0;
            const price = parseFloat(item.querySelector('.price').value) || 0;
            total += quantity * price;
        });

        const displayElement = document.getElementById(isEdit ? 'editTotalAmount' : 'totalAmount');
        displayElement.textContent = `₦${formatNumber(total)}`;
        return total;
    }

    // Form Submission Handlers
    orderForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const items = [];
        let isValid = true;
    
        orderItems.querySelectorAll('.order-item').forEach(item => {
            const productSelect = item.querySelector('.product-select');
            const quantity = item.querySelector('.quantity').value;
            const price = item.querySelector('.price').value;
    
            if (!productSelect.value || !quantity || !price) {
                isValid = false;
                return;
            }
    
            items.push({
                product_id: parseInt(productSelect.value),
                quantity: parseInt(quantity),
                price: parseFloat(price)
            });
        });

        if (!isValid || items.length === 0) {
            showToast('error', 'Please fill in all required fields');
            return;
        }

        const orderData = {
            customer_id: CUSTOMER_ID,
            order_date: document.getElementById('orderDate').value,
            items: items
        };

        submitOrder(orderData);
    });

    document.getElementById('editOrderForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const items = [];
        let isValid = true;
    
        editOrderItems.querySelectorAll('.order-item').forEach(item => {
            const productSelect = item.querySelector('.product-select');
            const quantity = item.querySelector('.quantity').value;
            const price = item.querySelector('.price').value;
    
            if (!productSelect.value || !quantity || !price) {
                isValid = false;
                return;
            }
    
            items.push({
                product_id: parseInt(productSelect.value),
                quantity: parseInt(quantity),
                price: parseFloat(price)
            });
        });

        if (!isValid || items.length === 0) {
            showToast('error', 'Please fill in all required fields');
            return;
        }

        const orderData = {
            id: document.getElementById('editOrderId').value,
            customer_id: CUSTOMER_ID,
            order_date: document.getElementById('editOrderDate').value,
            items: items
        };

        updateOrder(orderData);
    });

    function submitOrder(orderData) {
        fetch('../../api/customers/create_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(orderData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', 'Order created successfully');
                orderForm.reset();
                orderItems.innerHTML = '';
                document.getElementById('totalAmount').textContent = '₦0';
                const modal = bootstrap.Modal.getInstance(document.getElementById('addOrderModal'));
                modal.hide();
                loadCustomerOrders();
                loadCustomerDetails(); // Refresh balance
            } else {
                showToast('error', data.message || 'Error creating order');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Error creating order');
        });
    }

    function updateOrder(orderData) {
        fetch('../../api/customers/update_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(orderData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', 'Order updated successfully');
                const modal = bootstrap.Modal.getInstance(document.getElementById('editOrderModal'));
                modal.hide();
                loadCustomerOrders();
                loadCustomerDetails(); // Refresh balance
            } else {
                showToast('error', data.message || 'Error updating order');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Error updating order');
        });
    }

    function loadCustomerDetails() {
        fetch(`../../api/customers/read.php?id=${CUSTOMER_ID}`)
            .then(response => response.json())
            .then(customer => {
                if (customer.id) {
                    document.querySelectorAll('.customer-name').forEach(el => 
                        el.textContent = customer.name);
                    document.querySelector('.customer-company').textContent = 
                        customer.company_name || 'No Company';
                    document.querySelector('.customer-phone').textContent = customer.phone;
                    document.querySelector('.customer-state').textContent = customer.state;
                    
                    // Modified balance display to show negative values in red
                    const balanceElement = document.querySelector('.customer-balance');
                    const balance = -customer.balance; // Negate the balance to show positive for credit
                    
                    if (balance < 0) {
                        balanceElement.classList.add('text-danger');
                        balanceElement.textContent = `₦${formatNumber(Math.abs(balance))}`;
                    } else {
                        balanceElement.classList.remove('text-danger');
                        balanceElement.textContent = `₦${formatNumber(balance)}`;
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Error loading customer details');
            });
    }

    function loadCustomerOrders() {
        fetch(`../../api/customers/read_customer_orders.php?customer_id=${CUSTOMER_ID}`)
            .then(response => response.json())
            .then(orders => {
                allOrders = orders;
                filterOrders();
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Error loading orders');
            });
    }

    function filterOrders() {
        let filteredOrders = [...allOrders];
        
        if (startDate.value) {
            filteredOrders = filteredOrders.filter(order => 
                new Date(order.order_date) >= new Date(startDate.value)
            );
        }
        if (endDate.value) {
            filteredOrders = filteredOrders.filter(order => 
                new Date(order.order_date) <= new Date(endDate.value)
            );
        }
    
        if (searchOrder.value.trim()) {
            const searchTerm = searchOrder.value.toLowerCase();
            filteredOrders = filteredOrders.filter(order => {
                return order.order_date.includes(searchTerm) ||
                       order.total_amount.toString().includes(searchTerm) ||
                       order.items_array.some(item => 
                           item.product.toLowerCase().includes(searchTerm) ||
                           item.quantity.toString().includes(searchTerm) ||
                           item.price.toString().includes(searchTerm)
                       );
            });
        }
    
        updateOrdersTable(filteredOrders);
    }

    function resetAllFilters() {
        const today = new Date();
        const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate());
        startDate.value = lastMonth.toISOString().split('T')[0];
        endDate.value = today.toISOString().split('T')[0];
        searchOrder.value = '';
        filterOrders();
    }

    function updateOrdersTable(orders) {
        const tbody = document.querySelector('#customerOrdersTable tbody');
        tbody.innerHTML = '';
    
        if (!orders.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center py-4">
                        <div class="d-flex flex-column align-items-center">
                            <i class="bi bi-inbox text-muted" style="font-size: 2rem;"></i>
                            <h5 class="mt-2 text-muted">No orders found</h5>
                            <p class="text-muted">Try adjusting your search or filters</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }
    
        orders.forEach(order => {
            const tr = document.createElement('tr');
            const itemsList = order.items_array.map(item => 
                `<span class="badge bg-light text-dark me-1">
                    ${escapeHtml(item.product)} (${item.quantity} × ₦${formatNumber(item.price)})
                </span>`
            ).join('');
    
            tr.innerHTML = `
                <td>${formatDate(order.order_date)}</td>
                <td class="text-wrap">${itemsList}</td>
                <td>₦${formatNumber(order.total_amount)}</td>
                <td>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-info view-order" data-id="${order.id}">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-warning view-bookings">
                            <i class="bi bi-calendar2-check"></i>
                        </button>
                        <button class="btn btn-sm btn-primary edit-order" data-id="${order.id}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-order" data-id="${order.id}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
    
        attachOrderListeners();
    }

    document.getElementById('viewAllBookings').addEventListener('click', function() {
        loadAllCustomerBookings();
        const modal = new bootstrap.Modal(document.getElementById('viewBookingsModal'));
        modal.show();
    });

    function attachOrderListeners() {
        // View order details
        document.querySelectorAll('.view-order').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.dataset.id;
                loadOrderDetails(orderId);
            });
        });
    
        // Add booking view handler
        document.querySelectorAll('.view-bookings').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.closest('tr').querySelector('.view-order').dataset.id;
                loadCustomerBookings(orderId);
                const modal = new bootstrap.Modal(document.getElementById('viewBookingsModal'));
                modal.show();
            });
        });
    
        // Edit order
        document.querySelectorAll('.edit-order').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.dataset.id;
                loadOrderForEdit(orderId);
            });
        });
    
        // Delete order
        document.querySelectorAll('.delete-order').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.dataset.id;
                confirmDeleteOrder(orderId);
            });
        });

        // booking view handler for individual orders
        document.querySelectorAll('.view-bookings').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.closest('tr').querySelector('.view-order').dataset.id;
                loadOrderBookings(orderId);
                const modal = new bootstrap.Modal(document.getElementById('viewBookingsModal'));
                modal.show();
            });
        });
    }

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

    function loadOrderForEdit(orderId) {
        fetch(`../../api/customers/read_order.php?id=${orderId}`)
            .then(response => response.json())
            .then(order => {
                populateEditModal(order);
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Error loading order for edit');
            });
    }

    function populateEditModal(order) {
        document.getElementById('editOrderId').value = order.id;
        document.getElementById('editOrderDate').value = order.order_date;
        
        const editOrderItems = document.getElementById('editOrderItems');
        editOrderItems.innerHTML = '';
        
        order.items.forEach(item => {
            const itemRow = document.createElement('div');
            itemRow.className = 'row mb-3 order-item';
            itemRow.innerHTML = `
                <div class="col-md-4">
                    <select class="form-select product-select" name="edit_product_id[]" required>
                        <option value="">Select Product</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="number" class="form-control quantity" name="edit_quantity[]" min="1" value="${item.quantity}" required>
                </div>
                <div class="col-md-3">
                    <input type="number" class="form-control price" name="edit_price[]" step="0.01" value="${item.price}" required>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger remove-item">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            `;
            editOrderItems.appendChild(itemRow);
    
            // Load products and select current product
            const productSelect = itemRow.querySelector('.product-select');
            loadProductsIntoDropdown(productSelect, item.product_id);
    
            // Add handlers
            productSelect.addEventListener('change', function() {
                handleProductSelection(this);
            });
    
            const inputs = itemRow.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('input', () => calculateTotal(true));
            });
    
            itemRow.querySelector('.remove-item').addEventListener('click', function() {
                itemRow.remove();
                calculateTotal(true);
            });
        });
    
        calculateTotal(true);
        const editModal = new bootstrap.Modal(document.getElementById('editOrderModal'));
        editModal.show();
    }

    function confirmDeleteOrder(orderId) {
        showConfirmDialog(
            'Confirm Deletion',
            'Are you sure you want to delete this order? This will update the customer\'s balance.',
            () => deleteOrder(orderId)
        );
    }

    function deleteOrder(orderId) {
        fetch('../../api/customers/delete_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: orderId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', 'Order deleted successfully');
                loadCustomerOrders();
                loadCustomerDetails(); // Refresh balance
            } else {
                showToast('error', data.message || 'Error deleting order');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Error deleting order');
        });
    }

    function displayOrderDetails(order) {
        const details = document.getElementById('orderDetails');
        details.innerHTML = `
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Order Date:</strong> ${formatDate(order.order_date)}
                </div>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    ${order.items.map(item => `
                        <tr>
                            <td>${escapeHtml(item.product_name)}</td>
                            <td>${item.quantity}</td>
                            <td>₦${formatNumber(item.price)}</td>
                            <td>₦${formatNumber(item.quantity * item.price)}</td>
                        </tr>
                    `).join('')}
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                        <td>₦${formatNumber(order.total_amount)}</td>
                    </tr>
                </tfoot>
            </table>
        `;

        const modal = new bootstrap.Modal(document.getElementById('viewOrderModal'));
        modal.show();
    }
    function loadCustomerBookings(orderId) {
        fetch(`../../api/customers/read_customer_bookings.php?order_id=${orderId}`)
            .then(response => response.json())
            .then(bookings => {
                displayBookings(bookings, orderId);
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Error loading bookings');
            });
    }
    
    function loadAllCustomerBookings() {
        fetch(`../../api/customers/read_customer_bookings.php?customer_id=${CUSTOMER_ID}`)
            .then(response => response.json())
            .then(bookings => {
                displayBookings(bookings);
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Error loading bookings');
            });
    }
    
    // Function to load order-specific bookings
    function loadOrderBookings(orderId) {
        fetch(`../../api/customers/read_customer_bookings.php?order_id=${orderId}`)
            .then(response => response.json())
            .then(bookings => {
                displayBookings(bookings, orderId);
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Error loading bookings');
            });
    }
    
    // Updated display function to handle both cases
    function displayBookings(bookings, orderId = null) {
        const tbody = document.querySelector('#bookingsTable tbody');
        const modalTitle = document.querySelector('#viewBookingsModal .modal-title');
        tbody.innerHTML = '';
    
        // Update modal title based on context
        modalTitle.textContent = orderId ? 
            `Order #${orderId} - Loading Status` : 
            'All Customer Bookings';
    
        if (!bookings.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <div class="d-flex flex-column align-items-center">
                            <i class="bi bi-calendar-x text-muted" style="font-size: 2rem;"></i>
                            <h5 class="mt-2 text-muted">No bookings found</h5>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }
    
        bookings.forEach(booking => {
            const statusClass = getLoadingStatusClass(booking.status);
            
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${formatDate(booking.date)}</td>
                <td>${escapeHtml(booking.product_name)}</td>
                <td><span class="badge bg-${statusClass}">${booking.status}</span></td>
                <td class="text-end">${formatQuantity(booking.booked_quantity)}</td>
                <td class="text-end">${formatQuantity(booking.loaded_quantity)}</td>
                <td class="text-end">${formatQuantity(booking.remaining)}</td>
            `;
            tbody.appendChild(tr);
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

    function formatQuantity(num) {
        return parseFloat(num).toLocaleString('en-NG', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
            useGrouping: true
        });
    }

    // Utility functions
    function formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('en-GB', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    function formatNumber(num) {
        // Fixed formatting function to avoid double currency symbols
        return Math.abs(num).toLocaleString('en-NG', {
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