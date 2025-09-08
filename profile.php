<?php
require_once 'classes/Auth.php';
require_once 'classes/UserCRUD.php';

$auth = new Auth();
$userCRUD = new UserCRUD();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$current_user = $auth->getCurrentUser();
$error = '';
$success = '';

// Get user details with profile
$user_data = $userCRUD->getUserById($current_user['id']);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    if ($auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $user_update = [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'email' => trim($_POST['email'] ?? '')
        ];
        
        $profile_update = [
            'phone' => trim($_POST['phone'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'bio' => trim($_POST['bio'] ?? '')
        ];
        
        $result = $userCRUD->updateUser($current_user['id'], $user_update, $profile_update);
        
        if ($result['success']) {
            $success = $result['message'];
            // Refresh user data
            $user_data = $userCRUD->getUserById($current_user['id']);
            // Update session data
            $_SESSION['full_name'] = $user_update['full_name'];
            $_SESSION['email'] = $user_update['email'];
        } else {
            $error = $result['message'];
        }
    } else {
        $error = 'Invalid security token. Please try again.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Secure User Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: linear-gradient(45deg, #007bff, #28a745);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            font-weight: bold;
        }
        .profile-card {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
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
        <div class="row">
            <div class="col-md-4 mb-4">
                <!-- Profile Summary Card -->
                <div class="card profile-card">
                    <div class="card-body text-center">
                        <div class="profile-avatar mx-auto mb-3">
                            <?php echo strtoupper(substr($user_data['full_name'], 0, 1)); ?>
                        </div>
                        <h4 class="card-title"><?php echo Auth::sanitizeOutput($user_data['full_name']); ?></h4>
                        <p class="text-muted">@<?php echo Auth::sanitizeOutput($user_data['username']); ?></p>
                        <span class="badge bg-<?php echo $user_data['role'] === 'admin' ? 'warning' : 'primary'; ?> mb-3">
                            <?php echo ucfirst($user_data['role']); ?>
                        </span>
                        
                        <div class="text-start">
                            <small class="text-muted d-block mb-2">
                                <i class="fas fa-envelope me-2"></i>
                                <?php echo Auth::sanitizeOutput($user_data['email']); ?>
                            </small>
                            <?php if ($user_data['phone']): ?>
                                <small class="text-muted d-block mb-2">
                                    <i class="fas fa-phone me-2"></i>
                                    <?php echo Auth::sanitizeOutput($user_data['phone']); ?>
                                </small>
                            <?php endif; ?>
                            <small class="text-muted d-block">
                                <i class="fas fa-calendar me-2"></i>
                                Joined <?php echo date('F j, Y', strtotime($user_data['created_at'])); ?>
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h6>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="dashboard.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                        </a>
                        <?php if ($current_user['role'] === 'admin'): ?>
                            <a href="create_user.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-user-plus me-2"></i>Add New User
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <!-- Edit Profile Form -->
                <div class="card profile-card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-edit me-2"></i>Edit Profile
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo Auth::sanitizeOutput($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo Auth::sanitizeOutput($success); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" id="profileForm">
                            <input type="hidden" name="action" value="update_profile">
                            <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="full_name" class="form-label">
                                        <i class="fas fa-user me-1"></i>Full Name *
                                    </label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                           value="<?php echo Auth::sanitizeOutput($user_data['full_name']); ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope me-1"></i>Email Address *
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo Auth::sanitizeOutput($user_data['email']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="username" class="form-label">
                                        <i class="fas fa-at me-1"></i>Username
                                    </label>
                                    <input type="text" class="form-control" id="username" 
                                           value="<?php echo Auth::sanitizeOutput($user_data['username']); ?>" 
                                           disabled>
                                    <div class="form-text">Username cannot be changed</div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">
                                        <i class="fas fa-phone me-1"></i>Phone Number
                                    </label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo Auth::sanitizeOutput($user_data['phone'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">
                                    <i class="fas fa-map-marker-alt me-1"></i>Address
                                </label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?php echo Auth::sanitizeOutput($user_data['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="bio" class="form-label">
                                    <i class="fas fa-info-circle me-1"></i>Bio
                                </label>
                                <textarea class="form-control" id="bio" name="bio" rows="4" 
                                          placeholder="Tell us about yourself..."><?php echo Auth::sanitizeOutput($user_data['bio'] ?? ''); ?></textarea>
                                <div class="form-text">Maximum 500 characters</div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-shield-alt me-1"></i>Account Role
                                    </label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo ucfirst($user_data['role']); ?>" disabled>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-calendar me-1"></i>Member Since
                                    </label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo date('F j, Y', strtotime($user_data['created_at'])); ?>" disabled>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                                </a>
                                <div>
                                    <button type="button" class="btn btn-outline-warning me-2" onclick="resetForm()">
                                        <i class="fas fa-undo me-1"></i>Reset
                                    </button>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save me-1"></i>Save Changes
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Account Information -->
                <div class="card mt-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>Account Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-muted">Security</h6>
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        Password protected
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        Session secured
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        CSRF protection enabled
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted">Activity</h6>
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-clock text-primary me-2"></i>
                                        Last login: <?php echo date('M j, Y H:i'); ?>
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-edit text-primary me-2"></i>
                                        Profile updated: <?php echo $user_data['updated_at'] ? date('M j, Y', strtotime($user_data['updated_at'])) : 'Never'; ?>
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-user-plus text-primary me-2"></i>
                                        Account created: <?php echo date('M j, Y', strtotime($user_data['created_at'])); ?>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Store original form values for reset functionality
        const originalValues = {
            full_name: document.getElementById('full_name').value,
            email: document.getElementById('email').value,
            phone: document.getElementById('phone').value,
            address: document.getElementById('address').value,
            bio: document.getElementById('bio').value
        };
        
        function resetForm() {
            if (confirm('Are you sure you want to reset all changes?')) {
                document.getElementById('full_name').value = originalValues.full_name;
                document.getElementById('email').value = originalValues.email;
                document.getElementById('phone').value = originalValues.phone;
                document.getElementById('address').value = originalValues.address;
                document.getElementById('bio').value = originalValues.bio;
            }
        }
        
        // Character counter for bio
        const bioTextarea = document.getElementById('bio');
        const bioHelp = bioTextarea.nextElementSibling;
        
        function updateCharCount() {
            const remaining = 500 - bioTextarea.value.length;
            bioHelp.textContent = `${remaining} characters remaining`;
            bioHelp.className = remaining < 0 ? 'form-text text-danger' : 'form-text text-muted';
        }
        
        bioTextarea.addEventListener('input', updateCharCount);
        updateCharCount(); // Initial count
        
        // Form validation
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const fullName = document.getElementById('full_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const bio = document.getElementById('bio').value;
            
            if (fullName === '' || email === '') {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return;
            }
            
            if (bio.length > 500) {
                e.preventDefault();
                alert('Bio must not exceed 500 characters.');
                return;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return;
            }
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>