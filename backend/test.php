<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "Database connection successful!";
    
    // Test query
    $stmt = $conn->query("SELECT * FROM users LIMIT 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>