<?php
session_start();

// Check if admin is logged in
if(!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit();
}

require_once '../config.php';

$message = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach($_POST as $key => $value) {
        $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$value, $key]);
    }
    $message = "Settings updated successfully!";
}

// Get all settings
$settings = $db->query("SELECT * FROM settings")->fetchAll();
$settings_arr = [];
foreach($settings as $s) {
    $settings_arr[$s['setting_key']] = $s['setting_value'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Settings - <?php echo SITE_NAME; ?></title>
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
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #555; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        .btn {
            display: inline-block; padding: 10px 20px; border-radius: 5px;
            text-decoration: none; border: none; cursor: pointer; font-size: 14px;
        }
        .btn-primary { background: #3498db; color: white; }
        .btn-primary:hover { background: #2980b9; }
        .message { background: #d4edda; color: #155724; padding: 12px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #27ae60; }
        .grid-2 {
            display: grid; grid-template-columns: 1fr 1fr; gap: 20px;
        }
        @media (max-width: 768px) {
            .sidebar { width: 100%; height: auto; position: relative; }
            .main-content { margin-left: 0; }
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
            <li><a href="index.php">📊 Dashboard</a></li>
            <li><a href="companies.php">🏢 Companies</a></li>
            <li><a href="payments.php">💰 Payments</a></li>
            <li><a href="tickets.php">🎫 Support</a></li>
            <li><a href="subscriptions.php">📋 Subscriptions</a></li>
            <li><a href="settings.php" class="active">⚙️ Settings</a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="top-bar">
            <h2>Settings</h2>
            <div class="user-info">
                <span>👤 <?php echo $_SESSION['user_name'] ?? 'Admin'; ?></span>
                <a href="logout.php" class="logout-btn">🚪 Logout</a>
            </div>
        </div>
        
        <?php if($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h3>⚙️ System Settings</h3>
            <form method="POST">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Site Name</label>
                        <input type="text" name="site_name" value="<?php echo $settings_arr['site_name'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Site URL</label>
                        <input type="text" name="site_url" value="<?php echo $settings_arr['site_url'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Currency Symbol</label>
                        <input type="text" name="currency" value="<?php echo $settings_arr['currency'] ?? '$'; ?>">
                    </div>
                    <div class="form-group">
                        <label>Trial Days</label>
                        <input type="number" name="trial_days" value="<?php echo $settings_arr['trial_days'] ?? 14; ?>">
                    </div>
                    <div class="form-group">
                        <label>Bank Name</label>
                        <input type="text" name="bank_name" value="<?php echo $settings_arr['bank_name'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Bank Account Title</label>
                        <input type="text" name="bank_account_title" value="<?php echo $settings_arr['bank_account_title'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Bank Account Number</label>
                        <input type="text" name="bank_account_number" value="<?php echo $settings_arr['bank_account_number'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Bank IBAN</label>
                        <input type="text" name="bank_iban" value="<?php echo $settings_arr['bank_iban'] ?? ''; ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </form>
        </div>
    </div>
</body>
</html>