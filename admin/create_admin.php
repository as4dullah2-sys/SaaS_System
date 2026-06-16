<?php
require_once '../config.php';

echo "<h1>Admin User Creator</h1>";

try {
    // Check if admin_users table exists
    $stmt = $db->query("SHOW TABLES LIKE 'admin_users'");
    if($stmt->rowCount() == 0) {
        echo "<p style='color:red'>❌ Table 'admin_users' doesn't exist!</p>";
        echo "<p>Creating table...</p>";
        
        // Create admin_users table
        $db->exec("
            CREATE TABLE IF NOT EXISTS admin_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                role ENUM('super_admin', 'admin', 'support') DEFAULT 'admin',
                is_active BOOLEAN DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        echo "<p style='color:green'>✅ admin_users table created!</p>";
    }
    
    // Check if admin already exists
    $stmt = $db->query("SELECT COUNT(*) FROM admin_users");
    $count = $stmt->fetchColumn();
    
    if($count > 0) {
        echo "<p style='color:orange'>⚠️ Admin users already exist!</p>";
        echo "<p>Existing users:</p>";
        $users = $db->query("SELECT id, username, name, email, role FROM admin_users")->fetchAll();
        echo "<ul>";
        foreach($users as $u) {
            echo "<li>" . $u['username'] . " - " . $u['name'] . " (" . $u['role'] . ")</li>";
        }
        echo "</ul>";
        echo "<p><a href='login.php'>Go to Login →</a></p>";
    } else {
        // Insert admin user
        $stmt = $db->prepare("INSERT INTO admin_users (username, password, name, email, role) VALUES (?, MD5(?), ?, ?, 'super_admin')");
        $stmt->execute(['admin', 'admin123', 'Super Admin', 'admin@saas.com']);
        echo "<p style='color:green'>✅ Admin user created successfully!</p>";
        echo "<p><a href='login.php'>Go to Login →</a></p>";
    }
    
    echo "<h3>Login Details:</h3>";
    echo "<p><strong>URL:</strong> <a href='login.php'>http://localhost/saas_system/admin/login.php</a></p>";
    echo "<p><strong>Username:</strong> admin</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    
} catch(PDOException $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}
?>