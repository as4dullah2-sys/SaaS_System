-- ============================================
-- SAAS MASTER DATABASE
-- Run this first to create the master database
-- ============================================

CREATE DATABASE IF NOT EXISTS saas_master;
USE saas_master;

-- Admin Users
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    role ENUM('super_admin', 'admin', 'support') DEFAULT 'admin',
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Plans / Pricing
CREATE TABLE plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'USD',
    max_users INT DEFAULT 1,
    max_clients INT DEFAULT 100,
    max_products INT DEFAULT 1000,
    features TEXT,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Companies (Clients)
CREATE TABLE companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(100) NOT NULL,
    domain VARCHAR(100),
    db_name VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL DEFAULT '',
    phone VARCHAR(20),
    address TEXT,
    logo VARCHAR(255),
    plan_id INT,
    status ENUM('pending', 'active', 'inactive', 'trial') DEFAULT 'pending',
    trial_ends DATE,
    subscription_ends DATE,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (plan_id) REFERENCES plans(id)
);

-- Subscriptions
CREATE TABLE subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    plan_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'bank_transfer',
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    payment_proof VARCHAR(255),
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (plan_id) REFERENCES plans(id)
);

-- Bank Transfer Payments
CREATE TABLE bank_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    subscription_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    reference_no VARCHAR(50),
    proof_image VARCHAR(255),
    bank_name VARCHAR(100),
    account_number VARCHAR(50),
    payment_date DATE,
    status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    verified_by INT,
    verified_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id),
    FOREIGN KEY (verified_by) REFERENCES admin_users(id)
);

-- Support Tickets
CREATE TABLE tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    assigned_to INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (assigned_to) REFERENCES admin_users(id)
);

-- Ticket Replies
CREATE TABLE ticket_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT,
    is_admin BOOLEAN DEFAULT 0,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id)
);

-- Settings
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Activity Logs
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    user_type ENUM('admin', 'company') DEFAULT 'admin',
    action VARCHAR(100),
    details TEXT,
    ip_address VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- Insert Default Data
-- ============================================

-- Default Admin User (Password: admin123)
INSERT INTO admin_users (username, password, name, email, role) VALUES 
('admin', MD5('admin123'), 'Super Admin', 'admin@saas.com', 'super_admin');

-- Plans
INSERT INTO plans (name, price, max_users, max_clients, max_products, features) VALUES
('Starter', 250, 1, 100, 500, 'Basic ERP features, Single user, 100 clients limit, 500 products limit'),
('Professional', 350, 5, 500, 2000, 'Full ERP features, 5 users, 500 clients limit, 2000 products limit'),
('Enterprise', 500, 999, 9999, 99999, 'All features, Unlimited users, Unlimited clients, Unlimited products');

-- Settings
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'ERP SaaS System'),
('site_url', 'http://localhost/saas_system'),
('currency', 'USD'),
('bank_name', 'Habib Bank Limited'),
('bank_account_title', 'Your Business Name'),
('bank_account_number', '123456789'),
('bank_iban', 'PK99HBL012345678901'),
('bank_swift', 'HABBPKKA'),
('trial_days', '14');