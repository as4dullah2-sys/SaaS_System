<?php
// ============================================
// ERP CONFIGURATION - Fahad & Co.
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'fahad___co__717');

define('COMPANY_NAME', 'Fahad & Co.');
define('COMPANY_EMAIL', 'fahad@gmail.com');
define('COMPANY_PHONE', '03156956606');
define('CURRENCY_SYMBOL', 'Rs');

define('BANK_NAME', 'Habib Bank Limited');
define('BANK_ACCOUNT_TITLE', 'Your Business Name');
define('BANK_ACCOUNT_NUMBER', '123456789');
define('BANK_IBAN', 'PK99HBL012345678901');

define('ROOT_PATH', __DIR__);
define('SITE_URL', 'http://localhost/saas_system/generated/fahad___co__717');

try {
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

function formatMoney($amount) {
    return CURRENCY_SYMBOL . number_format($amount, 2);
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

function canEdit() {
    return isset($_SESSION['role']) && ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'editor');
}
?>