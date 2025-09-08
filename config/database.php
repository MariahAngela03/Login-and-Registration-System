<?php
/**
 * Database Configuration
 * Secure PDO connection setup with error handling
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'user_management';
    private $username = 'root';
    private $password = '';  // Leave empty for XAMPP default

    /**
     * Get database connection using PDO
     * @return PDO|null
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch(PDOException $e) {
            error_log("Connection Error: " . $e->getMessage());
            return null;
        }
        
        return $this->conn;
    }
}