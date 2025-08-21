

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

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-info">
                            <div class="stat-value">Tomorrow</div>
                            <div class="stat-label">Next Pickup</div>
                            <div class="stat-subtitle">9:00 AM - Regular pickup</div>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-info">
                            <div class="stat-value">45 kg</div>
                            <div class="stat-label">Monthly Collected</div>
                            <div class="stat-subtitle">This month</div>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-info">
                            <div class="stat-value">$127.50</div>
                            <div class="stat-label">Total Earnings</div>
                            <div class="stat-subtitle">This month</div>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-info">
                            <div class="stat-value">12</div>
                            <div class="stat-label">Completed Pickups</div>
                            <div class="stat-subtitle">This month</div>
                        </div>
                    </div>
                </div>
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
