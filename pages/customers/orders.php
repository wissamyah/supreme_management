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
                <h2>Orders Management</h2>
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
        <div class="card">
            <div class="card-body">
                <div class="table-responsive text-nowrap">
                    <table class="table table-striped" id="ordersTable">
                        <thead>
                            <tr>
                                <th width="12%">Date</th>
                                <th width="20%">Customer</th>
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
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="orderDate" class="form-label">Order Date</label>
                            <input type="date" class="form-control" id="orderDate" name="order_date" required>
                        </div>
                        <div class="col-md-6">
                            <label for="customerId" class="form-label">Customer</label>
                            <select class="form-select" id="customerId" name="customer_id" required>
                                <option value="">Select Customer</option>
                            </select>
                        </div>
                    </div>

                    <div id="orderItems">
                        <!-- Order items will be added here -->
                    </div>

                    <button type="button" class="btn btn-secondary mb-3" id="addItemBtn">
                        <i class="bi bi-plus"></i> Add Product
                    </button>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">Order Summary</h5>
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold">Total Amount:</span>
                                        <span class="fw-bold" id="totalAmount">₦0.00</span>
                                    </div>
                                </div>
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
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="editOrderDate" class="form-label">Order Date</label>
                            <input type="date" class="form-control" id="editOrderDate" name="order_date" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editCustomerId" class="form-label">Customer</label>
                            <select class="form-select" id="editCustomerId" name="customer_id" required>
                                <option value="">Select Customer</option>
                            </select>
                        </div>
                    </div>

                    <div id="editOrderItems">
                        <!-- Edit order items will be added here -->
                    </div>

                    <button type="button" class="btn btn-secondary mb-3" id="addEditItemBtn">
                        <i class="bi bi-plus"></i> Add Product
                    </button>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">Order Summary</h5>
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold">Total Amount:</span>
                                        <span class="fw-bold" id="editTotalAmount">₦0.00</span>
                                    </div>
                                </div>
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
                <h5 class="modal-title">All Bookings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive text-nowrap">
                    <table class="table table-striped" id="bookingsTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Customer</th>
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


<script src="<?php echo Path::url('assets/js/orders.js'); ?>"></script>
<?php require_once '../../includes/footer.php'; ?>