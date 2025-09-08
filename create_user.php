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

// Only admins can create users
if ($current_user['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

// Handle user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_user') {
    if ($auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $full_name = trim($_POST['full_name'] ?? '');
        $role = $_POST['role'] ?? 'user';
        
        // Validate input
        if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
            $error = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } else {
            // Create user using Auth class register method
            $result = $auth->register($username, $email, $password, $full_name);
            
            if ($result['success']) {
                // If user is created successfully and role is admin, update the role
                if ($role === 'admin') {
                    try {
                        $database = new Database();
                        $conn = $database->getConnection();
                        $stmt = $conn->prepare("UPDATE users SET role = 'admin' WHERE email = ?");
                        $stmt->execute([$email]);
                    } catch (Exception $e) {
                        error_log("Error updating user role: " . $e->getMessage());
                    }
                }
                
                // Create profile data if provided
                $profile_data = [
                    'phone' => trim($_POST['phone'] ?? ''),
                    'address' => trim($_POST['address'] ?? ''),
                    'bio' => trim($_POST['bio'] ?? '')
                ];
                
                // Get the newly created user's ID
                try {
                    $database = new Database();
                    $conn = $database->getConnection();
                    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    $new_user = $stmt->fetch();
                    
                    if ($new_user && array_filter($profile_data)) {
                        $userCRUD->createUserProfile($new_user['id'], $profile_data);
                    }
                } catch (Exception $e) {
                    error_log("Error creating user profile: " . $e->getMessage());
                }
                
                $success = 'User created successfully!';
            } else {
                $error = $result['message'];
            }
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
    <title>Create User - Secure User Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                        <li class="breadcrumb-item active" aria-current="page">Create User</li>
                    </ol>
                </nav>

                <!-- Create User Form -->
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-user-plus me-2"></i>Create New User
                        </h4>
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

                        <form method="POST" action="" id="createUserForm">
                            <input type="hidden" name="action" value="create_user">
                            <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                            
                            <!-- Basic Information Section -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="border-bottom pb-2 mb-3">
                                        <i class="fas fa-user me-2 text-primary"></i>Basic Information
                                    </h5>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="full_name" class="form-label">
                                        <i class="fas fa-user me-1"></i>Full Name *
                                    </label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                           value="<?php echo Auth::sanitizeOutput($_POST['full_name'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="username" class="form-label">
                                        <i class="fas fa-at me-1"></i>Username *
                                    </label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo Auth::sanitizeOutput($_POST['username'] ?? ''); ?>" required>
                                    <div class="form-text">Must be unique</div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope me-1"></i>Email Address *
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo Auth::sanitizeOutput($_POST['email'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="role" class="form-label">
                                        <i class="fas fa-shield-alt me-1"></i>User Role *
                                    </label>
                                    <select class="form-control" id="role" name="role" required>
                                        <option value="user" <?php echo ($_POST['role'] ?? '') === 'user' ? 'selected' : ''; ?>>Standard User</option>
                                        <option value="admin" <?php echo ($_POST['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label for="password" class="form-label">
                                        <i class="fas fa-lock me-1"></i>Password *
                                    </label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" 
                                               minlength="6" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                            <i class="fas fa-eye" id="toggleIcon"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Minimum 6 characters required</div>
                                </div>
                            </div>

                            <!-- Profile Information Section -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="border-bottom pb-2 mb-3">
                                        <i class="fas fa-id-card me-2 text-success"></i>Profile Information (Optional)
                                    </h5>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">
                                        <i class="fas fa-phone me-1"></i>Phone Number
                                    </label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo Auth::sanitizeOutput($_POST['phone'] ?? ''); ?>"
                                           placeholder="Enter phone number">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="address" class="form-label">
                                        <i class="fas fa-map-marker-alt me-1"></i>Address
                                    </label>
                                    <textarea class="form-control" id="address" name="address" rows="3"
                                              placeholder="Enter address"><?php echo Auth::sanitizeOutput($_POST['address'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="col-12 mb-3">
                                    <label for="bio" class="form-label">
                                        <i class="fas fa-info-circle me-1"></i>Bio
                                    </label>
                                    <textarea class="form-control" id="bio" name="bio" rows="4" 
                                              placeholder="Tell us about this user..." 
                                              maxlength="500"><?php echo Auth::sanitizeOutput($_POST['bio'] ?? ''); ?></textarea>
                                    <div class="form-text">Maximum 500 characters</div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex justify-content-between">
                                <a href="dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                                </a>
                                <div>
                                    <button type="button" class="btn btn-outline-warning me-2" onclick="resetForm()">
                                        <i class="fas fa-undo me-1"></i>Reset Form
                                    </button>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-user-plus me-1"></i>Create User
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        // Reset form
        function resetForm() {
            if (confirm('Are you sure you want to reset all fields?')) {
                document.getElementById('createUserForm').reset();
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
        document.getElementById('createUserForm').addEventListener('submit', function(e) {
            const fullName = document.getElementById('full_name').value.trim();
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const bio = document.getElementById('bio').value;
            
            if (fullName === '' || username === '' || email === '' || password === '') {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long.');
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