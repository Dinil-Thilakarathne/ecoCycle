

    <!-- Main Content -->
    <div class="main-content">
        <div class="dashboard-page">
            <!-- Page Header -->
            <div class="page-header">
                <div class="header-content">
                    <h1 class="page-title">Dashboard</h1>
                    <p class="page-subtitle">Welcome back, John!</p>
                </div>
            </div>

            <!-- Stats Feature Cards -->
            <?php
            $customerStats = [
                [
                    'title' => 'Next Pickup',
                    'value' => 'Tomorrow',
                    'icon' => 'fa-solid fa-calendar-day',
                    'subtitle' => '9:00 AM',
                ],
                [
                    'title' => 'Monthly Collected',
                    'value' => '45 kg',
                    'icon' => 'fa-solid fa-weight-hanging',
                    'subtitle' => 'This month',
                ],
                [
                    'title' => 'Total Earnings',
                    'value' => '$127.50',
                    'icon' => 'fa-solid fa-dollar-sign',
                    'subtitle' => 'This month',
                ],
                [
                    'title' => 'Completed Pickups',
                    'value' => '12',
                    'icon' => 'fa-solid fa-check-circle',
                    'subtitle' => 'This month',
                ],
            ];
            ?>
            <div class="stats-grid">
                <?php foreach ($customerStats as $stat): ?>
                    <div class="feature-card">
                        <div class="feature-card__header">
                            <h3 class="feature-card__title">
                                <?= htmlspecialchars($stat['title']) ?>
                            </h3>
                            <div class="feature-card__icon">
                                <i class="<?= htmlspecialchars($stat['icon']) ?>"></i>
                            </div>
                        </div>
                        <p class="feature-card__body">
                            <?= htmlspecialchars($stat['value']) ?>
                        </p>
                        <div class="feature-card__footer">
                            <span class="tag success"><?= htmlspecialchars($stat['subtitle']) ?></span>
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
                
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Pickup ID</th>
                                <th>Date & Time</th>
                                <th>Waste Categories</th>
                                <th>Weight</th>
                                <th>Status</th>
                                <th>Collector</th>
                                <th>Earnings</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>PU001</strong></td>
                                <td>
                                    <div class="cell-with-icon">
                                        <i class="fa-solid fa-calendar"></i>
                                        <div class="datetime-info">
                                            <span class="date">2024-01-10</span>
                                            <span class="time">09:00 AM</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="badge-group">
                                        <div class="tag">Plastic</div>
                                        <div class="tag">Paper</div>
                                    </div>
                                </td>
                                <td>
                                    <div class="cell-with-icon">
                                        <i class="fa-solid fa-weight-hanging"></i>
                                        15 kg
                                    </div>
                                </td>
                                <td><span class="status-badge completed">Completed</span></td>
                                <td>Mike Johnson</td>
                                <td class="earnings-cell">
                                    <span class="earnings-amount">$45.50</span>
                                </td>
                                <td>
                                    <button class="view-details-btn">View Details</button>
                                </td>
                            </tr>
                            
                            <tr>
                                <td><strong>PU002</strong></td>
                                <td>
                                    <div class="cell-with-icon">
                                        <i class="fa-solid fa-calendar"></i>
                                        <div class="datetime-info">
                                            <span class="date">2024-01-08</span>
                                            <span class="time">10:30 AM</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="badge-group">
                                        <div class="tag">Electronics</div>
                                    </div>
                                </td>
                                <td>
                                    <div class="cell-with-icon">
                                        <i class="fa-solid fa-weight-hanging"></i>
                                        25 kg
                                    </div>
                                </td>
                                <td><span class="status-badge completed">Completed</span></td>
                                <td>Sarah Wilson</td>
                                <td class="earnings-cell">
                                    <span class="earnings-amount">$82.00</span>
                                </td>
                                <td>
                                    <button class="view-details-btn">View Details</button>
                                </td>
                            </tr>
                            
                            <tr>
                                <td><strong>PU003</strong></td>
                                <td>
                                    <div class="cell-with-icon">
                                        <i class="fa-solid fa-calendar"></i>
                                        <div class="datetime-info">
                                            <span class="date">2024-01-15</span>
                                            <span class="time">09:00 AM</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="badge-group">
                                        <div class="tag">Glass</div>
                                        <div class="tag">Metal</div>
                                    </div>
                                </td>
                                <td>
                                    <div class="cell-with-icon">
                                        <i class="fa-solid fa-weight-hanging"></i>
                                        Pending
                                    </div>
                                </td>
                                <td><span class="status-badge scheduled">Scheduled</span></td>
                                <td>Mike Johnson</td>
                                <td class="earnings-cell">
                                    <span class="pending-earnings">Pending</span>
                                </td>
                                <td>
                                    <button class="view-details-btn">View Details</button>
                                </td>
                            </tr>
                            
                            <tr>
                                <td><strong>PU004</strong></td>
                                <td>
                                    <div class="cell-with-icon">
                                        <i class="fa-solid fa-calendar"></i>
                                        <div class="datetime-info">
                                            <span class="date">2024-01-12</span>
                                            <span class="time">02:00 PM</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="badge-group">
                                        <div class="tag">Organic</div>
                                    </div>
                                </td>
                                <td>
                                    <div class="cell-with-icon">
                                        <i class="fa-solid fa-weight-hanging"></i>
                                        18 kg
                                    </div>
                                </td>
                                <td><span class="status-badge in-progress">In Progress</span></td>
                                <td>David Brown</td>
                                <td class="earnings-cell">
                                    <span class="pending-earnings">Pending</span>
                                </td>
                                <td>
                                    <button class="view-details-btn">View Details</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
