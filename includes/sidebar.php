<?php require_once __DIR__ . '/../config/path.php'; ?>
<div class="sidebar-wrapper">
    <nav id="sidebar" class="sidebar">
        <!-- User Profile Section -->
        <div class="sidebar-user">
            <div class="d-flex align-items-center p-3 border-bottom border-secondary">
                <div class="flex-shrink-0">
                    <i class="bi bi-person-circle fs-3 text-light"></i>
                </div>
                <div class="flex-grow-1 ms-3">
                    <h6 class="mb-0 text-light"><?php echo $_SESSION['username'] ?? 'User'; ?></h6>
                    <div class="mt-1">
                        <a href="<?php echo Path::url('/pages/users/profile'); ?>" class="text-light text-decoration-none small">
                            <i class="bi bi-person-gear"></i> Profile
                        </a>
                        <span class="text-secondary mx-1">|</span>
                        <a href="<?php echo Path::url('/auth/logout'); ?>" class="badge bg-danger text-white text-decoration-none small px-2 py-1">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Menu -->
        <ul class="list-unstyled components">
            <li class="<?php echo ($_SERVER['REQUEST_URI'] == Path::url('/')) ? 'active' : ''; ?>">
                <a href="<?php echo Path::url('/'); ?>">
                    <i class="bi bi-graph-down-arrow"></i> Dashboard
                </a>
            </li>
            <?php if ($_SESSION['role'] == 'Admin' || $_SESSION['role'] == 'Moderator'): ?>
            <li class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/users/') !== false) ? 'active' : ''; ?>">
                <a href="<?php echo Path::url('/pages/users/'); ?>">
                    <i class="bi bi-people"></i> Users
                </a>
            </li>
            <?php endif; ?>
            <li>
                <a href="#" class="dropdown-toggle" data-bs-toggle="collapse" data-bs-target="#customersSubmenu">
                    <i class="bi bi-people-fill"></i> Customers
                </a>
                <ul class="collapse list-unstyled <?php echo (strpos($_SERVER['REQUEST_URI'], '/customers') !== false) ? 'show' : ''; ?>" id="customersSubmenu">
                    <li>
                        <a href="<?php echo Path::url('/pages/customers/'); ?>" 
                        class="<?php echo ($_SERVER['REQUEST_URI'] === Path::url('/pages/customers/') || $_SERVER['REQUEST_URI'] === Path::url('/pages/customers/index')) ? 'active' : ''; ?>">
                            <i class="bi bi-list"></i> List of Customers
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo Path::url('/pages/customers/orders'); ?>"
                        class="<?php echo ($_SERVER['REQUEST_URI'] === Path::url('/pages/customers/orders')) ? 'active' : ''; ?>">
                            <i class="bi bi-receipt"></i> Orders/Invoices
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo Path::url('/pages/customers/accounts'); ?>"
                        class="<?php echo ($_SERVER['REQUEST_URI'] === Path::url('/pages/customers/accounts')) ? 'active' : ''; ?>">
                            <i class="bi bi-cash"></i> Accounts
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo Path::url('/pages/customers/loadings'); ?>"
                        class="<?php echo ($_SERVER['REQUEST_URI'] === Path::url('/pages/customers/loadings')) ? 'active' : ''; ?>">
                            <i class="bi bi-truck"></i> Loadings
                        </a>
                    </li>
                </ul>
            </li>
            <li>
                <a href="#" class="dropdown-toggle" data-bs-toggle="collapse" data-bs-target="#inventorySubmenu">
                    <i class="bi bi-box-seam"></i> Inventory
                </a>
                <ul class="collapse list-unstyled <?php echo (strpos($_SERVER['REQUEST_URI'], '/inventory') !== false) ? 'show' : ''; ?>" id="inventorySubmenu">
                    <li>
                        <a href="<?php echo Path::url('/pages/inventory/'); ?>" 
                        class="<?php echo ($_SERVER['REQUEST_URI'] === Path::url('/pages/inventory/') || $_SERVER['REQUEST_URI'] === Path::url('/pages/inventory/index')) ? 'active' : ''; ?>">
                            <i class="bi bi-box"></i> Stock Overview
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo Path::url('/pages/inventory/production'); ?>"
                        class="<?php echo ($_SERVER['REQUEST_URI'] === Path::url('/pages/inventory/production')) ? 'active' : ''; ?>">
                            <i class="bi bi-receipt"></i> Production Records
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo Path::url('/pages/inventory/report'); ?>"
                        class="<?php echo ($_SERVER['REQUEST_URI'] === Path::url('/pages/inventory/report')) ? 'active' : ''; ?>">
                            <i class="bi bi-file-earmark-text"></i> Production Report
                        </a>
                    </li>
                </ul>
            </li>
            <li>
                <a href="#" class="dropdown-toggle" data-bs-toggle="collapse" data-bs-target="#suppliersSubmenu">
                    <i class="bi bi-people-fill"></i> Suppliers
                </a>
                <ul class="collapse list-unstyled <?php echo (strpos($_SERVER['REQUEST_URI'], '/suppliers') !== false) ? 'show' : ''; ?>" id="suppliersSubmenu">
                    <li>
                        <a href="<?php echo Path::url('/pages/suppliers/'); ?>" 
                        class="<?php echo ($_SERVER['REQUEST_URI'] === Path::url('/pages/suppliers/') || $_SERVER['REQUEST_URI'] === Path::url('/pages/suppliers/index')) ? 'active' : ''; ?>">
                            <i class="bi bi-list"></i> List of Suppliers
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </nav>
</div>