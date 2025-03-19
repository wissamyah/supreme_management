<?php
// pages/customers/loadings.php
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
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../customers/" class="text-decoration-none">Customers</a></li>
                        <li class="breadcrumb-item active">Loading Records</li>
                    </ol>
                </nav>
                <h2>Loading Records</h2>
            </div>
            <div class="col-md-6 text-end">
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addLoadingModal">
                    <i class="bi bi-plus-circle"></i> New Loading
                </button>
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
                        <label for="statusFilter" class="form-label">Status</label>
                        <select class="form-select" id="statusFilter">
                            <option value="">All Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="searchLoading" class="form-label">Search</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="searchLoading" placeholder="Search loadings...">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading Records Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="loadingTable">
                        <thead>
                            <tr>
                                <th>Loading Date</th>
                                <th>Customer</th>
                                <th>Vehicle/Driver Info</th>
                                <th>Loading Details</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">Actions</th>
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

<!-- Add Loading Modal -->
<div class="modal fade" id="addLoadingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Loading Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="loadingForm">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="loadingDate" class="form-label">Loading Date</label>
                            <input type="date" class="form-control" id="loadingDate" name="loading_date" required>
                        </div>
                        <div class="col-md-6">
                            <label for="customerId" class="form-label">Customer</label>
                            <select class="form-select" id="customerId" name="customer_id" required>
                                <option value="">Select Customer</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="plateNumber" class="form-label">Plate Number</label>
                            <input type="text" class="form-control" id="plateNumber" name="plate_number" required>
                        </div>
                        <div class="col-md-6">
                            <label for="waybillNumber" class="form-label">Waybill Number</label>
                            <input type="text" class="form-control" id="waybillNumber" name="waybill_number">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="driverName" class="form-label">Driver Name</label>
                            <input type="text" class="form-control" id="driverName" name="driver_name">
                        </div>
                        <div class="col-md-6">
                            <label for="driverPhone" class="form-label">Driver Phone</label>
                            <input type="tel" class="form-control" id="driverPhone" name="driver_phone">
                        </div>
                    </div>

                    <h6 class="mb-3">Loading Items</h6>
                    <div id="loadingItems" class="mb-3">
                        <!-- Loading items will be added here -->
                    </div>

                    <button type="button" class="btn btn-secondary mb-3" id="addItemBtn" disabled>
                        <i class="bi bi-plus"></i> Add Item
                    </button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Loading</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Loading Modal -->
<div class="modal fade" id="viewLoadingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Loading Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="loadingDetails">
                    <!-- Loading details will be populated here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo Path::url('assets/js/loadings.js'); ?>"></script>
<?php require_once '../../includes/footer.php'; ?>