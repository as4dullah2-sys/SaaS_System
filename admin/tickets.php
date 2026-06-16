<?php
session_start();

// Check if admin is logged in
if(!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit();
}

require_once '../config.php';

$message = '';

// Handle ticket actions
if(isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    if($_GET['action'] == 'close') {
        $db->prepare("UPDATE tickets SET status = 'closed' WHERE id = ?")->execute([$id]);
        $message = "Ticket closed!";
    }
    
    if($_GET['action'] == 'resolve') {
        $db->prepare("UPDATE tickets SET status = 'resolved' WHERE id = ?")->execute([$id]);
        $message = "Ticket resolved!";
    }
}

// Get all tickets
$tickets = $db->query("
    SELECT t.*, c.company_name, c.email 
    FROM tickets t 
    JOIN companies c ON t.company_id = c.id 
    ORDER BY 
        CASE t.status 
            WHEN 'open' THEN 1 
            WHEN 'in_progress' THEN 2 
            WHEN 'resolved' THEN 3 
            WHEN 'closed' THEN 4 
        END,
        t.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Support Tickets - <?php echo SITE_NAME; ?></title>
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
        .badge-open { background: #cce5ff; color: #004085; }
        .badge-in_progress { background: #fff3cd; color: #856404; }
        .badge-resolved { background: #d4edda; color: #155724; }
        .badge-closed { background: #e2e3e5; color: #383d41; }
        .badge-low { background: #d4edda; color: #155724; }
        .badge-medium { background: #fff3cd; color: #856404; }
        .badge-high { background: #f8d7da; color: #721c24; }
        .badge-urgent { background: #f8d7da; color: #721c24; }
        .btn {
            display: inline-block; padding: 5px 12px; border-radius: 4px;
            text-decoration: none; font-size: 12px; border: none; cursor: pointer;
        }
        .btn-success { background: #27ae60; color: white; }
        .btn-success:hover { background: #229954; }
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
            <li><a href="companies.php">🏢 Companies</a></li>
            <li><a href="payments.php">💰 Payments</a></li>
            <li><a href="tickets.php" class="active">🎫 Support</a></li>
            <li><a href="subscriptions.php">📋 Subscriptions</a></li>
            <li><a href="settings.php">⚙️ Settings</a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="top-bar">
            <h2>Support Tickets</h2>
            <div class="user-info">
                <span>👤 <?php echo $_SESSION['user_name'] ?? 'Admin'; ?></span>
                <a href="logout.php" class="logout-btn">🚪 Logout</a>
            </div>
        </div>
        
        <?php if($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h3>All Tickets</h3>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Company</th>
                        <th>Subject</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($tickets)): ?>
                        <tr><td colspan="7" style="text-align:center; padding:40px;">No tickets yet</td></tr>
                    <?php else: ?>
                        <?php foreach($tickets as $t): ?>
                        <tr>
                            <td><?php echo $t['id']; ?></td>
                            <td><strong><?php echo $t['company_name']; ?></strong></td>
                            <td><?php echo $t['subject']; ?></td>
                            <td><span class="badge badge-<?php echo $t['priority']; ?>"><?php echo ucfirst($t['priority']); ?></span></td>
                            <td><span class="badge badge-<?php echo $t['status']; ?>"><?php echo ucfirst($t['status']); ?></span></td>
                            <td><?php echo date('d-m-Y', strtotime($t['created_at'])); ?></td>
                            <td>
                                <?php if($t['status'] != 'closed' && $t['status'] != 'resolved'): ?>
                                    <a href="?action=resolve&id=<?php echo $t['id']; ?>" class="btn btn-success">Resolve</a>
                                    <a href="?action=close&id=<?php echo $t['id']; ?>" class="btn btn-danger">Close</a>
                                <?php else: ?>
                                    <span style="color: #95a5a6;">Done</span>
                                <?php endif; ?>
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