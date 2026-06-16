<?php
// ============================================
// ERP CONFIGURATION - {COMPANY_NAME}
// ============================================

// Session Management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', '{COMPANY_DB}');

// Company Configuration
define('COMPANY_NAME', '{COMPANY_NAME}');
define('COMPANY_EMAIL', '{COMPANY_EMAIL}');
define('COMPANY_PHONE', '{COMPANY_PHONE}');
define('CURRENCY_SYMBOL', '{CURRENCY}');

// Bank Details (for manual payments)
define('BANK_NAME', '{BANK_NAME}');
define('BANK_ACCOUNT_TITLE', '{BANK_ACCOUNT}');
define('BANK_ACCOUNT_NUMBER', '{BANK_IBAN}');

// Paths
define('ROOT_PATH', __DIR__);

// Database Connection
try {
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// Helper Functions
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