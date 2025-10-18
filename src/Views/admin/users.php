<?php
// View receives $customers, $companies, $collectors from the controller
// Ensure variables exist to avoid undefined notices
$customers = $customers ?? [];
$companies = $companies ?? [];
$collectors = $collectors ?? [];

if (!function_exists('adminUsersViewLog')) {
    function adminUsersViewLog(...$args): void
    {
        if (function_exists('consoleLog')) {
            consoleLog(...$args);
        }
    }
}

adminUsersViewLog('users view loaded, customers count: ' . count($customers));
?>

<script>
    // Expose a client-side copy of server user data to simplify modal population.
    window.__USER_DATA = <?php echo json_encode(['customers' => $customers, 'companies' => $companies, 'collectors' => $collectors], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
</script>
<?php

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

// Server-side utility: find user from sample arrays by type and id
function findUserById(string $type, string $id)
{
    global $customers, $companies, $collectors;

    // normalize and accept singular or plural forms
    $t = strtolower(trim((string) $type));
    if (in_array($t, ['customers', 'customer'], true))
        $t = 'customer';
    elseif (in_array($t, ['companies', 'company'], true))
        $t = 'company';
    elseif (in_array($t, ['collectors', 'collector'], true))
        $t = 'collector';
    else
        return null;

    // pick source and ensure it's an array (avoid null)
    switch ($t) {
        case 'customer':
            $source = $customers ?? [];
            break;
        case 'company':
            $source = $companies ?? [];
            break;
        case 'collector':
            $source = $collectors ?? [];
            break;
        default:
            $source = [];
    }

    adminUsersViewLog('source:', $source);

    foreach ((array) $source as $u) {
        adminUsersViewLog('Checking user:', $u);
        if (isset($u['id'])) {
            // Debug hex representation to detect invisible/extra bytes
            $uIdStr = (string) $u['id'];
            $lookupIdStr = (string) $id;
            $uHex = bin2hex($uIdStr);
            $lookupHex = bin2hex($lookupIdStr);
            adminUsersViewLog('id-compare', ['u_id' => $uIdStr, 'u_hex' => $uHex, 'lookup_id' => $lookupIdStr, 'lookup_hex' => $lookupHex]);

            if (strcasecmp($uIdStr, $lookupIdStr) === 0) {
                return $u;
            }
        }
    }

    return null;
}

// If URL requests a particular user (server-side render), prepare $modalUser
$modalUser = null;
$modalUserType = null;
if (!empty($_GET['view']) && !empty($_GET['id'])) {
    $t = $_GET['view'];
    $iRaw = $_GET['id'];
    // Normalize incoming id: strip any appended suffixes (e.g. C001:128) and trim
    $i = preg_replace('/[:#].*/', '', trim((string) $iRaw));

    // Debug: show what the server will lookup
    adminUsersViewLog('users.php lookup', ['view_raw' => $t, 'id_raw' => $iRaw, 'id_normalized' => $i]);

    $found = findUserById($t, $i);
    if ($found) {
        $modalUser = $found;
        $modalUserType = $t;
    }
    // If params were provided but no user was found, output a small console warning to aid debugging
    if (empty($modalUser)) {
        $v = htmlspecialchars($_GET['view']);
        $ii = htmlspecialchars($_GET['id']);
        echo "<script>console.warn('users.php: no user found for view=' + " . json_encode($v) . " + ' id=' + " . json_encode($ii) . ");</script>";
    }

    // Expose minimal debug info in an HTML comment so you can view page source to confirm what the server saw
    echo "<!-- users.php debug: view=" . htmlspecialchars($_GET['view'] ?? '') . " id=" . htmlspecialchars($_GET['id'] ?? '') . " modalUser=" . json_encode($modalUser, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . " -->";
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
                                        <td>$<?= number_format($customer['totalEarnings'], 2) ?></td>
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

<!-- User Detail Modal Component -->
<?php if (!empty($modalUser)): ?>
    <?php
    $mu = $modalUser;
    $muId = $mu['id'] ?? '';
    $muName = $mu['name'] ?? '';
    $muEmail = $mu['email'] ?? '';
    $muPhone = $mu['phone'] ?? '';
    $muAddress = $mu['address'] ?? '';
    $muStatus = $mu['status'] ?? '';
    $muVehicle = $mu['vehicleId'] ?? '';
    $muTotalPickups = $mu['totalPickups'] ?? ($mu['todayPickups'] ?? '0');
    $muTotalEarnings = $mu['totalEarnings'] ?? '0';
    $muTotalBids = $mu['totalBids'] ?? '0';
    $muTotalPurchases = $mu['totalPurchases'] ?? '0';
    ?>
    <div id="user-detail-modal" class="user-modal open" role="dialog" aria-modal="true" aria-hidden="false">
        <div class="user-modal__dialog">
            <button class="close" aria-label="Close">&times;</button>
            <h3>User Details</h3>
            <div class="user-modal__grid">
                <div><strong>ID</strong></div>
                <div class="ud-id"><?= htmlspecialchars($muId) ?></div>
                <div><strong>Name</strong></div>
                <div class="ud-name"><?= htmlspecialchars($muName) ?></div>
                <div><strong>Email</strong></div>
                <div class="ud-email"><?= htmlspecialchars($muEmail) ?></div>
                <div><strong>Phone</strong></div>
                <div class="ud-phone"><?= htmlspecialchars($muPhone) ?></div>
                <div><strong>Address</strong></div>
                <div class="ud-address"><?= htmlspecialchars($muAddress ?: '-') ?></div>
                <div><strong>Status</strong></div>
                <div class="ud-status"><?= htmlspecialchars(ucfirst($muStatus ?: '-')) ?></div>
                <div><strong>Vehicle ID</strong></div>
                <div class="ud-vehicle"><?= htmlspecialchars($muVehicle ?: '-') ?></div>
                <div><strong>Total Pickups</strong></div>
                <div class="ud-totalpickups"><?= htmlspecialchars($muTotalPickups) ?></div>
                <div><strong>Total Earnings</strong></div>
                <div class="ud-totalearnings"><?= '$' . number_format((float) $muTotalEarnings, 2) ?></div>
                <div><strong>Total Bids</strong></div>
                <div class="ud-totalbids"><?= htmlspecialchars($muTotalBids) ?></div>
                <div><strong>Total Purchases</strong></div>
                <div class="ud-totalpurchases"><?= htmlspecialchars($muTotalPurchases) ?></div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div id="user-detail-modal" class="user-modal" role="dialog" aria-modal="true" aria-hidden="true">
        <div class="user-modal__dialog">
            <button class="close" aria-label="Close">&times;</button>
            <h3>User Details</h3>
            <div class="user-modal__grid">
                <div><strong>ID</strong></div>
                <div class="ud-id"></div>
                <div><strong>Name</strong></div>
                <div class="ud-name"></div>
                <div><strong>Email</strong></div>
                <div class="ud-email"></div>
                <div><strong>Phone</strong></div>
                <div class="ud-phone"></div>
                <div><strong>Address</strong></div>
                <div class="ud-address"></div>
                <div><strong>Status</strong></div>
                <div class="ud-status"></div>
                <div><strong>Vehicle ID</strong></div>
                <div class="ud-vehicle"></div>
                <div><strong>Total Pickups</strong></div>
                <div class="ud-totalpickups"></div>
                <div><strong>Total Earnings</strong></div>
                <div class="ud-totalearnings"></div>
                <div><strong>Total Bids</strong></div>
                <div class="ud-totalbids"></div>
                <div><strong>Total Purchases</strong></div>
                <div class="ud-totalpurchases"></div>
            </div>
        </div>
    </div>
<?php endif; ?>
