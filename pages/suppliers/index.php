<?php
session_start();
require_once '../../config/path.php';
require_once '../../includes/header.php';
require_once '../../includes/topbar.php';
require_once '../../includes/sidebar.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-6">
                <h2>Supplier Management</h2>
            </div>
            <div class="col-md-6">
                <div class="d-flex gap-2 justify-content-end">
                    <div class="flex-grow-1" style="max-width: 300px;">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="searchSupplier" placeholder="Search suppliers...">
                        </div>
                    </div>
                    <button class="btn btn-secondary btn-sm" id="resetSupplierSearch">Reset Filter</button>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                        <i class="bi bi-plus-circle"></i> Add New Supplier
                    </button>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive text-nowrap"> 
                    <table class="table table-striped" id="suppliersTable" style="min-height: 150px;">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Bank Details</th>
                                <th>Reference Person</th>
                                <th>Balance</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Supplier Modal -->
<div class="modal fade" id="addSupplierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="supplierForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number(s)</label>
                        <input type="text" class="form-control" id="phone" name="phone" placeholder="Separate multiple numbers with commas">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bank Details</label>
                        <div id="bankDetailsContainer">
                            <div class="bank-detail-row mb-2">
                                <div class="row">
                                    <div class="col-md-4 mb-2">
                                        <input type="text" class="form-control" name="account_name[]" placeholder="Account Name">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <input type="text" class="form-control" name="bank_name[]" placeholder="Bank Name">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <input type="text" class="form-control" name="account_number[]" placeholder="Account Number">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="addBankDetail">
                            <i class="bi bi-plus-circle"></i> Add Another Account
                        </button>
                    </div>
                    <div class="mb-3">
                        <label for="referencePerson" class="form-label">Reference Person</label>
                        <input type="text" class="form-control" id="referencePerson" name="reference_person">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bank Details Modal -->
<div class="modal fade" id="bankDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bank Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="bankDetailsList" class="mb-3">
                    <!-- Bank details will be populated by JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Supplier Modal -->
<div class="modal fade" id="editSupplierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editSupplierForm">
                <div class="modal-body">
                    <input type="hidden" id="editId" name="id">
                    <div class="mb-3">
                        <label for="editName" class="form-label">Name</label>
                        <input type="text" class="form-control" id="editName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editPhone" class="form-label">Phone Number(s)</label>
                        <input type="text" class="form-control" id="editPhone" name="phone" placeholder="Separate multiple numbers with commas">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bank Details</label>
                        <div id="editBankDetailsContainer">
                            <!-- Bank details will be populated by JavaScript -->
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="editAddBankDetail">
                            <i class="bi bi-plus-circle"></i> Add Another Account
                        </button>
                    </div>
                    <div class="mb-3">
                        <label for="editReferencePerson" class="form-label">Reference Person</label>
                        <input type="text" class="form-control" id="editReferencePerson" name="reference_person">
                    </div>
                    <div class="mb-3">
                        <label for="editBalance" class="form-label">Balance (₦)</label>
                        <input type="text" class="form-control" id="editBalance" name="balance" readonly>
                        <small class="form-text text-muted">Balance is updated through paddy supply transactions</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?php echo Path::url('assets/js/suppliers.js'); ?>"></script>
<?php require_once '../../includes/footer.php'; ?>