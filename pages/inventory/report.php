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
                        <li class="breadcrumb-item active">Production Report</li>
                    </ol>
                </nav>
                <h2>Production Report</h2>
            </div>
            <div class="col-md-6 text-end">
                <button class="btn btn-primary btn-sm" id="printReport">
                    <i class="bi bi-printer"></i> Print Report
                </button>
                <button class="btn btn-success btn-sm" id="exportExcel">
                    <i class="bi bi-file-excel"></i> Export to Excel
                </button>
            </div>
        </div>

        <!-- Report Filters -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-body">
                <form id="reportForm">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="startDate" class="form-label">Start Date</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                                <input type="date" class="form-control" id="startDate" name="start_date" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="endDate" class="form-label">End Date</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                                <input type="date" class="form-control" id="endDate" name="end_date" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="productFilter" class="form-label">Product</label>
                            <select class="form-select" id="productFilter" name="product_id">
                                <option value="">All Products</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="categoryFilter" class="form-label">Category</label>
                            <select class="form-select" id="categoryFilter" name="category">
                                <option value="">All Categories</option>
                                <option value="Head Rice">Head Rice</option>
                                <option value="By-product">By-product</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label d-none d-md-block">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Report Content -->
        <div id="reportContent">
            <!-- Summary Section -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted">Total Production</h6>
                            <h3 class="mb-0" id="totalProduction">0 Bags</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted">Head Rice Production</h6>
                            <h3 class="mb-0" id="headRiceProduction">0 Bags</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted">By-product Production</h6>
                            <h3 class="mb-0" id="byProductProduction">0 Bags</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Production Analysis -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Daily Production Trend</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="productionTrendChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Production by Category</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="categoryPieChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Report Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Detailed Production Report</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="detailedReportTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th class="text-end">Quantity</th>
                                    <th class="text-end">Running Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Populated by JavaScript -->
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td colspan="3">Total</td>
                                    <td class="text-end" id="tableTotalQuantity">0 Bags</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Print Layout (hidden by default) -->
        <div id="printArea" class="d-none">
            <div class="text-center mb-4">
                <h2>Supreme Rice Mills Ltd.</h2>
                <h3>Production Report</h3>
                <p class="mb-1" id="printDateRange"></p>
                <p id="printFilters"></p>
            </div>
            <div id="printableTable">
                <!-- Populated by JavaScript -->
            </div>
            <div class="mt-4">
                <p class="mb-1">Generated by: <?php echo $_SESSION['username']; ?></p>
                <p>Date: <span id="printGeneratedDate"></span></p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<script src="<?php echo Path::url('assets/js/inventory_report.js'); ?>"></script>
<?php require_once '../../includes/footer.php'; ?>