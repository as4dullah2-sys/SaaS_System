<?php
// ============================================
// SAAS CONFIGURATION
// ============================================

// Session Management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'saas_master');

// Site Configuration
define('SITE_NAME', 'ERP SaaS System');
define('SITE_URL', 'http://localhost/saas_system');
define('CURRENCY', 'USD');
define('CURRENCY_SYMBOL', '$');


// Bank Details (for manual payments)
define('BANK_NAME', 'Habib Bank Limited');
define('BANK_ACCOUNT_TITLE', 'Your Business Name');
define('BANK_ACCOUNT_NUMBER', '123456789');
define('BANK_IBAN', 'PK99HBL012345678901');
define('BANK_SWIFT', 'HABBPKKA');

// Trial Settings
define('TRIAL_DAYS', 14);

// Paths
define('ROOT_PATH', __DIR__);
define('GENERATED_PATH', ROOT_PATH . '/generated');
define('TEMPLATES_PATH', ROOT_PATH . '/templates');
define('BACKUP_PATH', ROOT_PATH . '/backup');

// Database Connection
try {
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// ============================================
// HELPER FUNCTIONS
// ============================================

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin';
}

function isSuperAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'super_admin';
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function formatMoney($amount) {
    return CURRENCY_SYMBOL . number_format($amount, 2);
}

function generateUniqueDBName($company_name) {
    $clean = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $company_name));
    $random = rand(100, 999);
    return $clean . '_' . $random;
}

function createCompanyDatabase($db_name) {
    global $db;
    try {
        $db->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

function generateERPFiles($company) {
    $company_dir = GENERATED_PATH . '/' . $company['db_name'];
    
    // Create directory
    if(!file_exists($company_dir)) {
        mkdir($company_dir, 0777, true);
        mkdir($company_dir . '/includes', 0777, true);
        mkdir($company_dir . '/assets', 0777, true);
    }
    
    // Copy ERP files with company details
    copyTemplateFiles($company, $company_dir);
    
    // Replace placeholders with company details
    replacePlaceholders($company_dir, $company);
    
    return $company_dir;
}

function copyTemplateFiles($company, $target_dir) {
    $templates = [
        'config.template.php' => 'config.php',
        'header.template.php' => 'includes/header.php',
        'footer.template.php' => 'includes/footer.php',
        'logo.png' => 'assets/logo.png'
    ];
    
    foreach($templates as $src => $dest) {
        $source_path = TEMPLATES_PATH . '/' . $src;
        $dest_path = $target_dir . '/' . $dest;
        if(file_exists($source_path)) {
            copy($source_path, $dest_path);
        }
    }
    
    // Copy main ERP files
    $erp_files = [
        'index.php', 'login.php', 'logout.php',
        'dashboard.php', 'clients.php', 'products.php',
        'transactions.php', 'payments.php', 'expenses.php',
        'ledger.php', 'reports.php', 'users.php',
        'view_receipt.php', 'download_ledger.php',
        'client_login.php', 'client_dashboard.php',
        'client_logout.php', 'client_download_ledger.php'
    ];
    
    foreach($erp_files as $file) {
        $source = TEMPLATES_PATH . '/erp/' . $file;
        $dest = $target_dir . '/' . $file;
        if(file_exists($source)) {
            copy($source, $dest);
        }
    }
}

function replacePlaceholders($dir, $company) {
    // Recursively replace placeholders in all PHP files
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir)
    );
    
    foreach($files as $file) {
        if($file->isFile() && pathinfo($file, PATHINFO_EXTENSION) == 'php') {
            $content = file_get_contents($file->getPathname());
            
            // Replace placeholders
            $replacements = [
                '{COMPANY_NAME}' => $company['company_name'],
                '{COMPANY_DB}' => $company['db_name'],
                '{COMPANY_EMAIL}' => $company['email'],
                '{COMPANY_PHONE}' => $company['phone'],
                '{SITE_URL}' => SITE_URL,
                '{CURRENCY}' => CURRENCY_SYMBOL,
                '{BANK_NAME}' => BANK_NAME,
                '{BANK_ACCOUNT}' => BANK_ACCOUNT_NUMBER,
                '{BANK_IBAN}' => BANK_IBAN
            ];
            
            $new_content = str_replace(
                array_keys($replacements),
                array_values($replacements),
                $content
            );
            
            file_put_contents($file->getPathname(), $new_content);
        }
    }
}

function generateCompanyZip($company_dir, $company_name) {
    $zip_file = GENERATED_PATH . '/' . $company_name . '_' . date('Y-m-d') . '.zip';
    
    $zip = new ZipArchive();
    if($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($company_dir)
        );
        
        foreach($files as $file) {
            if(!$file->isDir()) {
                $file_path = $file->getRealPath();
                $relative_path = substr($file_path, strlen($company_dir) + 1);
                $zip->addFile($file_path, $relative_path);
            }
        }
        
        $zip->close();
        return $zip_file;
    }
    return false;
}

function logActivity($user_id, $user_type, $action, $details = '') {
    global $db;
    $stmt = $db->prepare("INSERT INTO activity_logs (user_id, user_type, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $user_type, $action, $details, $_SERVER['REMOTE_ADDR']]);
}

function getBankDetails() {
    return [
        'bank_name' => BANK_NAME,
        'account_title' => BANK_ACCOUNT_TITLE,
        'account_number' => BANK_ACCOUNT_NUMBER,
        'iban' => BANK_IBAN,
        'swift' => BANK_SWIFT
    ];
}
?>