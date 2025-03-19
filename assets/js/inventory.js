document.addEventListener('DOMContentLoaded', function() {
    let allProducts = [];
    const searchProduct = document.getElementById('searchProduct');
    const productForm = document.getElementById('productForm');
    const editProductForm = document.getElementById('editProductForm');

    // Initialize event listeners
    searchProduct.addEventListener('input', filterProducts);
    initializeForms();
    
    // Load initial data
    loadProducts();

    function initializeForms() {
        // Add Product Form Handler
        productForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('../../api/inventory/create_product.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', 'Product created successfully');
                    productForm.reset();
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addProductModal'));
                    modal.hide();
                    loadProducts();
                } else {
                    showToast('error', data.message || 'Error creating product');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Error creating product');
            });
        });

        // Edit Product Form Handler
        editProductForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('../../api/inventory/update_product.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', 'Product updated successfully');
                    editProductForm.reset();
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editProductModal'));
                    modal.hide();
                    loadProducts();
                } else {
                    showToast('error', data.message || 'Error updating product');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Error updating product');
            });
        });
    }

    function loadProducts() {
        fetch('../../api/inventory/read_products.php')
            .then(response => response.json())
            .then(products => {
                if (Array.isArray(products)) {
                    allProducts = products;
                    updateStatistics(products);
                    filterProducts();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Error loading products');
            });
    }

    function updateStatistics(products) {
        let totalPhysical = 0;
        let totalBooked = 0;

        products.forEach(product => {
            totalPhysical += parseFloat(product.physical_stock);
            totalBooked += parseFloat(product.booked_stock);
        });

        document.getElementById('totalPhysicalStock').textContent = 
            formatNumber(totalPhysical) + ' Bags';
        document.getElementById('totalBookedStock').textContent = 
            formatNumber(totalBooked) + ' Bags';
        document.getElementById('totalAvailableStock').textContent = 
            formatNumber(totalPhysical - totalBooked) + ' Bags';
    }

    function filterProducts() {
        let filteredProducts = [...allProducts];
        
        if (searchProduct.value.trim()) {
            const searchTerm = searchProduct.value.toLowerCase();
            filteredProducts = filteredProducts.filter(product => 
                product.name.toLowerCase().includes(searchTerm) ||
                product.category.toLowerCase().includes(searchTerm)
            );
        }
        
        updateProductsTable(filteredProducts);
    }

    function updateProductsTable(products) {
        const tbody = document.querySelector('#inventoryTable tbody');
        tbody.innerHTML = '';
        
        if (!products.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <div class="d-flex flex-column align-items-center">
                            <i class="bi bi-inbox text-muted" style="font-size: 2rem;"></i>
                            <h5 class="mt-2 text-muted">No products found</h5>
                            <p class="text-muted">Try adjusting your search criteria</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        products.forEach(product => {
            const tr = document.createElement('tr');
            const availableStock = product.physical_stock - product.booked_stock;
            const stockClass = availableStock < 0 ? 'text-danger' : 'text-success';
            
            tr.innerHTML = `
                <td>${escapeHtml(product.name)}</td>
                <td>
                    <span class="badge bg-${product.category === 'Head Rice' ? 'primary' : 'secondary'}">
                        ${escapeHtml(product.category)}
                    </span>
                </td>
                <td class="text-end fw-medium">${formatNumber(product.physical_stock)}</td>
                <td class="text-end fw-medium">${formatNumber(product.booked_stock)}</td>
                <td class="text-end fw-medium ${stockClass}">${formatNumber(availableStock)}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-primary edit-product" data-id="${product.id}">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-product" data-id="${product.id}">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });

        attachButtonListeners();
    }

    function attachButtonListeners() {
        // Edit button listeners
        document.querySelectorAll('.edit-product').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.dataset.id;
                loadProductForEdit(productId);
            });
        });

        // Delete button listeners
        document.querySelectorAll('.delete-product').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.dataset.id;
                confirmDeleteProduct(productId);
            });
        });
    }

    function loadProductForEdit(productId) {
        fetch(`../../api/inventory/read_products.php?id=${productId}`)
            .then(response => response.json())
            .then(product => {
                if (product.id) {
                    document.getElementById('editProductId').value = product.id;
                    document.getElementById('editProductName').value = product.name;
                    document.getElementById('editCategory').value = product.category;
                    
                    const editModal = new bootstrap.Modal(document.getElementById('editProductModal'));
                    editModal.show();
                } else {
                    showToast('error', 'Error loading product data');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Error loading product data');
            });
    }

    function confirmDeleteProduct(productId) {
        showConfirmDialog(
            'Delete Product',
            'Are you sure you want to delete this product? This cannot be undone.',
            () => deleteProduct(productId)
        );
    }

    function deleteProduct(productId) {
        fetch('../../api/inventory/delete_product.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: productId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', 'Product deleted successfully');
                loadProducts();
            } else {
                showToast('error', data.message || 'Error deleting product');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Error deleting product');
        });
    }

    // Utility function to format numbers with commas
    function formatNumber(num) {
        return parseFloat(num).toLocaleString('en-NG', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
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