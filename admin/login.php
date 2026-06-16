<?php
// No redirects at the top - just check session
session_start();

// Database connection
require_once '../config.php';

// If already logged in, redirect to dashboard
if(isset($_SESSION['user_id']) && isset($_SESSION['is_admin'])) {
    header('Location: index.php');
    exit();
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = md5(trim($_POST['password']));
    
    try {
        $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ? AND password = ? AND is_active = 1");
        $stmt->execute([$username, $password]);
        $user = $stmt->fetch();
        
        if($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['is_admin'] = true;
            
            // Log activity
            $stmt = $db->prepare("INSERT INTO activity_logs (user_id, user_type, action, details, ip_address) VALUES (?, 'admin', 'login', ?, ?)");
            $stmt->execute([$user['id'], 'Admin logged in', $_SERVER['REMOTE_ADDR']]);
            
            // Redirect to dashboard - using relative path
            header('Location: index.php');
            exit();
        } else {
            $error = "Invalid username or password!";
        }
    } catch(PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Login - <?php echo SITE_NAME ?? 'ERP SaaS'; ?></title>
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
            transition: border-color 0.3s;
        }
        .input-group input:focus {
            outline: none;
            border-color: #667eea;
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
            transition: background 0.3s;
        }
        button:hover { background: #5a67d8; }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #e74c3c;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #667eea;
            text-decoration: none;
        }
        .back-link:hover { text-decoration: underline; }
        .demo-box {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            text-align: center;
            font-size: 13px;
        }
        .demo-box strong { color: #2c3e50; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2>🔐 Admin Login</h2>
            <h4>Welcome to <?php echo SITE_NAME ?? 'ERP SaaS System'; ?></h4>
            
            <?php if($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" name="username" value="admin" required autofocus>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" value="admin123" required>
                </div>
                <button type="submit">Login</button>
            </form>
            
            <div class="demo-box">
                <strong>Default Login:</strong><br>
                Username: admin<br>
                Password: admin123
            </div>
            
            <a href="../index.php" class="back-link">← Back to Home</a>
        </div>
    </div>
</body>
</html>