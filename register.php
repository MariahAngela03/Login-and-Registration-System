<?php
require_once 'classes/Auth.php';

$auth = new Auth();

// Redirect to dashboard if already logged in
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    
    // Validate password confirmation
    if ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $result = $auth->register($username, $email, $password, $full_name);
        
        if ($result['success']) {
            $success = $result['message'];
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
    <title>Register - Secure User Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-4">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <i class="fas fa-user-plus fa-3x text-success"></i>
                            <h3 class="mt-3">Create Account</h3>
                            <p class="text-muted">Join our secure platform</p>
                        </div>
                        
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
                        
                        <form method="POST" action="" id="registerForm">
                            <input type="hidden" name="action" value="register">
                            
                            <div class="row">
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
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-1"></i>Email Address *
                                </label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo Auth::sanitizeOutput($_POST['email'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">
                                        <i class="fas fa-lock me-1"></i>Password *
                                    </label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" 
                                               minlength="6" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password', 'toggleIcon1')">
                                            <i class="fas fa-eye" id="toggleIcon1"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Minimum 6 characters</div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">
                                        <i class="fas fa-lock me-1"></i>Confirm Password *
                                    </label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                               minlength="6" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password', 'toggleIcon2')">
                                            <i class="fas fa-eye" id="toggleIcon2"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="terms" required>
                                    <label class="form-check-label" for="terms">
                                        I agree to the <a href="#" class="text-decoration-none">Terms of Service</a> and 
                                        <a href="#" class="text-decoration-none">Privacy Policy</a> *
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-success w-100 mb-3">
                                <i class="fas fa-user-plus me-1"></i>Create Account
                            </button>
                        </form>
                        
                        <div class="text-center">
                            <p class="mb-0">Already have an account?</p>
                            <a href="index.php" class="btn btn-outline-primary">
                                <i class="fas fa-sign-in-alt me-1"></i>Login Here
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        function togglePassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(iconId);
            
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
        
        // Client-side form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const terms = document.getElementById('terms').checked;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return;
            }
            
            if (!terms) {
                e.preventDefault();
                alert('Please accept the terms of service!');
                return;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
                return;
            }
        });
        
        // Real-time password matching validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword !== '' && password !== confirmPassword) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    </script>
</body>
</html>