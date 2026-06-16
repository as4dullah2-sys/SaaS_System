<?php
require_once '../config.php';

// Check if client is logged in
if(!isset($_SESSION['client_logged_in']) || !isset($_SESSION['client_id'])) {
    header('Location: login.php');
    exit();
}

$client_id = $_SESSION['client_id'];
$error = '';
$success = '';

// Get client details
$stmt = $db->prepare("SELECT * FROM companies WHERE id = ?");
$stmt->execute([$client_id]);
$company = $stmt->fetch();

if(!$company) {
    session_destroy();
    header('Location: login.php');
    exit();
}

// Check if setup is already done for this client
$setup_file = '../generated/' . $company['db_name'] . '/.setup_complete';
if(file_exists($setup_file)) {
    // Redirect to their ERP dashboard
    header('Location: ../generated/' . $company['db_name'] . '/index.php');
    exit();
}

// Handle setup form submission
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_project'])) {
    $company_name = $company['company_name'];
    $db_name = $company['db_name'];
    $email = $company['email'];
    $phone = $company['phone'];
    
    try {
        // Create directory structure
        $company_dir = '../generated/' . $db_name;
        if(!file_exists($company_dir)) {
            mkdir($company_dir, 0777, true);
            mkdir($company_dir . '/includes', 0777, true);
            mkdir($company_dir . '/assets', 0777, true);
        }
        
        // Create database for client using a separate connection (avoids switching global $db context)
        $db->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
        $company_db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . $db_name, DB_USER, DB_PASS);
        $company_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create tables — execute each statement individually (PDO doesn't support multi-statement exec)
        $company_db->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                name VARCHAR(100) NOT NULL,
                role ENUM('admin', 'editor', 'viewer') DEFAULT 'viewer',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $company_db->exec("
            CREATE TABLE IF NOT EXISTS clients (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                phone VARCHAR(20),
                email VARCHAR(100),
                address TEXT,
                balance DECIMAL(10,2) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $company_db->exec("
            CREATE TABLE IF NOT EXISTS products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                category VARCHAR(50),
                unit VARCHAR(20),
                stock DECIMAL(10,2) DEFAULT 0,
                purchase_price DECIMAL(10,2) DEFAULT 0,
                sale_price DECIMAL(10,2) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $company_db->exec("
            CREATE TABLE IF NOT EXISTS transactions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                type VARCHAR(20),
                client_id INT,
                product_id INT,
                quantity DECIMAL(10,2),
                rate DECIMAL(10,2),
                total DECIMAL(10,2),
                date DATE,
                payment VARCHAR(20),
                paid_amount DECIMAL(10,2) DEFAULT 0,
                due_amount DECIMAL(10,2) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            )
        ");
        
        $company_db->exec("
            CREATE TABLE IF NOT EXISTS expenses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                category VARCHAR(50),
                description TEXT,
                amount DECIMAL(10,2),
                expense_date DATE,
                payment_method VARCHAR(20),
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $company_db->exec("
            CREATE TABLE IF NOT EXISTS payments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                client_id INT NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                payment_date DATE NOT NULL,
                payment_method VARCHAR(20) DEFAULT 'cash',
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
            )
        ");
        
        // Insert default admin user
        $company_db->exec("INSERT INTO users (username, password, name, role) VALUES 
            ('admin', '" . MD5('admin123') . "', 'Administrator', 'admin')");
        
        // Create config.php
        $config_content = "<?php
// ============================================
// ERP CONFIGURATION - " . addslashes($company_name) . "
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', '" . DB_HOST . "');
define('DB_USER', '" . DB_USER . "');
define('DB_PASS', '" . DB_PASS . "');
define('DB_NAME', '" . $db_name . "');

define('COMPANY_NAME', '" . addslashes($company_name) . "');
define('COMPANY_EMAIL', '" . $email . "');
define('COMPANY_PHONE', '" . $phone . "');
define('CURRENCY_SYMBOL', 'Rs');

define('BANK_NAME', '" . BANK_NAME . "');
define('BANK_ACCOUNT_TITLE', '" . BANK_ACCOUNT_TITLE . "');
define('BANK_ACCOUNT_NUMBER', '" . BANK_ACCOUNT_NUMBER . "');
define('BANK_IBAN', '" . BANK_IBAN . "');

define('ROOT_PATH', __DIR__);
define('SITE_URL', '" . SITE_URL . "/generated/" . $db_name . "');

try {
    \$db = new PDO(\"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME, DB_USER, DB_PASS);
    \$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException \$e) {
    die(\"Database Connection Failed: \" . \$e->getMessage());
}

function formatMoney(\$amount) {
    return CURRENCY_SYMBOL . number_format(\$amount, 2);
}

function isLoggedIn() {
    return isset(\$_SESSION['user_id']);
}

function isAdmin() {
    return isset(\$_SESSION['role']) && \$_SESSION['role'] == 'admin';
}

function canEdit() {
    return isset(\$_SESSION['role']) && (\$_SESSION['role'] == 'admin' || \$_SESSION['role'] == 'editor');
}
?>";
        file_put_contents($company_dir . '/config.php', $config_content);
        
        // Create index.php
        file_put_contents($company_dir . '/index.php', "<?php\nrequire_once 'config.php';\nheader('Location: login.php');\nexit();\n?>");
        
        // Create login.php
        $login_content = "<?php
require_once 'config.php';

if(isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

\$error = '';

if(\$_SERVER['REQUEST_METHOD'] == 'POST') {
    \$username = trim(\$_POST['username']);
    \$password = md5(trim(\$_POST['password']));
    
    \$stmt = \$db->prepare(\"SELECT * FROM users WHERE username = ? AND password = ?\");
    \$stmt->execute([\$username, \$password]);
    \$user = \$stmt->fetch();
    
    if(\$user) {
        \$_SESSION['user_id'] = \$user['id'];
        \$_SESSION['name'] = \$user['name'];
        \$_SESSION['role'] = \$user['role'];
        \$_SESSION['username'] = \$user['username'];
        header('Location: dashboard.php');
        exit();
    } else {
        \$error = \"Invalid username or password!\";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>" . addslashes($company_name) . " - Login</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 350px; }
        h2 { text-align: center; color: #2c3e50; margin-bottom: 30px; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; }
        button { width: 100%; padding: 10px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #5a67d8; }
        .error { color: red; text-align: center; }
        .info { text-align: center; margin-top: 15px; font-size: 12px; color: #999; }
    </style>
</head>
<body>
    <div class=\"login-box\">
        <h2>🐔 " . addslashes($company_name) . "</h2>
        <?php if(\$error): ?>
            <div class=\"error\"><?php echo \$error; ?></div>
        <?php endif; ?>
        <form method=\"POST\">
            <input type=\"text\" name=\"username\" placeholder=\"Username\" value=\"admin\" required>
            <input type=\"password\" name=\"password\" placeholder=\"Password\" value=\"admin123\" required>
            <button type=\"submit\">Login</button>
        </form>
        <div class=\"info\">Default: admin / admin123</div>
    </div>
</body>
</html>";
        file_put_contents($company_dir . '/login.php', $login_content);
        
        // Create dashboard.php
        $dashboard_content = "<?php
require_once 'config.php';

if(!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

\$total_clients = \$db->query(\"SELECT COUNT(*) FROM clients\")->fetchColumn();
\$total_products = \$db->query(\"SELECT COUNT(*) FROM products\")->fetchColumn();
\$total_sales = \$db->query(\"SELECT SUM(total) FROM transactions WHERE type='sale'\")->fetchColumn() ?: 0;
\$total_purchases = \$db->query(\"SELECT SUM(total) FROM transactions WHERE type='purchase'\")->fetchColumn() ?: 0;
\$profit = \$total_sales - \$total_purchases;
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
    <div class=\"header\">
        <div class=\"logo\">🐔 <?php echo COMPANY_NAME; ?></div>
        <div class=\"user\">
            <span>👤 <?php echo \$_SESSION['name']; ?> (<?php echo \$_SESSION['role']; ?>)</span>
            <a href=\"logout.php\" class=\"logout-btn\">Logout</a>
        </div>
    </div>
    <div class=\"nav\">
        <a href=\"dashboard.php\" class=\"active\">📊 Dashboard</a>
        <a href=\"clients.php\">👥 Clients</a>
        <a href=\"products.php\">📦 Products</a>
        <a href=\"transactions.php\">🔄 Transactions</a>
        <a href=\"payments.php\">💰 Payments</a>
        <a href=\"expenses.php\">💸 Expenses</a>
        <a href=\"reports.php\">📊 Reports</a>
    </div>
    <div class=\"container\">
        <div class=\"stats\">
            <div class=\"stat-card\"><h3>Total Clients</h3><div class=\"number\"><?php echo \$total_clients; ?></div></div>
            <div class=\"stat-card\"><h3>Total Products</h3><div class=\"number\"><?php echo \$total_products; ?></div></div>
            <div class=\"stat-card\"><h3>Total Sales</h3><div class=\"number\"><?php echo formatMoney(\$total_sales); ?></div></div>
            <div class=\"stat-card\"><h3>Total Purchases</h3><div class=\"number\"><?php echo formatMoney(\$total_purchases); ?></div></div>
            <div class=\"stat-card <?php echo \$profit >= 0 ? 'profit' : 'loss'; ?>\">
                <h3>Profit/Loss</h3>
                <div class=\"number\"><?php echo formatMoney(\$profit); ?></div>
            </div>
        </div>
        <div class=\"card\">
            <h2>📋 Welcome to <?php echo COMPANY_NAME; ?></h2>
            <p class=\"welcome\">Your ERP system is ready to use. Start managing your business today!</p>
            <div class=\"features\">
                <div class=\"feature\"><h4>👥 Clients</h4><p>Manage your clients</p></div>
                <div class=\"feature\"><h4>📦 Products</h4><p>Manage your products</p></div>
                <div class=\"feature\"><h4>🔄 Transactions</h4><p>Sales & Purchases</p></div>
                <div class=\"feature\"><h4>💰 Payments</h4><p>Track payments</p></div>
                <div class=\"feature\"><h4>💸 Expenses</h4><p>Track expenses</p></div>
                <div class=\"feature\"><h4>📊 Reports</h4><p>Generate reports</p></div>
            </div>
        </div>
    </div>
</body>
</html>";
        file_put_contents($company_dir . '/dashboard.php', $dashboard_content);
        
        // Create logout.php
        file_put_contents($company_dir . '/logout.php', "<?php\nsession_start();\nsession_destroy();\nheader('Location: login.php');\nexit();\n?>");
        
        // Mark setup as complete
        file_put_contents($setup_file, date('Y-m-d H:i:s'));
        
        $success = "🎉 Project created successfully! Your ERP system is ready.";
        $company_dir_url = SITE_URL . '/generated/' . $db_name;
        
    } catch(PDOException $e) {
        $error = "Error creating project: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Setup - <?php echo $company['company_name']; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .setup-container { max-width: 600px; width: 100%; }
        .setup-box {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h2 { text-align: center; margin-bottom: 10px; color: #2c3e50; }
        .subtitle { text-align: center; color: #666; margin-bottom: 20px; }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #e74c3c;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #27ae60;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #27ae60;
            color: white;
            border-radius: 8px;
            text-decoration: none;
            text-align: center;
        }
        .btn:hover { background: #229954; }
        .btn-primary {
            background: #667eea;
        }
        .btn-primary:hover { background: #5a67d8; }
        .info-box {
            background: #e8f4f8;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #004085;
            border-left: 4px solid #3498db;
        }
        .info-box strong { color: #2c3e50; }
        .center { text-align: center; }
        .mt-20 { margin-top: 20px; }
        .url-box {
            background: #f0f0f0;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            word-break: break-all;
            margin: 10px 0;
        }
        .create-btn {
            width: 100%;
            padding: 15px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .create-btn:hover {
            background: #229954;
        }
        ul { margin: 10px 0 0 20px; }
        ul li { margin: 5px 0; }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-box">
            <h2>🚀 Create Your ERP Project</h2>
            <p class="subtitle">Setting up <?php echo $company['company_name']; ?></p>
            
            <?php if($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="success"><?php echo $success; ?></div>
                <div class="info-box">
                    <p><strong>✅ Your ERP system is now ready!</strong></p>
                    <p><strong>Login Details:</strong></p>
                    <p>🔗 URL: <a href="<?php echo $company_dir_url; ?>" target="_blank"><?php echo $company_dir_url; ?></a></p>
                    <p>👤 Username: <strong>admin</strong></p>
                    <p>🔑 Password: <strong>admin123</strong></p>
                </div>
                <div class="center mt-20">
                    <a href="<?php echo $company_dir_url; ?>" class="btn btn-primary" target="_blank">🚀 Open Your ERP System</a>
                </div>
                <div class="center mt-20">
                    <a href="../dashboard.php" class="btn">← Back to Dashboard</a>
                </div>
            <?php else: ?>
                <div class="info-box">
                    <p><strong>📋 Project Details:</strong></p>
                    <p>Company: <strong><?php echo $company['company_name']; ?></strong></p>
                    <p>Email: <?php echo $company['email']; ?></p>
                    <p>Phone: <?php echo $company['phone'] ?: 'N/A'; ?></p>
                    <p style="margin-top: 10px;">Click the button below to create your ERP system with:</p>
                    <ul>
                        <li>✅ Database setup</li>
                        <li>✅ User management</li>
                        <li>✅ Client management</li>
                        <li>✅ Product management</li>
                        <li>✅ Sales & Purchases</li>
                        <li>✅ Payment tracking</li>
                        <li>✅ Expense tracking</li>
                        <li>✅ Reports & analytics</li>
                    </ul>
                </div>
                
                <form method="POST">
                    <button type="submit" name="create_project" class="create-btn">
                        🚀 Create My ERP Project
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>