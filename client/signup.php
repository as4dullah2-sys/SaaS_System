<?php
require_once '../config.php';

$plan_id = intval($_GET['plan'] ?? 0);
$stmt = $db->prepare("SELECT * FROM plans WHERE id = ? AND is_active = 1");
$stmt->execute([$plan_id]);
$plans = $stmt->fetch();

if(!$plans) {
    redirect('../index.php');
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $company_name = $_POST['company_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $password = md5($_POST['password']);
    $db_name = generateUniqueDBName($company_name);
    
    // Check if email exists
    $stmt = $db->prepare("SELECT id FROM companies WHERE email = ?");
    $stmt->execute([$email]);
    if($stmt->fetch()) {
        $error = "Email already registered!";
    } else {
        // Calculate trial end date
        $trial_ends = date('Y-m-d', strtotime('+' . TRIAL_DAYS . ' days'));
        
        // Insert company
        $stmt = $db->prepare("INSERT INTO companies (company_name, db_name, email, password, phone, address, plan_id, status, trial_ends) VALUES (?, ?, ?, ?, ?, ?, ?, 'trial', ?)");
        $stmt->execute([$company_name, $db_name, $email, $password, $phone, $address, $plan_id, $trial_ends]);
        $company_id = $db->lastInsertId();
        
        // Create database
        createCompanyDatabase($db_name);
        
        // Import ERP database structure
        $sql_file = '../database/erp.sql';
        if(file_exists($sql_file)) {
            $command = "mysql -u " . DB_USER . " -p" . DB_PASS . " " . $db_name . " < $sql_file";
            exec($command);
        }
        
        // Generate ERP files
        $company = $db->prepare("SELECT * FROM companies WHERE id = ?");
        $company->execute([$company_id]);
        $company_data = $company->fetch();
        
        $generated_path = generateERPFiles($company_data);
        createSubscription($company_id, $plan_id);
        
        // Log activity
        logActivity($company_id, 'company', 'signup', 'New company registered: ' . $company_name);

        // Auto-login the new client and send them straight to setup
        $_SESSION['client_logged_in'] = true;
        $_SESSION['client_id']        = $company_id;
        $_SESSION['client_name']      = $company_name;
        $_SESSION['client_email']     = $email;
        redirect('setup.php');
    }
}

function createSubscription($company_id, $plan_id) {
    global $db;
    $stmt = $db->prepare("SELECT price FROM plans WHERE id = ?");
    $stmt->execute([$plan_id]);
    $plan = $stmt->fetch();
    
    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d', strtotime('+1 year'));
    
    $stmt = $db->prepare("INSERT INTO subscriptions (company_id, plan_id, amount, start_date, end_date, is_active) VALUES (?, ?, ?, ?, ?, 1)");
    $stmt->execute([$company_id, $plan_id, $plan['price'], $start_date, $end_date]);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Sign Up - <?php echo SITE_NAME; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; }
        .container { max-width: 600px; margin: 40px auto; padding: 0 20px; }
        .card { background: white; border-radius: 10px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { margin-bottom: 20px; color: #333; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #555; }
        input, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        textarea { resize: vertical; min-height: 80px; }
        .btn { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; border: none; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #5a67d8; }
        .error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        .success { background: #d4edda; color: #155724; padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        .back-link { display: block; margin-top: 20px; color: #667eea; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h2>🚀 Start Your Free Trial</h2>
            <p style="color: #666; margin-bottom: 20px;">
                You are signing up for the <strong><?php echo $plans['name']; ?></strong> plan.
                Free trial for <?php echo TRIAL_DAYS; ?> days.
            </p>
            
            <?php if($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="success"><?php echo $success; ?></div>
                <p style="margin-top: 20px;">
                    <strong>Login Details:</strong><br>
                    Email: <?php echo $_POST['email']; ?><br>
                    Password: (The one you entered)
                </p>
                <a href="login.php" class="btn">Go to Login</a>
            <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="plan_id" value="<?php echo $plan_id; ?>">
                    
                    <div class="form-group">
                        <label>Company Name:</label>
                        <input type="text" name="company_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Phone:</label>
                        <input type="text" name="phone">
                    </div>
                    
                    <div class="form-group">
                        <label>Address:</label>
                        <textarea name="address"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Password:</label>
                        <input type="password" name="password" required>
                    </div>
                    
                    <button type="submit" class="btn">Create Account</button>
                </form>
            <?php endif; ?>
            
            <a href="../index.php" class="back-link">← Back to Home</a>
        </div>
    </div>
</body>
</html>