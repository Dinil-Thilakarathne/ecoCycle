<?php
// Centralized dummy data
$dummy = require base_path('config/dummy.php');
$customers = $dummy['customers'];
$companies = $dummy['companies'];
$collectors = $dummy['collectors'];

// Helper function for status badges
function getStatusBadge($status)
{
    switch ($status) {
        case 'active':
            return '<div class="tag online">Active</div>';
        case 'suspended':
            return '<div class="tag warning">Suspended</div>';
        case 'pending':
            return '<div class="tag pending">Pending</div>';
        case 'offline':
            return '<div class="tag secondary">Offline</div>';
        default:
            return '<div class="tag">' . htmlspecialchars($status) . '</div>';
    }
}
?>

<div>
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header__content">
            <h2 class="page-header__title">User Management</h2>
            <p class="page-header__description">Manage customers, companies, and collectors</p>
        </div>
        <div class="search-input">
            <i class="fa-solid fa-search search-icon"></i>
            <input type="text" id="searchInput" placeholder="Search users..." onkeyup="filterUsers()" />
        </div>
    </div>

    <!-- Tabs Container -->
    <div class="tabs">
        <!-- Tab List -->
        <div class="tabs-list">
            <button class="tabs-trigger active" onclick="showTab('customers')" id="customers-tab">
                <i class="fa-solid fa-users"></i>
                Customers (<?= count($customers) ?>)
            </button>
            <button class="tabs-trigger" onclick="showTab('companies')" id="companies-tab">
                <i class="fa-solid fa-building"></i>
                Companies (<?= count($companies) ?>)
            </button>
            <button class="tabs-trigger" onclick="showTab('collectors')" id="collectors-tab">
                <i class="fa-solid fa-truck"></i>
                Collectors (<?= count($collectors) ?>)
            </button>
        </div>

        <!-- Customers Tab Content -->
        <div class="tabs-content active" id="customers-content">
            <div class="activity-card">
                <div class="activity-card__header">
                    <h3 class="activity-card__title">Customer Management</h3>
                    <p class="activity-card__description">Manage customer accounts and profiles</p>
                </div>
                <div class="activity-card__content">
                    <div style="overflow-x: auto;">
                        <table class="data-table" id="customers-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Total Pickups</th>
                                    <th>Total Earnings</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customers as $customer): ?>
                                    <tr data-user-type="customer" data-name="<?= strtolower($customer['name']) ?>"
                                        data-email="<?= strtolower($customer['email']) ?>">
                                        <td class="font-medium"><?= htmlspecialchars($customer['name']) ?></td>
                                        <td><?= htmlspecialchars($customer['email']) ?></td>
                                        <td><?= htmlspecialchars($customer['phone']) ?></td>
                                        <td><?= htmlspecialchars($customer['totalPickups']) ?></td>
                                        <td>Rs <?= number_format($customer['totalEarnings'], 2) ?></td>
                                        <td><?= getStatusBadge($customer['status']) ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="icon-button"
                                                    onclick="viewUser('<?= $customer['id'] ?>', 'customer')"
                                                    title="View Details">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                                <button class="icon-button approve"
                                                    onclick="approveUser('<?= $customer['id'] ?>', 'customer')"
                                                    title="Approve User">
                                                    <i class="fa-solid fa-user-check"></i>
                                                </button>
                                                <button class="icon-button suspend"
                                                    onclick="suspendUser('<?= $customer['id'] ?>', 'customer')"
                                                    title="Suspend User">
                                                    <i class="fa-solid fa-user-times"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Companies Tab Content -->
        <div class="tabs-content" id="companies-content">
            <div class="activity-card">
                <div class="activity-card__header">
                    <h3 class="activity-card__title">Company Management</h3>
                    <p class="activity-card__description">Manage recycling company accounts</p>
                </div>
                <div class="activity-card__content">
                    <div style="overflow-x: auto;">
                        <table class="data-table" id="companies-table">
                            <thead>
                                <tr>
                                    <th>Company Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Total Bids</th>
                                    <th>Total Purchases</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($companies as $company): ?>
                                    <tr data-user-type="company" data-name="<?= strtolower($company['name']) ?>"
                                        data-email="<?= strtolower($company['email']) ?>">
                                        <td class="font-medium"><?= htmlspecialchars($company['name']) ?></td>
                                        <td><?= htmlspecialchars($company['email']) ?></td>
                                        <td><?= htmlspecialchars($company['phone']) ?></td>
                                        <td><?= htmlspecialchars($company['totalBids']) ?></td>
                                        <td><?= htmlspecialchars($company['totalPurchases']) ?></td>
                                        <td><?= getStatusBadge($company['status']) ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="icon-button"
                                                    onclick="viewUser('<?= $company['id'] ?>', 'company')"
                                                    title="View Details">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                                <button class="icon-button approve"
                                                    onclick="approveUser('<?= $company['id'] ?>', 'company')"
                                                    title="Approve Company">
                                                    <i class="fa-solid fa-user-check"></i>
                                                </button>
                                                <button class="icon-button suspend"
                                                    onclick="suspendUser('<?= $company['id'] ?>', 'company')"
                                                    title="Suspend Company">
                                                    <i class="fa-solid fa-user-times"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Collectors Tab Content -->
        <div class="tabs-content" id="collectors-content">
            <div class="activity-card">
                <div class="activity-card__header">
                    <h3 class="activity-card__title">Collector Management</h3>
                    <p class="activity-card__description">Manage collector accounts and assignments</p>
                </div>
                <div class="activity-card__content">
                    <div style="overflow-x: auto;">
                        <table class="data-table" id="collectors-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Vehicle ID</th>
                                    <th>Today's Pickups</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($collectors as $collector): ?>
                                    <tr data-user-type="collector" data-name="<?= strtolower($collector['name']) ?>"
                                        data-email="<?= strtolower($collector['email']) ?>">
                                        <td class="font-medium"><?= htmlspecialchars($collector['name']) ?></td>
                                        <td><?= htmlspecialchars($collector['email']) ?></td>
                                        <td><?= htmlspecialchars($collector['phone']) ?></td>
                                        <td><?= htmlspecialchars($collector['vehicleId']) ?></td>
                                        <td><?= htmlspecialchars($collector['todayPickups']) ?></td>
                                        <td><?= getStatusBadge($collector['status']) ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="icon-button"
                                                    onclick="viewUser('<?= $collector['id'] ?>', 'collector')"
                                                    title="View Details">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                                <button class="icon-button approve"
                                                    onclick="approveUser('<?= $collector['id'] ?>', 'collector')"
                                                    title="Approve Collector">
                                                    <i class="fa-solid fa-user-check"></i>
                                                </button>
                                                <button class="icon-button suspend"
                                                    onclick="suspendUser('<?= $collector['id'] ?>', 'collector')"
                                                    title="Suspend Collector">
                                                    <i class="fa-solid fa-user-times"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Tab functionality
    function showTab(tabName) {
        // Hide all tab contents
        const contents = document.querySelectorAll('.tabs-content');
        contents.forEach(content => content.classList.remove('active'));

        // Remove active class from all triggers
        const triggers = document.querySelectorAll('.tabs-trigger');
        triggers.forEach(trigger => trigger.classList.remove('active'));

        // Show selected tab content
        document.getElementById(tabName + '-content').classList.add('active');
        document.getElementById(tabName + '-tab').classList.add('active');
    }

    // Search functionality
    function filterUsers() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        const activeTab = document.querySelector('.tabs-content.active');
        const table = activeTab.querySelector('table');
        const rows = table.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const name = row.getAttribute('data-name') || '';
            const email = row.getAttribute('data-email') || '';

            if (name.includes(searchTerm) || email.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // User management functions
    function viewUser(userId, userType) {
        console.log(`Viewing ${userType} ${userId}`);
        alert(`Viewing details for ${userType} ${userId}. In a real application, this would show detailed user information, account history, and activity logs.`);

        // You could redirect to a user details page:
        // window.location.href = `/admin/users/${userType}/${userId}`;
    }

    function approveUser(userId, userType) {
        if (confirm(`Are you sure you want to approve this ${userType}?`)) {
            console.log(`Approving ${userType} ${userId}`);
            alert(`${userType.charAt(0).toUpperCase() + userType.slice(1)} ${userId} has been approved. In a real application, this would update the user status and send a notification email.`);

            // You would make an AJAX request here:
            /*
            fetch('/api/users/approve', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    userId: userId,
                    userType: userType
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to approve user');
                }
            });
            */
        }
    }

    function suspendUser(userId, userType) {
        const reason = prompt(`Please enter the reason for suspending this ${userType}:`);
        if (reason && reason.trim()) {
            console.log(`Suspending ${userType} ${userId} for reason: ${reason}`);
            alert(`${userType.charAt(0).toUpperCase() + userType.slice(1)} ${userId} has been suspended. Reason: ${reason}. In a real application, this would update the user status and send a notification.`);

            // You would make an AJAX request here:
            /*
            fetch('/api/users/suspend', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    userId: userId,
                    userType: userType,
                    reason: reason
                })
            });
            */
        }
    }

    // Initialize search functionality on page load
    document.addEventListener('DOMContentLoaded', function () {
        // Add event listener to search input for real-time filtering
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('input', filterUsers);
    });
</script>