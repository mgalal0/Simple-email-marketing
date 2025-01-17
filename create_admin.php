<?php
// create_admin.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'marketing');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Default admin credentials
$admin_username = "admin";
$admin_password = "admin123"; // You should change this immediately after first login

// Hash the password
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

// Prepare and execute the query
$stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
$stmt->bind_param("ss", $admin_username, $hashed_password);

try {
    if ($stmt->execute()) {
        echo "Admin user created successfully!\n";
        echo "Username: " . $admin_username . "\n";
        echo "Password: " . $admin_password . "\n";
        echo "\nIMPORTANT: Please change this password immediately after your first login!";
    } else {
        echo "Error creating admin user: " . $stmt->error;
    }
} catch (Exception $e) {
    if ($conn->errno === 1062) {
        echo "Admin user already exists!";
    } else {
        echo "Error: " . $e->getMessage();
    }
}

$stmt->close();
$conn->close();
?>