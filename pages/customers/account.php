<?php
session_start();
require_once '../../config/path.php';
require_once '../../includes/header.php';
require_once '../../includes/topbar.php';
require_once '../../includes/sidebar.php';

// Get customer ID from URL parameter
$customerId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$customerId) {
    header('Location: ' . Path::url('/pages/customers/'));
    exit();
}
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <!-- Back Button -->
        <div class="row mb-3">
                <div class="col-12 d-flex align-items-center">
                    <a href="<?php echo Path::url('/pages/customers/'); ?>" class="btn btn-sm btn-outline-secondary me-3">
                        <i class="bi bi-arrow-left"></i> Back to Customers
                    </a>
                    <a href="<?php echo Path::url('/pages/orders/customer.php?id=' . $customerId); ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-receipt"></i> View Customer Orders
                    </a>
                </div>
            </div>

        <!-- Customer Information Card -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div id="customerInfo">
                            <!-- Customer info populated by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-md-6">
                <button class="btn btn-primary me-2 btn-sm" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
                    <i class="bi bi-cash"></i> Record Payment
                </button>
                <button class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#creditNoteModal">
                    <i class="bi bi-file-text"></i> Create Credit Note
                </button>
            </div>
        </div>

        <!-- Transactions Tab Card -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Transaction History</h5>
            </div>
            <div class="card-body">
                <div class="transaction-table-container text-nowrap">
                    <table class="table table-striped transaction-table" id="transactionsTable">
                        <thead>
                            <tr>
                                <th class="date-column">Date</th>
                                <th class="type-column">Type</th>
                                <th class="description-column">Description</th>
                                <th class="amount-column text-end">Amount</th>
                                <th class="balance-column text-end">Running Balance</th>
                                <th class="actions-column text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Transactions populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Record Payment Modal -->
<div class="modal fade" id="recordPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="paymentForm">
                <input type="hidden" name="customer_id" value="<?php echo $customerId; ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="paymentDate" class="form-label">Payment Date</label>
                        <input type="date" class="form-control" id="paymentDate" name="payment_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="paymentAmount" class="form-label">Payment Amount (₦)</label>
                        <input type="number" class="form-control" id="paymentAmount" name="amount" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="paymentMethod" class="form-label">Payment Method</label>
                        <select class="form-select" id="paymentMethod" name="payment_method" required>
                            <option value="Bank Transfer" selected>Bank Transfer</option>
                            <option value="Cash">Cash</option>
                            <option value="Check">Check</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="paymentReference" class="form-label">Reference/Notes</label>
                        <input type="text" class="form-control" id="paymentReference" name="reference">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Record Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Credit Note Modal -->
<div class="modal fade" id="creditNoteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Credit Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="creditNoteForm">
                <input type="hidden" name="customer_id" value="<?php echo $customerId; ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="creditNoteDate" class="form-label">Credit Note Date</label>
                        <input type="date" class="form-control" id="creditNoteDate" name="credit_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="creditNoteAmount" class="form-label">Amount (₦)</label>
                        <input type="number" class="form-control" id="creditNoteAmount" name="amount" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="creditNoteReason" class="form-label">Reason</label>
                        <select class="form-select" id="creditNoteReason" name="reason" required>
                            <option value="Market Fluctuation Adjustment">Market Fluctuation Adjustment</option>
                            <option value="Discount">Discount</option>
                            <option value="Return">Return</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="creditNoteReference" class="form-label">Reference/Notes</label>
                        <input type="text" class="form-control" id="creditNoteReference" name="reference">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Credit Note</button>
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

<script>
    // Pass customer ID to JavaScript
    const customerId = <?php echo $customerId; ?>;
</script>
<script src="<?php echo Path::url('assets/js/account.js'); ?>"></script>
<?php require_once '../../includes/footer.php'; ?>