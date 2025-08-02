<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoCycle Dashboard Navigation</title>
    <link rel="stylesheet" href="<?= asset('css/main.css') ?>">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .header {
            background: #2d3748;
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 2.5rem;
        }

        .header p {
            margin: 10px 0 0 0;
            opacity: 0.8;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            padding: 40px;
        }

        .dashboard-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            border: 2px solid #e2e8f0;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .dashboard-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .dashboard-section h3 {
            color: #2d3748;
            margin-bottom: 15px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dashboard-section .icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .admin-section .icon {
            background: #e53e3e;
        }

        .customer-section .icon {
            background: #38a169;
        }

        .collector-section .icon {
            background: #d69e2e;
        }

        .company-section .icon {
            background: #805ad5;
        }

        .page-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .page-links li {
            margin-bottom: 8px;
        }

        .page-links a {
            display: block;
            padding: 10px 15px;
            background: white;
            color: #4a5568;
            text-decoration: none;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }

        .page-links a:hover {
            background: #4299e1;
            color: white;
            transform: translateX(5px);
        }

        .main-dashboard {
            background: #4299e1;
            color: white;
            font-weight: 600;
        }

        .main-dashboard:hover {
            background: #3182ce;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🌱 EcoCycle Dashboard Navigation</h1>
            <p>Access all dashboard pages for development and testing</p>
        </div>

        <div class="dashboard-grid">
            <!-- Admin Dashboard -->
            <div class="dashboard-section admin-section">
                <h3>
                    <span class="icon">A</span>
                    Admin Dashboard
                </h3>
                <ul class="page-links">
                    <li><a href="/admin" class="main-dashboard">🏠 Main Dashboard</a></li>
                    <li><a href="/admin/users">👥 User Management</a></li>
                    <li><a href="/admin/reports">📊 Reports & Analytics</a></li>
                    <li><a href="/admin/content">📝 Content Management</a></li>
                    <li><a href="/admin/settings">⚙️ System Settings</a></li>
                </ul>
            </div>

            <!-- Customer Dashboard -->
            <div class="dashboard-section customer-section">
                <h3>
                    <span class="icon">C</span>
                    Customer Dashboard
                </h3>
                <ul class="page-links">
                    <li><a href="/customer" class="main-dashboard">🏠 Main Dashboard</a></li>
                    <li><a href="/customer/schedule">📅 Schedule Pickup</a></li>
                    <li><a href="/customer/history">📋 Pickup History</a></li>
                    <li><a href="/customer/rewards">🎁 My Rewards</a></li>
                    <li><a href="/customer/education">📚 Education Center</a></li>
                    <li><a href="/customer/profile">👤 My Profile</a></li>
                </ul>
            </div>

            <!-- Collector Dashboard -->
            <div class="dashboard-section collector-section">
                <h3>
                    <span class="icon">R</span>
                    Collector Dashboard
                </h3>
                <ul class="page-links">
                    <li><a href="/collector" class="main-dashboard">🏠 Main Dashboard</a></li>
                    <li><a href="/collector/pickups">🚛 Pickup Assignments</a></li>
                    <li><a href="/collector/routes">🗺️ Route Planning</a></li>
                    <li><a href="/collector/earnings">💰 Earnings & Payments</a></li>
                    <li><a href="/collector/reports">📈 Collection Reports</a></li>
                    <li><a href="/collector/profile">👤 Collector Profile</a></li>
                </ul>
            </div>

            <!-- Company Dashboard -->
            <div class="dashboard-section company-section">
                <h3>
                    <span class="icon">B</span>
                    Company Dashboard
                </h3>
                <ul class="page-links">
                    <li><a href="/company" class="main-dashboard">🏠 Main Dashboard</a></li>
                    <li><a href="/company/waste">♻️ Waste Management</a></li>
                    <li><a href="/company/schedule">📅 Schedule Collection</a></li>
                    <li><a href="/company/analytics">📊 Analytics & Reports</a></li>
                    <li><a href="/company/billing">💵 Billing & Invoices</a></li>
                    <li><a href="/company/sustainability">🌱 Sustainability Reports</a></li>
                    <li><a href="/company/profile">🏢 Company Profile</a></li>
                </ul>
            </div>
        </div>
    </div>
</body>

</html>