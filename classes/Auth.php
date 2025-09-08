<?php
require_once 'config/database.php';

/**
 * Authentication Class
 * Handles user registration, login, session management, and security
 */
class Auth {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        
        // Start session if not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set secure session parameters
        $this->setSecureSessionParams();
    }
    
    /**
     * Set secure session parameters
     */
    private function setSecureSessionParams() {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
        ini_set('session.cookie_lifetime', 3600); // 1 hour
    }
    
    /**
     * Register new user
     * @param string $username
     * @param string $email
     * @param string $password
     * @param string $full_name
     * @return array
     */
    public function register($username, $email, $password, $full_name) {
        try {
            // Validate input
            if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
                return ['success' => false, 'message' => 'All fields are required.'];
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid email format.'];
            }
            
            if (strlen($password) < 6) {
                return ['success' => false, 'message' => 'Password must be at least 6 characters long.'];
            }
            
            // Check if user already exists
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Username or email already exists.'];
            }
            
            // Hash password securely
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $this->conn->prepare(
                "INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)"
            );
            
            if ($stmt->execute([$username, $email, $hashed_password, $full_name])) {
                return ['success' => true, 'message' => 'Registration successful! You can now login.'];
            } else {
                return ['success' => false, 'message' => 'Registration failed. Please try again.'];
            }
            
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred. Please try again.'];
        }
    }
    
    /**
     * Login user
     * @param string $username
     * @param string $password
     * @return array
     */
    public function login($username, $password) {
        try {
            if (empty($username) || empty($password)) {
                return ['success' => false, 'message' => 'Username and password are required.'];
            }
            
            // Get user from database
            $stmt = $this->conn->prepare(
                "SELECT id, username, email, password, full_name, role FROM users WHERE username = ? OR email = ?"
            );
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();
                
                // Generate CSRF token
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                
                return ['success' => true, 'message' => 'Login successful!'];
            } else {
                return ['success' => false, 'message' => 'Invalid username or password.'];
            }
            
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred. Please try again.'];
        }
    }
    
    /**
     * Check if user is logged in and session is valid
     * @return bool
     */
    public function isLoggedIn() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Check session timeout (1 hour)
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
            $this->logout();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    /**
     * Logout user
     */
    public function logout() {
        session_unset();
        session_destroy();
    }
    
    /**
     * Get current user information
     * @return array|false
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'full_name' => $_SESSION['full_name'],
            'role' => $_SESSION['role']
        ];
    }
    
    /**
     * Generate and validate CSRF token
     */
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Sanitize output to prevent XSS
     * @param string $string
     * @return string
     */
    public static function sanitizeOutput($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}