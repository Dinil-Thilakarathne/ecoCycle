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

                                                                <button class="icon-button suspend"
                                                onclick="suspendUser('
                                            <?= $collector['id'] ?>', 'collector')"
                                                title="Suspend Collector">
                                                <i class="fa-solid fa-user-times"></i>
                                                </button>

                                                <button class="icon-button"
                                                    onclick="assignVehicle('<?= $collector['id'] ?>')"
                                                    title="Assign Vehicle">
                                                    <i class="fa-solid fa-truck-moving"></i>
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

    function assignVehicle(userId) {
        // Find user
        let user = null;
        if (window.__USER_DATA && window.__USER_DATA.collectors) {
            user = window.__USER_DATA.collectors.find(u => u.id == userId);
        }

        const currentVehicleId = user ? (user.vehicleId || null) : null;
        const vehicles = window.__USER_DATA.vehicles || [];

        // Filter available vehicles + current vehicle
        const options = vehicles.filter(v => v.status === 'available' || v.id == currentVehicleId);

        // Build select options
        let optionsHtml = '<option value="">-- No Vehicle (Unassign) --</option>';
        options.forEach(v => {
            const selected = v.id == currentVehicleId ? 'selected' : '';
            optionsHtml += `<option value="${v.id}" ${selected}>${v.plate_number || v.plateNumber} (${v.type})</option>`;
        });

        const container = document.createElement('div');
        container.innerHTML = `
            <div style="margin-bottom: 1rem;">
                <p style="margin-bottom: 0.5rem;">Select a vehicle to assign to this collector:</p>
                <select class="form-control" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                    ${optionsHtml}
                </select>
            </div>
        `;

        Modal.open({
            title: 'Assign Vehicle',
            content: container,
            actions: [
                { label: 'Cancel', variant: 'outline', dismiss: true },
                {
                    label: 'Save Assignment',
                    variant: 'primary',
                    dismiss: false,
                    loadingLabel: 'Saving...',
                    onClick: async ({ body, close, setLoading }) => {
                        const select = body.querySelector('select');
                        const vehicleId = select.value;

                        setLoading(true);

                        try {
                            const response = await fetch('/api/users/assign-vehicle', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ userId: userId, vehicleId: vehicleId })
                            });

                            const data = await response.json();

                            if (data.success || response.ok) {
                                if (window.toast) toast('Vehicle assignment updated.', 'success');
                                else alert('Vehicle assignment updated.');
                                close();
                                location.reload();
                            } else {
                                alert(data.error || 'Failed to assign vehicle');
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            alert('An error occurred.');
                        } finally {
                            setLoading(false);
                        }
                    }
                }
            ]
        });
    }
</script>