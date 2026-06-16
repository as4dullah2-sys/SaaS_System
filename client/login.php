<?php
require_once '../config.php';

// If already logged in, redirect
if(isset($_SESSION['client_logged_in'])) {
    redirect('dashboard.php');
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = md5($_POST['password']);
    
    $stmt = $db->prepare("SELECT * FROM companies WHERE email = ? AND password = ? AND is_active = 1");
    $stmt->execute([$email, $password]);
    $company = $stmt->fetch();
    
    if($company) {
        // Check if company has access
        if($company['status'] == 'active' || $company['status'] == 'trial') {
            $_SESSION['client_logged_in'] = true;
            $_SESSION['client_id'] = $company['id'];
            $_SESSION['client_name'] = $company['company_name'];
            $_SESSION['client_email'] = $company['email'];
            redirect('dashboard.php');
        } else {
            $error = "Your account is not active. Please contact support.";
        }
    } else {
        $error = "Invalid email or password!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Client Login - <?php echo SITE_NAME; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .login-container { width: 100%; max-width: 400px; padding: 20px; }
        .login-box {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .login-box h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .login-box h4 {
            text-align: center;
            color: #7f8c8d;
            margin-bottom: 30px;
            font-weight: normal;
        }
        .input-group { margin-bottom: 20px; }
        .input-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        .input-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover { background: #5a67d8; }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #667eea;
            text-decoration: none;
        }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2>👤 Client Login</h2>
            <h4><?php echo SITE_NAME; ?></h4>
            
            <?php if($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="input-group">
                    <label>Email</label>
                    <input type="email" name="email" required autofocus>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit">Login</button>
            </form>
            
            <a href="../index.php" class="back-link">← Back to Home</a>
        </div>
    </div>
</body>
</html>