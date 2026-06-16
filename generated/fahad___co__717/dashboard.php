<?php
require_once 'config.php';

if(!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$total_clients = $db->query("SELECT COUNT(*) FROM clients")->fetchColumn();
$total_products = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_sales = $db->query("SELECT SUM(total) FROM transactions WHERE type='sale'")->fetchColumn() ?: 0;
$total_purchases = $db->query("SELECT SUM(total) FROM transactions WHERE type='purchase'")->fetchColumn() ?: 0;
$profit = $total_sales - $total_purchases;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - <?php echo COMPANY_NAME; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        .header .logo { font-size: 24px; font-weight: bold; }
        .header .user { display: flex; align-items: center; gap: 15px; }
        .logout-btn { background: #e74c3c; color: white; padding: 8px 20px; border-radius: 5px; text-decoration: none; }
        .logout-btn:hover { background: #c0392b; }
        .nav { background: #2c3e50; display: flex; flex-wrap: wrap; }
        .nav a { color: white; padding: 12px 20px; text-decoration: none; }
        .nav a:hover, .nav a.active { background: #3498db; }
        .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center; }
        .stat-card h3 { font-size: 14px; color: #666; margin-bottom: 10px; }
        .stat-card .number { font-size: 28px; font-weight: bold; color: #333; }
        .stat-card.profit { background: #27ae60; color: white; }
        .stat-card.profit .number, .stat-card.profit h3 { color: white; }
        .stat-card.loss { background: #e74c3c; color: white; }
        .stat-card.loss .number, .stat-card.loss h3 { color: white; }
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card h2 { margin-bottom: 20px; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; }
        .welcome { font-size: 18px; color: #555; }
        .features { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px; }
        .feature { background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; }
        .feature h4 { color: #2c3e50; margin-bottom: 5px; }
        .feature p { color: #666; font-size: 13px; }
        @media (max-width: 768px) {
            .stats { grid-template-columns: 1fr; }
            .header .logo { font-size: 18px; }
            .nav a { padding: 8px 12px; font-size: 12px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">🐔 <?php echo COMPANY_NAME; ?></div>
        <div class="user">
            <span>👤 <?php echo $_SESSION['name']; ?> (<?php echo $_SESSION['role']; ?>)</span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    <div class="nav">
        <a href="dashboard.php" class="active">📊 Dashboard</a>
        <a href="clients.php">👥 Clients</a>
        <a href="products.php">📦 Products</a>
        <a href="transactions.php">🔄 Transactions</a>
        <a href="payments.php">💰 Payments</a>
        <a href="expenses.php">💸 Expenses</a>
        <a href="reports.php">📊 Reports</a>
    </div>
    <div class="container">
        <div class="stats">
            <div class="stat-card"><h3>Total Clients</h3><div class="number"><?php echo $total_clients; ?></div></div>
            <div class="stat-card"><h3>Total Products</h3><div class="number"><?php echo $total_products; ?></div></div>
            <div class="stat-card"><h3>Total Sales</h3><div class="number"><?php echo formatMoney($total_sales); ?></div></div>
            <div class="stat-card"><h3>Total Purchases</h3><div class="number"><?php echo formatMoney($total_purchases); ?></div></div>
            <div class="stat-card <?php echo $profit >= 0 ? 'profit' : 'loss'; ?>">
                <h3>Profit/Loss</h3>
                <div class="number"><?php echo formatMoney($profit); ?></div>
            </div>
        </div>
        <div class="card">
            <h2>📋 Welcome to <?php echo COMPANY_NAME; ?></h2>
            <p class="welcome">Your ERP system is ready to use. Start managing your business today!</p>
            <div class="features">
                <div class="feature"><h4>👥 Clients</h4><p>Manage your clients</p></div>
                <div class="feature"><h4>📦 Products</h4><p>Manage your products</p></div>
                <div class="feature"><h4>🔄 Transactions</h4><p>Sales & Purchases</p></div>
                <div class="feature"><h4>💰 Payments</h4><p>Track payments</p></div>
                <div class="feature"><h4>💸 Expenses</h4><p>Track expenses</p></div>
                <div class="feature"><h4>📊 Reports</h4><p>Generate reports</p></div>
            </div>
        </div>
    </div>
</body>
</html>