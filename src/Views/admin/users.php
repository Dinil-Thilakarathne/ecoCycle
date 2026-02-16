<?php
// View receives $customers, $companies, $collectors from the controller
// Ensure variables exist to avoid undefined notices
$customers = $customers ?? [];
$companies = $companies ?? [];
$customers = $customers ?? [];
$companies = $companies ?? [];
$collectors = $collectors ?? [];
$vehicles = $vehicles ?? [];


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
    window.__USER_DATA = <?php echo json_encode(['customers' => $customers, 'companies' => $companies, 'collectors' => $collectors, 'vehicles' => $vehicles], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
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
                    <div>
                        <h3 class="activity-card__title">Collector Management</h3>
                        <p class="activity-card__description">Manage collector accounts and assignments</p>
                    </div>
                    <button class="btn btn-primary" onclick="showAddUserModal()" style="margin-left: auto;">
                        <i class="fa-solid fa-user-plus"></i> Add User
                    </button>
                </div>
                <div class="activity-card__content">
                    <div style="overflow-x: auto;">
                        <table class="data-table" id="collectors-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Vehicle</th>
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
                                        <td>
                                            <?php
                                            $assignedVehicle = '-';
                                            if (!empty($collector['vehicleId'])) {
                                                foreach ($vehicles as $v) {
                                                    if ($v['id'] == $collector['vehicleId']) {
                                                        $assignedVehicle = htmlspecialchars($v['plateNumber'] . ' (' . $v['type'] . ')');
                                                        break;
                                                    }
                                                }
                                            }
                                            echo $assignedVehicle;
                                            ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($collector['todayPickups']) ?>
                                        </td>
                                        <td>
                                            <?= getStatusBadge($collector['status']) ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="icon-button" onclick="viewUser(this, 'collector')"
                                                    title="View Details">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>

                                                <button class="icon-button suspend" onclick="suspendUser('
                                            <?= $collector['id'] ?>', 'collector')" title="Suspend Collector">
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
        // Populate by looking up the full record in window.__USER_DATA
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
        }

        // Fallback or use found user
        const src = user || {};
        // Merge fallback data from table cells if user not found or incomplete
        if (!user) {
            const cells = row.querySelectorAll('td');
            src.id = id;
            src.name = (cells[0] && cells[0].textContent.trim()) || '';
            src.email = (cells[1] && cells[1].textContent.trim()) || '';
            src.phone = (cells[2] && cells[2].textContent.trim()) || '';
            // add other fields as needed for fallback
        }

        // Define fields to show
        const allFields = [
            { key: 'id', label: 'ID', types: ['all'] },
            { key: 'name', label: 'Name', types: ['all'] },
            { key: 'email', label: 'Email', types: ['all'] },
            { key: 'phone', label: 'Phone', types: ['all'] },
            { key: 'address', label: 'Address', types: ['customer'] },
            { key: 'status', label: 'Status', types: ['all'], format: v => v ? (v.charAt(0).toUpperCase() + v.slice(1)) : '-' },
            { key: 'vehicleId', label: 'Vehicle ID', types: ['collector'], altKeys: ['vehicle'] },
            { key: 'totalPickups', label: 'Total Pickups', types: ['customer', 'collector'], altKeys: ['todayPickups'] },
            { key: 'totalEarnings', label: 'Total Earnings', types: ['customer'], format: v => 'Rs ' + parseFloat(v || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
            { key: 'totalBids', label: 'Total Bids', types: ['company'] },
            { key: 'totalPurchases', label: 'Total Purchases', types: ['company'] }
        ];

        const content = document.createElement('div');
        content.className = 'user-modal__grid'; // Keep existing grid class if styles compatible, or use inline
        // If existing CSS class isn't available globally, we can set inline styles
        content.style.display = 'grid';
        content.style.gridTemplateColumns = '1fr 2fr';
        content.style.gap = '8px 16px';
        content.style.fontSize = '0.9rem';

        allFields.forEach(field => {
            if (!field.types.includes('all') && !field.types.includes(rowType)) return;

            let val = src[field.key];
            if ((val === undefined || val === null) && field.altKeys) {
                for (const k of field.altKeys) {
                    if (src[k] !== undefined && src[k] !== null) {
                        val = src[k];
                        break;
                    }
                }
            }

            if (!val && val !== 0) val = '-'; // Show dash for empty

            if (field.format) val = field.format(val);

            const labelEl = document.createElement('div');
            labelEl.style.fontWeight = '600';
            labelEl.style.color = '#374151';
            labelEl.textContent = field.label;

            const valEl = document.createElement('div');
            valEl.style.color = '#111827';
            valEl.textContent = String(val);

            content.appendChild(labelEl);
            content.appendChild(valEl);
        });

        Modal.open({
            title: 'User Details',
            content: content,
            actions: [{ label: 'Close', variant: 'outline', dismiss: true }]
        });
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
        } catch (e) { }

        if (!tabFromUrl && window.location.hash) {
            const m = window.location.hash.match(/tab=([^&]+)/);
            if (m && m[1]) tabFromUrl = decodeURIComponent(m[1]);
        }

        // Default to 'customers' if nothing provided
        const initialTab = tabFromUrl || 'customers';
        showTab(initialTab, /* updateUrl= */ false);

        // Check if server requested a specific user view (SSR support)
        // We do this by checking if we have PHP injected variables
        // But since we removed the PHP modal code, we can check logic or just rely on client actions.
        // If you want to auto-open based on URL params logic from PHP step 98:
        // We can replicate that logic here if needed, finding the row and clicking it.
        const urlParams = new URL(window.location.href).searchParams;
        if (urlParams.has('view') && urlParams.has('id')) {
            const v = urlParams.get('view');
            const i = urlParams.get('id');
            // Attempt to find row
            const selector = `tr[data-user-type="${v}"][data-id="${i}"] .icon-button[title="View Details"]`;
            const btn = document.querySelector(selector);
            if (btn) btn.click();
        }
    });

    // Capitalize helper
    function capitalize(s) {
        if (!s) return '';
        return s.charAt(0).toUpperCase() + s.slice(1);
    }

    function suspendUser(userId, userType) {
        const container = document.createElement('div');
        container.innerHTML = `
            <div style="margin-bottom: 1rem;">
                <p style="margin-bottom: 0.5rem;">Please enter the reason for suspending this user:</p>
                <textarea class="form-control" rows="4" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;" placeholder="Reason for suspension..."></textarea>
                <div class="error-msg" style="color: #ef4444; font-size: 0.875rem; margin-top: 0.25rem; display: none;">Reason is required</div>
            </div>
        `;

        Modal.open({
            title: 'Suspend User',
            content: container,
            actions: [
                { label: 'Cancel', variant: 'outline', dismiss: true },
                {
                    label: 'Suspend User',
                    variant: 'primary', // Assumes css for danger/primary mapped or use styling
                    dismiss: false,
                    loadingLabel: 'Suspending...',
                    onClick: async ({ body, close, setLoading }) => {
                        const textarea = body.querySelector('textarea');
                        const errorMsg = body.querySelector('.error-msg');
                        const reason = textarea.value.trim();

                        if (!reason) {
                            errorMsg.style.display = 'block';
                            return;
                        } else {
                            errorMsg.style.display = 'none';
                        }

                        setLoading(true);

                        try {
                            const response = await fetch('/api/users/suspend', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    userId: userId,
                                    reason: reason
                                })
                            });

                            const data = await response.json();

                            if (data.success) {
                                if (window.toast) toast(`${capitalize(userType)} suspended successfully.`, 'success');
                                else alert(`${capitalize(userType)} suspended successfully.`);
                                close();
                                // Optional: reload or update UI
                                // location.reload(); 
                            } else {
                                alert(data.error || 'Failed to suspend user');
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            alert('An error occurred while suspending the user.');
                        } finally {
                            setLoading(false);
                        }
                    }
                }
            ]
        });
    }

    function showAddUserModal() {
        const container = document.createElement('div');
        container.innerHTML = `
            <div style="display: grid; gap: 1rem;">
                <div>
                    <label style="display: block; margin-bottom: 0.25rem; font-weight: 500;">Name *</label>
                    <input type="text" id="newUserName" class="form-control" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;" placeholder="Full name" />
                    <div class="error-msg" id="nameError" style="color: #ef4444; font-size: 0.875rem; margin-top: 0.25rem; display: none;"></div>
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 0.25rem; font-weight: 500;">Email *</label>
                    <input type="email" id="newUserEmail" class="form-control" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;" placeholder="email@example.com" />
                    <div class="error-msg" id="emailError" style="color: #ef4444; font-size: 0.875rem; margin-top: 0.25rem; display: none;"></div>
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 0.25rem; font-weight: 500;">Phone *</label>
                    <input type="tel" id="newUserPhone" class="form-control" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;" placeholder="0771234567" />
                    <div class="error-msg" id="phoneError" style="color: #ef4444; font-size: 0.875rem; margin-top: 0.25rem; display: none;"></div>
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 0.25rem; font-weight: 500;">Password *</label>
                    <input type="password" id="newUserPassword" class="form-control" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;" placeholder="Minimum 6 characters" />
                    <div class="error-msg" id="passwordError" style="color: #ef4444; font-size: 0.875rem; margin-top: 0.25rem; display: none;"></div>
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 0.25rem; font-weight: 500;">Confirm Password *</label>
                    <input type="password" id="newUserPasswordConfirm" class="form-control" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;" placeholder="Re-enter password" />
                    <div class="error-msg" id="passwordConfirmError" style="color: #ef4444; font-size: 0.875rem; margin-top: 0.25rem; display: none;"></div>
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 0.25rem; font-weight: 500;">License Number *</label>
                    <input type="text" id="newUserLicense" class="form-control" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;" placeholder="Driver's license number" />
                    <div class="error-msg" id="licenseError" style="color: #ef4444; font-size: 0.875rem; margin-top: 0.25rem; display: none;"></div>
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 0.25rem; font-weight: 500;">NIC *</label>
                    <input type="text" id="newUserNIC" class="form-control" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;" placeholder="National Identity Card number" />
                    <div class="error-msg" id="nicError" style="color: #ef4444; font-size: 0.875rem; margin-top: 0.25rem; display: none;"></div>
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 0.25rem; font-weight: 500;">Address *</label>
                    <textarea id="newUserAddress" class="form-control" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; min-height: 80px;" placeholder="Full address"></textarea>
                    <div class="error-msg" id="addressError" style="color: #ef4444; font-size: 0.875rem; margin-top: 0.25rem; display: none;"></div>
                </div>
            </div>
        `;

        Modal.open({
            title: 'Add New Collector',
            content: container,
            actions: [
                { label: 'Cancel', variant: 'outline', dismiss: true },
                {
                    label: 'Create User',
                    variant: 'primary',
                    dismiss: false,
                    loadingLabel: 'Creating...',
                    onClick: async ({ body, close, setLoading }) => {
                        // Clear previous errors
                        body.querySelectorAll('.error-msg').forEach(el => el.style.display = 'none');

                        // Get form values
                        const name = body.querySelector('#newUserName').value.trim();
                        const email = body.querySelector('#newUserEmail').value.trim();
                        const phone = body.querySelector('#newUserPhone').value.trim();
                        const password = body.querySelector('#newUserPassword').value;
                        const passwordConfirm = body.querySelector('#newUserPasswordConfirm').value;
                        const license = body.querySelector('#newUserLicense').value.trim();
                        const nic = body.querySelector('#newUserNIC').value.trim();
                        const address = body.querySelector('#newUserAddress').value.trim();

                        // Validation
                        let hasError = false;

                        if (!name) {
                            body.querySelector('#nameError').textContent = 'Name is required';
                            body.querySelector('#nameError').style.display = 'block';
                            hasError = true;
                        }

                        if (!email) {
                            body.querySelector('#emailError').textContent = 'Email is required';
                            body.querySelector('#emailError').style.display = 'block';
                            hasError = true;
                        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                            body.querySelector('#emailError').textContent = 'Please provide a valid email address';
                            body.querySelector('#emailError').style.display = 'block';
                            hasError = true;
                        }

                        if (!phone) {
                            body.querySelector('#phoneError').textContent = 'Phone number is required';
                            body.querySelector('#phoneError').style.display = 'block';
                            hasError = true;
                        }

                        if (!password) {
                            body.querySelector('#passwordError').textContent = 'Password is required';
                            body.querySelector('#passwordError').style.display = 'block';
                            hasError = true;
                        } else if (password.length < 6) {
                            body.querySelector('#passwordError').textContent = 'Password must be at least 6 characters';
                            body.querySelector('#passwordError').style.display = 'block';
                            hasError = true;
                        }

                        if (password !== passwordConfirm) {
                            body.querySelector('#passwordConfirmError').textContent = 'Passwords do not match';
                            body.querySelector('#passwordConfirmError').style.display = 'block';
                            hasError = true;
                        }

                        if (!license) {
                            body.querySelector('#licenseError').textContent = 'License number is required';
                            body.querySelector('#licenseError').style.display = 'block';
                            hasError = true;
                        }

                        if (!nic) {
                            body.querySelector('#nicError').textContent = 'NIC is required';
                            body.querySelector('#nicError').style.display = 'block';
                            hasError = true;
                        }

                        if (!address) {
                            body.querySelector('#addressError').textContent = 'Address is required';
                            body.querySelector('#addressError').style.display = 'block';
                            hasError = true;
                        }

                        if (hasError) {
                            return;
                        }

                        setLoading(true);

                        try {
                            const response = await fetch('/api/users', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    name: name,
                                    email: email,
                                    phone: phone,
                                    password: password,
                                    type: 'collector',
                                    licenseNumber: license,
                                    nic: nic,
                                    address: address
                                })
                            });

                            const data = await response.json();

                            if (response.ok && data.success) {
                                if (window.toast) toast('Collector created successfully', 'success');
                                else alert('Collector created successfully');
                                close();
                                // Reload page to show new collector
                                location.reload();
                            } else {
                                const errorMsg = data.error || data.message || 'Failed to create collector';
                                if (window.toast) toast(errorMsg, 'error');
                                else alert(errorMsg);
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            if (window.toast) toast('An error occurred while creating the collector', 'error');
                            else alert('An error occurred while creating the collector');
                        } finally {
                            setLoading(false);
                        }
                    }
                }
            ]
        });
    }
</script>