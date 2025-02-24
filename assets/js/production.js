document.addEventListener('DOMContentLoaded', function() {
    let allProductions = [];
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');
    const productFilter = document.getElementById('productFilter');
    const categoryFilter = document.getElementById('categoryFilter');
    const productionForm = document.getElementById('productionForm');
    const addProductionItem = document.getElementById('addProductionItem');
    const productionItems = document.getElementById('productionItems');

    // Set default dates
    const today = new Date();
    const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate());
    startDate.value = lastMonth.toISOString().split('T')[0];
    endDate.value = today.toISOString().split('T')[0];
    document.getElementById('productionDate').value = today.toISOString().split('T')[0];

    // Initialize event listeners
    startDate.addEventListener('change', filterProductions);
    endDate.addEventListener('change', filterProductions);
    productFilter.addEventListener('change', filterProductions);
    categoryFilter.addEventListener('change', filterProductions);
    
    // Add production item button handler
    addProductionItem.addEventListener('click', addProductionItemRow);

    // Initialize forms and load data
    initializeForms();
    loadProducts();
    loadProductions();

    function initializeForms() {
        // Production Form Handler
        productionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('../../api/inventory/record_production.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', 'Production recorded successfully');
                    productionForm.reset();
                    document.getElementById('productionDate').value = today.toISOString().split('T')[0];
                    productionItems.innerHTML = '';
                    addProductionItemRow(); // Add one empty row
                    const modal = bootstrap.Modal.getInstance(document.getElementById('recordProductionModal'));
                    modal.hide();
                    loadProductions();
                } else {
                    showToast('error', data.message || 'Error recording production');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Error recording production');
            });
        });

        // Edit Production Form Handler
        document.getElementById('editProductionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('../../api/inventory/update_production.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', 'Production record updated successfully');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editProductionModal'));
                    modal.hide();
                    loadProductions();
                } else {
                    showToast('error', data.message || 'Error updating production record');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Error updating production record');
            });
        });
    }

    function loadProducts() {
        fetch('../../api/inventory/read_products.php')
            .then(response => response.json())
            .then(products => {
                if (Array.isArray(products)) {
                    populateProductDropdowns(products);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Error loading products');
            });
    }

    function populateProductDropdowns(products) {
        const dropdowns = document.querySelectorAll('.product-select');
        dropdowns.forEach(dropdown => {
            dropdown.innerHTML = '<option value="">Select Product</option>';
            products.forEach(product => {
                const option = document.createElement('option');
                option.value = product.id;
                option.textContent = `${product.name} (${product.category})`;
                dropdown.appendChild(option);
            });
        });

        // Also populate the filter dropdown
        productFilter.innerHTML = '<option value="">All Products</option>';
        products.forEach(product => {
            const option = document.createElement('option');
            option.value = product.id;
            option.textContent = product.name;
            productFilter.appendChild(option);
        });
    }

    function loadProductions() {
        fetch('../../api/inventory/read_production_records.php')
            .then(response => response.json())
            .then(productions => {
                if (Array.isArray(productions)) {
                    allProductions = productions;
                    updateStatistics(productions);
                    filterProductions();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Error loading production records');
            });
    }

    function updateStatistics(productions) {
        const today = new Date().toISOString().split('T')[0];
        const thisWeek = new Date();
        thisWeek.setDate(thisWeek.getDate() - 7);
        const thisMonth = new Date();
        thisMonth.setDate(1);

        const todayProduction = productions.reduce((sum, record) => 
            record.production_date === today ? sum + parseFloat(record.quantity) : sum, 0);

        const weekProduction = productions.reduce((sum, record) => 
            new Date(record.production_date) >= thisWeek ? sum + parseFloat(record.quantity) : sum, 0);

        const monthProduction = productions.reduce((sum, record) => 
            new Date(record.production_date) >= thisMonth ? sum + parseFloat(record.quantity) : sum, 0);

        document.getElementById('todayProduction').textContent = formatNumber(todayProduction) + ' Bags';
        document.getElementById('weekProduction').textContent = formatNumber(weekProduction) + ' Bags';
        document.getElementById('monthProduction').textContent = formatNumber(monthProduction) + ' Bags';
    }

    function filterProductions() {
        let filteredProductions = [...allProductions];
        
        if (startDate.value) {
            filteredProductions = filteredProductions.filter(record => 
                record.production_date >= startDate.value
            );
        }
        if (endDate.value) {
            filteredProductions = filteredProductions.filter(record => 
                record.production_date <= endDate.value
            );
        }
        if (productFilter.value) {
            filteredProductions = filteredProductions.filter(record => 
                String(record.product_id) === String(productFilter.value)
            );
        }
        if (categoryFilter.value) {
            filteredProductions = filteredProductions.filter(record => 
                record.category === categoryFilter.value
            );
        }
        
        updateProductionTable(filteredProductions);
    }

    function updateProductionTable(productions) {
        const tbody = document.querySelector('#productionTable tbody');
        tbody.innerHTML = '';
        
        if (!productions.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <div class="d-flex flex-column align-items-center">
                            <i class="bi bi-calendar-x text-muted" style="font-size: 2rem;"></i>
                            <h5 class="mt-2 text-muted">No production records found</h5>
                            <p class="text-muted">Try adjusting your filters</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        productions.forEach(record => {
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
                <td class="text-center">
                    <span class="badge bg-success">Recorded</span>
                </td>
                <td class="text-end">
                    <button class="btn btn-sm btn-primary edit-production" data-id="${record.id}">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-production" data-id="${record.id}">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });

        attachButtonListeners();
    }

    function addProductionItemRow() {
        const itemRow = document.createElement('div');
        itemRow.className = 'row mb-3 production-item';
        itemRow.innerHTML = `
            <div class="col-md-5">
                <select class="form-select product-select" name="product_id[]" required>
                    <option value="">Select Product</option>
                </select>
            </div>
            <div class="col-md-4">
                <input type="number" class="form-control quantity" name="quantity[]" 
                       min="0.01" step="0.01" required>
            </div>
            <div class="col-md-3">
                <button type="button" class="btn btn-danger remove-item">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;

        productionItems.appendChild(itemRow);
        
        // Load products into the new dropdown
        loadProducts();

        // Add remove button handler
        itemRow.querySelector('.remove-item').addEventListener('click', function() {
            itemRow.remove();
        });
    }

    function attachButtonListeners() {
        // Edit button listeners
        document.querySelectorAll('.edit-production').forEach(button => {
            button.addEventListener('click', function() {
                const productionId = this.dataset.id;
                loadProductionForEdit(productionId);
            });
        });

        // Delete button listeners
        document.querySelectorAll('.delete-production').forEach(button => {
            button.addEventListener('click', function() {
                const productionId = this.dataset.id;
                confirmDeleteProduction(productionId);
            });
        });
    }

    function loadProductionForEdit(productionId) {
        fetch(`../../api/inventory/read_production_records.php?id=${productionId}`)
            .then(response => response.json())
            .then(record => {
                if (record.id) {
                    document.getElementById('editProductionId').value = record.id;
                    document.getElementById('editProductionDate').value = record.production_date;
                    document.getElementById('editProductId').value = record.product_id;
                    document.getElementById('editQuantity').value = record.quantity;
                    
                    const editModal = new bootstrap.Modal(document.getElementById('editProductionModal'));
                    editModal.show();
                } else {
                    showToast('error', 'Error loading production record');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Error loading production record');
            });
    }

    function confirmDeleteProduction(productionId) {
        showConfirmDialog(
            'Delete Production Record',
            'Are you sure you want to delete this production record? This will update the inventory stock levels.',
            () => deleteProduction(productionId)
        );
    }

    function deleteProduction(productionId) {
        fetch('../../api/inventory/delete_production.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: productionId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', 'Production record deleted successfully');
                loadProductions();
            } else {
                showToast('error', data.message || 'Error deleting production record');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Error deleting production record');
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