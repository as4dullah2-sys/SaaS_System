<?php
// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Generate a unique database name
 */
function generateUniqueDBName($company_name) {
    $clean = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $company_name));
    $random = rand(100, 999);
    return $clean . '_' . $random;
}

/**
 * Create company database
 */
function createCompanyDatabase($db_name) {
    global $db;
    try {
        $db->exec("CREATE DATABASE IF NOT EXISTS $db_name");
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

/**
 * Log activity
 */
function logActivity($user_id, $user_type, $action, $details = '') {
    global $db;
    $stmt = $db->prepare("INSERT INTO activity_logs (user_id, user_type, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $user_type, $action, $details, $_SERVER['REMOTE_ADDR']]);
}

/**
 * Get setting value
 */
function getSetting($key) {
    global $db;
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['setting_value'] : null;
}

/**
 * Update setting
 */
function updateSetting($key, $value) {
    global $db;
    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->execute([$key, $value, $value]);
}

/**
 * Get plan price
 */
function getPlanPrice($plan_id) {
    global $db;
    $stmt = $db->prepare("SELECT price FROM plans WHERE id = ?");
    $stmt->execute([$plan_id]);
    $result = $stmt->fetch();
    return $result ? $result['price'] : 0;
}

/**
 * Get bank details
 */
function getBankDetails() {
    return [
        'bank_name' => getSetting('bank_name'),
        'account_title' => getSetting('bank_account_title'),
        'account_number' => getSetting('bank_account_number'),
        'iban' => getSetting('bank_iban'),
        'swift' => getSetting('bank_swift')
    ];
}

/**
 * Format money
 */
function formatMoney($amount) {
    $currency = getSetting('currency') ?: '$';
    return $currency . number_format($amount, 2);
}

/**
 * Redirect
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * Check if client is logged in
 */
function isClientLoggedIn() {
    return isset($_SESSION['client_logged_in']) && isset($_SESSION['client_id']);
}

/**
 * Get company by ID
 */
function getCompany($id) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM companies WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Get plan by ID
 */
function getPlan($id) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM plans WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Get subscription by company ID
 */
function getSubscription($company_id) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM subscriptions WHERE company_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$company_id]);
    return $stmt->fetch();
}

/**
 * Days until expiration
 */
function daysUntilExpiration($date) {
    $diff = strtotime($date) - time();
    return ceil($diff / (60 * 60 * 24));
}

/**
 * Check if company has expired
 */
function isCompanyExpired($company_id) {
    $sub = getSubscription($company_id);
    if(!$sub) return true;
    return strtotime($sub['end_date']) < time();
}

/**
 * Generate random password
 */
function generatePassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    for($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}

/**
 * Sanitize input
 */
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Get client IP
 */
function getClientIP() {
    return $_SERVER['REMOTE_ADDR'];
}

/**
 * Check if request is AJAX
 */
function isAjax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * JSON response
 */
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if(empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>