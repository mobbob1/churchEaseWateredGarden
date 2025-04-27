<?php
require_once 'config.php';

try {
    // Create a new password hash
    $password = 'Admin@123';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Delete existing admin user
    $stmt = $pdo->prepare("DELETE FROM users WHERE username = ?");
    $stmt->execute(['admin']);
    
    // Create fresh admin user
    $stmt = $pdo->prepare("
        INSERT INTO users (
            username, 
            email, 
            password, 
            full_name, 
            role, 
            status, 
            department, 
            position,
            login_attempts
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        'admin',
        'admin@example.com',
        $hash,
        'System Administrator',
        'admin',
        'active',
        'IT',
        'System Administrator',
        0
    ]);
    
    echo "Admin user reset successfully!<br>";
    echo "Username: admin<br>";
    echo "Password: Admin@123<br>";
    echo "Generated Hash: " . $hash . "<br>";
    
    // Verify the hash works
    echo "Hash Verification Test: " . (password_verify('Admin@123', $hash) ? 'PASSED' : 'FAILED') . "<br>";
    
    // Show the user in database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute(['admin']);
    $user = $stmt->fetch();
    
    echo "<br>User in Database:<br>";
    echo "Username: " . $user['username'] . "<br>";
    echo "Status: " . $user['status'] . "<br>";
    echo "Stored Hash: " . $user['password'] . "<br>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}