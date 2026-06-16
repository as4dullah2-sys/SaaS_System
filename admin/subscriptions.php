<?php
session_start();

// Check if admin is logged in
if(!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit();
}

require_once '../config.php';

// Get all subscriptions
$subscriptions = $db->query("
    SELECT s.*, c.company_name, c.email, c.status as company_status, p.name as plan_name
    FROM subscriptions s
    JOIN companies c ON s.company_id = c.id
    JOIN plans p ON s.plan_id = p.id
    ORDER BY s.created_at DESC
")->fetchAll();

// Get stats
$total_subscriptions = count($subscriptions);
$active_subscriptions = $db->query("SELECT COUNT(*) FROM subscriptions WHERE is_active = 1 AND payment_status = 'paid'")->fetchColumn();
$total_revenue = $db->query("SELECT SUM(amount) FROM subscriptions WHERE payment_status = 'paid'")->fetchColumn();
$total_revenue = $total_revenue ?: 0;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Subscriptions - <?php echo SITE_NAME; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; }
        .sidebar {
            position: fixed; left: 0; top: 0; width: 250px; height: 100%;
            background: #2c3e50; color: white; padding: 20px 0; overflow-y: auto;
        }
        .sidebar .logo { text-align: center; padding: 20px; border-bottom: 1px solid #34495e; margin-bottom: 20px; }
        .sidebar .logo h3 { font-size: 20px; }
        .sidebar ul { list-style: none; padding: 0; }
        .sidebar ul li a {
            display: block; padding: 12px 25px; color: white; text-decoration: none;
            transition: 0.3s; border-left: 3px solid transparent;
        }
        .sidebar ul li a:hover, .sidebar ul li a.active {
            background: #34495e; border-left-color: #3498db;
        }
        .main-content { margin-left: 250px; padding: 20px; }
        .top-bar {
            background: white; padding: 15px 25px; border-radius: 10px; margin-bottom: 20px;
            display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .top-bar h2 { color: #2c3e50; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .logout-btn { background: #e74c3c; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; }
        .logout-btn:hover { background: #c0392b; }
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card h3 { margin-bottom: 15px; color: #2c3e50; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ecf0f1; }
        th { background: #f8f9fa; font-weight: 600; }
        tr:hover { background: #f8f9fa; }
        .badge {
            display: inline-block; padding: 3px 8px; border-radius: 12px;
            font-size: 11px; font-weight: bold;
        }
        .badge-paid { background: #d4edda; color: #155724; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-failed { background: #f8d7da; color: #721c24; }
        .badge-active { background: #d4edda; color: #155724; }
        .badge-inactive { background: #f8d7da; color: #721c24; }
        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px; margin-bottom: 20px;
        }
        .stat-card {
            background: white; padding: 20px; border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center;
        }
        .stat-card h4 { color: #7f8c8d; font-size: 14px; }
        .stat-card .number { font-size: 28px; font-weight: bold; color: #2c3e50; }
        @media (max-width: 768px) {
            .sidebar { width: 100%; height: auto; position: relative; }
            .main-content { margin-left: 0; }
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
            <li><a href="index.php">📊 Dashboard</a></li>
            <li><a href="companies.php">🏢 Companies</a></li>
            <li><a href="payments.php">💰 Payments</a></li>
            <li><a href="tickets.php">🎫 Support</a></li>
            <li><a href="subscriptions.php" class="active">📋 Subscriptions</a></li>
            <li><a href="settings.php">⚙️ Settings</a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="top-bar">
            <h2>Subscriptions</h2>
            <div class="user-info">
                <span>👤 <?php echo $_SESSION['user_name'] ?? 'Admin'; ?></span>
                <a href="logout.php" class="logout-btn">🚪 Logout</a>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h4>Total Subscriptions</h4>
                <div class="number"><?php echo $total_subscriptions; ?></div>
            </div>
            <div class="stat-card">
                <h4>Active Subscriptions</h4>
                <div class="number" style="color:#27ae60;"><?php echo $active_subscriptions; ?></div>
            </div>
            <div class="stat-card">
                <h4>Total Revenue</h4>
                <div class="number" style="color:#3498db;"><?php echo formatMoney($total_revenue); ?></div>
            </div>
        </div>
        
        <div class="card">
            <h3>All Subscriptions</h3>
            <table>
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Plan</th>
                        <th>Amount</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Payment Status</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($subscriptions)): ?>
                        <tr><td colspan="7" style="text-align:center; padding:40px;">No subscriptions yet</td></tr>
                    <?php else: ?>
                        <?php foreach($subscriptions as $s): ?>
                        <tr>
                            <td><strong><?php echo $s['company_name']; ?></strong></td>
                            <td><?php echo $s['plan_name']; ?></td>
                            <td><?php echo formatMoney($s['amount']); ?></td>
                            <td><?php echo date('d-m-Y', strtotime($s['start_date'])); ?></td>
                            <td><?php echo date('d-m-Y', strtotime($s['end_date'])); ?></td>
                            <td><span class="badge badge-<?php echo $s['payment_status']; ?>"><?php echo ucfirst($s['payment_status']); ?></span></td>
                            <td><span class="badge badge-<?php echo $s['is_active'] ? 'active' : 'inactive'; ?>"><?php echo $s['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>