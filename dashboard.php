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

// Check if user can perform admin actions (both admin and user roles)
$can_manage_users = ($current_user['role'] === 'admin' || $current_user['role'] === 'user');

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $auth->logout();
    header('Location: index.php');
    exit();
}

// Handle user deletion (admin and user roles)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    if ($can_manage_users && $auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $user_id = intval($_POST['user_id'] ?? 0);
        $result = $userCRUD->deleteUser($user_id);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    } else {
        $error = 'Unauthorized action.';
    }
}

// Handle bulk delete (admin and user roles)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_delete') {
    if ($can_manage_users && $auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $selected_users = $_POST['selected_users'] ?? [];
        $deleted_count = 0;
        $errors = [];
        
        foreach ($selected_users as $user_id) {
            $user_id = intval($user_id);
            if ($user_id !== $current_user['id']) { // Don't delete self
                $result = $userCRUD->deleteUser($user_id);
                if ($result['success']) {
                    $deleted_count++;
                } else {
                    $errors[] = $result['message'];
                }
            }
        }
        
        if ($deleted_count > 0) {
            $success = "Successfully deleted {$deleted_count} user(s).";
        }
        if (!empty($errors)) {
            $error = implode('; ', array_unique($errors));
        }
    } else {
        $error = 'Unauthorized action.';
    }
}

// Get all users for display
$search_term = $_GET['search'] ?? '';
if ($search_term) {
    $users = $userCRUD->searchUsers($search_term);
} else {
    $users = $userCRUD->getAllUsers();
}

// Handle success/error messages from URL parameters
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Secure User Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .navbar-brand {
            font-weight: bold;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(45deg, #007bff, #28a745);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        .table-actions .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .bulk-actions {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1rem;
            display: none;
        }
        .bulk-actions.show {
            display: block;
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
                        <div class="user-avatar me-2">
                            <?php echo strtoupper(substr($current_user['full_name'], 0, 1)); ?>
                        </div>
                        <?php echo Auth::sanitizeOutput($current_user['full_name']); ?>
                        <?php if ($current_user['role'] === 'admin'): ?>
                            <span class="badge bg-warning ms-2">Admin</span>
                        <?php else: ?>
                            <span class="badge bg-info ms-2">User</span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="profile.php">
                            <i class="fas fa-user me-2"></i>My Profile
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="?action=logout">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">Navigation</h6>
                        <div class="list-group list-group-flush">
                            <a href="dashboard.php" class="list-group-item list-group-item-action active">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                            <a href="profile.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-user me-2"></i>My Profile
                            </a>
                            <?php if ($can_manage_users): ?>
                                <a href="create_user.php" class="list-group-item list-group-item-action">
                                    <i class="fas fa-user-plus me-2"></i>Add User
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-chart-bar me-2"></i>Statistics
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="text-center">
                            <div class="row">
                                <div class="col-12 mb-2">
                                    <div class="bg-primary text-white rounded p-2">
                                        <i class="fas fa-users"></i>
                                        <span class="ms-2"><?php echo count($users); ?> Users</span>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="bg-success text-white rounded p-2">
                                        <i class="fas fa-user-check"></i>
                                        <span class="ms-2">Active System</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <!-- Welcome Card -->
                <div class="row mb-4">
                    <div class="col">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h4 class="card-title">
                                    <i class="fas fa-wave-square me-2"></i>
                                    Welcome, <?php echo Auth::sanitizeOutput($current_user['full_name']); ?>!
                                </h4>
                                <p class="card-text mb-0">
                                    Manage users and profiles securely. Last login: <?php echo date('Y-m-d H:i:s'); ?>
                                </p>
                            </div>
                        </div>
                    </div>
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

                <!-- User Management Card -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-users me-2"></i>User Management
                        </h5>
                        <?php if ($can_manage_users): ?>
                            <a href="create_user.php" class="btn btn-success btn-sm">
                                <i class="fas fa-user-plus me-1"></i>Add User
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-body">
                        <!-- Search Bar and Bulk Actions -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <form method="GET" class="d-flex">
                                    <input type="text" class="form-control" name="search" 
                                           placeholder="Search users..." value="<?php echo Auth::sanitizeOutput($search_term); ?>">
                                    <button class="btn btn-outline-primary ms-2" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <?php if ($search_term): ?>
                                        <a href="dashboard.php" class="btn btn-outline-secondary ms-1">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    <?php endif; ?>
                                </form>
                            </div>
                            <?php if ($can_manage_users): ?>
                                <div class="col-md-6 text-end">
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="toggleBulkActions()">
                                        <i class="fas fa-trash me-1"></i>Bulk Actions
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Bulk Actions Panel -->
                        <?php if ($can_manage_users): ?>
                            <div class="bulk-actions" id="bulkActionsPanel">
                                <form method="POST" id="bulkActionForm">
                                    <input type="hidden" name="action" value="bulk_delete">
                                    <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>
                                            <strong>Bulk Actions:</strong> 
                                            <span id="selectedCount">0</span> user(s) selected
                                        </span>
                                        <div>
                                            <button type="button" class="btn btn-sm btn-outline-secondary me-2" onclick="selectAllUsers()">
                                                Select All
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary me-2" onclick="deselectAllUsers()">
                                                Deselect All
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="bulkDeleteUsers()">
                                                <i class="fas fa-trash me-1"></i>Delete Selected
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>

                        <!-- Users Table -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <?php if ($can_manage_users): ?>
                                            <th width="50">
                                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                            </th>
                                        <?php endif; ?>
                                        <th>Avatar</th>
                                        <th>Name</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($users)): ?>
                                        <tr>
                                            <td colspan="<?php echo ($can_manage_users) ? '8' : '7'; ?>" class="text-center text-muted">
                                                <?php echo $search_term ? 'No users found matching your search.' : 'No users found.'; ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <?php if ($can_manage_users): ?>
                                                    <td>
                                                        <?php if ($user['id'] != $current_user['id']): ?>
                                                            <input type="checkbox" class="user-checkbox" name="selected_users[]" value="<?php echo $user['id']; ?>" onchange="updateSelectedCount()">
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endif; ?>
                                                <td>
                                                    <div class="user-avatar">
                                                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <strong><?php echo Auth::sanitizeOutput($user['full_name']); ?></strong>
                                                    <?php if ($user['phone']): ?>
                                                        <br><small class="text-muted">
                                                            <i class="fas fa-phone fa-sm"></i> 
                                                            <?php echo Auth::sanitizeOutput($user['phone']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo Auth::sanitizeOutput($user['username']); ?></td>
                                                <td><?php echo Auth::sanitizeOutput($user['email']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'warning' : 'primary'; ?>">
                                                        <?php echo ucfirst($user['role']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small><?php echo date('M j, Y', strtotime($user['created_at'])); ?></small>
                                                </td>
                                                <td class="table-actions">
                                                    <a href="view_user.php?id=<?php echo $user['id']; ?>" 
                                                       class="btn btn-sm btn-outline-info" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    
                                                    <?php if ($can_manage_users || $current_user['id'] == $user['id']): ?>
                                                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($can_manage_users && $current_user['id'] != $user['id']): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                title="Delete" onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo addslashes($user['full_name']); ?>')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirm Deletion
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete user <strong id="deleteUserName"></strong>?</p>
                    <p class="text-danger mb-0">
                        <i class="fas fa-warning me-1"></i>
                        This action cannot be undone and will permanently remove all user data.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" id="deleteForm" style="display: inline;">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="user_id" id="deleteUserId">
                        <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i>Delete User
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Delete confirmation function
        function confirmDelete(userId, userName) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUserName').textContent = userName;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
        
        // Toggle bulk actions panel
        function toggleBulkActions() {
            const panel = document.getElementById('bulkActionsPanel');
            panel.classList.toggle('show');
        }
        
        // Select all users checkbox functionality
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.user-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            
            updateSelectedCount();
        }
        
        // Select all users button
        function selectAllUsers() {
            const checkboxes = document.querySelectorAll('.user-checkbox');
            const selectAll = document.getElementById('selectAll');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
            selectAll.checked = true;
            
            updateSelectedCount();
        }
        
        // Deselect all users button
        function deselectAllUsers() {
            const checkboxes = document.querySelectorAll('.user-checkbox');
            const selectAll = document.getElementById('selectAll');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            selectAll.checked = false;
            
            updateSelectedCount();
        }
        
        // Update selected count
        function updateSelectedCount() {
            const checkboxes = document.querySelectorAll('.user-checkbox:checked');
            const count = checkboxes.length;
            document.getElementById('selectedCount').textContent = count;
            
            // Update select all checkbox state
            const allCheckboxes = document.querySelectorAll('.user-checkbox');
            const selectAll = document.getElementById('selectAll');
            
            if (count === 0) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            } else if (count === allCheckboxes.length) {
                selectAll.checked = true;
                selectAll.indeterminate = false;
            } else {
                selectAll.checked = false;
                selectAll.indeterminate = true;
            }
        }
        
        // Bulk delete users
        function bulkDeleteUsers() {
            const checkboxes = document.querySelectorAll('.user-checkbox:checked');
            
            if (checkboxes.length === 0) {
                alert('Please select at least one user to delete.');
                return;
            }
            
            const userCount = checkboxes.length;
            if (confirm(`Are you sure you want to delete ${userCount} user(s)? This action cannot be undone!`)) {
                // Add selected users to bulk form
                const form = document.getElementById('bulkActionForm');
                
                // Remove existing hidden inputs
                const existingInputs = form.querySelectorAll('input[name="selected_users[]"]');
                existingInputs.forEach(input => input.remove());
                
                // Add selected users as hidden inputs
                checkboxes.forEach(checkbox => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'selected_users[]';
                    input.value = checkbox.value;
                    form.appendChild(input);
                });
                
                form.submit();
            }
        }
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Initialize selected count on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateSelectedCount();
        });
    </script>
</body>
</html>