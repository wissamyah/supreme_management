<?php
session_start();
require_once 'config/path.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . Path::url('auth/login.php'));
    exit();
}

require_once 'includes/header.php';
require_once 'includes/topbar.php';
require_once 'includes/sidebar.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col">
                <h2>Dashboard</h2>
            </div>
        </div>

        <div class="row">
            <!-- Example Dashboard Cards -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow-sm h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total Users</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalUsers">Loading...</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-people fs-2 text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add more dashboard cards as needed -->
        </div>
    </div>
</div>

<script>
// Load dashboard data
function loadDashboardData() {
    fetch('<?php echo Path::url('api/dashboard/stats.php'); ?>')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('totalUsers').textContent = data.stats.totalUsers;
            }
        })
        .catch(error => console.error('Error:', error));
}

document.addEventListener('DOMContentLoaded', loadDashboardData);
</script>

<?php require_once 'includes/footer.php'; ?>