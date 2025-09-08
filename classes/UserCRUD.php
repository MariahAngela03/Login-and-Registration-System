<?php
require_once 'config/database.php';

/**
 * User CRUD Operations Class
 * Handles Create, Read, Update, Delete operations for user profiles
 */
class UserCRUD {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Get all users with their profiles
     * @return array
     */
    public function getAllUsers() {
        try {
            $stmt = $this->conn->prepare("
                SELECT u.id, u.username, u.email, u.full_name, u.role, u.created_at,
                       p.phone, p.address, p.bio, p.profile_image
                FROM users u
                LEFT JOIN user_profiles p ON u.id = p.user_id
                ORDER BY u.created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get all users error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get user by ID with profile
     * @param int $id
     * @return array|false
     */
    public function getUserById($id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT u.id, u.username, u.email, u.full_name, u.role, u.created_at,
                       p.phone, p.address, p.bio, p.profile_image
                FROM users u
                LEFT JOIN user_profiles p ON u.id = p.user_id
                WHERE u.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get user by ID error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create new user profile
     * @param int $user_id
     * @param array $profile_data
     * @return array
     */
    public function createUserProfile($user_id, $profile_data) {
        try {
            // Check if profile already exists
            $stmt = $this->conn->prepare("SELECT id FROM user_profiles WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            if ($stmt->fetch()) {
                return $this->updateUserProfile($user_id, $profile_data);
            }
            
            // Insert new profile
            $stmt = $this->conn->prepare(
                "INSERT INTO user_profiles (user_id, phone, address, bio, profile_image) VALUES (?, ?, ?, ?, ?)"
            );
            
            if ($stmt->execute([
                $user_id,
                $profile_data['phone'] ?? null,
                $profile_data['address'] ?? null,
                $profile_data['bio'] ?? null,
                $profile_data['profile_image'] ?? null
            ])) {
                return ['success' => true, 'message' => 'Profile created successfully!'];
            } else {
                return ['success' => false, 'message' => 'Failed to create profile.'];
            }
            
        } catch (PDOException $e) {
            error_log("Create profile error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while creating profile.'];
        }
    }
    
    /**
     * Update user information and profile
     * @param int $user_id
     * @param array $user_data
     * @param array $profile_data
     * @return array
     */
    public function updateUser($user_id, $user_data, $profile_data = []) {
        try {
            $this->conn->beginTransaction();
            
            // Update user table
            if (!empty($user_data)) {
                $stmt = $this->conn->prepare(
                    "UPDATE users SET full_name = ?, email = ? WHERE id = ?"
                );
                $stmt->execute([
                    $user_data['full_name'],
                    $user_data['email'],
                    $user_id
                ]);
            }
            
            // Update or create profile
            if (!empty($profile_data)) {
                $this->updateUserProfile($user_id, $profile_data);
            }
            
            $this->conn->commit();
            return ['success' => true, 'message' => 'User updated successfully!'];
            
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Update user error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update user.'];
        }
    }
    
    /**
     * Update user profile
     * @param int $user_id
     * @param array $profile_data
     * @return array
     */
    private function updateUserProfile($user_id, $profile_data) {
        try {
            // Check if profile exists
            $stmt = $this->conn->prepare("SELECT id FROM user_profiles WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $profile_exists = $stmt->fetch();
            
            if ($profile_exists) {
                // Update existing profile
                $stmt = $this->conn->prepare(
                    "UPDATE user_profiles SET phone = ?, address = ?, bio = ?, profile_image = ? WHERE user_id = ?"
                );
                $stmt->execute([
                    $profile_data['phone'] ?? null,
                    $profile_data['address'] ?? null,
                    $profile_data['bio'] ?? null,
                    $profile_data['profile_image'] ?? null,
                    $user_id
                ]);
            } else {
                // Create new profile
                $stmt = $this->conn->prepare(
                    "INSERT INTO user_profiles (user_id, phone, address, bio, profile_image) VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    $user_id,
                    $profile_data['phone'] ?? null,
                    $profile_data['address'] ?? null,
                    $profile_data['bio'] ?? null,
                    $profile_data['profile_image'] ?? null
                ]);
            }
            
            return ['success' => true, 'message' => 'Profile updated successfully!'];
            
        } catch (PDOException $e) {
            error_log("Update profile error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update profile.'];
        }
    }
    
    /**
     * Delete user and associated profile data
     * @param int $user_id
     * @return array
     */
    public function deleteUser($user_id) {
        try {
            // Start transaction
            $this->conn->beginTransaction();
            
            // Get user info before deletion for logging
            $stmt = $this->conn->prepare("SELECT username, email, full_name, role FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user_info = $stmt->fetch();
            
            if (!$user_info) {
                $this->conn->rollback();
                return ['success' => false, 'message' => 'User not found.'];
            }
            
            // Don't allow deletion of admin users
            if ($user_info['role'] === 'admin') {
                // Check if this is the last admin
                $stmt = $this->conn->prepare("SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'");
                $stmt->execute();
                $admin_count = $stmt->fetch()['admin_count'];
                
                if ($admin_count <= 1) {
                    $this->conn->rollback();
                    return ['success' => false, 'message' => 'Cannot delete the last admin user.'];
                }
            }
            
            // Delete user profile first (if exists)
            $stmt = $this->conn->prepare("DELETE FROM user_profiles WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            // Delete user record
            $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
            $result = $stmt->execute([$user_id]);
            
            if ($result && $stmt->rowCount() > 0) {
                $this->conn->commit();
                
                // Log the deletion (optional)
                error_log("User deleted: ID={$user_id}, Username={$user_info['username']}, Email={$user_info['email']}");
                
                return [
                    'success' => true, 
                    'message' => "User '{$user_info['full_name']}' has been successfully deleted.",
                    'deleted_user' => $user_info
                ];
            } else {
                $this->conn->rollback();
                return ['success' => false, 'message' => 'Failed to delete user. User may not exist.'];
            }
            
        } catch (PDOException $e) {
            $this->conn->rollback();
            error_log("Delete user error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while deleting the user.'];
        }
    }
    
    /**
     * Check if user can be deleted (prevent deleting admin users, etc.)
     * @param int $user_id
     * @param int $current_user_id
     * @return array
     */
    public function canDeleteUser($user_id, $current_user_id) {
        try {
            // Prevent self-deletion
            if ($user_id == $current_user_id) {
                return ['can_delete' => false, 'message' => 'You cannot delete your own account.'];
            }
            
            // Get user info
            $stmt = $this->conn->prepare("SELECT role, username FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['can_delete' => false, 'message' => 'User not found.'];
            }
            
            // Check if trying to delete the last admin (optional protection)
            if ($user['role'] === 'admin') {
                $stmt = $this->conn->prepare("SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'");
                $stmt->execute();
                $admin_count = $stmt->fetch()['admin_count'];
                
                if ($admin_count <= 1) {
                    return ['can_delete' => false, 'message' => 'Cannot delete the last admin user.'];
                }
            }
            
            return ['can_delete' => true, 'message' => 'User can be deleted.'];
            
        } catch (PDOException $e) {
            error_log("Can delete user check error: " . $e->getMessage());
            return ['can_delete' => false, 'message' => 'Error checking user deletion permissions.'];
        }
    }
    
    /**
     * Search users by name, username, or email
     * @param string $search_term
     * @return array
     */
    public function searchUsers($search_term) {
        try {
            $search_term = '%' . $search_term . '%';
            $stmt = $this->conn->prepare("
                SELECT u.id, u.username, u.email, u.full_name, u.role, u.created_at,
                       p.phone, p.address, p.bio, p.profile_image
                FROM users u
                LEFT JOIN user_profiles p ON u.id = p.user_id
                WHERE u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ?
                ORDER BY u.created_at DESC
            ");
            $stmt->execute([$search_term, $search_term, $search_term]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Search users error: " . $e->getMessage());
            return [];
        }
    }
}