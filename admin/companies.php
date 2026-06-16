<?php
session_start();

// Check if admin is logged in
if(!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit();
}

require_once '../config.php';

$message = '';
$error = '';

// Handle actions
if(isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    if($_GET['action'] == 'activate') {
        $db->prepare("UPDATE companies SET status = 'active' WHERE id = ?")->execute([$id]);
        $message = "Company activated!";
    }
    
    if($_GET['action'] == 'deactivate') {
        $db->prepare("UPDATE companies SET status = 'inactive' WHERE id = ?")->execute([$id]);
        $message = "Company deactivated!";
    }
    
    if($_GET['action'] == 'delete') {
        $db->prepare("DELETE FROM companies WHERE id = ?")->execute([$id]);
        $message = "Company deleted!";
    }
}

// Get all companies
$companies = $db->query("
    SELECT c.*, p.name as plan_name 
    FROM companies c 
    LEFT JOIN plans p ON c.plan_id = p.id 
    ORDER BY c.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Companies - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
        .badge-active { background: #d4edda; color: #155724; }
        .badge-inactive { background: #f8d7da; color: #721c24; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-trial { background: #cce5ff; color: #004085; }
        .btn {
            display: inline-block; padding: 5px 12px; border-radius: 4px;
            text-decoration: none; font-size: 12px; border: none; cursor: pointer;
        }
        .btn-success { background: #27ae60; color: white; }
        .btn-success:hover { background: #229954; }
        .btn-warning { background: #f39c12; color: white; }
        .btn-warning:hover { background: #e67e22; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-danger:hover { background: #c0392b; }
        .btn-primary { background: #3498db; color: white; }
        .btn-primary:hover { background: #2980b9; }
        .message { background: #d4edda; color: #155724; padding: 12px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #27ae60; }
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
            <li><a href="companies.php" class="active">🏢 Companies</a></li>
            <li><a href="payments.php">💰 Payments</a></li>
            <li><a href="tickets.php">🎫 Support</a></li>
            <li><a href="subscriptions.php">📋 Subscriptions</a></li>
            <li><a href="settings.php">⚙️ Settings</a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="top-bar">
            <h2>Companies</h2>
            <div class="user-info">
                <span>👤 <?php echo $_SESSION['user_name'] ?? 'Admin'; ?></span>
                <a href="logout.php" class="logout-btn">🚪 Logout</a>
            </div>
        </div>
        
        <?php if($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h3>All Companies</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Company</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Plan</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($companies)): ?>
                        <tr><td colspan="7" style="text-align:center; padding:40px;">No companies yet</td></tr>
                    <?php else: ?>
                        <?php foreach($companies as $c): ?>
                        <tr>
                            <td><?php echo $c['id']; ?></td>
                            <td><strong><?php echo $c['company_name']; ?></strong></td>
                            <td><?php echo $c['email']; ?></td>
                            <td><?php echo $c['phone'] ?: '-'; ?></td>
                            <td><?php echo $c['plan_name'] ?: 'N/A'; ?></td>
                            <td><span class="badge badge-<?php echo $c['status']; ?>"><?php echo ucfirst($c['status']); ?></span></td>
                            <td>
                                <a href="?action=activate&id=<?php echo $c['id']; ?>" class="btn btn-success">Activate</a>
                                <a href="?action=deactivate&id=<?php echo $c['id']; ?>" class="btn btn-warning">Deactivate</a>
                                <a href="?action=delete&id=<?php echo $c['id']; ?>" class="btn btn-danger" onclick="return confirm('Delete this company?')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>