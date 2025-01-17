<?php
// components/sidebar.php
function renderSidebar($active_page = 'dashboard') {
    // Get admin info from session
    $username = $_SESSION['username'] ?? 'Admin';
    ?>
    <div class="col-md-2 sidebar p-0">
        <div class="d-flex flex-column">
            <!-- Admin Profile -->
            <div class="p-3 mb-0">
                <div class="d-flex align-items-center">
                    <i class="fas fa-user me-2 text-white"></i>
                    <div>
                        <h6 class="text-white mb-0"><?php echo htmlspecialchars($username); ?></h6>
                        <small class="text-white-50">Administrator</small>
                    </div>
                </div>
            </div>

            <h6 class="text-white p-3 mb-0">Institute of Administrative Development</h6>
            
            <a href="dashboard.php" class="<?php echo $active_page === 'dashboard' ? 'active bg-primary' : ''; ?>">
                <i class="fas fa-home me-2"></i> Dashboard
            </a>
            <a href="email_filter.php" class="<?php echo $active_page === 'email_filter' ? 'active bg-primary' : ''; ?>">
    <i class="fas fa-check-circle me-2"></i> Email Filter
</a>
            <a href="send_email.php" class="<?php echo $active_page === 'send_email' ? 'active bg-primary' : ''; ?>">
                <i class="fas fa-paper-plane me-2"></i> Send Email
            </a>
            <a href="smtp_config.php" class="<?php echo $active_page === 'smtp_config' ? 'active bg-primary' : ''; ?>">
                <i class="fas fa-cog me-2"></i> SMTP Settings
            </a>
            <a href="logout.php">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </div>
    </div>
    <?php
}
?>
<head>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
        }
        .sidebar a {
            color: #fff !important;
            text-decoration: none !important;
            padding: 10px 15px !important;
            display: block !important;
        }
        .sidebar a:hover {
            background-color: #495057 !important;
        }
        .stat-card {
            border-radius: 10px !important;
            padding: 20px !important;
            margin-bottom: 20px !important;
            box-shadow: 0 0 10px rgba(0,0,0,0.1) !important;
        }
    </style>