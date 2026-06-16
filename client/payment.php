<?php
require_once '../config.php';

if(!isset($_SESSION['client_logged_in'])) {
    redirect('login.php');
}

$client_id = $_SESSION['client_id'];
$message = '';
$error = '';

// Get company and plan details
$stmt = $db->prepare("SELECT * FROM companies WHERE id = ?");
$stmt->execute([$client_id]);
$company = $stmt->fetch();

$stmt = $db->prepare("SELECT * FROM plans WHERE id = ?");
$stmt->execute([$company['plan_id']]);
$plan = $stmt->fetch();

// Get bank details
$bank_name = getSetting('bank_name');
$bank_account = getSetting('bank_account_title');
$bank_number = getSetting('bank_account_number');
$bank_iban = getSetting('bank_iban');

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = $_POST['amount'];
    $reference_no = $_POST['reference_no'];
    $bank_name_input = $_POST['bank_name'];
    $account_number = $_POST['account_number'];
    $payment_date = $_POST['payment_date'];
    $notes = $_POST['notes'];
    
    // Handle file upload
    $proof_image = '';
    if(isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        $ext = strtolower(pathinfo($_FILES['proof_image']['name'], PATHINFO_EXTENSION));
        if(in_array($ext, $allowed)) {
            $upload_dir = '../uploads/payments/';
            if(!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
            $proof_image = $upload_dir . time() . '_' . $_FILES['proof_image']['name'];
            move_uploaded_file($_FILES['proof_image']['tmp_name'], $proof_image);
        } else {
            $error = "Invalid file type. Please upload JPG, PNG, or PDF.";
        }
    }
    
    if(!$error) {
        $stmt = $db->prepare("INSERT INTO bank_payments (company_id, amount, reference_no, bank_name, account_number, payment_date, proof_image, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$client_id, $amount, $reference_no, $bank_name_input, $account_number, $payment_date, $proof_image, $notes]);
        
        $message = "Payment submitted successfully! We will verify it within 24-48 hours.";
        logActivity($client_id, 'company', 'payment_submit', "Submitted payment: $amount");
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Make Payment - <?php echo SITE_NAME; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo { font-size: 24px; font-weight: bold; }
        .logout-btn {
            background: #e74c3c;
            color: white;
            padding: 8px 20px;
            border-radius: 5px;
            text-decoration: none;
        }
        .container { max-width: 800px; margin: 40px auto; padding: 0 20px; }
        .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .card h2 { margin-bottom: 20px; color: #2c3e50; }
        .bank-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .bank-details p { margin: 5px 0; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #555; }
        input, textarea, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover { background: #229954; }
        .btn-back {
            background: #95a5a6;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
        }
        .btn-back:hover { background: #7f8c8d; }
        .message { background: #d4edda; color: #155724; padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        @media (max-width: 768px) { .container { margin: 20px auto; } }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">🚀 <?php echo SITE_NAME; ?></div>
        <div>
            <span style="margin-right: 15px;">👤 <?php echo $company['company_name']; ?></span>
            <a href="logout.php" class="logout-btn">🚪 Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <h2>💳 Make Payment</h2>
            <p><strong>Plan:</strong> <?php echo $plan['name']; ?></p>
            <p><strong>Amount:</strong> <?php echo formatMoney($plan['price']); ?></p>
            
            <div class="bank-details">
                <h4>🏦 Transfer to this Bank Account:</h4>
                <p><strong>Bank:</strong> <?php echo $bank_name; ?></p>
                <p><strong>Account Title:</strong> <?php echo $bank_account; ?></p>
                <p><strong>Account Number:</strong> <?php echo $bank_number; ?></p>
                <p><strong>IBAN:</strong> <?php echo $bank_iban; ?></p>
                <p style="color: #e74c3c; margin-top: 10px;"><strong>⚡ After transfer, submit proof below</strong></p>
            </div>
            
            <?php if($message): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if(!$message): ?>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Amount (USD)</label>
                        <input type="number" name="amount" value="<?php echo $plan['price']; ?>" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Reference Number (from bank transfer)</label>
                        <input type="text" name="reference_no" placeholder="Enter bank reference number">
                    </div>
                    <div class="form-group">
                        <label>Bank Name (you transferred from)</label>
                        <input type="text" name="bank_name" placeholder="Your bank name">
                    </div>
                    <div class="form-group">
                        <label>Account Number (you transferred from)</label>
                        <input type="text" name="account_number" placeholder="Your account number">
                    </div>
                    <div class="form-group">
                        <label>Payment Date</label>
                        <input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Upload Payment Proof (Screenshot/PDF)</label>
                        <input type="file" name="proof_image" accept="image/*,.pdf">
                    </div>
                    <div class="form-group">
                        <label>Notes (Optional)</label>
                        <textarea name="notes" rows="2" placeholder="Any additional information"></textarea>
                    </div>
                    <button type="submit" class="btn">Submit Payment</button>
                </form>
            <?php endif; ?>
            
            <a href="dashboard.php" class="btn btn-back" style="background: #95a5a6; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; display: inline-block; margin-top: 15px;">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>