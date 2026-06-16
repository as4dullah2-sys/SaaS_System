<?php
session_start();

// Check if admin is logged in
if(!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit();
}

require_once '../config.php';

// Get statistics
$total_companies = $db->query("SELECT COUNT(*) FROM companies")->fetchColumn();
$active_companies = $db->query("SELECT COUNT(*) FROM companies WHERE status = 'active'")->fetchColumn();
$trial_companies = $db->query("SELECT COUNT(*) FROM companies WHERE status = 'trial'")->fetchColumn();
$pending_payments = $db->query("SELECT COUNT(*) FROM bank_payments WHERE status = 'pending'")->fetchColumn();
$total_tickets = $db->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
$open_tickets = $db->query("SELECT COUNT(*) FROM tickets WHERE status != 'closed'")->fetchColumn();

// Total revenue
$total_revenue = $db->query("SELECT SUM(amount) FROM bank_payments WHERE status = 'verified'")->fetchColumn();
$total_revenue = $total_revenue ?: 0;

// Recent companies
$recent_companies = $db->query("SELECT * FROM companies ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Recent payments
$recent_payments = $db->query("
    SELECT bp.*, c.company_name 
    FROM bank_payments bp 
    JOIN companies c ON bp.company_id = c.id 
    ORDER BY bp.created_at DESC LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
        }
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100%;
            background: #2c3e50;
            color: white;
            padding: 20px 0;
            overflow-y: auto;
        }
        .sidebar .logo {
            text-align: center;
            padding: 20px;
            border-bottom: 1px solid #34495e;
            margin-bottom: 20px;
        }
        .sidebar .logo h3 { font-size: 20px; }
        .sidebar .logo small { font-size: 12px; opacity: 0.7; }
        .sidebar ul {
            list-style: none;
            padding: 0;
        }
        .sidebar ul li a {
            display: block;
            padding: 12px 25px;
            color: white;
            text-decoration: none;
            transition: 0.3s;
            border-left: 3px solid transparent;
        }
        .sidebar ul li a:hover {
            background: #34495e;
            border-left-color: #3498db;
        }
        .sidebar ul li a.active {
            background: #34495e;
            border-left-color: #3498db;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .top-bar {
            background: white;
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .top-bar h2 { color: #2c3e50; }
        .top-bar .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .top-bar .user-info span { color: #555; }
        .logout-btn {
            background: #e74c3c;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
        }
        .logout-btn:hover { background: #c0392b; }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-card h4 { color: #7f8c8d; font-size: 14px; }
        .stat-card .number { font-size: 28px; font-weight: bold; color: #2c3e50; }
        .stat-card .number.green { color: #27ae60; }
        .stat-card .number.blue { color: #3498db; }
        .stat-card .number.orange { color: #f39c12; }
        .stat-card .number.red { color: #e74c3c; }
        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card h3 {
            margin-bottom: 15px;
            color: #2c3e50;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        tr:hover { background: #f8f9fa; }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        .badge-active { background: #d4edda; color: #155724; }
        .badge-inactive { background: #f8d7da; color: #721c24; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-trial { background: #cce5ff; color: #004085; }
        .badge-verified { background: #d4edda; color: #155724; }
        .badge-rejected { background: #f8d7da; color: #721c24; }
        @media (max-width: 768px) {
            .sidebar { width: 100%; height: auto; position: relative; }
            .main-content { margin-left: 0; }
            .stats-grid { grid-template-columns: 1fr; }
            .grid-2 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <h3>🚀 <?php echo SITE_NAME; ?></h3>
            <small>Admin Panel</small>
        </div>
        <ul>
            <li><a href="index.php" class="active">📊 Dashboard</a></li>
            <li><a href="companies.php">🏢 Companies</a></li>
            <li><a href="payments.php">💰 Payments</a></li>
            <li><a href="tickets.php">🎫 Support</a></li>
            <li><a href="subscriptions.php">📋 Subscriptions</a></li>
            <li><a href="settings.php">⚙️ Settings</a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="top-bar">
            <h2>Dashboard</h2>
            <div class="user-info">
                <span>👤 <?php echo $_SESSION['user_name'] ?? 'Admin'; ?></span>
                <a href="logout.php" class="logout-btn">🚪 Logout</a>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h4>Total Companies</h4>
                <div class="number"><?php echo $total_companies; ?></div>
            </div>
            <div class="stat-card">
                <h4>Active Companies</h4>
                <div class="number green"><?php echo $active_companies; ?></div>
            </div>
            <div class="stat-card">
                <h4>Trial Companies</h4>
                <div class="number blue"><?php echo $trial_companies; ?></div>
            </div>
            <div class="stat-card">
                <h4>Pending Payments</h4>
                <div class="number orange"><?php echo $pending_payments; ?></div>
            </div>
            <div class="stat-card">
                <h4>Total Revenue</h4>
                <div class="number green"><?php echo formatMoney($total_revenue); ?></div>
            </div>
            <div class="stat-card">
                <h4>Open Tickets</h4>
                <div class="number red"><?php echo $open_tickets; ?></div>
            </div>
        </div>
        
        <div class="grid-2">
            <div class="card">
                <h3>Recent Companies</h3>
                <table>
                    <thead>
                        <tr><th>Company</th><th>Email</th><th>Plan</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_companies as $c): ?>
                        <tr>
                            <td><strong><?php echo $c['company_name']; ?></strong></td>
                            <td><?php echo $c['email']; ?></td>
                            <td><?php echo $c['plan_id']; ?></td>
                            <td><span class="badge badge-<?php echo $c['status']; ?>"><?php echo ucfirst($c['status']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($recent_companies)): ?>
                        <tr><td colspan="4" style="text-align:center;">No companies yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card">
                <h3>Recent Payments</h3>
                <table>
                    <thead>
                        <tr><th>Company</th><th>Amount</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_payments as $p): ?>
                        <tr>
                            <td><?php echo $p['company_name']; ?></td>
                            <td><?php echo formatMoney($p['amount']); ?></td>
                            <td><span class="badge badge-<?php echo $p['status']; ?>"><?php echo ucfirst($p['status']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($recent_payments)): ?>
                        <tr><td colspan="3" style="text-align:center;">No payments yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>