<?php
require_once 'config.php';

$plans = $db->query("SELECT * FROM plans WHERE is_active = 1 ORDER BY price")->fetchAll();
$bank_details = getBankDetails();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo SITE_NAME; ?> - All-in-One Business Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            color: #333;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            font-size: 42px;
            margin-bottom: 10px;
        }
        .header p {
            font-size: 18px;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }
        .feature {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .feature h3 {
            margin-bottom: 10px;
            color: #667eea;
        }
        .plans {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }
        .plan {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }
        .plan:hover {
            transform: translateY(-5px);
        }
        .plan.popular {
            border: 2px solid #667eea;
            position: relative;
        }
        .plan.popular::before {
            content: 'Popular';
            position: absolute;
            top: -12px;
            right: 20px;
            background: #667eea;
            color: white;
            padding: 5px 20px;
            border-radius: 20px;
            font-size: 12px;
        }
        .plan h3 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        .plan .price {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 20px;
        }
        .plan .price small {
            font-size: 14px;
            font-weight: normal;
            color: #666;
        }
        .plan ul {
            list-style: none;
            text-align: left;
            margin: 20px 0;
            padding: 0;
        }
        .plan ul li {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .plan ul li::before {
            content: '✓ ';
            color: #27ae60;
            font-weight: bold;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background: #5a67d8;
        }
        .btn-outline {
            background: transparent;
            border: 2px solid #667eea;
            color: #667eea;
        }
        .btn-outline:hover {
            background: #667eea;
            color: white;
        }
        .footer {
            text-align: center;
            padding: 30px;
            color: #666;
            border-top: 1px solid #e0e0e0;
        }
        .bank-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .bank-details strong {
            display: inline-block;
            width: 150px;
        }
        @media (max-width: 768px) {
            .header h1 {
                font-size: 28px;
            }
            .plans {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚀 <?php echo SITE_NAME; ?></h1>
        <p>Complete Business Management Solution for Poultry, Medicine, and General Trading</p>
    </div>
    
    <div class="container">
        <div class="features">
            <div class="feature">
                <h3>📊 Manage Everything</h3>
                <p>Clients, Products, Sales, Purchases, Expenses, Payments - All in one place</p>
            </div>
            <div class="feature">
                <h3>📱 Client Portal</h3>
                <p>Your clients can view their own ledger and transaction history</p>
            </div>
            <div class="feature">
                <h3>📈 Reports & Analytics</h3>
                <p>Beautiful charts and reports to understand your business better</p>
            </div>
            <div class="feature">
                <h3>💰 Payment Management</h3>
                <p>Track payments, partial payments, and outstanding balances</p>
            </div>
        </div>
        
        <h2 style="text-align: center; margin-bottom: 30px;">Choose Your Plan</h2>
        <div class="plans">
            <?php foreach($plans as $plan): ?>
            <div class="plan <?php echo $plan['id'] == 2 ? 'popular' : ''; ?>">
                <h3><?php echo $plan['name']; ?></h3>
                <div class="price"><?php echo formatMoney($plan['price']); ?><small>/year</small></div>
                <ul>
                    <li><?php echo $plan['max_users']; ?> User(s)</li>
                    <li><?php echo $plan['max_clients']; ?> Clients</li>
                    <li><?php echo $plan['max_products']; ?> Products</li>
                    <li><?php echo $plan['features']; ?></li>
                </ul>
                <a href="client/signup.php?plan=<?php echo $plan['id']; ?>" class="btn">Start Free Trial</a>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div style="text-align: center; margin-bottom: 30px;">
            <h3>💳 Payment Options</h3>
            <div class="bank-details">
                <p><strong>Bank:</strong> <?php echo $bank_details['bank_name']; ?></p>
                <p><strong>Account Title:</strong> <?php echo $bank_details['account_title']; ?></p>
                <p><strong>Account Number:</strong> <?php echo $bank_details['account_number']; ?></p>
                <p><strong>IBAN:</strong> <?php echo $bank_details['iban']; ?></p>
                <p><strong>SWIFT:</strong> <?php echo $bank_details['swift']; ?></p>
                <p style="margin-top: 10px; color: #e74c3c;">
                    <strong>⚡ After payment, send proof to: support@yoursite.com</strong>
                </p>
            </div>
        </div>
        
        <div style="text-align: center;">
            <h3>Already have an account?</h3>
            <a href="client/login.php" class="btn btn-outline">Login to Dashboard</a>
        </div>
    </div>
    
    <div class="footer">
        <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        <p>For support: support@yoursite.com | Phone: +92-XXX-XXXXXXX</p>
    </div>
</body>
</html>