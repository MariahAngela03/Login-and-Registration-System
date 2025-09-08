<?php
require_once 'classes/Auth.php';
require_once 'classes/UserCRUD.php';

$auth = new Auth();
$userCRUD = new UserCRUD();

// Check if user is logged in and is admin
if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$current_user = $auth->getCurrentUser();

// Only admins can delete users
if ($current_user['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

$user_id = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

// Get user data to display
$user_data = $userCRUD->getUserById($user_id);
if (!$user_data) {
    header('Location: dashboard.php?error=' . urlencode('User not found.'));
    exit();
}

// Check if user can be deleted
$delete_check = $userCRUD->canDeleteUser($user_id, $current_user['id']);

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    if (!$auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } elseif (!$delete_check['can_delete']) {
        $error = $delete_check['message'];
    } elseif (($_POST['confirmation'] ?? '') !== 'DELETE') {
        $error = 'Please type "DELETE" to confirm the deletion.';
    } else {
        $result = $userCRUD->deleteUser($user_id);
        
        if ($result['success']) {
            header('Location: dashboard.php?success=' . urlencode($result['message']));
            exit();
        } else {
            $error = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete User - Secure User Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .danger-zone {
            border: 2px solid #dc3545;
            border-radius: 10px;
            background: linear-gradient(135deg, #fff5f5 0%, #fee 100%);
        }
        .warning-icon {
            color: #dc3545;
            font-size: 4rem;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-user-shield me-2"></i>User Management
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" 
                       data-bs-toggle="dropdown">
                        <div class="rounded-circle bg-white text-primary me-2 d-flex align-items-center justify-content-center" 
                             style="width: 32px; height: 32px; font-weight: bold;">
                            <?php echo strtoupper(substr($current_user['full_name'], 0, 1)); ?>
                        </div>
                        <?php echo Auth::sanitizeOutput($current_user['full_name']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a></li>
                        <li><a class="dropdown-item" href="profile.php">
                            <i class="fas fa-user me-2"></i>My Profile
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="dashboard.php?action=logout">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="view_user.php?id=<?php echo $user_id; ?>">User Details</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Delete User</li>
                    </ol>
                </nav>

                <!-- Danger Zone -->
                <div class="card danger-zone shadow">
                    <div class="card-header bg-danger text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>Danger Zone - Delete User
                        </h4>
                    </div>
                    <div class="card-body text-center">
                        <div class="warning-icon mb-4">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        
                        <h3 class="text-danger mb-3">
                            You are about to permanently delete this user
                        </h3>

                        <!-- User Information -->
                        <div class="card mb-4 mx-auto" style="max-width: 500px;">
                            <div class="card-body">
                                <div class="row text-start">
                                    <div class="col-sm-4"><strong>Full Name:</strong></div>
                                    <div class="col-sm-8"><?php echo Auth::sanitizeOutput($user_data['full_name']); ?></div>
                                </div>
                                <hr>
                                <div class="row text-start">
                                    <div class="col-sm-4"><strong>Username:</strong></div>
                                    <div class="col-sm-8"><?php echo Auth::sanitizeOutput($user_data['username']); ?></div>
                                </div>
                                <hr>
                                <div class="row text-start">
                                    <div class="col-sm-4"><strong>Email:</strong></div>
                                    <div class="col-sm-8"><?php echo Auth::sanitizeOutput($user_data['email']); ?></div>
                                </div>
                                <hr>
                                <div class="row text-start">
                                    <div class="col-sm-4"><strong>Role:</strong></div>
                                    <div class="col-sm-8">
                                        <span class="badge bg-<?php echo $user_data['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                            <?php echo ucfirst($user_data['role']); ?>
                                        </span>
                                    </div>
                                </div>
                                <hr>
                                <div class="row text-start">
                                    <div class="col-sm-4"><strong>Member Since:</strong></div>
                                    <div class="col-sm-8"><?php echo date('M j, Y', strtotime($user_data['created_at'])); ?></div>
                                </div>
                            </div>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo Auth::sanitizeOutput($error); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!$delete_check['can_delete']): ?>
                            <div class="alert alert-warning" role="alert">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Cannot Delete User:</strong> <?php echo Auth::sanitizeOutput($delete_check['message']); ?>
                            </div>
                            
                            <div class="d-flex justify-content-center gap-3">
                                <a href="view_user.php?id=<?php echo $user_id; ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-1"></i>Back to User Details
                                </a>
                                <a href="dashboard.php" class="btn btn-primary">
                                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning" role="alert">
                                <h6><i class="fas fa-exclamation-triangle me-2"></i>Warning: This action cannot be undone!</h6>
                                <p class="mb-0">
                                    Deleting this user will permanently remove:
                                    <br>• User account and login credentials
                                    <br>• All profile information
                                    <br>• Associated data and settings
                                </p>
                            </div>

                            <form method="POST" action="" id="deleteUserForm">
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                                
                                <div class="mb-4">
                                    <label for="confirmation" class="form-label">
                                        <strong>Type "DELETE" to confirm:</strong>
                                    </label>
                                    <input type="text" class="form-control mx-auto" id="confirmation" 
                                           name="confirmation" style="max-width: 200px;" 
                                           placeholder="Type DELETE" required>
                                    <div class="form-text text-muted">This confirmation is case-sensitive</div>
                                </div>

                                <div class="d-flex justify-content-center gap-3">
                                    <a href="view_user.php?id=<?php echo $user_id; ?>" class="btn btn-outline-secondary btn-lg">
                                        <i class="fas fa-times me-1"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-danger btn-lg" id="deleteBtn" disabled>
                                        <i class="fas fa-trash-alt me-1"></i>Delete User Permanently
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enable/disable delete button based on confirmation input
        const confirmationInput = document.getElementById('confirmation');
        const deleteBtn = document.getElementById('deleteBtn');
        
        if (confirmationInput && deleteBtn) {
            confirmationInput.addEventListener('input', function() {
                deleteBtn.disabled = this.value !== 'DELETE';
                
                if (this.value === 'DELETE') {
                    deleteBtn.classList.remove('btn-outline-danger');
                    deleteBtn.classList.add('btn-danger');
                } else {
                    deleteBtn.classList.remove('btn-danger');
                    deleteBtn.classList.add('btn-outline-danger');
                }
            });

            // Final confirmation before submission
            document.getElementById('deleteUserForm').addEventListener('submit', function(e) {
                if (confirmationInput.value !== 'DELETE') {
                    e.preventDefault();
                    alert('Please type "DELETE" exactly to confirm.');
                    return;
                }
                
                const userName = '<?php echo addslashes($user_data['full_name']); ?>';
                if (!confirm(`Are you absolutely sure you want to permanently delete ${userName}? This action cannot be undone!`)) {
                    e.preventDefault();
                }
            });
        }
    </script>
</body>
</html>

