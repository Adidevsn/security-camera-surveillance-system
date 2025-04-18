<?php
session_start();

// Set proper CORS headers
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
header("Access-Control-Allow-Origin: $origin");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

require_once '../config/database.php';
require_once '../includes/auth.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check authentication status
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['check'])) {
    echo json_encode([
        'success' => true,
        'authenticated' => isLoggedIn()
    ]);
    exit();
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents("php://input"));
        
        if (!isset($data->username) || !isset($data->password)) {
            throw new Exception("Username and password are required");
        }

        $username = trim($data->username);
        $password = trim($data->password);
        $remember = isset($data->remember) ? (bool)$data->remember : false;

        if (empty($username) || empty($password)) {
            throw new Exception("Username and password cannot be empty");
        }

        $database = new Database();
        $db = $database->getConnection();

        $query = "SELECT id, username, role FROM users WHERE username = :username AND password = :password";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $password); // In real app, use password_hash() and password_verify()
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Set session cookie lifetime if remember me is checked
            if ($remember) {
                ini_set('session.cookie_lifetime', 30 * 24 * 60 * 60); // 30 days
                ini_set('session.gc_maxlifetime', 30 * 24 * 60 * 60);
            }

            loginUser($row['id'], $row['username'], $row['role']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $row['id'],
                    'username' => $row['username'],
                    'role' => $row['role']
                ]
            ]);
        } else {
            throw new Exception("Invalid username or password");
        }
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} 
// Handle logout
elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['logout'])) {
    logoutUser();
    echo json_encode([
        'success' => true,
        'message' => 'Logged out successfully'
    ]);
} 
else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}
?>