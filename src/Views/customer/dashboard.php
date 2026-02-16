<?php
use function htmlspecialchars as e;

$initialPickupRequests = $pickupRequests ?? $recentPickups ?? [];
$pickupRequests = is_array($initialPickupRequests) ? array_values($initialPickupRequests) : [];
// Remove cancelled requests from the dashboard so they are never displayed here
$pickupRequests = array_values(array_filter($pickupRequests, static function ($r) {
    $status = strtolower((string) ($r['status'] ?? ''));
    return $status !== 'cancelled';
}));
$filter = $_GET['filter'] ?? 'all';
$normalizedFilter = is_string($filter) ? strtolower($filter) : 'all';

$filteredRequests = $pickupRequests;
if ($normalizedFilter !== 'all') {
    $filteredRequests = array_values(array_filter(
        $pickupRequests,
        static function ($request) use ($normalizedFilter) {
            $status = strtolower((string) ($request['status'] ?? ''));
            return $status === $normalizedFilter;
        }
    ));
}

$pendingCount = 0;
$scheduledCount = 0;
$completedCount = 0;
foreach ($pickupRequests as $request) {
    $status = strtolower((string) ($request['status'] ?? ''));
    if ($status === 'pending') {
        $pendingCount++;
    }
    if (in_array($status, ['assigned', 'confirmed'], true)) {
        $scheduledCount++;
    }
    if ($status === 'completed') {
        $completedCount++;
    }
}
$totalCount = count($pickupRequests);

if (!function_exists('customer_pickup_status_class')) {
    function customer_pickup_status_class(string $status): string
    {
        $normalized = strtolower($status);
        switch ($normalized) {
            case 'pending':
                return 'pending';
            case 'assigned':
            case 'confirmed':
                return 'assigned';
            case 'completed':
                return 'completed';
            case 'cancelled':
                return 'warning';
            default:
                return 'secondary';
        }
    }
}

if (!function_exists('customer_pickup_format_datetime')) {
    function customer_pickup_format_datetime(?string $value): string
    {
        if (!$value) {
            return '-';
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return '-';
        }

        return date('M d, Y', $timestamp);
    }
}

$customerStats = [
    [
        'title' => 'Total Requests',
        'value' => $totalCount,
        'icon' => 'fa-solid fa-truck',
        'subtitle' => 'All time',
    ],
    [
        'title' => 'Pending Request',
        'value' => $pendingCount,
        'icon' => 'fa-solid fa-hourglass-half',
        'subtitle' => 'Awaiting confirmation',
    ],
    [
        'title' => 'Scheduled Pickups',
        'value' => $scheduledCount,
        'icon' => 'fa-solid fa-calendar-check',
        'subtitle' => 'Assigned / Confirmed',
    ],
    [
        'title' => 'Total Income',
        'value' => "Rs: 10,000.00",
        'icon' => 'fa-solid fa-wallet',
        'subtitle' => 'Total earnings (Rs)'
    ],
];
?>

<!-- Main Content -->
<div class="main-content">
    <div class="dashboard-page">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content" style="display: flex; align-items: center; gap: 1.5rem;">
                <?php
                $profileData = $userProfile ?? [];
                $firstName = $profileData['firstName'] ?? ($user['name'] ?? 'Customer');
                $firstName = $firstName !== '' ? $firstName : ($user['name'] ?? 'Customer');
                $imagePath = $profileData['profileImage'] ?? null;
                $profilePic = $imagePath ? asset($imagePath) : asset('assets/logo-icon.png');
                ?>
                <img src="<?= htmlspecialchars($profilePic) ?>" class="avatar"
                    style="width:56px;height:56px;object-fit:cover;border-radius:50%;border:2px solid #e0f2fe;box-shadow:0 2px 8px rgba(34,197,94,0.08);">
                <h1 style="margin:0;">Welcome back, <?= htmlspecialchars($firstName) ?>!</h1>
            </div>
        </div>

        <!-- Stats Feature Cards -->
        <div class="stats-grid">
            <?php foreach ($customerStats as $stat): ?>
                <div class="feature-card">
                    <div class="feature-card__header">
                        <h3 class="feature-card__title">
                            <?= e($stat['title']) ?>
                        </h3>
                        <div class="feature-card__icon">
                            <i class="<?= e($stat['icon']) ?>"></i>
                        </div>
                    </div>
                    <p class="feature-card__body">
                        <?= e((string) $stat['value']) ?>
                    </p>
                    <div class="feature-card__footer">
                        <span class="tag success"><?= e($stat['subtitle']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Recent Pickups Table -->
        <div class="table-section">
            <div class="section-header">
                <h2 class="section-title">Recent Pickups</h2>
                <p class="section-subtitle">Your latest waste collection activities</p>
            </div>

            <div class="action-buttons" style="margin-bottom:1.5rem;">
                <?php
                $filters = [
                    'all' => 'All Requests',
                    'pending' => 'Pending',
                    'assigned' => 'Assigned',
                    'confirmed' => 'Confirmed',
                    'completed' => 'Completed',
                    // cancelled intentionally omitted from dashboard filters
                ];
                foreach ($filters as $key => $label):
                    $isActive = $normalizedFilter === $key ? 'btn-primary' : 'btn-outline';
                    ?>
                    <button type="button" class="btn <?= $isActive ?>" data-filter="<?= e($key) ?>">
                        <?= e($label) ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="table-container"
                style="overflow-x:auto;box-shadow:0 2px 12px rgba(34,197,94,0.08);border-radius:16px;">
                <table class="data-table" style="min-width:900px;">
                    <thead>
                        <tr>
                            <th style="width:60px;">PID</th>
                            <th>Address</th>
                            <th>Time Slot</th>
                            <th>Waste Categories</th>
                            <th>Created</th>
                            <th>Scheduled</th>
                            <th>Collector</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="dashboard-pickup-body">
                        <?php if (empty($filteredRequests)): ?>
                            <tr>
                                <td colspan="9" class="empty-state">
                                    <div class="empty-content">
                                        <div class="empty-icon">📦</div>
                                        <h3>No pickup requests found</h3>
                                        <p>No pickup requests match your current filter.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($filteredRequests as $request):
                                $status = (string) ($request['status'] ?? 'pending');
                                $collector = $request['collectorName'] ?? '';
                                $categoryListRaw = $request['wasteCategories'] ?? [];
                                $categoryList = is_array($categoryListRaw) ? $categoryListRaw : [];
                                ?>
                                <tr data-request-id="<?= e((string) $request['id']) ?>">
                                    <td><?= e((string) $request['id']) ?></td>
                                    <td><?= e((string) ($request['address'] ?? '')) ?></td>
                                    <td><?= e((string) ($request['timeSlot'] ?? '')) ?></td>
                                    <td>
                                        <?php
                                        $categoryNames = array_values(array_filter(array_map('strval', is_array($categoryList) ? $categoryList : [])));
                                        ?>
                                        <?php if (!empty($categoryNames)): ?>
                                            <div class="badge-group">
                                                <?php foreach ($categoryNames as $categoryName): ?>
                                                    <span class="tag"><?= e($categoryName) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span>-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e(customer_pickup_format_datetime($request['createdAt'] ?? null)) ?></td>
                                    <td><?= e(customer_pickup_format_datetime($request['scheduledAt'] ?? null)) ?></td>
                                    <td><?= e($collector !== '' ? $collector : '-') ?></td>
                                    <td>
                                        <span class="tag <?= e(customer_pickup_status_class($status)) ?>">
                                            <?= e(ucfirst($status)) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        // Client-side renderer for the Recent Pickups table so filtering does not change the URL
        const state = {
            requests: <?= json_encode($pickupRequests, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
            filter: '<?= e($normalizedFilter) ?>'
        };

        const tableBody = document.getElementById('dashboard-pickup-body');
        const filterButtons = document.querySelectorAll('[data-filter]');

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function formatDate(value) {
            if (!value) return '-';
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) {
                const parsed = new Date(String(value).replace(' ', 'T'));
                if (Number.isNaN(parsed.getTime())) return '-';
                return parsed.toLocaleDateString(undefined, { month: 'short', day: '2-digit', year: 'numeric' });
            }
            return date.toLocaleDateString(undefined, { month: 'short', day: '2-digit', year: 'numeric' });
        }

        function statusClass(status) {
            const map = {
                pending: 'pending',
                assigned: 'assigned',
                confirmed: 'assigned',
                completed: 'completed',
                cancelled: 'warning'
            };
            return map[(status || '').toLowerCase()] || 'secondary';
        }

        function renderWasteCategories(rawList) {
            const list = Array.isArray(rawList) ? rawList : [];
            const normalized = list.map((item) => (typeof item === 'string' ? item.trim() : String(item).trim())).filter(Boolean);
            if (!normalized.length) return '<span>-</span>';
            return '<div class="badge-group">' + normalized.map(n => `<span class="tag">${escapeHtml(n)}</span>`).join('') + '</div>';
        }

        function capitalize(str) {
            if (!str) return '';
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        function renderTable() {
            if (!tableBody) return;

            const filtered = state.filter === 'all' ? state.requests : state.requests.filter(r => ((r.status || '').toLowerCase()) === state.filter);

            if (!filtered.length) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="9" class="empty-state">
                            <div class="empty-content">
                                <div class="empty-icon">📦</div>
                                <h3>No pickup requests found</h3>
                                <p>No pickup requests match your current filter.</p>
                            </div>
                        </td>
                    </tr>`;
                return;
            }

            const rows = filtered.map((request) => {
                const status = (request.status || 'pending');
                const normalizedStatus = status.toLowerCase();
                const collector = request.collectorName ? request.collectorName : '-';
                const canEdit = ['pending', 'assigned'].includes(normalizedStatus);
                const canCancel = ['pending', 'assigned', 'confirmed'].includes(normalizedStatus);

                return `
                    <tr data-request-id="${escapeHtml(String(request.id))}">
                        <td>${escapeHtml(String(request.id))}</td>
                        <td>${escapeHtml(request.address || '')}</td>
                        <td>${escapeHtml(request.timeSlot || '')}</td>
                        <td>${renderWasteCategories(request.wasteCategories)}</td>
                        <td>${escapeHtml(formatDate(request.createdAt))}</td>
                        <td>${escapeHtml(formatDate(request.scheduledAt))}</td>
                        <td>${escapeHtml(collector)}</td>
                        <td><span class="tag ${statusClass(status)}">${escapeHtml(capitalize(status))}</span></td>
                    </tr>`;
            });

            tableBody.innerHTML = rows.join('');
        }

        function attachFilterListeners() {
            filterButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    state.filter = button.getAttribute('data-filter') || 'all';
                    filterButtons.forEach((btn) => {
                        btn.classList.remove('btn-primary');
                        btn.classList.add('btn-outline');
                    });
                    button.classList.remove('btn-outline');
                    button.classList.add('btn-primary');
                    renderTable();
                });
            });
        }

        // Delegate edit/cancel buttons to existing page (they link to /customer/pickup currently)
        document.addEventListener('click', function (e) {
            const target = e.target;
            if (!(target instanceof HTMLElement)) return;
            const action = target.getAttribute('data-action');
            const id = target.getAttribute('data-id');
            if (!action || !id) return;

            if (action === 'edit') {
                // Navigate to the full pickup management page to edit
                window.location.href = '/customer/pickup?edit=' + encodeURIComponent(id);
            } else if (action === 'cancel') {
                window.location.href = '/customer/pickup?cancel=' + encodeURIComponent(id);
            }
        });

        // Initialize
        attachFilterListeners();
        renderTable();
    })();
</script>