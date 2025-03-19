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
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../inventory/" class="text-decoration-none">Inventory</a></li>
                        <li class="breadcrumb-item active">Production Records</li>
                    </ol>
                </nav>
                <h2>Daily Production Records</h2>
            </div>
            <div class="col-md-6 text-end">
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#recordProductionModal">
                    <i class="bi bi-plus-circle"></i> Record New Production
                </button>
            </div>
        </div>

        <!-- Production Summary Cards -->
        <div class="row mb-4">
            <!-- Today's Production Card -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 bg-primary bg-opacity-10 p-3 me-3">
                                <i class="bi bi-calendar-check fs-4 text-primary"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Today's Production</h6>
                                <h3 class="mb-0 fw-bold" id="todayProduction">0 Bags</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- This Week's Production Card -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 bg-success bg-opacity-10 p-3 me-3">
                                <i class="bi bi-graph-up fs-4 text-success"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">This Week's Production</h6>
                                <h3 class="mb-0 fw-bold" id="weekProduction">0 Bags</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- This Month's Production Card -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 bg-info bg-opacity-10 p-3 me-3">
                                <i class="bi bi-bar-chart fs-4 text-info"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">This Month's Production</h6>
                                <h3 class="mb-0 fw-bold" id="monthProduction">0 Bags</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="startDate" class="form-label">Start Date</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                            <input type="date" class="form-control" id="startDate">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="endDate" class="form-label">End Date</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                            <input type="date" class="form-control" id="endDate">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="productFilter" class="form-label">Product</label>
                        <select class="form-select" id="productFilter">
                            <option value="">All Products</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="categoryFilter" class="form-label">Category</label>
                        <select class="form-select" id="categoryFilter">
                            <option value="">All Categories</option>
                            <option value="Head Rice">Head Rice</option>
                            <option value="By-product">By-product</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Production Records Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="card-title mb-0">Production History</h5>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="productionTable">
                        <thead>
                            <tr>
                                <th width="15%">Date</th>
                                <th width="25%">Product</th>
                                <th width="15%">Category</th>
                                <th width="15%" class="text-end">Quantity</th>
                                <th width="15%" class="text-center">Status</th>
                                <th width="15%" class="text-end">Actions</th>
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

<!-- Record Production Modal -->
<div class="modal fade" id="recordProductionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Production</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="productionForm">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="productionDate" class="form-label">Production Date</label>
                            <input type="date" class="form-control" id="productionDate" name="production_date" required>
                        </div>
                    </div>

                    <div id="productionItems">
                        <!-- Production items will be added here -->
                        <div class="row mb-3 production-item">
                            <div class="col-md-5">
                                <label class="form-label">Product</label>
                                <select class="form-select product-select" name="product_id[]" required>
                                    <option value="">Select Product</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Quantity</label>
                                <input type="number" class="form-control quantity" name="quantity[]" 
                                       min="0.01" step="0.01" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label d-block">&nbsp;</label>
                                <button type="button" class="btn btn-danger remove-item">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-secondary mb-3" id="addProductionItem">
                        <i class="bi bi-plus"></i> Add Another Product
                    </button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Record Production</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Production Modal -->
<div class="modal fade" id="editProductionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Production Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editProductionForm">
                <input type="hidden" id="editProductionId" name="id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editProductionDate" class="form-label">Production Date</label>
                        <input type="date" class="form-control" id="editProductionDate" name="production_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="editProductId" class="form-label">Product</label>
                        <select class="form-select" id="editProductId" name="product_id" required>
                            <option value="">Select Product</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editQuantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="editQuantity" name="quantity" 
                               min="0.01" step="0.01" required>
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

<script src="<?php echo Path::url('assets/js/production.js'); ?>"></script>
<?php require_once '../../includes/footer.php'; ?>