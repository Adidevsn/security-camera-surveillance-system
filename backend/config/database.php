<?php
class Database {
    // Update these values with your actual database credentials
    private $host = "localhost";          // Database server (usually 'localhost')
    private $db_name = "security_camera_system";  // Database name you created
    private $username = "root";           // Database username
    private $password = "";               // Database password
    
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            return $this->conn;
        } catch(PDOException $e) {
            error_log("Connection error: " . $e->getMessage());
            throw new Exception("Database connection failed. Please check your configuration.");
        }
    }
}
?>