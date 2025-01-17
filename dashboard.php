<?php
// dashboard.php
require_once 'config.php';
session_start();
require_once 'components/sidebar.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get email statistics
$stmt = $conn->prepare("SELECT COUNT(*) as total_campaigns, 
    SUM(recipient_count) as total_recipients 
    FROM email_logs");
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get recent campaigns
$stmt = $conn->prepare("SELECT * FROM email_logs 
    ORDER BY sent_at DESC LIMIT 5");
$stmt->execute();
$recent_campaigns = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get SMTP configuration
$stmt = $conn->prepare("SELECT * FROM smtp_config 
    ORDER BY id DESC LIMIT 1");
$stmt->execute();
$smtp_config = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Email Marketing Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php renderSidebar('dashboard'); ?>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <h2 class="mb-4">Dashboard</h2>
                
                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="stat-card bg-primary text-white">
                            <h5>Total Campaigns</h5>
                            <h2><?php echo $stats['total_campaigns'] ?? 0; ?></h2>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card bg-success text-white">
                            <h5>Total Recipients</h5>
                            <h2><?php echo $stats['total_recipients'] ?? 0; ?></h2>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card bg-info text-white">
                            <h5>SMTP Status</h5>
                            <h2><?php echo $smtp_config ? 'Configured' : 'Not Configured'; ?></h2>
                        </div>
                    </div>
                </div>

                <!-- Recent Campaigns Table -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Campaigns</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Recipients</th>
                                    <th>Status</th>
                                    <th>Sent At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_campaigns as $campaign): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($campaign['subject']); ?></td>
                                    <td><?php echo $campaign['recipient_count']; ?></td>
                                    <td><?php echo htmlspecialchars($campaign['status']); ?></td>
                                    <td><?php echo $campaign['sent_at']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recent_campaigns)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">No campaigns sent yet</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>