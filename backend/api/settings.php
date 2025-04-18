<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/database.php';
require_once '../includes/auth.php';

requireAuth();

$database = new Database();
$db = $database->getConnection();

// Get user settings
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $query = "SELECT * FROM user_settings WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode([
            'success' => true,
            'settings' => [
                'motion_sensitivity' => $row['motion_sensitivity'],
                'storage_limit' => $row['storage_limit'],
                'notifications_enabled' => (bool)$row['notifications_enabled'],
                'notification_email' => $row['notification_email'],
                'auto_delete' => (bool)$row['auto_delete'],
                'retention_days' => $row['retention_days']
            ]
        ]);
    } else {
        // Return default settings if none exist
        echo json_encode([
            'success' => true,
            'settings' => [
                'motion_sensitivity' => 50,
                'storage_limit' => 100, // GB
                'notifications_enabled' => true,
                'notification_email' => $_SESSION['username'],
                'auto_delete' => true,
                'retention_days' => 30
            ]
        ]);
    }
}

// Update settings
elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents("php://input"));

    // Check if settings exist for user
    $checkStmt = $db->prepare("SELECT id FROM user_settings WHERE user_id = :user_id");
    $checkStmt->bindParam(':user_id', $_SESSION['user_id']);
    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {
        // Update existing settings
        $query = "UPDATE user_settings SET 
                  motion_sensitivity = :motion_sensitivity,
                  storage_limit = :storage_limit,
                  notifications_enabled = :notifications_enabled,
                  notification_email = :notification_email,
                  auto_delete = :auto_delete,
                  retention_days = :retention_days
                  WHERE user_id = :user_id";
    } else {
        // Insert new settings
        $query = "INSERT INTO user_settings 
                  (user_id, motion_sensitivity, storage_limit, notifications_enabled, notification_email, auto_delete, retention_days) 
                  VALUES 
                  (:user_id, :motion_sensitivity, :storage_limit, :notifications_enabled, :notification_email, :auto_delete, :retention_days)";
    }

    $stmt = $db->prepare($query);
    $stmt->bindParam(':motion_sensitivity', $data->motion_sensitivity);
    $stmt->bindParam(':storage_limit', $data->storage_limit);
    $stmt->bindParam(':notifications_enabled', $data->notifications_enabled, PDO::PARAM_BOOL);
    $stmt->bindParam(':notification_email', $data->notification_email);
    $stmt->bindParam(':auto_delete', $data->auto_delete, PDO::PARAM_BOOL);
    $stmt->bindParam(':retention_days', $data->retention_days);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Settings updated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update settings'
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}
?>