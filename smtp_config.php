<?php
// smtp_config.php
require_once 'config.php';
require_once 'components/sidebar.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
            case 'edit':
                $account_name = filter_input(INPUT_POST, 'account_name', FILTER_SANITIZE_STRING);
                $host = filter_input(INPUT_POST, 'host', FILTER_SANITIZE_STRING);
                $port = filter_input(INPUT_POST, 'port', FILTER_VALIDATE_INT);
                $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
                $password = $_POST['password'];
                $encryption = filter_input(INPUT_POST, 'encryption', FILTER_SANITIZE_STRING);
                $from_email = filter_input(INPUT_POST, 'from_email', FILTER_VALIDATE_EMAIL);
                $from_name = filter_input(INPUT_POST, 'from_name', FILTER_SANITIZE_STRING);
                $delay_ms = filter_input(INPUT_POST, 'delay_ms', FILTER_VALIDATE_INT);
                $is_active = isset($_POST['is_active']) ? 1 : 0;

                if ($_POST['action'] === 'add') {
                    $stmt = $conn->prepare("INSERT INTO smtp_config (account_name, host, port, username, password, encryption, from_email, from_name, delay_ms, is_active) 
                                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssisssssis", $account_name, $host, $port, $username, $password, $encryption, $from_email, $from_name, $delay_ms, $is_active);
                } else {
                    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
                    if (empty($_POST['password'])) {
                        // Update without password
                        $stmt = $conn->prepare("UPDATE smtp_config SET account_name=?, host=?, port=?, username=?, encryption=?, 
                                             from_email=?, from_name=?, delay_ms=?, is_active=? WHERE id=?");
                        $stmt->bind_param("ssissssiii", $account_name, $host, $port, $username, $encryption, 
                                       $from_email, $from_name, $delay_ms, $is_active, $id);
                    } else {
                        // Update including password
                        $stmt = $conn->prepare("UPDATE smtp_config SET account_name=?, host=?, port=?, username=?, password=?, 
                                             encryption=?, from_email=?, from_name=?, delay_ms=?, is_active=? WHERE id=?");
                        $stmt->bind_param("ssissssssii", $account_name, $host, $port, $username, $password, 
                                       $encryption, $from_email, $from_name, $delay_ms, $is_active, $id);
                    }
                }
                
                if ($stmt->execute()) {
                    $success = "SMTP configuration " . ($_POST['action'] === 'add' ? 'added' : 'updated') . " successfully";
                } else {
                    $error = "Error saving SMTP configuration";
                }
                break;

            case 'delete':
                $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
                $stmt = $conn->prepare("DELETE FROM smtp_config WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $success = "SMTP configuration deleted successfully";
                } else {
                    $error = "Error deleting SMTP configuration";
                }
                break;
        }
    }
}

// Get all SMTP configurations
$smtp_configs = $conn->query("SELECT * FROM smtp_config ORDER BY account_name")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html dir="ltr">
<head>
    <title>SMTP Configurations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <meta charset="UTF-8">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php renderSidebar('smtp_config'); ?>
            
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>SMTP Configurations</h2>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#smtpModal">
                        Add New SMTP Account
                    </button>
                </div>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Account Name</th>
                                        <th>Host</th>
                                        <th>Username</th>
                                        <th>From Email</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($smtp_configs as $config): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($config['account_name']); ?></td>
                                        <td><?php echo htmlspecialchars($config['host']); ?></td>
                                        <td><?php echo htmlspecialchars($config['username']); ?></td>
                                        <td><?php echo htmlspecialchars($config['from_email']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $config['is_active'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $config['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary edit-smtp" 
                                                    data-bs-toggle="modal" data-bs-target="#smtpModal"
                                                    data-smtp="<?php echo htmlspecialchars(json_encode($config)); ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger delete-smtp" 
                                                    data-id="<?php echo $config['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SMTP Modal -->
    <div class="modal fade" id="smtpModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">SMTP Configuration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="smtpForm" method="POST">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="id" value="">
                        
                        <div class="mb-3">
                            <label>Account Name</label>
                            <input type="text" name="account_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>SMTP Host</label>
                            <input type="text" name="host" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>SMTP Port</label>
                            <input type="number" name="port" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control">
                            <small class="text-muted edit-mode-only" style="display: none;">Leave empty to keep current password</small>
                        </div>
                        <div class="mb-3">
                            <label>Encryption</label>
                            <select name="encryption" class="form-control" required>
                                <option value="tls">TLS</option>
                                <option value="ssl">SSL</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>From Email</label>
                            <input type="email" name="from_email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>From Name</label>
                            <input type="text" name="from_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Delay Between Emails (milliseconds)</label>
                            <input type="number" name="delay_ms" class="form-control" required value="1000" min="0" step="100">
                            <small class="text-muted">Recommended minimum: 1000ms (1 second)</small>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" class="form-check-input" value="1" checked>
                                <label class="form-check-label">Active</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Configuration</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this SMTP configuration?
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle edit button clicks
        document.querySelectorAll('.edit-smtp').forEach(button => {
            button.addEventListener('click', function() {
                const smtp = JSON.parse(this.dataset.smtp);
                const form = document.getElementById('smtpForm');
                form.action.value = 'edit';
                form.id.value = smtp.id;
                form.account_name.value = smtp.account_name;
                form.host.value = smtp.host;
                form.port.value = smtp.port;
                form.username.value = smtp.username;
                form.password.value = '';
                form.encryption.value = smtp.encryption;
                form.from_email.value = smtp.from_email;
                form.from_name.value = smtp.from_name;
                form.delay_ms.value = smtp.delay_ms;
                form.is_active.checked = smtp.is_active == 1;
                document.querySelector('.edit-mode-only').style.display = 'block';
            });
        });

        // Handle delete button clicks
        document.querySelectorAll('.delete-smtp').forEach(button => {
            button.addEventListener('click', function() {
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                document.querySelector('#deleteModal input[name="id"]').value = this.dataset.id;
                deleteModal.show();
            });
        });

        // Reset form when adding new SMTP
        document.querySelector('[data-bs-target="#smtpModal"]').addEventListener('click', function() {
            const form = document.getElementById('smtpForm');
            form.reset();
            form.action.value = 'add';
            form.id.value = '';
            document.querySelector('.edit-mode-only').style.display = 'none';
        });

        // Form validation
        document.getElementById('smtpForm').addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });

        // Handle port suggestions based on encryption
        document.querySelector('select[name="encryption"]').addEventListener('change', function() {
            const portInput = document.querySelector('input[name="port"]');
            if (this.value === 'tls') {
                portInput.value = '587';
            } else if (this.value === 'ssl') {
                portInput.value = '465';
            }
        });

        // Auto-fill from_email when username is an email
        document.querySelector('input[name="username"]').addEventListener('blur', function() {
            const fromEmail = document.querySelector('input[name="from_email"]');
            if (this.value.includes('@') && !fromEmail.value) {
                fromEmail.value = this.value;
            }
        });

        // Show appropriate alerts for form actions
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('success')) {
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show';
            alert.innerHTML = `
                ${urlParams.get('success')}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.col-md-10').insertBefore(alert, document.querySelector('.card'));
        }
    });
    </script>
</body>
</html>