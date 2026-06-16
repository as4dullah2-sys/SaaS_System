<?php
require_once '../config.php';

// Check if client is logged in
if(!isset($_SESSION['client_logged_in']) || !isset($_SESSION['client_id'])) {
    redirect('login.php');
}

$client_id = $_SESSION['client_id'];

// Get company details
$stmt = $db->prepare("SELECT * FROM companies WHERE id = ?");
$stmt->execute([$client_id]);
$company = $stmt->fetch();

// Get plan details
$stmt = $db->prepare("SELECT * FROM plans WHERE id = ?");
$stmt->execute([$company['plan_id']]);
$plan = $stmt->fetch();

// Get subscription
$stmt = $db->prepare("SELECT * FROM subscriptions WHERE company_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$client_id]);
$subscription = $stmt->fetch();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Dashboard - <?php echo $company['company_name']; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .logo { font-size: 24px; font-weight: bold; }
        .user-info { display: flex; gap: 15px; align-items: center; }
        .logout-btn {
            background: #e74c3c;
            color: white;
            padding: 8px 20px;
            border-radius: 5px;
            text-decoration: none;
        }
        .logout-btn:hover { background: #c0392b; }
        .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
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
        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card h3 { margin-bottom: 15px; color: #2c3e50; }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            background: #3498db;
        }
        .btn:hover { background: #2980b9; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #229954; }
        .btn-warning { background: #f39c12; }
        .btn-warning:hover { background: #e67e22; }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        .badge-active { background: #d4edda; color: #155724; }
        .badge-inactive { background: #f8d7da; color: #721c24; }
        .badge-trial { background: #cce5ff; color: #004085; }
        .badge-pending { background: #fff3cd; color: #856404; }
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">🚀 <?php echo SITE_NAME; ?></div>
        <div class="user-info">
            <span>👤 <?php echo $company['company_name']; ?></span>
            <a href="logout.php" class="logout-btn">🚪 Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <h4>Your Plan</h4>
                <div class="number" style="font-size: 20px;"><?php echo $plan['name'] ?? 'N/A'; ?></div>
            </div>
            <div class="stat-card">
                <h4>Status</h4>
                <div class="number" style="font-size: 20px;">
                    <span class="badge badge-<?php echo $company['status']; ?>">
                        <?php echo ucfirst($company['status']); ?>
                    </span>
                </div>
            </div>
            <div class="stat-card">
                <h4>Valid Until</h4>
                <div class="number" style="font-size: 20px;">
                    <?php echo $subscription ? date('d-m-Y', strtotime($subscription['end_date'])) : 'N/A'; ?>
                </div>
            </div>
            <div class="stat-card">
                <h4>Days Left</h4>
                <div class="number">
                    <?php 
                    if($subscription) {
                        $diff = strtotime($subscription['end_date']) - time();
                        echo ceil($diff / (60 * 60 * 24));
                    } else {
                        echo 'N/A';
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="card">
                <h3>🚀 Quick Actions</h3>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <a href="payment.php" class="btn btn-success">💰 Make Payment</a>
                    <a href="tickets.php" class="btn btn-warning">🎫 Support Ticket</a>
                    <a href="../generated/<?php echo $company['db_name']; ?>/" class="btn">📊 Open ERP System</a>
                </div>
            </div>
            <div class="card">
                <h3>📋 Account Information</h3>
                <p><strong>Company:</strong> <?php echo $company['company_name']; ?></p>
                <p><strong>Email:</strong> <?php echo $company['email']; ?></p>
                <p><strong>Phone:</strong> <?php echo $company['phone'] ?: 'N/A'; ?></p>
                <p><strong>Plan:</strong> <?php echo $plan['name'] ?? 'N/A'; ?></p>
                <p><strong>Status:</strong> <span class="badge badge-<?php echo $company['status']; ?>"><?php echo ucfirst($company['status']); ?></span></p>
            </div>
        </div>
    </div>
</body>
</html>