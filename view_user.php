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
$user_id = intval($_GET['id'] ?? 0);

// Get user data
$user_data = $userCRUD->getUserById($user_id);
if (!$user_data) {
    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details - Secure User Management System</title>
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
            margin: 0 auto;
        }
        .info-card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
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
</nav>

<div class="container mt-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">User Details</li>
        </ol>
    </nav>

    <div class="row">
        <!-- User Profile Card -->
        <div class="col-lg-4 mb-4">
            <div class="card info-card">
                <div class="card-body text-center">
                    <div class="profile-avatar mb-3">
                        <?php echo strtoupper(substr($user_data['full_name'], 0, 1)); ?>
                    </div>
                    
                    <h3 class="card-title"><?php echo Auth::sanitizeOutput($user_data['full_name']); ?></h3>
                    <p class="text-muted mb-2">@<?php echo Auth::sanitizeOutput($user_data['username']); ?></p>
                    
                    <span class="badge bg-<?php echo $user_data['role'] === 'admin' ? 'warning' : 'primary'; ?> mb-3">
                        <i class="fas fa-<?php echo $user_data['role'] === 'admin' ? 'crown' : 'user'; ?> me-1"></i>
                        <?php echo ucfirst($user_data['role']); ?>
                    </span>
                    
                    <div class="row text-center mt-4">
                        <div class="col">
                            <small class="text-muted d-block">Member Since</small>
                            <strong><?php echo date('M Y', strtotime($user_data['created_at'])); ?></strong>
                        </div>
                        <div class="col">
                            <small class="text-muted d-block">Last Updated</small>
                            <strong><?php echo $user_data['updated_at'] ? date('M Y', strtotime($user_data['updated_at'])) : 'Never'; ?></strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card info-card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-bolt me-2"></i>Quick Actions
                    </h6>
                </div>
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i>Back to Dashboard
                    </a>
                    
                    <?php if ($current_user['role'] === 'admin' || $current_user['id'] == $user_data['id']): ?>
                        <a href="edit_user.php?id=<?php echo $user_data['id']; ?>" 
                           class="list-group-item list-group-item-action">
                            <i class="fas fa-edit me-2"></i>Edit User
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($current_user['role'] === 'admin'): ?>
                        <a href="create_user.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-user-plus me-2"></i>Add New User
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- User Details -->
        <div class="col-lg-8">
            <!-- Contact Information -->
            <div class="card info-card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-address-card me-2"></i>Contact Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-primary text-white me-3 d-flex align-items-center justify-content-center" 
                                     style="width: 40px; height: 40px;">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Email Address</small>
                                    <strong><?php echo Auth::sanitizeOutput($user_data['email']); ?></strong>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-success text-white me-3 d-flex align-items-center justify-content-center" 
                                     style="width: 40px; height: 40px;">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Phone Number</small>
                                    <strong><?php echo $user_data['phone'] ? Auth::sanitizeOutput($user_data['phone']) : 'Not provided'; ?></strong>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <div class="rounded-circle bg-info text-white me-3 d-flex align-items-center justify-content-center" 
                                     style="width: 40px; height: 40px; flex-shrink: 0;">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Address</small>
                                    <strong><?php echo $user_data['address'] ? Auth::sanitizeOutput($user_data['address']) : 'Not provided'; ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Biography -->
            <?php if ($user_data['bio']): ?>
                <div class="card info-card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>Biography
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0"><?php echo nl2br(Auth::sanitizeOutput($user_data['bio'])); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Account Statistics -->
            <div class="card info-card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Account Statistics
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="card stat-card bg-primary text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-calendar-plus fa-2x mb-2"></i>
                                    <h6>Account Created</h6>
                                    <small><?php echo date('M j, Y', strtotime($user_data['created_at'])); ?></small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="card stat-card bg-info text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-edit fa-2x mb-2"></i>
                                    <h6>Last Updated</h6>
                                    <small><?php echo $user_data['updated_at'] ? date('M j, Y', strtotime($user_data['updated_at'])) : 'Never updated'; ?></small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="card stat-card bg-success text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                                    <h6>Status</h6>
                                    <small>Active User</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="card stat-card bg-warning text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-shield-alt fa-2x mb-2"></i>
                                    <h6>Security Level</h6>
                                    <small><?php echo $user_data['role'] === 'admin' ? 'Administrator' : 'Standard User'; ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="alert alert-light border">
                                <div class="row text-center">
                                    <div class="col-md-4">
                                        <i class="fas fa-user-check text-success fa-lg me-2"></i>
                                        <small><strong>Profile Complete:</strong> 
                                            <?php 
                                            $completeness = 0;
                                            if ($user_data['email']) $completeness += 25;
                                            if ($user_data['full_name']) $completeness += 25;
                                            if ($user_data['phone']) $completeness += 25;
                                            if ($user_data['bio'] || $user_data['address']) $completeness += 25;
                                            echo $completeness . '%';
                                            ?>
                                        </small>
                                    </div>
                                    <div class="col-md-4">
                                        <i class="fas fa-clock text-info fa-lg me-2"></i>
                                        <small><strong>Days Active:</strong> 
                                            <?php echo ceil((time() - strtotime($user_data['created_at'])) / 86400); ?> days
                                        </small>
                                    </div>
                                    <div class="col-md-4">
                                        <i class="fas fa-key text-warning fa-lg me-2"></i>
                                        <small><strong>Access Level:</strong> 
                                            <?php echo ucfirst($user_data['role']); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Add hover effects to stat cards
    document.querySelectorAll('.stat-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
</script>
</body>
</html>