<?php
require_once 'config.php';
require_once 'components/sidebar.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$filtered_emails = [];
$invalid_emails = [];
$total_count = 0;
$valid_count = 0;
$invalid_count = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_list = array_filter(explode("\n", $_POST['email_list']));
    $email_list = array_map('trim', $email_list);
    $total_count = count($email_list);
    
    foreach ($email_list as $email) {
        // Basic email validation
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Check DNS records
            $domain = substr(strrchr($email, "@"), 1);
            if (checkdnsrr($domain, "MX")) {
                $filtered_emails[] = $email;
                $valid_count++;
            } else {
                $invalid_emails[] = [
                    'email' => $email,
                    'reason' => 'Invalid domain or no mail server found'
                ];
                $invalid_count++;
            }
        } else {
            $invalid_emails[] = [
                'email' => $email,
                'reason' => 'Invalid email format'
            ];
            $invalid_count++;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Email Filter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .result-box {
            max-height: 300px;
            overflow-y: auto;
        }
        .copy-btn {
            position: absolute;
            right: 10px;
            top: 10px;
        }
        .stats-card {
            transition: all 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php renderSidebar('email_filter'); ?>
            
            <div class="col-md-10 p-4">
                <h2>Email Filter</h2>
                
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Filter Emails</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label>Paste Emails (one email per line)</label>
                                        <textarea name="email_list" class="form-control" rows="10" required><?php echo isset($_POST['email_list']) ? htmlspecialchars($_POST['email_list']) : ''; ?></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-filter me-2"></i>Filter Emails
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <div class="card stats-card bg-primary text-white">
                                    <div class="card-body">
                                        <h6>Total Emails</h6>
                                        <h2><?php echo $total_count; ?></h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <div class="card stats-card bg-success text-white">
                                    <div class="card-body">
                                        <h6>Valid Emails</h6>
                                        <h2><?php echo $valid_count; ?></h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <div class="card stats-card bg-danger text-white">
                                    <div class="card-body">
                                        <h6>Invalid Emails</h6>
                                        <h2><?php echo $invalid_count; ?></h2>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Valid Emails</h5>
                                        <button class="btn btn-sm btn-outline-primary copy-btn" onclick="copyEmails('valid-emails')">
                                            <i class="fas fa-copy"></i> Copy
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div class="result-box" id="valid-emails">
                                            <?php foreach ($filtered_emails as $email): ?>
                                                <div class="text-success mb-1">
                                                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($email); ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Invalid Emails</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="result-box">
                                            <?php foreach ($invalid_emails as $item): ?>
                                                <div class="text-danger mb-2">
                                                    <i class="fas fa-times-circle me-2"></i><?php echo htmlspecialchars($item['email']); ?>
                                                    <br>
                                                    <small class="text-muted ms-4">Reason: <?php echo htmlspecialchars($item['reason']); ?></small>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyEmails(elementId) {
            const validEmails = Array.from(document.querySelectorAll(`#${elementId} .text-success`))
                .map(div => div.textContent.trim())
                .join('\n');
            
            navigator.clipboard.writeText(validEmails).then(() => {
                alert('Valid emails copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy emails:', err);
                alert('Failed to copy emails. Please try again.');
            });
        }
    </script>
</body>
</html>