<?php
session_start();
require_once '../../config/path.php';
require_once '../../includes/header.php';
require_once '../../includes/topbar.php';
require_once '../../includes/sidebar.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h2>Inventory Management</h2>
            </div>
            <div class="col-md-6 text-end">
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="bi bi-plus-circle"></i> Add New Product
                </button>
            </div>
        </div>

        <!-- Stock Overview Cards -->
        <div class="row mb-4">
            <!-- Total Physical Stock Card -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 bg-primary bg-opacity-10 p-3 me-3">
                                <i class="bi bi-box-seam fs-4 text-primary"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Total Physical Stock</h6>
                                <h3 class="mb-0 fw-bold" id="totalPhysicalStock">0 Bags</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Booked Stock Card -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 bg-warning bg-opacity-10 p-3 me-3">
                                <i class="bi bi-bookmark-check fs-4 text-warning"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Total Booked Stock</h6>
                                <h3 class="mb-0 fw-bold" id="totalBookedStock">0 Bags</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Available Stock Card -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 bg-success bg-opacity-10 p-3 me-3">
                                <i class="bi bi-boxes fs-4 text-success"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Available Stock</h6>
                                <h3 class="mb-0 fw-bold" id="totalAvailableStock">0 Bags</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inventory Table Card -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="card-title mb-0">Product Inventory</h5>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-1">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" class="form-control border-1 bg-light" 
                                   id="searchProduct" placeholder="Search products...">
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="inventoryTable">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th class="text-end">Physical Stock</th>
                                <th class="text-end">Booked Stock</th>
                                <th class="text-end">Available Stock</th>
                                <th class="text-center">Actions</th>
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

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="productForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="productName" class="form-label">Product Name</label>
                        <input type="text" class="form-control" id="productName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select" id="category" name="category" required>
                            <option value="">Select Category</option>
                            <option value="Head Rice">Head Rice</option>
                            <option value="By-product">By-product</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="initialStock" class="form-label">Initial Physical Stock</label>
                        <input type="number" class="form-control" id="initialStock" name="physical_stock" 
                               min="0" step="0.01" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editProductForm">
                <input type="hidden" id="editProductId" name="id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editProductName" class="form-label">Product Name</label>
                        <input type="text" class="form-control" id="editProductName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editCategory" class="form-label">Category</label>
                        <select class="form-select" id="editCategory" name="category" required>
                            <option value="Head Rice">Head Rice</option>
                            <option value="By-product">By-product</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="stockAdjustment" class="form-label">Stock Adjustment</label>
                        <div class="input-group">
                            <select class="form-select" id="adjustmentType" name="adjustment_type">
                                <option value="add">Add</option>
                                <option value="subtract">Subtract</option>
                            </select>
                            <input type="number" class="form-control" id="stockAdjustment" 
                                   name="stock_adjustment" min="0" step="0.01">
                        </div>
                        <small class="text-muted">Leave empty for no adjustment</small>
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

<script src="<?php echo Path::url('assets/js/inventory.js'); ?>"></script>
<?php require_once '../../includes/footer.php'; ?>