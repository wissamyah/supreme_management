document.addEventListener('DOMContentLoaded', function() {
    let allOrders = [];
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');
    const searchOrder = document.getElementById('searchOrder');
    const resetFilters = document.getElementById('resetFilters');
    const orderForm = document.getElementById('orderForm');
    const addItemBtn = document.getElementById('addItemBtn');
    const orderItems = document.getElementById('orderItems');
    const customerId = document.getElementById('customerId');
    const orderDate = document.getElementById('orderDate');

    // Set default dates
    const today = new Date();
    const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate());
    startDate.value = lastMonth.toISOString().split('T')[0];
    endDate.value = today.toISOString().split('T')[0];
    orderDate.value = today.toISOString().split('T')[0];

    // Add filter event listeners
    startDate.addEventListener('change', filterOrders);
    endDate.addEventListener('change', filterOrders);
    searchOrder.addEventListener('input', filterOrders);
    resetFilters.addEventListener('click', resetAllFilters);

    // Initialize everything else
    loadCustomers();
    loadOrders();

    document.addEventListener('click', function(e) {
        const editButton = e.target.closest('.edit-order');
        if (editButton) {
            const orderId = editButton.dataset.id;
            loadOrderForEdit(orderId);
        }
    });

    function loadCustomers() {
        fetch('../../api/customers/read.php')
            .then(response => response.json())
            .then(customers => {
                const mainDropdown = document.getElementById('customerId');
                const editDropdown = document.getElementById('editCustomerId');
                
                [mainDropdown, editDropdown].forEach(dropdown => {
                    if (dropdown) {
                        dropdown.innerHTML = '<option value="">Select Customer</option>';
                        customers.forEach(customer => {
                            const option = document.createElement('option');
                            option.value = customer.id;
                            option.textContent = `${customer.name} ${customer.company_name ? `(${customer.company_name})` : ''}`;
                            dropdown.appendChild(option);
                        });
                    }
                });
            })
            .catch(error => {
                console.error('Error loading customers:', error);
                showToast('error', 'Error loading customers');
            });
    }

    // Add new item row
    addItemBtn.addEventListener('click', function() {
        addOrderItemRow();
    });

    function submitOrder(orderData) {
        // Validate each item has required fields
        const validatedItems = orderData.items.map(item => ({
            product_id: parseInt(item.product_id),
            quantity: parseInt(item.quantity),
            price: parseFloat(item.price)
        }));
    
        const payload = {
            customer_id: parseInt(orderData.customer_id),
            order_date: orderData.order_date,
            items: validatedItems
        };
    
        fetch('../../api/customers/create_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => Promise.reject(err));
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showToast('success', 'Order created successfully');
                orderForm.reset();
                orderItems.innerHTML = '';
                document.getElementById('totalAmount').textContent = '₦0.00';
                addOrderItemRow();
                const modal = bootstrap.Modal.getInstance(document.getElementById('addOrderModal'));
                modal.hide();
                loadOrders();
            } else {
                showToast('error', data.message || 'Error creating order');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', error.message || 'Error creating order');
        });
    }

    // Calculate total amount
    function calculateTotal() {
        let total = 0;
        document.querySelectorAll('.order-item').forEach(item => {
            const quantity = parseFloat(item.querySelector('[name="quantity[]"]').value) || 0;
            const price = parseFloat(item.querySelector('[name="price[]"]').value) || 0;
            total += quantity * price;
        });

        document.getElementById('totalAmount').textContent = `₦${formatNumber(total)}`;
        return total;
    }

    // Handle form submission
    orderForm.addEventListener('submit', function(e) {
        e.preventDefault();
    
        const items = [];
        let isValid = true;
    
        document.querySelectorAll('.order-item').forEach(item => {
            const productSelect = item.querySelector('.product-select');
            const quantityInput = item.querySelector('.quantity');
            const priceInput = item.querySelector('.price');
            
            if (!productSelect.value || !quantityInput.value || !priceInput.value) {
                isValid = false;
                return;
            }
    
            items.push({
                product_id: parseInt(productSelect.value),
                quantity: parseInt(quantityInput.value),
                price: parseFloat(priceInput.value)
            });
        });
    
        if (!isValid || items.length === 0) {
            showToast('error', 'Please fill in all required fields');
            return;
        }
    
        if (!customerId.value) {
            showToast('error', 'Please select a customer');
            return;
        }
    
        const orderData = {
            customer_id: parseInt(customerId.value),
            order_date: orderDate.value,
            items: items
        };
    
        submitOrder(orderData);
    });

    // Add edit item button handler
    document.getElementById('addEditItemBtn').addEventListener('click', function() {
        const itemRow = document.createElement('div');
        itemRow.className = 'row mb-3 edit-order-item';
        itemRow.innerHTML = `
            <div class="col-md-4">
                <select class="form-select product-select" name="edit_product_id[]" required>
                    <option value="">Select Product</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="number" class="form-control edit-quantity" name="edit_quantity[]" min="1" required>
            </div>
            <div class="col-md-3">
                <input type="number" class="form-control edit-price" name="edit_price[]" step="0.01" required>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger remove-edit-item">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
        
        document.getElementById('editOrderItems').appendChild(itemRow);
        
        const productSelect = itemRow.querySelector('.product-select');
        fetch('../../api/inventory/read_products.php?include_price=true')
            .then(response => response.json())
            .then(products => {
                products.forEach(product => {
                    const option = document.createElement('option');
                    option.value = product.id;
                    option.textContent = `${product.name} (${product.physical_stock - product.booked_stock} available)`;
                    option.dataset.price = product.price;
                    productSelect.appendChild(option);
                });
            });
    
        const inputs = itemRow.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('input', calculateEditTotal);
        });
    
        itemRow.querySelector('.remove-edit-item').addEventListener('click', function() {
            itemRow.remove();
            calculateEditTotal();
        });
    });

    // Edit order form submission
    document.getElementById('editOrderForm').addEventListener('submit', function(e) {
        e.preventDefault();
    
        const items = [];
        let isValid = true;
    
        document.querySelectorAll('.edit-order-item').forEach(item => {
            const productSelect = item.querySelector('.product-select');
            const quantity = item.querySelector('.edit-quantity').value;
            const price = item.querySelector('.edit-price').value;
    
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
            customer_id: document.getElementById('editCustomerId').value,
            order_date: document.getElementById('editOrderDate').value,
            items: items
        };
    
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
                loadOrders();
            } else {
                showToast('error', data.message || 'Error updating order');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Error updating order');
        });
    });

    function loadOrders() {
        fetch('../../api/customers/read_orders.php')
            .then(response => response.json())
            .then(orders => {
                allOrders = orders; // Store all orders
                filterOrders(); // Apply filters
            })
            .catch(error => {
                console.error('Error loading orders:', error);
                showToast('error', 'Error loading orders');
            });
    }

    function filterOrders() {
        let filteredOrders = [...allOrders];
        
        // Apply date filters
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
    
        // Apply search filter
        if (searchOrder.value.trim()) {
            const searchTerm = searchOrder.value.toLowerCase();
            filteredOrders = filteredOrders.filter(order => {
                return order.order_date.includes(searchTerm) ||
                       order.display_name.toLowerCase().includes(searchTerm) ||
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
        const tbody = document.querySelector('#ordersTable tbody');
        tbody.innerHTML = '';
        
        if (!orders.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-4">
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
            const itemsList = order.items_array.map(item => `
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-light text-dark">
                        ${escapeHtml(item.product)} (${item.quantity} × ₦${formatNumber(item.price)})
                    </span>
                    <span class="badge bg-${getLoadingStatusClass(item.loading_status)}">
                        ${item.loading_status}
                    </span>
                </div>
            `).join('');
    
            tr.innerHTML = `
                <td>${new Date(order.order_date).toLocaleDateString('en-GB')}</td>
                <td>${escapeHtml(order.display_name)}</td>
                <td class="text-wrap">${itemsList}</td>
                <td>₦${formatNumber(order.total_amount)}</td>
                <td>
                    <button class="btn btn-sm btn-info view-order" data-id="${order.id}">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-primary edit-order" data-id="${order.id}">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-order" data-id="${order.id}">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    
        attachOrderListeners();
    }

    // Event listener for View All Bookings button
document.getElementById('viewAllBookings').addEventListener('click', function() {
    loadAllBookings();
    const modal = new bootstrap.Modal(document.getElementById('viewBookingsModal'));
    modal.show();
});

function loadAllBookings() {
    fetch('../../api/customers/read_all_bookings.php')
        .then(response => response.json())
        .then(bookings => {
            displayBookings(bookings);
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Error loading bookings');
        });
}

function displayBookings(bookings) {
    const tbody = document.querySelector('#bookingsTable tbody');
    tbody.innerHTML = '';

    if (!bookings.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4">
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
            <td>${escapeHtml(booking.customer_display_name)}</td>
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

    function attachOrderListeners() {
        // View order details
        document.querySelectorAll('.view-order').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.dataset.id;
                fetch(`../../api/customers/read_order.php?id=${orderId}`)
                    .then(response => response.json())
                    .then(order => {
                        displayOrderDetails(order);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('error', 'Error loading order details');
                    });
            });
        });

        // Delete order
        document.querySelectorAll('.delete-order').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.dataset.id;
                showConfirmDialog(
                    'Confirm Deletion',
                    'Are you sure you want to delete this order? This will update the customer\'s balance.',
                    () => deleteOrder(orderId)
                );
            });
        });
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
                loadOrders();
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
        if (!order || typeof order !== 'object') {
            showToast('error', 'Invalid order data');
            return;
        }
    
        const details = document.getElementById('orderDetails');
        details.innerHTML = `
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Order Date:</strong> ${formatDate(order.order_date)}
                </div>
                <div class="col-md-6">
                    <strong>Customer:</strong> ${escapeHtml(order.customer_name)}
                </div>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Loading Status</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    ${order.items && order.items.length > 0 ? order.items.map(item => `
                        <tr>
                            <td>${escapeHtml(item.product_name)}</td>
                            <td>${formatNumber(item.quantity)}</td>
                            <td>₦${formatNumber(item.price)}</td>
                            <td><span class="badge bg-${getLoadingStatusClass(item.loading_status)}">${item.loading_status}</span></td>
                            <td>₦${formatNumber(item.quantity * item.price)}</td>
                        </tr>
                    `).join('') : '<tr><td colspan="5" class="text-center">No items found</td></tr>'}
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-end"><strong>Total:</strong></td>
                        <td>₦${formatNumber(order.total_amount)}</td>
                    </tr>
                </tfoot>
            </table>
        `;
    
        const modal = new bootstrap.Modal(document.getElementById('viewOrderModal'));
        modal.show();
    }

    // Edit Order Modal
    function loadOrderForEdit(orderId) {
        fetch(`../../api/customers/read_order.php?id=${orderId}`)
            .then(response => response.json())
            .then(order => {
                if (!order || order.success === false) {
                    throw new Error(order.message || 'Failed to load order');
                }
    
                // Populate main fields
                document.getElementById('editOrderId').value = order.id;
                document.getElementById('editOrderDate').value = order.order_date;
                document.getElementById('editCustomerId').value = order.customer_id;
                
                const editOrderItems = document.getElementById('editOrderItems');
                editOrderItems.innerHTML = '';
                
                if (order.items && order.items.length > 0) {
                    order.items.forEach(item => {
                        const itemRow = document.createElement('div');
                        itemRow.className = 'row mb-3 edit-order-item';
                        itemRow.innerHTML = `
                            <div class="col-md-4">
                                <select class="form-select product-select" name="edit_product_id[]" required>
                                    <option value="">Select Product</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="number" class="form-control edit-quantity" 
                                    name="edit_quantity[]" min="1" value="${item.quantity}" required>
                            </div>
                            <div class="col-md-3">
                                <input type="number" class="form-control edit-price" 
                                    name="edit_price[]" step="0.01" value="${item.price}" required readonly>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-danger remove-edit-item">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        `;
                        editOrderItems.appendChild(itemRow);
    
                        // Load products and select current product
                        const productSelect = itemRow.querySelector('.product-select');
                        loadProductsIntoDropdown(productSelect, item.product_id);
    
                        // Add event listeners
                        const inputs = itemRow.querySelectorAll('input');
                        inputs.forEach(input => {
                            input.addEventListener('input', calculateEditTotal);
                        });
    
                        itemRow.querySelector('.remove-edit-item').addEventListener('click', function() {
                            itemRow.remove();
                            calculateEditTotal();
                        });
                    });
                }
    
                calculateEditTotal();
                const editModal = new bootstrap.Modal(document.getElementById('editOrderModal'));
                editModal.show();
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Error loading order for edit');
            });
    }

    function populateEditModal(order) {
        document.getElementById('editOrderId').value = order.id;
        document.getElementById('editOrderDate').value = order.order_date;
        document.getElementById('editCustomerId').value = order.customer_id;
        
        const editOrderItems = document.getElementById('editOrderItems');
        editOrderItems.innerHTML = '';
        
        if (order.items && Array.isArray(order.items)) {
            order.items.forEach(item => {
                const itemRow = document.createElement('div');
                itemRow.className = 'row mb-3 edit-order-item';
                itemRow.innerHTML = `
                    <div class="col-md-4">
                        <select class="form-select product-select" name="edit_product_id[]" required>
                            <option value="">Select Product</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="number" class="form-control edit-quantity" name="edit_quantity[]" min="1" value="${item.quantity}" required>
                    </div>
                    <div class="col-md-3">
                        <input type="number" class="form-control edit-price" name="edit_price[]" value="${item.price}" readonly>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger remove-edit-item">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                `;
                editOrderItems.appendChild(itemRow);
        
                const productSelect = itemRow.querySelector('.product-select');
                loadProductsIntoDropdown(productSelect, item.product_id);
        
                const inputs = itemRow.querySelectorAll('input');
                inputs.forEach(input => {
                    input.addEventListener('input', calculateEditTotal);
                });
        
                itemRow.querySelector('.remove-edit-item').addEventListener('click', function() {
                    itemRow.remove();
                    calculateEditTotal();
                });
            });
        }
        
        calculateEditTotal();
        const editModal = new bootstrap.Modal(document.getElementById('editOrderModal'));
        editModal.show();
    }

    function handleProductSelection(selectElement) {
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const itemRow = selectElement.closest('.order-item');
        const priceInput = itemRow.querySelector('.price');
        const quantityInput = itemRow.querySelector('.quantity');
    
        if (selectedOption && selectedOption.value) {
            const availableStock = parseFloat(selectedOption.dataset.availableStock) || 0;
            const price = parseFloat(selectedOption.dataset.price) || 0;
    
            if (availableStock <= 0) {
                showToast('warning', 'This product is out of stock');
                selectElement.value = '';
                priceInput.value = '';
                quantityInput.value = '';
                return;
            }
    
            priceInput.value = price;
            quantityInput.max = availableStock;
            // Don't set default quantity
        } else {
            priceInput.value = '';
            quantityInput.value = '';
            quantityInput.removeAttribute('max');
        }
        
        calculateTotal();
    }

    function addOrderItemRow() {
        const itemRow = document.createElement('div');
        itemRow.className = 'row mb-3 order-item';
        itemRow.innerHTML = `
            <div class="col-md-4">
                <select class="form-select product-select" name="product_id[]" required>
                    <option value="">Select Product</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="number" class="form-control quantity" name="quantity[]" min="1" placeholder="Quantity" required>
            </div>
            <div class="col-md-3">
                <input type="number" class="form-control price" name="price[]" step="0.01" placeholder="Price" required>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger remove-item">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
        orderItems.appendChild(itemRow);
    
        const productSelect = itemRow.querySelector('.product-select');
        const priceInput = itemRow.querySelector('.price');
        const quantityInput = itemRow.querySelector('.quantity');
    
        loadProductsIntoDropdown(productSelect);
    
        productSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.value) {
                const price = parseFloat(selectedOption.dataset.price) || 0;
                priceInput.value = price.toFixed(2);
                calculateTotal();
            }
        });
    
        quantityInput.addEventListener('input', calculateTotal);
        priceInput.addEventListener('input', calculateTotal);
    
        itemRow.querySelector('.remove-item').addEventListener('click', function() {
            itemRow.remove();
            calculateTotal();
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
    

    function calculateEditTotal() {
        let total = 0;
        const items = document.querySelectorAll('.edit-order-item');
        
        items.forEach(item => {
            const quantity = parseFloat(item.querySelector('.edit-quantity').value) || 0;
            const price = parseFloat(item.querySelector('.edit-price').value) || 0;
            total += quantity * price;
        });

        document.getElementById('editTotalAmount').textContent = `₦${formatNumber(total)}`;
        return total;
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