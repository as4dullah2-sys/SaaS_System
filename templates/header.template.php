<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo COMPANY_NAME; ?> - ERP System</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; }
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
        .nav {
            background: #2c3e50;
            display: flex;
            flex-wrap: wrap;
            padding: 0;
        }
        .nav a {
            color: white;
            padding: 12px 20px;
            text-decoration: none;
        }
        .nav a:hover { background: #3498db; }
        .nav a.active { background: #3498db; }
        .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
        .card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card h2 { margin-bottom: 15px; color: #2c3e50; }
        .btn {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            background: #3498db;
            border: none;
            cursor: pointer;
        }
        .btn:hover { background: #2980b9; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #229954; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ecf0f1; }
        th { background: #f8f9fa; font-weight: 600; }
        tr:hover { background: #f8f9fa; }
        @media (max-width: 768px) {
            .nav a { padding: 10px; font-size: 12px; }
            th, td { font-size: 12px; padding: 6px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">🐔 <?php echo COMPANY_NAME; ?></div>
        <div class="user-info">
            <span>👤 <?php echo $_SESSION['name'] ?? 'Guest'; ?></span>
            <a href="logout.php" class="logout-btn">🚪 Logout</a>
        </div>
    </div>
    <div class="nav">
        <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">📊 Dashboard</a>
        <a href="clients.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'clients.php' ? 'active' : ''; ?>">👥 Clients</a>
        <a href="products.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">📦 Products</a>
        <a href="transactions.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'transactions.php' ? 'active' : ''; ?>">🔄 Transactions</a>
        <a href="payments.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'payments.php' ? 'active' : ''; ?>">💰 Payments</a>
        <a href="expenses.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'expenses.php' ? 'active' : ''; ?>">💸 Expenses</a>
        <a href="ledger.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'ledger.php' ? 'active' : ''; ?>">📒 Ledger</a>
        <a href="reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">📊 Reports</a>
        <?php if(isAdmin()): ?>
            <a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">👑 Users</a>
        <?php endif; ?>
    </div>
    <div class="container">