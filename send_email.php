<?php
// send_email.php
require 'vendor/autoload.php';
require_once 'config.php';
require_once 'components/sidebar.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_POST['request_type']) && $_POST['request_type'] === 'ajax') {
    ob_clean();
    header('Content-Type: application/json');
}

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Function to get available SMTP accounts
function getAvailableSmtpAccounts($conn) {
    $stmt = $conn->prepare("SELECT * FROM smtp_config WHERE is_active = 1 ORDER BY id");
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Function to configure PHPMailer with SMTP settings
function configureMailer($mail, $smtp_config) {
    $mail->isSMTP();
    $mail->Host = $smtp_config['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $smtp_config['username'];
    $mail->Password = $smtp_config['password'];
    $mail->SMTPSecure = $smtp_config['encryption'];
    $mail->Port = $smtp_config['port'];
    $mail->setFrom($smtp_config['from_email'], $smtp_config['from_name']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get all active SMTP configurations
    $smtp_configs = getAvailableSmtpAccounts($conn);
    
    if (empty($smtp_configs)) {
        echo json_encode(['success' => false, 'message' => 'No active SMTP accounts available']);
        exit;
    }

    // Process email list
    $email_list = array_filter(explode("\n", $_POST['email_list']));
    $email_list = array_map('trim', $email_list);
    $total_emails = count($email_list);
    
    $success_count = 0;
    $error_count = 0;
    $progress = [];
    
    // Initialize counters
    $current_smtp_index = 0;
    $emails_sent_current_smtp = 0;
    $max_emails_per_smtp = 400;
    
    // Create PHPMailer instance
    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    $mail->isHTML(true);
    
    // Handle attachment
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        $file_ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
        if (in_array($file_ext, $allowed)) {
            $mail->addAttachment($_FILES['attachment']['tmp_name'], $_FILES['attachment']['name']);
        }
    }

    // Set email content
    $mail->Subject = $_POST['subject'];
    $mail->Body = $_POST['message'];
    $mail->AltBody = strip_tags($_POST['message']);

    // Process each email
    foreach ($email_list as $email) {
        $sent = false;
        $attempts = 0;
        $max_attempts = count($smtp_configs);

        while (!$sent && $attempts < $max_attempts) {
            // Check if we need to switch SMTP accounts
            if ($emails_sent_current_smtp >= $max_emails_per_smtp) {
                $current_smtp_index = ($current_smtp_index + 1) % count($smtp_configs);
                $emails_sent_current_smtp = 0;
            }

            try {
                // Configure current SMTP
                $smtp_config = $smtp_configs[$current_smtp_index];
                configureMailer($mail, $smtp_config);

                // Clear previous addresses and add new one
                $mail->clearAddresses();
                $mail->addAddress($email);

                // Try to send
                $mail->send();
                
                // Email sent successfully
                $success_count++;
                $emails_sent_current_smtp++;
                $sent = true;
                
                $progress[] = [
                    'email' => $email,
                    'success' => true,
                    'message' => "Sent via " . $smtp_config['username']
                ];

                // Apply delay
                $delay_ms = $smtp_config['delay_ms'] ?? 1000;
                usleep($delay_ms * 1000);

            } catch (Exception $e) {
                // If sending fails, switch to next SMTP account
                $attempts++;
                $current_smtp_index = ($current_smtp_index + 1) % count($smtp_configs);
                $emails_sent_current_smtp = 0;

                // If we've tried all SMTP accounts and still failed
                if ($attempts >= $max_attempts) {
                    $error_count++;
                    $progress[] = [
                        'email' => $email,
                        'success' => false,
                        'message' => "Delivery pending"
                    ];
                }
            }
        }
    }

    // Log the campaign
    $stmt = $conn->prepare("INSERT INTO email_logs (subject, recipient_count, status, smtp_config_id) VALUES (?, ?, ?, ?)");
    $status = "Sent: $success_count, Pending: $error_count";
    $current_smtp_id = $smtp_configs[0]['id'];
    $stmt->bind_param("sisi", $_POST['subject'], $total_emails, $status, $current_smtp_id);
    $stmt->execute();
    $campaign_id = $conn->insert_id;

    // Return results
    echo json_encode([
        'success' => true,
        'progress' => $progress,
        'summary' => [
            'total' => $total_emails,
            'sent' => $success_count,
            'pending' => $error_count
        ],
        'campaign' => [
            'id' => $campaign_id,
            'subject' => $_POST['subject'],
            'recipient_count' => $total_emails,
            'status' => $status,
            'sent_at' => date('Y-m-d H:i:s')
        ]
    ]);
    exit;
}

// Get recent campaigns for display
$stmt = $conn->prepare("SELECT * FROM email_logs ORDER BY sent_at DESC LIMIT 5");
$stmt->execute();
$recent_campaigns = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get SMTP account count for display
$smtp_count = count(getAvailableSmtpAccounts($conn));
$max_emails = $smtp_count * 400;
?>



    <!DOCTYPE html>
    <html>
    <head>
        <title>Send Email Campaign</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <!-- Include Summernote CSS/JS -->
        <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
        <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
        
        <style>
            .log-entry {
    font-size: 0.9rem;
    padding: 4px 8px;
    border-radius: 4px;


}
.log-entry:hover {
    background-color: rgba(0,0,0,0.05);
}
#email-log {
    background: #f8f9fa;
    font-family: monospace;
}
.text-success {
    color: #28a745;
}
.text-danger {
    color: #dc3545;
}
        
            .loading-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 9999;
            }
            .loading-spinner {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                color: white;
                text-align: center;
            }
            .delete-btn {
                color: #dc3545;
                cursor: pointer;
                border: none;
                background: none;
                padding: 5px;
            }
            .delete-btn:hover {
                color: #c82333;
            }
            .campaign-card {
                transition: all 0.3s ease;
            }
            .campaign-card:hover {
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
            .send-progress {
                display: none;
                margin-top: 10px;
            }
            .progress {
                height: 20px;
            }
        </style>
    </head>
    <body>

    
        <!-- Loading Overlay -->
        <div class="loading-overlay">
            <div class="loading-spinner">
                <div class="spinner-border text-light" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="mt-2">Sending emails...</div>
            </div>
        </div>

        <div class="container-fluid">
            
            <div class="row">

                <?php renderSidebar('send_email'); ?>
                
                <div class="col-md-10 p-4">
                <div class="alert alert-info">
    <i class="fas fa-info-circle"></i> 
    Currently <?php echo $smtp_count; ?> active SMTP accounts available.
    Maximum <?php echo $max_emails; ?> emails can be sent (400 per account).
</div>
                    <h2>Send Email Campaign</h2>
                    
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">New Campaign</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" enctype="multipart/form-data" id="emailForm">
                                        <div class="mb-3">
                                            <label>Subject</label>
                                            <input type="text" name="subject" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label>Message</label>
                                            <textarea id="messageEditor" name="message" class="form-control" required></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label>Attachment (Allowed: JPG, JPEG, PNG, GIF, PDF)</label>
                                            <input type="file" name="attachment" class="form-control" 
                                                accept=".jpg,.jpeg,.png,.gif,.pdf">
                                        </div>
                                        <div class="mb-3">
                                            <label>Email List (one email per line)</label>
                                            <textarea name="email_list" class="form-control" rows="10" required></textarea>
                                        </div>
                                        <div class="send-progress mb-3">
    <div class="progress mb-2">
        <div class="progress-bar" role="progressbar" style="width: 0%"></div>
    </div>
    <div class="progress-details">
        <div id="progress-status" class="mb-2">Preparing to send...</div>
        <div id="email-log" class="border rounded p-2" style="max-height: 150px; overflow-y: auto;">
            <!-- Real-time email sending logs will appear here -->
        </div>
    </div>
</div>
                                        <button type="submit" class="btn btn-primary" id="submitButton">
                                            <i class="fas fa-paper-plane me-2"></i>Send Campaign
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Recent Campaigns</h5>
                                </div>
                                <div class="card-body">
                                    <div class="list-group" id="campaignsList">
                                        <?php foreach ($recent_campaigns as $campaign): ?>
                                        <div class="list-group-item campaign-card" id="campaign-<?php echo $campaign['id']; ?>">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($campaign['subject']); ?></h6>
                                                    <p class="mb-1">Recipients: <?php echo $campaign['recipient_count']; ?></p>
                                                    <small>Status: <?php echo htmlspecialchars($campaign['status']); ?></small>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php echo date('M j, Y g:i A', strtotime($campaign['sent_at'])); ?>
                                                    </small>
                                                </div>
                                                <button class="delete-btn" onclick="deleteCampaign(<?php echo $campaign['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                        <?php if (empty($recent_campaigns)): ?>
                                        <div class="list-group-item">
                                            <p class="mb-1">No campaigns sent yet</p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bootstrap Bundle with Popper -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        
        <script>
            // Initialize Summernote
            $(document).ready(function() {
                $('#messageEditor').summernote({
                    height: 300,
                    toolbar: [
                        ['style', ['style']],
                        ['font', ['bold', 'underline', 'clear']],
                        ['color', ['color']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['table', ['table']],
                        ['insert', ['link']],
                        ['view', ['fullscreen', 'codeview']]
                    ]
                });
            });

            // Delete campaign function
            function deleteCampaign(logId) {
                if (!confirm('Are you sure you want to delete this campaign log?')) {
                    return;
                }

                const formData = new FormData();
                formData.append('log_id', logId);

                fetch('delete_log.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the campaign card with animation
                        const campaignCard = document.getElementById(`campaign-${logId}`);
                        campaignCard.style.opacity = '0';
                        setTimeout(() => {
                            campaignCard.remove();
                            // Show "No campaigns" message if no campaigns left
                            const campaignsList = document.getElementById('campaignsList');
                            if (campaignsList.children.length === 0) {
                                campaignsList.innerHTML = '<div class="list-group-item"><p class="mb-1">No campaigns sent yet</p></div>';
                            }
                        }, 300);
                    } else {
                        alert(data.message || 'Error deleting campaign log');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting campaign log');
                });
            }

            // Form submission handling
// Replace the existing form submission code with this updated version
document.getElementById('emailForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Validate emails
    const emailList = document.querySelector('textarea[name="email_list"]').value;
    const emails = emailList.split('\n').filter(email => email.trim() !== '');
    
    if (emails.length === 0) {
        alert('Please enter at least one email address');
        return;
    }
    
    // Validate email format
    const invalidEmails = emails.filter(email => {
        return !email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/);
    });
    
    if (invalidEmails.length > 0) {
        alert('Invalid email addresses found:\n' + invalidEmails.join('\n'));
        return;
    }
    
    // Show progress elements
    const progressDiv = document.querySelector('.send-progress');
    const progressBar = progressDiv.querySelector('.progress-bar');
    const progressStatus = document.getElementById('progress-status');
    const emailLog = document.getElementById('email-log');
    
    progressDiv.style.display = 'block';
    document.querySelector('.loading-overlay').style.display = 'block';
    
    // Disable submit button
    const submitButton = this.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending...';
    
    // Initialize counters
    let totalEmails = emails.length;
    let sentCount = 0;
    let failedCount = 0;

    // Function to update progress
    function updateProgress(email, success, message = '') {
        const timestamp = new Date().toLocaleTimeString();
        const status = success ? 'success' : 'danger';
        const icon = success ? 'check-circle' : 'times-circle';
        const result = success ? 'Sent' : 'Failed';
        
        // Update counters
        if (success) sentCount++; else failedCount++;
        
        // Calculate progress percentage
        const progress = ((sentCount + failedCount) / totalEmails) * 100;
        
        // Update progress bar
        progressBar.style.width = `${progress}%`;
        progressStatus.innerHTML = `Progress: ${sentCount + failedCount} of ${totalEmails} (${sentCount} sent, ${failedCount} failed)`;
        
        // Add log entry
        const logEntry = document.createElement('div');
        logEntry.className = `log-entry text-${status} mb-1`;
        logEntry.innerHTML = `
            <small class="text-muted">${timestamp}</small> 
            <i class="fas fa-${icon}"></i> 
            ${result}: ${email}
            ${message ? `<br><small class="text-muted ps-4">${message}</small>` : ''}
        `;
        emailLog.insertBefore(logEntry, emailLog.firstChild);
    }
    
    // Submit the form
    const formData = new FormData(this);
    formData.append('request_type', 'ajax');
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        try {
            const result = JSON.parse(data);
            if (result.success) {
                // Update progress for each email
                if (result.progress) {
                    result.progress.forEach(item => {
                        updateProgress(item.email, item.success, item.message);
                    });
                }
                
                // Update the campaigns list with the new campaign
                if (result.campaign) {
                    const campaignsList = document.getElementById('campaignsList');
                    const newCampaignHtml = `
                        <div class="list-group-item campaign-card" id="campaign-${result.campaign.id}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">${result.campaign.subject}</h6>
                                    <p class="mb-1">Recipients: ${result.campaign.recipient_count}</p>
                                    <small>Status: ${result.campaign.status}</small>
                                    <br>
                                    <small class="text-muted">${result.campaign.sent_at}</small>
                                </div>
                                <button class="delete-btn" onclick="deleteCampaign(${result.campaign.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>`;
                    
                    // Remove "No campaigns" message if it exists
                    const noCampaignsMsg = campaignsList.querySelector('p.mb-1');
                    if (noCampaignsMsg && noCampaignsMsg.textContent === 'No campaigns sent yet') {
                        noCampaignsMsg.closest('.list-group-item').remove();
                    }
                    
                    // Add new campaign to the top of the list
                    campaignsList.insertAdjacentHTML('afterbegin', newCampaignHtml);
                }
                
                // Reset form
                $('#messageEditor').summernote('reset');
                this.reset();
                
                // Add completion message
                const completionMessage = document.createElement('div');
                completionMessage.className = 'alert alert-info mt-2';
                completionMessage.textContent = `Campaign completed: ${sentCount} sent, ${failedCount} failed`;
                emailLog.insertBefore(completionMessage, emailLog.firstChild);
            } else {
                throw new Error(result.message || 'Error sending emails');
            }
        } catch (e) {
            console.error('Error:', e);
            const errorMessage = document.createElement('div');
            errorMessage.className = 'alert alert-danger mt-2';
            errorMessage.textContent = e.message;
            emailLog.insertBefore(errorMessage, emailLog.firstChild);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const errorMessage = document.createElement('div');
        errorMessage.className = 'alert alert-danger mt-2';
        errorMessage.textContent = 'Error sending emails: ' + error.message;
        emailLog.insertBefore(errorMessage, emailLog.firstChild);
    })
    .finally(() => {
        // Hide loading overlay and reset button after short delay
        setTimeout(() => {
            document.querySelector('.loading-overlay').style.display = 'none';
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Send Campaign';
        }, 1000);
    });
});
        </script>
    </body>
    </html>