document.addEventListener('DOMContentLoaded', function() {
    let allSuppliers = []; // Store all suppliers
    const searchSupplier = document.getElementById('searchSupplier');
    
    const resetSupplierSearch = document.getElementById('resetSupplierSearch');
    resetSupplierSearch.addEventListener('click', function() {
        searchSupplier.value = '';
        filterSuppliers();
    });

    // Initialize bank detail buttons
    document.getElementById('addBankDetail').addEventListener('click', function() {
        addBankDetailRow('bankDetailsContainer');
    });
    
    document.getElementById('editAddBankDetail').addEventListener('click', function() {
        addBankDetailRow('editBankDetailsContainer');
    });

    // Add event listener for search
    searchSupplier.addEventListener('input', filterSuppliers);

    function addBankDetailRow(containerId) {
        const container = document.getElementById(containerId);
        const isEdit = containerId === 'editBankDetailsContainer';
        const prefix = isEdit ? 'edit_' : '';
        
        const rowDiv = document.createElement('div');
        rowDiv.className = 'bank-detail-row mb-2';
        rowDiv.innerHTML = `
            <div class="row">
                <div class="col-md-4 mb-2">
                    <input type="text" class="form-control" name="${prefix}account_name[]" placeholder="Account Name">
                </div>
                <div class="col-md-4 mb-2">
                    <input type="text" class="form-control" name="${prefix}bank_name[]" placeholder="Bank Name">
                </div>
                <div class="col-md-3 mb-2">
                    <input type="text" class="form-control" name="${prefix}account_number[]" placeholder="Account Number">
                </div>
                <div class="col-md-1 mb-2 d-flex align-items-center p-0">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-bank-detail">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `;
        
        container.appendChild(rowDiv);
        
        // Add event listener to remove button
        rowDiv.querySelector('.remove-bank-detail').addEventListener('click', function() {
            rowDiv.remove();
        });
    }

    function filterSuppliers() {
        let filteredSuppliers = [...allSuppliers];
        
        // Apply search filter
        if (searchSupplier.value.trim()) {
            const searchTerm = searchSupplier.value.toLowerCase();
            filteredSuppliers = filteredSuppliers.filter(supplier => {
                return supplier.name.toLowerCase().includes(searchTerm) ||
                       (supplier.phone && supplier.phone.toLowerCase().includes(searchTerm)) ||
                       (supplier.bank_details && supplier.bank_details.toLowerCase().includes(searchTerm)) ||
                       (supplier.reference_person && supplier.reference_person.toLowerCase().includes(searchTerm)) ||
                       supplier.balance.toString().includes(searchTerm);
            });
        }
        
        updateSuppliersTable(filteredSuppliers);
    }

    // Initialize form submissions
    initializeForms();
    // Load initial supplier data
    loadSuppliers();

    function initializeForms() {
        // Add Supplier Form
        const supplierForm = document.getElementById('supplierForm');
        if (supplierForm) {
            supplierForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Process bank details into JSON structure
                const bankDetails = [];
                const accountNames = supplierForm.querySelectorAll('input[name="account_name[]"]');
                const bankNames = supplierForm.querySelectorAll('input[name="bank_name[]"]');
                const accountNumbers = supplierForm.querySelectorAll('input[name="account_number[]"]');
                
                for (let i = 0; i < accountNames.length; i++) {
                    if (accountNames[i].value.trim() || bankNames[i].value.trim() || accountNumbers[i].value.trim()) {
                        bankDetails.push({
                            account_name: accountNames[i].value.trim(),
                            bank_name: bankNames[i].value.trim(),
                            account_number: accountNumbers[i].value.trim()
                        });
                    }
                }

                // Create form data
                const formData = new FormData(this);
                formData.delete('account_name[]');
                formData.delete('bank_name[]');
                formData.delete('account_number[]');
                
                // Add bank details as JSON
                formData.append('bank_details', JSON.stringify(bankDetails));
                
                fetch('../../api/suppliers/create.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showToast('success', 'Supplier created successfully');
                        supplierForm.reset();
                        document.getElementById('bankDetailsContainer').innerHTML = '';
                        addBankDetailRow('bankDetailsContainer');
                        const modal = bootstrap.Modal.getInstance(document.getElementById('addSupplierModal'));
                        modal && modal.hide();
                        loadSuppliers();
                    } else {
                        showToast('error', data.message || 'Error creating supplier');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('error', 'An error occurred while creating supplier');
                });
            });
        }

        // Edit Supplier Form
        const editSupplierForm = document.getElementById('editSupplierForm');
        if (editSupplierForm) {
            editSupplierForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Process bank details into JSON structure
                const bankDetails = [];
                const accountNames = editSupplierForm.querySelectorAll('input[name="edit_account_name[]"]');
                const bankNames = editSupplierForm.querySelectorAll('input[name="edit_bank_name[]"]');
                const accountNumbers = editSupplierForm.querySelectorAll('input[name="edit_account_number[]"]');
                
                for (let i = 0; i < accountNames.length; i++) {
                    if (accountNames[i].value.trim() || bankNames[i].value.trim() || accountNumbers[i].value.trim()) {
                        bankDetails.push({
                            account_name: accountNames[i].value.trim(),
                            bank_name: bankNames[i].value.trim(),
                            account_number: accountNumbers[i].value.trim()
                        });
                    }
                }
                
                // Create data object
                const supplierData = {
                    id: document.getElementById('editId').value,
                    name: document.getElementById('editName').value,
                    phone: document.getElementById('editPhone').value,
                    bank_details: JSON.stringify(bankDetails),
                    reference_person: document.getElementById('editReferencePerson').value
                };

                fetch('../../api/suppliers/update.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(supplierData)
                })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showToast('success', 'Supplier updated successfully');
                        editSupplierForm.reset();
                        const modal = bootstrap.Modal.getInstance(document.getElementById('editSupplierModal'));
                        modal && modal.hide();
                        loadSuppliers();
                    } else {
                        showToast('error', data.message || 'Error updating supplier');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('error', 'An error occurred while updating supplier');
                });
            });
        }
    }

    function loadSuppliers() {
        fetch('../../api/suppliers/read.php')
            .then(response => response.json())
            .then(data => {
                if (Array.isArray(data)) {
                    allSuppliers = data; // Store all suppliers
                    filterSuppliers(); // Apply filters
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Error loading suppliers');
            });
    }

    function updateSuppliersTable(suppliers) {
        const tbody = document.querySelector('#suppliersTable tbody');
        if (!tbody) return;
    
        tbody.innerHTML = '';
        
        if (!suppliers.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <div class="d-flex flex-column align-items-center">
                            <i class="bi bi-people text-muted" style="font-size: 2rem;"></i>
                            <h5 class="mt-2 text-muted">No suppliers found</h5>
                            <p class="text-muted">Try adjusting your search criteria</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }
    
        suppliers.forEach(supplier => {
            const tr = document.createElement('tr');
            const balanceClass = supplier.balance > 0 ? 'text-success' : 'text-muted';
            
            // Format phone numbers as pills/badges
            let phoneDisplay = '<div class="d-flex flex-wrap gap-1">';
            if (supplier.phone) {
                const phones = supplier.phone.split(',').map(p => p.trim());
                phones.forEach(phone => {
                    if (phone) {
                        phoneDisplay += `<span class="badge bg-light text-dark">${escapeHtml(phone)}</span>`;
                    }
                });
            } else {
                phoneDisplay += '<span class="text-muted">-</span>';
            }
            phoneDisplay += '</div>';
            
            // Bank accounts UI
            const bankDetails = supplier.bank_details ? JSON.parse(supplier.bank_details) : [];
            let bankDisplay = '';
            
            if (bankDetails.length > 0) {
                bankDisplay = `
                    <button class="btn btn-sm btn-outline-secondary view-bank-details" data-id="${supplier.id}">
                        <i class="bi bi-bank"></i> View ${bankDetails.length} Account${bankDetails.length > 1 ? 's' : ''}
                    </button>
                `;
            } else {
                bankDisplay = '<span class="text-muted">-</span>';
            }
            
            tr.innerHTML = `
                <td>${escapeHtml(supplier.name)}</td>
                <td>${phoneDisplay}</td>
                <td>${bankDisplay}</td>
                <td>${escapeHtml(supplier.reference_person || '-')}</td>
                <td class="${balanceClass} fw-medium">₦${formatNumber(supplier.balance)}</td>
                <td>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-primary edit-supplier" data-id="${supplier.id}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-supplier" data-id="${supplier.id}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
    
        // Attach event listeners to new buttons
        attachButtonListeners();
    }

    function attachButtonListeners() {
        // View bank details buttons
        document.querySelectorAll('.view-bank-details').forEach(button => {
            button.addEventListener('click', function() {
                const supplierId = this.dataset.id;
                const supplier = allSuppliers.find(s => s.id == supplierId);
                
                if (supplier && supplier.bank_details) {
                    displayBankDetails(JSON.parse(supplier.bank_details));
                }
            });
        });
        
        // Edit button listeners
        document.querySelectorAll('.edit-supplier').forEach(button => {
            button.addEventListener('click', function() {
                const supplierId = this.dataset.id;
                fetch(`../../api/suppliers/read.php?id=${supplierId}`)
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(supplier => {
                        if (supplier.id) {
                            document.getElementById('editId').value = supplier.id;
                            document.getElementById('editName').value = supplier.name;
                            document.getElementById('editPhone').value = supplier.phone || '';
                            document.getElementById('editReferencePerson').value = supplier.reference_person || '';
                            document.getElementById('editBalance').value = supplier.balance;
                            
                            // Populate bank details
                            const bankDetailsContainer = document.getElementById('editBankDetailsContainer');
                            bankDetailsContainer.innerHTML = '';
                            
                            if (supplier.bank_details) {
                                const bankDetails = JSON.parse(supplier.bank_details);
                                
                                if (bankDetails.length > 0) {
                                    bankDetails.forEach(detail => {
                                        const rowDiv = document.createElement('div');
                                        rowDiv.className = 'bank-detail-row mb-2';
                                        rowDiv.innerHTML = `
                                            <div class="row">
                                                <div class="col-md-4 mb-2">
                                                    <input type="text" class="form-control" name="edit_account_name[]" 
                                                           value="${escapeHtml(detail.account_name || '')}" placeholder="Account Name">
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <input type="text" class="form-control" name="edit_bank_name[]" 
                                                           value="${escapeHtml(detail.bank_name || '')}" placeholder="Bank Name">
                                                </div>
                                                <div class="col-md-3 mb-2">
                                                    <input type="text" class="form-control" name="edit_account_number[]" 
                                                           value="${escapeHtml(detail.account_number || '')}" placeholder="Account Number">
                                                </div>
                                                <div class="col-md-1 mb-2 d-flex align-items-center p-0">
                                                    <button type="button" class="btn btn-sm btn-outline-danger remove-bank-detail">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        `;
                                        
                                        bankDetailsContainer.appendChild(rowDiv);
                                        
                                        // Add event listener to remove button
                                        rowDiv.querySelector('.remove-bank-detail').addEventListener('click', function() {
                                            rowDiv.remove();
                                        });
                                    });
                                } else {
                                    addBankDetailRow('editBankDetailsContainer');
                                }
                            } else {
                                addBankDetailRow('editBankDetailsContainer');
                            }
                            
                            const editModal = new bootstrap.Modal(document.getElementById('editSupplierModal'));
                            editModal.show();
                        } else {
                            showToast('error', 'Error loading supplier data');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('error', 'Error loading supplier data');
                    });
            });
        });

        // Delete button listeners
        document.querySelectorAll('.delete-supplier').forEach(button => {
            button.addEventListener('click', function() {
                const supplierId = this.dataset.id;
                
                showConfirmDialog(
                    'Confirm Deletion',
                    'Are you sure you want to delete this supplier? This action cannot be undone.',
                    () => {
                        fetch('../../api/suppliers/delete.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                id: supplierId
                            })
                        })
                        .then(response => {
                            if (!response.ok) throw new Error('Network response was not ok');
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                showToast('success', 'Supplier deleted successfully');
                                loadSuppliers();
                            } else {
                                showToast('error', data.message || 'Error deleting supplier');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showToast('error', 'Error deleting supplier');
                        });
                    }
                );
            });
        });
    }
    
    function displayBankDetails(bankDetails) {
        const container = document.getElementById('bankDetailsList');
        container.innerHTML = '';
        
        if (!bankDetails || bankDetails.length === 0) {
            container.innerHTML = '<p class="text-muted">No bank details available</p>';
            
            const modal = new bootstrap.Modal(document.getElementById('bankDetailsModal'));
            modal.show();
            return;
        }
        
        bankDetails.forEach((detail, index) => {
            if (detail.account_name || detail.bank_name || detail.account_number) {
                const detailDiv = document.createElement('div');
                detailDiv.className = 'card mb-3 border-0 shadow-sm';
                
                const formattedText = `${escapeHtml(detail.account_name)} | ${escapeHtml(detail.bank_name)}
${escapeHtml(detail.account_number)}`;
                
                detailDiv.innerHTML = `
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="col-md-8 fw-medium">${escapeHtml(detail.account_name || '-')}</div>
                            <button class="btn btn-sm btn-outline-primary copy-account" data-info="${escapeHtml(formattedText)}">
                                <i class="bi bi-clipboard"></i> Copy
                            </button>
                        </div>

                        <div class="row mb-2">
                            <div class="col-md-4 text-muted">Bank:</div>
                            <div class="col-md-8">${escapeHtml(detail.bank_name || '-')}</div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 text-muted">Account Number:</div>
                            <div class="col-md-8 font-monospace">${escapeHtml(detail.account_number || '-')}</div>
                        </div>
                    </div>
                `;
                
                container.appendChild(detailDiv);
                
                // Add copy functionality
                detailDiv.querySelector('.copy-account').addEventListener('click', function() {
                    const textToCopy = this.dataset.info;
                    navigator.clipboard.writeText(textToCopy)
                        .then(() => {
                            this.innerHTML = '<i class="bi bi-check"></i> Copied!';
                            setTimeout(() => {
                                this.innerHTML = '<i class="bi bi-clipboard"></i> Copy';
                            }, 2000);
                        })
                        .catch(err => {
                            console.error('Failed to copy: ', err);
                            showToast('error', 'Failed to copy to clipboard');
                        });
                });
            }
        });
        
        const modal = new bootstrap.Modal(document.getElementById('bankDetailsModal'));
        modal.show();
    }

    // Utility function to format numbers with commas
    function formatNumber(num) {
        return parseFloat(num).toLocaleString('en-NG', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
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
    
    // Initialize the first bank detail row in add form
    addBankDetailRow('bankDetailsContainer');
});