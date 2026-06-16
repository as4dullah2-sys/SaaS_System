<?php
session_start();

// Check if admin is logged in
if(!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit();
}

require_once '../config.php';

$message = '';

// Handle payment verification
if(isset($_GET['verify']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $stmt = $db->prepare("SELECT * FROM bank_payments WHERE id = ?");
    $stmt->execute([$id]);
    $payment = $stmt->fetch();
    
    if($payment) {
        // Update payment status
        $db->prepare("UPDATE bank_payments SET status = 'verified', verified_by = ?, verified_at = NOW() WHERE id = ?")->execute([$_SESSION['user_id'], $id]);
        
        // Update company status
        $db->prepare("UPDATE companies SET status = 'active', payment_verified = 1, payment_verified_at = NOW() WHERE id = ?")->execute([$payment['company_id']]);
        
        // Update subscription
        $db->prepare("UPDATE subscriptions SET payment_status = 'paid' WHERE company_id = ?")->execute([$payment['company_id']]);
        
        $message = "Payment verified successfully! Account activated.";
    }
}

// Handle payment rejection
if(isset($_GET['reject']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $db->prepare("UPDATE bank_payments SET status = 'rejected' WHERE id = ?")->execute([$id]);
    $message = "Payment rejected!";
}

// Get all payments
$payments = $db->query("
    SELECT bp.*, c.company_name, c.email 
    FROM bank_payments bp 
    JOIN companies c ON bp.company_id = c.id 
    ORDER BY bp.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payments - <?php echo SITE_NAME; ?></title>
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
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-verified { background: #d4edda; color: #155724; }
        .badge-rejected { background: #f8d7da; color: #721c24; }
        .btn {
            display: inline-block; padding: 5px 12px; border-radius: 4px;
            text-decoration: none; font-size: 12px; border: none; cursor: pointer;
        }
        .btn-success { background: #27ae60; color: white; }
        .btn-success:hover { background: #229954; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-danger:hover { background: #c0392b; }
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
            <li><a href="payments.php" class="active">💰 Payments</a></li>
            <li><a href="tickets.php">🎫 Support</a></li>
            <li><a href="subscriptions.php">📋 Subscriptions</a></li>
            <li><a href="settings.php">⚙️ Settings</a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="top-bar">
            <h2>Payments</h2>
            <div class="user-info">
                <span>👤 <?php echo $_SESSION['user_name'] ?? 'Admin'; ?></span>
                <a href="logout.php" class="logout-btn">🚪 Logout</a>
            </div>
        </div>
        
        <?php if($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h3>Bank Transfer Payments</h3>
            <table>
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Amount</th>
                        <th>Reference</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($payments)): ?>
                        <tr><td colspan="6" style="text-align:center; padding:40px;">No payments yet</td></tr>
                    <?php else: ?>
                        <?php foreach($payments as $p): ?>
                        <tr>
                            <td><strong><?php echo $p['company_name']; ?></strong></td>
                            <td><?php echo formatMoney($p['amount']); ?></td>
                            <td><?php echo $p['reference_no'] ?: '-'; ?></td>
                            <td><?php echo date('d-m-Y', strtotime($p['created_at'])); ?></td>
                            <td><span class="badge badge-<?php echo $p['status']; ?>"><?php echo ucfirst($p['status']); ?></span></td>
                            <td>
                                <?php if($p['status'] == 'pending'): ?>
                                    <a href="?verify=1&id=<?php echo $p['id']; ?>" class="btn btn-success">✅ Verify</a>
                                    <a href="?reject=1&id=<?php echo $p['id']; ?>" class="btn btn-danger">❌ Reject</a>
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