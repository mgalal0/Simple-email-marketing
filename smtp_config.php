<?php
// smtp_config.php
require_once 'config.php';
require_once 'components/sidebar.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get current SMTP configuration
$stmt = $conn->prepare("SELECT * FROM smtp_config ORDER BY id DESC LIMIT 1");
$stmt->execute();
$current_config = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = filter_input(INPUT_POST, 'host', FILTER_SANITIZE_STRING);
    $port = filter_input(INPUT_POST, 'port', FILTER_VALIDATE_INT);
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    $encryption = filter_input(INPUT_POST, 'encryption', FILTER_SANITIZE_STRING);
    $from_email = filter_input(INPUT_POST, 'from_email', FILTER_VALIDATE_EMAIL);
    $from_name = filter_input(INPUT_POST, 'from_name', FILTER_SANITIZE_STRING);
    $delay_ms = filter_input(INPUT_POST, 'delay_ms', FILTER_VALIDATE_INT);
    
    // If password is empty and there's an existing config, keep the old password
    if (empty($password) && $current_config) {
        $password = $current_config['password'];
    }
    
    // Delete existing configuration
    $conn->query("DELETE FROM smtp_config");
    
    $stmt = $conn->prepare("INSERT INTO smtp_config (host, port, username, password, encryption, from_email, from_name, delay_ms) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sisssssi", $host, $port, $username, $password, $encryption, $from_email, $from_name, $delay_ms);
    
    if ($stmt->execute()) {
        $success = "SMTP configuration saved successfully";
        // Refresh the current config
        $stmt = $conn->prepare("SELECT * FROM smtp_config ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $current_config = $stmt->get_result()->fetch_assoc();
    } else {
        $error = "Error saving SMTP configuration";
    }
}
?>

<!DOCTYPE html>
<html dir="ltr"> 
    <head>
    <title>SMTP Configuration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <meta charset="UTF-8">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php renderSidebar('smtp_config'); ?>
            
            <div class="col-md-10 p-4">
                <h2>SMTP Configuration</h2>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label>SMTP Host</label>
                                <input type="text" name="host" placeholder="Gmail = smtp.gmail.com" class="form-control" required 
                                       value="<?php echo htmlspecialchars($current_config['host'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label>SMTP Port</label>
                                <input type="number" name="port" placeholder="Gmail = 587 " class="form-control" required 
                                       value="<?php echo htmlspecialchars($current_config['port'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label>Username</label>
                                <input type="text" name="username"  placeholder="Gmail = example@gmail.com " class="form-control" required 
                                       value="<?php echo htmlspecialchars($current_config['username'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label>Password (leave empty to keep current password)</label>
                                <input type="password" name="password" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label>Encryption</label>
                                <select name="encryption" class="form-control" required>
                                    <option value="tls" <?php echo ($current_config['encryption'] ?? '') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                    <option value="ssl" <?php echo ($current_config['encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label>From Email</label>
                                <input type="email" name="from_email" placeholder="Gmail = example@gmail.com " class="form-control" required 
                                       value="<?php echo htmlspecialchars($current_config['from_email'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label>From Name</label>
                                <input type="text" name="from_name" placeholder="Gmail = example" class="form-control" required 
                                       value="<?php echo htmlspecialchars($current_config['from_name'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label>Delay Between Emails (milliseconds)</label>
                                <input type="number" name="delay_ms" class="form-control" required 
                                       value="<?php echo htmlspecialchars($current_config['delay_ms'] ?? '1000'); ?>"
                                       min="0" step="100">
                                <small class="text-muted">Recommended minimum: 1000ms (1 second) to avoid server blacklisting</small>
                            </div>
                            <button type="submit" class="btn btn-primary">Save Configuration</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>