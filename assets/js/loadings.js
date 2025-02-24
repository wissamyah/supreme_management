// assets/js/loadings.js
document.addEventListener('DOMContentLoaded', function() {
    let allLoadings = [];
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');
    const statusFilter = document.getElementById('statusFilter');
    const searchLoading = document.getElementById('searchLoading');
    const loadingForm = document.getElementById('loadingForm');
    const customerId = document.getElementById('customerId');
    const loadingItems = document.getElementById('loadingItems');

    // Set default dates
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('loadingDate').value = today;
    startDate.value = new Date(new Date().setMonth(new Date().getMonth() - 1)).toISOString().split('T')[0];
    endDate.value = today;

    // Initialize event listeners
    startDate.addEventListener('change', filterLoadings);
    endDate.addEventListener('change', filterLoadings);
    statusFilter.addEventListener('change', filterLoadings);
    searchLoading.addEventListener('input', filterLoadings);

    // Load initial data
    loadCustomersWithOrders();
    loadLoadings();

    function loadCustomersWithOrders() {
        fetch('../../api/customers/read_customers_with_orders.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (!data || !Array.isArray(data)) {
                    throw new Error('Invalid data format received');
                }
                
                customerId.innerHTML = '<option value="">Select Customer</option>';
                data.forEach(customer => {
                    const option = document.createElement('option');
                    option.value = customer.id;
                    option.textContent = customer.company_name ? 
                        `${customer.name} (${customer.company_name})` : 
                        customer.name;
                    customerId.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Error loading customers: ' + error.message);
                customerId.innerHTML = '<option value="">Error loading customers</option>';
            });
    }

    // Customer selection handler
    customerId.addEventListener('change', function() {
        const selectedCustomerId = this.value;
        loadingItems.innerHTML = '';
        document.getElementById('addItemBtn').disabled = true;
    
        if (!selectedCustomerId) {
            return;
        }
    
        fetch(`../../api/customers/read_customer_orders.php?customer_id=${selectedCustomerId}`)
            .then(response => response.json())
            .then(orders => {
                const hasAvailableItems = orders.some(order => 
                    order.items_array.some(item => {
                        const availableQuantity = item.quantity - (item.loaded_quantity || 0);
                        return availableQuantity > 0 || item.loading_status === 'Pending';
                    })
                );
    
                document.getElementById('addItemBtn').disabled = !hasAvailableItems;
                
                if (!hasAvailableItems) {
                    showToast('warning', 'No pending orders found for this customer');
                    return;
                }
    
                addLoadingItemRow();
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Error loading customer orders');
            });
    });

    function addLoadingItemRow(items = null) {
        const itemRow = document.createElement('div');
        itemRow.className = 'row mb-3 loading-item';
        itemRow.innerHTML = `
            <div class="col-md-5">
                <select class="form-select order-item-select" name="order_item_id" required>
                    <option value="">Select Product</option>
                </select>
            </div>
            <div class="col-md-4">
                <input type="number" class="form-control quantity" name="quantity" 
                       min="1" step="1" required>
            </div>
            <div class="col-md-3">
                <button type="button" class="btn btn-danger remove-item">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
    
        loadingItems.appendChild(itemRow);
    
        const select = itemRow.querySelector('.order-item-select');
        const quantityInput = itemRow.querySelector('.quantity');
    
        // Load items into dropdown
        loadPendingOrderItems(select, items?.order_item_id)
            .then(() => {
                if (items) {
                    // If we have existing items, set them after dropdown is populated
                    select.value = items.order_item_id;
                    quantityInput.value = items.quantity;
                }
            });
    
        // Add handlers
        select.addEventListener('change', function() {
            handleOrderItemSelection(this);
        });
    
        quantityInput.addEventListener('input', function() {
            validateQuantityInput(this);
        });
    
        itemRow.querySelector('.remove-item').addEventListener('click', function() {
            itemRow.remove();
        });
    
        return itemRow;
    }

    function validateQuantityInput(input) {
        const value = parseInt(input.value);
        const max = parseInt(input.getAttribute('max'));
        
        if (value && max && value > max) {
            input.value = max;
            showToast('warning', `Quantity cannot exceed ${max} bags`);
        }
    }

    async function loadPendingOrderItems(select, selectedItemId = null) {
        const selectedCustomerId = document.getElementById('customerId').value;
        if (!selectedCustomerId) return;
    
        try {
            const response = await fetch(`../../api/customers/read_customer_orders.php?customer_id=${selectedCustomerId}`);
            const orders = await response.json();
    
            select.innerHTML = '<option value="">Select Product</option>';
            orders.forEach(order => {
                if (order.items_array && Array.isArray(order.items_array)) {
                    order.items_array.forEach(item => {
                        // Calculate available quantity
                        const loadedQty = parseInt(item.loaded_quantity || 0);
                        const totalQty = parseInt(item.quantity);
                        const availableQuantity = totalQty - loadedQty;
    
                        if (availableQuantity > 0) {
                            const option = document.createElement('option');
                            option.value = item.id;
                            option.textContent = `${item.product} (${availableQuantity} bags booked)`;
                            option.dataset.maxQuantity = availableQuantity;
                            
                            if (selectedItemId && parseInt(item.id) === parseInt(selectedItemId)) {
                                option.selected = true;
                            }
                            select.appendChild(option);
                        }
                    });
                }
            });
        } catch (error) {
            console.error('Error loading products:', error);
            showToast('error', 'Error loading products');
        }
    } 
    
    function handleOrderItemSelection(select) {
        const itemRow = select.closest('.loading-item');
        const quantityInput = itemRow.querySelector('.quantity');
        const selectedOption = select.options[select.selectedIndex];

        if (selectedOption && selectedOption.value) {
            const maxQuantity = parseFloat(selectedOption.dataset.maxQuantity);
            quantityInput.max = maxQuantity;
            quantityInput.min = 0.01;
            quantityInput.step = 0.01;
            quantityInput.placeholder = `Max: ${maxQuantity}`;
        } else {
            quantityInput.value = '';
            quantityInput.removeAttribute('max');
        }
    }

    // Add Item Button Handlers
    document.getElementById('addItemBtn').addEventListener('click', () => addLoadingItemRow());

    // Form Submissions
    loadingForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        console.log('Starting form submission validation');
    
        const loadingItems = document.getElementById('loadingItems');
        const items = [];
        let isValid = true;
    
        // Validate all items
        loadingItems.querySelectorAll('.loading-item').forEach((row, index) => {
            const select = row.querySelector('.order-item-select');
            const quantityInput = row.querySelector('.quantity');
            
            console.log(`Validating row ${index}:`, {
                selectValue: select.value,
                quantityValue: quantityInput.value,
                selectValid: select.checkValidity(),
                quantityValid: quantityInput.checkValidity()
            });
    
            if (select.value && quantityInput.value) {
                const item = {
                    order_item_id: parseInt(select.value),
                    quantity: parseInt(quantityInput.value)
                };
                items.push(item);
            } else {
                isValid = false;
            }
        });
    
        console.log('Collected items:', items);
    
        if (!isValid || items.length === 0) {
            showToast('error', 'Please add at least one item and fill all required fields');
            return;
        }
    
        const formData = {
            customer_id: parseInt(document.getElementById('customerId').value),
            loading_date: document.getElementById('loadingDate').value,
            plate_number: document.getElementById('plateNumber').value.trim(),
            waybill_number: document.getElementById('waybillNumber').value.trim(),
            driver_name: document.getElementById('driverName').value.trim(),
            driver_phone: document.getElementById('driverPhone').value.trim(),
            items: items
        };
    
        console.log('Submitting form data:', formData);
    
        try {
            const response = await fetch('../../api/inventory/create_loading_record.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });
    
            const data = await response.json();
            console.log('Server response:', data);
    
            if (data.success) {
                showToast('success', 'Loading record created successfully');
                loadingForm.reset();
                loadingItems.innerHTML = '';
                document.getElementById('addItemBtn').disabled = true;
                const modal = bootstrap.Modal.getInstance(document.getElementById('addLoadingModal'));
                modal.hide();
                loadLoadings();
                loadCustomersWithOrders();
            } else {
                showToast('error', data.message || 'Error creating loading record');
            }
        } catch (error) {
            console.error('Error submitting form:', error);
            showToast('error', 'Error creating loading record: ' + error.message);
        }
    });


    function loadLoadings() {
        let url = '../../api/inventory/read_loading_records.php';
        const params = new URLSearchParams();

        if (startDate.value) params.append('start_date', startDate.value);
        if (endDate.value) params.append('end_date', endDate.value);
        if (statusFilter.value) params.append('status', statusFilter.value);

        if (params.toString()) {
            url += '?' + params.toString();
        }

        fetch(url)
            .then(response => response.json())
            .then(loadings => {
                if (Array.isArray(loadings)) {
                    allLoadings = loadings;
                    filterLoadings();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Error loading records');
            });
    }

    function filterLoadings() {
        let filteredLoadings = [...allLoadings];
        
        const searchTerm = searchLoading.value.toLowerCase().trim();
        if (searchTerm) {
            filteredLoadings = filteredLoadings.filter(loading => {
                return loading.customer_display_name.toLowerCase().includes(searchTerm) ||
                       loading.plate_number?.toLowerCase().includes(searchTerm) ||
                       loading.waybill_number?.toLowerCase().includes(searchTerm) ||
                       loading.driver_name?.toLowerCase().includes(searchTerm);
            });
        }
        
        updateLoadingsTable(filteredLoadings);
    }

    function updateLoadingsTable(loadings) {
        const tbody = document.querySelector('#loadingTable tbody');
        tbody.innerHTML = '';

        if (!loadings.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <div class="d-flex flex-column align-items-center">
                            <i class="bi bi-inbox text-muted" style="font-size: 2rem;"></i>
                            <h5 class="mt-2 text-muted">No loading records found</h5>
                            <p class="text-muted">Try adjusting your filters</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        loadings.forEach(loading => {
            const tr = document.createElement('tr');
            const vehicleInfo = [
                loading.plate_number ? `<strong>Vehicle:</strong> ${escapeHtml(loading.plate_number)}` : null,
                loading.driver_name ? `<strong>Driver:</strong> ${escapeHtml(loading.driver_name)}` : null,
                loading.driver_phone ? `<strong>Phone:</strong> ${escapeHtml(loading.driver_phone)}` : null,
                loading.waybill_number ? `<strong>Waybill:</strong> ${escapeHtml(loading.waybill_number)}` : null
            ].filter(Boolean).join('<br>');

            const loadingInfo = loading.items_array.map(item => 
                `<div class="mb-1">
                    <span class="badge bg-light text-dark">${escapeHtml(item.product_name)}</span>
                    <small class="text-muted">(${item.loaded_quantity} of ${item.total_quantity})</small>
                    <span class="badge bg-${getLoadingStatusClass(item.loading_status)}">${item.loading_status}</span>
                </div>`
            ).join('');

            tr.innerHTML = `
                <td>${loading.formatted_date}</td>
                <td>${escapeHtml(loading.customer_display_name)}</td>
                <td>${vehicleInfo}</td>
                <td>${loadingInfo}</td>
                <td class="text-center">
                    <span class="badge bg-${getStatusClass(loading.status)}">${loading.status}</span>
                </td>
                <td class="text-end">
                    <button class="btn btn-sm btn-info view-loading" data-id="${loading.id}">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-loading" data-id="${loading.id}">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });

        attachLoadingListeners();
    }

    function attachLoadingListeners() {
        // View loading details
        document.querySelectorAll('.view-loading').forEach(button => {
            button.addEventListener('click', function() {
                const loadingId = this.dataset.id;
                viewLoadingDetails(loadingId);
            });
        });

        // Edit loading
        // document.querySelectorAll('.edit-loading').forEach(button => {
        //     button.addEventListener('click', function() {
        //         const loadingId = this.dataset.id;
        //         loadLoadingForEdit(loadingId);
        //     });
        // });

        // Delete loading
        document.querySelectorAll('.delete-loading').forEach(button => {
            button.addEventListener('click', function() {
                const loadingId = this.dataset.id;
                confirmDeleteLoading(loadingId);
            });
        });
    }

    function viewLoadingDetails(loadingId) {
        fetch(`../../api/inventory/read_loading_records.php?id=${loadingId}`)
            .then(response => response.json())
            .then(loading => {
                const details = document.getElementById('loadingDetails');
                details.innerHTML = `
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Loading Date:</strong> ${loading.formatted_date}</p>
                            <p><strong>Customer:</strong> ${escapeHtml(loading.customer_display_name)}</p>
                            <p><strong>Status:</strong> 
                               <span class="badge bg-${getStatusClass(loading.status)}">${loading.status}</span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Plate Number:</strong> ${escapeHtml(loading.plate_number || '-')}</p>
                            <p><strong>Driver:</strong> ${escapeHtml(loading.driver_name || '-')}</p>
                            <p><strong>Driver Phone:</strong> ${escapeHtml(loading.driver_phone || '-')}</p>
                            <p><strong>Waybill Number:</strong> ${escapeHtml(loading.waybill_number || '-')}</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-end">Loaded Quantity</th>
                                    <th class="text-end">Total Quantity</th>
                                    <th class="text-center">Status</th>
                                    </tr>
                            </thead>
                            <tbody>
                                ${loading.items_array.map(item => `
                                    <tr>
                                        <td>${escapeHtml(item.product_name)}</td>
                                        <td class="text-end">${formatNumber(item.loaded_quantity)}</td>
                                        <td class="text-end">${formatNumber(item.total_quantity)}</td>
                                        <td class="text-center">
                                            <span class="badge bg-${getLoadingStatusClass(item.loading_status)}">
                                                ${item.loading_status}
                                            </span>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;

                const modal = new bootstrap.Modal(document.getElementById('viewLoadingModal'));
                modal.show();
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Error loading details');
            });
    }

    // function loadLoadingForEdit(loadingId) {
    //     fetch(`../../api/inventory/read_loading_records.php?id=${loadingId}`)
    //         .then(response => response.json())
    //         .then(loading => {
    //             document.getElementById('editLoadingId').value = loading.id;
    //             document.getElementById('editLoadingDate').value = loading.loading_date;
    //             document.getElementById('editCustomerId').value = loading.customer_id;
    //             document.getElementById('editCustomerName').value = loading.customer_display_name;
    //             document.getElementById('editPlateNumber').value = loading.plate_number || '';
    //             document.getElementById('editWaybillNumber').value = loading.waybill_number || '';
    //             document.getElementById('editDriverName').value = loading.driver_name || '';
    //             document.getElementById('editDriverPhone').value = loading.driver_phone || '';
    
    //             // Clear and populate items
    //             editLoadingItems.innerHTML = '';
    //             loading.items_array.forEach(item => {
    //                 addLoadingItemRow({
    //                     order_item_id: item.id,  // Changed from item.order_item_id
    //                     quantity: item.loaded_quantity
    //                 });
    //             });
    
    //             const modal = new bootstrap.Modal(document.getElementById('editLoadingModal'));
    //             modal.show();
    //         });
    // }

    // editLoadingForm.addEventListener('submit', function(e) {
    //     e.preventDefault();
        
    //     const formData = {
    //         id: document.getElementById('editLoadingId').value,
    //         customer_id: document.getElementById('editCustomerId').value,
    //         loading_date: document.getElementById('editLoadingDate').value,
    //         plate_number: document.getElementById('editPlateNumber').value,
    //         waybill_number: document.getElementById('editWaybillNumber').value,
    //         driver_name: document.getElementById('editDriverName').value,
    //         driver_phone: document.getElementById('editDriverPhone').value,
    //         items: []
    //     };

    //     const items = editLoadingItems.querySelectorAll('.loading-item');
    //     items.forEach(item => {
    //         const orderItemId = item.querySelector('.order-item-select').value;
    //         const quantity = parseFloat(item.querySelector('.quantity').value);
    //         if (orderItemId && quantity) {
    //             formData.items.push({
    //                 order_item_id: orderItemId,
    //                 quantity: quantity
    //             });
    //         }
    //     });

    //     if (!formData.items.length) {
    //         showToast('error', 'Please add at least one item');
    //         return;
    //     }

    //     fetch('../../api/inventory/update_loading_record.php', {
    //         method: 'POST',
    //         headers: {
    //             'Content-Type': 'application/json'
    //         },
    //         body: JSON.stringify(formData)
    //     })
    //     .then(response => response.json())
    //     .then(data => {
    //         if (data.success) {
    //             showToast('success', 'Loading record updated successfully');
    //             const modal = bootstrap.Modal.getInstance(document.getElementById('editLoadingModal'));
    //             modal.hide();
    //             loadLoadings();
    //         } else {
    //             showToast('error', data.message || 'Error updating loading record');
    //         }
    //     })
    //     .catch(error => {
    //         console.error('Error:', error);
    //         showToast('error', 'Error updating loading record');
    //     });
    // });

    function confirmDeleteLoading(loadingId) {
        showConfirmDialog(
            'Delete Loading Record',
            'Are you sure you want to delete this loading record? This action cannot be undone.',
            () => deleteLoading(loadingId)
        );
    }

    function deleteLoading(loadingId) {
        fetch('../../api/inventory/delete_loading_record.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: loadingId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', 'Loading record deleted successfully');
                loadLoadings();
            } else {
                showToast('error', data.message || 'Error deleting loading record');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Error deleting loading record');
        });
    }

    // Utility Functions
    function getStatusClass(status) {
        switch (status) {
            case 'Completed': return 'success';
            case 'Pending': return 'warning';
            case 'Cancelled': return 'danger';
            default: return 'secondary';
        }
    }

    function getLoadingStatusClass(status) {
        switch (status) {
            case 'Fully Loaded': return 'success';
            case 'Partially Loaded': return 'warning';
            default: return 'secondary';
        }
    }

    function formatNumber(num) {
        return parseFloat(num).toLocaleString('en-NG', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
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