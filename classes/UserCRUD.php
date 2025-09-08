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
     * Delete user and their profile
     * @param int $user_id
     * @return array
     */
    public function deleteUser($user_id) {
        try {
            // Don't allow deletion of admin users
            $stmt = $this->conn->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if ($user && $user['role'] === 'admin') {
                return ['success' => false, 'message' => 'Cannot delete admin users.'];
            }
            
            // Delete user (profile will be deleted automatically due to foreign key constraint)
            $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
            
            if ($stmt->execute([$user_id])) {
                return ['success' => true, 'message' => 'User deleted successfully!'];
            } else {
                return ['success' => false, 'message' => 'Failed to delete user.'];
            }
            
        } catch (PDOException $e) {
            error_log("Delete user error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while deleting user.'];
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