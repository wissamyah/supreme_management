<?php
session_start();
require_once '../../config/path.php';
require_once '../../includes/header.php';
require_once '../../includes/topbar.php';
require_once '../../includes/sidebar.php';

// Get customer details
$customerId = isset($_GET['id']) ? $_GET['id'] : null;
if (!$customerId) {
    header('Location: ../customers/');
    exit();
}
?>

<div class="content-wrapper">
    <div class="container-fluid">
    <div class="row mb-4">
    <!-- Back Button and Account Link -->
    <div class="row mb-3">
        <div class="col-12 d-flex align-items-center">
            <a href="<?php echo Path::url('/pages/customers/'); ?>" class="btn btn-sm btn-outline-secondary me-3">
                <i class="bi bi-arrow-left"></i> Back to Customers
            </a>
            <a href="<?php echo Path::url('/pages/customers/account.php?id=' . $customerId); ?>" class="btn btn-sm btn-primary">
                <i class="bi bi-cash-stack"></i> View Account Details
            </a>
        </div>
    </div>
    <div class="col-md-6">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../customers/" class="text-decoration-none">Customers</a></li>
                <li class="breadcrumb-item active customer-name">Customer Orders</li>
            </ol>
        </nav>
    </div>
    <div class="col-md-6 text-end">
        <button class="btn btn-warning btn-sm me-2" id="viewAllBookings">
            <i class="bi bi-calendar2-check"></i> View All Bookings
        </button>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addOrderModal">
            <i class="bi bi-plus-circle"></i> Create New Order
        </button>
    </div>
</div>

        <!-- Customer Info Card -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-md-3">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 bg-primary bg-opacity-10 p-3 me-3">
                                <i class="bi bi-person fs-4 text-primary"></i>
                            </div>
                            <div>
                                <small class="text-uppercase text-muted fw-semibold d-block mb-1">Customer</small>
                                <h5 class="mb-0 customer-name fw-bold">Loading...</h5>
                                <span class="text-muted customer-company fs-sm">Loading...</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 bg-success bg-opacity-10 p-3 me-3">
                                <i class="bi bi-telephone fs-4 text-success"></i>
                            </div>
                            <div>
                                <small class="text-uppercase text-muted fw-semibold d-block mb-1">Phone</small>
                                <h5 class="mb-0 customer-phone fw-bold">Loading...</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 bg-info bg-opacity-10 p-3 me-3">
                                <i class="bi bi-geo-alt fs-4 text-info"></i>
                            </div>
                            <div>
                                <small class="text-uppercase text-muted fw-semibold d-block mb-1">State</small>
                                <h5 class="mb-0 customer-state fw-bold">Loading...</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 bg-warning bg-opacity-10 p-3 me-3">
                                <i class="bi bi-wallet2 fs-4 text-warning"></i>
                            </div>
                            <div>
                                <small class="text-uppercase text-muted fw-semibold d-block mb-1">Balance</small>
                                <h5 class="mb-0 customer-balance fw-bold">Loading...</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-4">
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
            <div class="col-md-4">
                <label for="searchOrder" class="form-label">Search</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" id="searchOrder" placeholder="Search orders...">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label d-block">&nbsp;</label>
                <button class="btn btn-secondary btn-sm" id="resetFilters">Reset Filters</button>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Orders History</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped text-nowrap" id="customerOrdersTable">
                        <thead>
                            <tr>
                                <th width="12%">Date</th>
                                <th width="40%">Order Details</th>
                                <th width="15%">Total Amount</th>
                                <th width="13%">Actions</th>
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

<!-- Add Order Modal -->
<div class="modal fade" id="addOrderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="orderForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="orderDate" class="form-label">Order Date</label>
                        <input type="date" class="form-control" id="orderDate" name="order_date" required>
                    </div>

                    <div id="orderItems">
                        <!-- Order items will be added here -->
                    </div>

                    <button type="button" class="btn btn-secondary mb-3" id="addItemBtn">
                        <i class="bi bi-plus"></i> Add Product
                    </button>

                    <div class="card bg-light">
                        <div class="card-body">
                            <h5 class="card-title">Order Summary</h5>
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold">Total Amount:</span>
                                <span class="fw-bold" id="totalAmount">₦0</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Order Modal -->
<div class="modal fade" id="editOrderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editOrderForm">
                <div class="modal-body">
                    <input type="hidden" id="editOrderId" name="id">
                    <div class="mb-3">
                        <label for="editOrderDate" class="form-label">Order Date</label>
                        <input type="date" class="form-control" id="editOrderDate" name="order_date" required>
                    </div>

                    <div id="editOrderItems">
                        <!-- Edit order items will be added here -->
                    </div>

                    <button type="button" class="btn btn-secondary mb-3" id="addEditItemBtn">
                        <i class="bi bi-plus"></i> Add Product
                    </button>

                    <div class="card bg-light">
                        <div class="card-body">
                            <h5 class="card-title">Order Summary</h5>
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold">Total Amount:</span>
                                <span class="fw-bold" id="editTotalAmount">₦0</span>
                            </div>
                        </div>
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

<!-- View Order Modal -->
<div class="modal fade" id="viewOrderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="orderDetails">
                    <!-- Order details will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Bookings Modal -->
<div class="modal fade" id="viewBookingsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Customer Bookings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive text-nowrap">
                    <table class="table table-striped" id="bookingsTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Product</th>
                                <th>Status</th>
                                <th>Booked</th>
                                <th>Loaded</th>
                                <th>Remaining</th>
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

<script>
    // Pass customer ID to JavaScript
    const CUSTOMER_ID = '<?php echo $customerId; ?>';
</script>
<script src="<?php echo Path::url('assets/js/customer_orders.js'); ?>"></script>
<?php require_once '../../includes/footer.php'; ?>