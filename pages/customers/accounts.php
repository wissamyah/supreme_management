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
                <h2>Accounts Overview</h2>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="row mb-4">
            <!-- Total Receivables Card -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 bg-danger bg-opacity-10 p-3 me-3">
                                <i class="bi bi-cash-stack fs-4 text-danger"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Total Receivables</h6>
                                <h3 class="mb-0 text-danger fw-bold" id="totalReceivables">₦0</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Credit Liability Card -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 bg-success bg-opacity-10 p-3 me-3">
                                <i class="bi bi-credit-card fs-4 text-success"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Total Credit Liability</h6>
                                <h3 class="mb-0 text-success fw-bold" id="totalLiability">₦0</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Balance Table Card -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="card-title mb-0">Customer Balances</h5>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-1">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" class="form-control border-1 bg-light" 
                                   id="searchCustomer" placeholder="Search customers...">
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="balancesTable">
                        <thead>
                            <tr>
                                <th class="sortable" data-sort="name">
                                    Customer Name 
                                    <i class="bi bi-arrow-down-up text-muted ms-1"></i>
                                </th>
                                <th class="text-end sortable" data-sort="balance">
                                    Current Balance 
                                    <i class="bi bi-arrow-down-up text-muted ms-1"></i>
                                </th>
                                <th class="text-center">Balance Status</th>
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

<script src="<?php echo Path::url('assets/js/accounts.js'); ?>"></script>
<?php require_once '../../includes/footer.php'; ?>