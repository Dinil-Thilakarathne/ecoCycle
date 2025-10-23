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
                                <?php foreach ((array) ($customers ?? []) as $customer): ?>
                                    <tr data-user-type="customer" data-id="<?= $customer['id'] ?>">
                                        <td class="font-medium"><?= htmlspecialchars($customer['name']) ?></td>
                                        <td><?= htmlspecialchars($customer['email']) ?></td>
                                        <td><?= htmlspecialchars($customer['phone']) ?></td>
                                        <td><?= htmlspecialchars($customer['totalPickups']) ?></td>
                                        <td>Rs <?= number_format($customer['totalEarnings'], 2) ?></td>
                                        <td><?= getStatusBadge($customer['status']) ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="icon-button" onclick="viewUser(this, 'customer')"
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
                                <?php foreach ((array) ($companies ?? []) as $company): ?>
                                    <tr data-user-type="company" data-id="<?= $company['id'] ?>">
                                        <td class="font-medium"><?= htmlspecialchars($company['name']) ?></td>
                                        <td><?= htmlspecialchars($company['email']) ?></td>
                                        <td><?= htmlspecialchars($company['phone']) ?></td>
                                        <td><?= htmlspecialchars($company['totalBids']) ?></td>
                                        <td><?= htmlspecialchars($company['totalPurchases']) ?></td>
                                        <td><?= getStatusBadge($company['status']) ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="icon-button" onclick="viewUser(this, 'company')"
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
                                    <th>Today's Pickups</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ((array) ($collectors ?? []) as $collector): ?>
                                    <tr data-user-type="collector" data-id="<?= $collector['id'] ?>">
                                        <td class="font-medium"><?= htmlspecialchars($collector['name']) ?></td>
                                        <td><?= htmlspecialchars($collector['email']) ?></td>
                                        <td><?= htmlspecialchars($collector['phone']) ?></td>
                                        <td><?= htmlspecialchars($collector['todayPickups']) ?></td>
                                        <td><?= getStatusBadge($collector['status']) ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="icon-button" onclick="viewUser(this, 'collector')"
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
    function showTab(tabName, updateUrl = true) {
        if (!tabName) return;

        // Hide all tab contents
        const contents = document.querySelectorAll('.tabs-content');
        contents.forEach(content => content.classList.remove('active'));

        // Remove active class from all triggers
        const triggers = document.querySelectorAll('.tabs-trigger');
        triggers.forEach(trigger => trigger.classList.remove('active'));

        // Show selected tab content (guard elements exist)
        const contentEl = document.getElementById(tabName + '-content');
        const triggerEl = document.getElementById(tabName + '-tab');
        if (contentEl) contentEl.classList.add('active');
        if (triggerEl) triggerEl.classList.add('active');

        // Update the URL query parameter so the tab state persists on refresh
        if (updateUrl && window.history && window.location) {
            try {
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tabName);
                // Use replaceState to avoid polluting history with each click
                window.history.replaceState(null, '', url.toString());
            } catch (e) {
                // Fallback: set hash if URL API isn't available
                window.location.hash = '#tab=' + encodeURIComponent(tabName);
            }
        }
    }

    // Search functionality (use visible table cells instead of removed data-* attributes)
    function filterUsers() {
        const searchTerm = (document.getElementById('searchInput').value || '').toLowerCase();
        const activeTab = document.querySelector('.tabs-content.active');
        if (!activeTab) return;
        const table = activeTab.querySelector('table');
        if (!table) return;
        const rows = table.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            const name = (cells[0] && cells[0].textContent || '').toLowerCase();
            const email = (cells[1] && cells[1].textContent || '').toLowerCase();

            if (name.includes(searchTerm) || email.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // User management functions
    function viewUser(el, userType) {
        // Populate and open the modal by looking up the full record in window.__USER_DATA
        // Falls back to reading visible table cells when the store doesn't have the record.
        if (!el || !el.closest) return;
        const row = el.closest('tr');
        if (!row) return;

        const id = row.getAttribute('data-id') || '';
        const rowType = (userType && String(userType).toLowerCase()) || (row.getAttribute('data-user-type') || '').toLowerCase();

        // Map singular type -> store key
        const lookupMap = { customer: 'customers', company: 'companies', collector: 'collectors' };
        const storeKey = lookupMap[rowType] || null;

        let user = null;
        try {
            if (window.__USER_DATA && storeKey && Array.isArray(window.__USER_DATA[storeKey])) {
                const pool = window.__USER_DATA[storeKey];
                user = pool.find(u => (u.id || '').toString().toLowerCase() === id.toString().toLowerCase()) || null;
            }
        } catch (err) {
            console.warn('user lookup failed', err);
            user = null;
        }

        // Fallback: read visible table cells (name/email/phone etc.)
        const cells = row.querySelectorAll('td');
        const fallback = {
            id: id,
            name: (cells[0] && cells[0].textContent.trim()) || '',
            email: (cells[1] && cells[1].textContent.trim()) || '',
            phone: (cells[2] && cells[2].textContent.trim()) || '',
            // attempt to parse commonly present numeric columns where applicable
            totalPickups: (cells[3] && cells[3].textContent.trim()) || '',
            totalEarnings: (cells[4] && cells[4].textContent.replace(/[^0-9.\-]/g, '').trim()) || '0',
            status: (cells[5] && cells[5].textContent.trim()) || ''
        };

        const src = user || fallback;

        // Fill modal fields
        const modal = document.getElementById('user-detail-modal');
        if (!modal) return;
        const setOrHide = (selector, text, opts = {}) => {
            const elm = modal.querySelector(selector);
            if (!elm) return;
            const label = elm.previousElementSibling; // should be the <div><strong>Label</strong></div>

            // Normalize text
            const value = (text === null || text === undefined) ? '' : String(text).trim();

            // Decide visibility: hide when value is empty or a single dash
            const hide = (value === '' || value === '-');

            if (hide) {
                if (label) label.style.display = 'none';
                elm.style.display = 'none';
            } else {
                if (label) label.style.display = '';
                elm.style.display = '';
                elm.textContent = value;
            }
        };
        // Define all possible selectors and field groups per user type
        const allSelectors = ['.ud-id', '.ud-name', '.ud-email', '.ud-phone', '.ud-address', '.ud-status', '.ud-vehicle', '.ud-totalpickups', '.ud-totalearnings', '.ud-totalbids', '.ud-totalpurchases'];

        const allowedByType = {
            customer: ['.ud-id', '.ud-name', '.ud-email', '.ud-phone', '.ud-address', '.ud-status', '.ud-totalpickups', '.ud-totalearnings'],
            company: ['.ud-id', '.ud-name', '.ud-email', '.ud-phone', '.ud-status', '.ud-totalbids', '.ud-totalpurchases'],
            collector: ['.ud-id', '.ud-name', '.ud-email', '.ud-phone', '.ud-status', '.ud-vehicle', '.ud-totalpickups']
        };

        // Hide everything first
        allSelectors.forEach(sel => {
            const e = modal.querySelector(sel);
            if (e) {
                const lbl = e.previousElementSibling;
                if (lbl) lbl.style.display = 'none';
                e.style.display = 'none';
            }
        });
        // Populate and show only allowed selectors for this user type
        const allowed = allowedByType[rowType] || [];
        if (allowed.includes('.ud-id')) setOrHide('.ud-id', src.id || '');
        if (allowed.includes('.ud-name')) setOrHide('.ud-name', src.name || '');
        if (allowed.includes('.ud-email')) setOrHide('.ud-email', src.email || '');
        if (allowed.includes('.ud-phone')) setOrHide('.ud-phone', src.phone || '');
        if (allowed.includes('.ud-address')) setOrHide('.ud-address', src.address || '');
        if (allowed.includes('.ud-status')) setOrHide('.ud-status', src.status ? (src.status.charAt(0).toUpperCase() + src.status.slice(1)) : '');
        if (allowed.includes('.ud-vehicle')) setOrHide('.ud-vehicle', src.vehicleId || src.vehicle || '');
        if (allowed.includes('.ud-totalpickups')) setOrHide('.ud-totalpickups', src.totalPickups || src.todayPickups || src.totalPickups);

        // Earnings formatted
        const earningsRaw = src.totalEarnings || src.totalEarnings === 0 ? src.totalEarnings : (src.totalEarnings || src.totalEarnings === 0 ? src.totalEarnings : src.totalEarnings || src.totalEarnings);
        const earningsVal = parseFloat(earningsRaw || fallback.totalEarnings || 0);
        if (allowed.includes('.ud-totalearnings')) {
            if (!isNaN(earningsVal)) {
                setOrHide('.ud-totalearnings', 'Rs ' + earningsVal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            } else {
                setOrHide('.ud-totalearnings', fallback.totalEarnings || '0');
            }
        }

        if (allowed.includes('.ud-totalbids')) setOrHide('.ud-totalbids', src.totalBids || '0');
        if (allowed.includes('.ud-totalpurchases')) setOrHide('.ud-totalpurchases', src.totalPurchases || '0');

        // Open modal
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
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

    // Initialize search functionality and restore tab from URL on page load
    document.addEventListener('DOMContentLoaded', function () {
        // Add event listener to search input for real-time filtering
        const searchInput = document.getElementById('searchInput');
        if (searchInput) searchInput.addEventListener('input', filterUsers);

        // Restore active tab from URL (?tab=...) or from hash (#tab=...)
        let tabFromUrl = null;
        try {
            const params = new URL(window.location.href).searchParams;
            tabFromUrl = params.get('tab');
        } catch (e) {
            // ignore
        }

        if (!tabFromUrl && window.location.hash) {
            const m = window.location.hash.match(/tab=([^&]+)/);
            if (m && m[1]) tabFromUrl = decodeURIComponent(m[1]);
        }

        // Default to 'customers' if nothing provided
        const initialTab = tabFromUrl || 'customers';
        showTab(initialTab, /* updateUrl= */ false);
    });

    // Capitalize helper
    function capitalize(s) {
        if (!s) return '';
        return s.charAt(0).toUpperCase() + s.slice(1);
    }

    // Modal close handlers
    document.addEventListener('click', function (e) {
        const modal = document.getElementById('user-detail-modal');
        if (!modal) return;

        if (e.target.matches('#user-detail-modal .close') || e.target.matches('#user-detail-modal')) {
            modal.classList.remove('open');

            // Remove view/id from URL without reloading
            try {
                const url = new URL(window.location.href);
                if (url.searchParams.has('view') || url.searchParams.has('id')) {
                    url.searchParams.delete('view');
                    url.searchParams.delete('id');
                    window.history.replaceState(null, '', url.toString());
                }
            } catch (err) {
                // ignore
            }
        }
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
