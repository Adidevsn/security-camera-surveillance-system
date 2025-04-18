<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/database.php';
require_once '../includes/auth.php';

requireAuth();

$database = new Database();
$db = $database->getConnection();

// Get all cameras
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $query = "SELECT * FROM cameras WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();

    $cameras = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $cameras[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'ip_address' => $row['ip_address'],
            'location' => $row['location'],
            'status' => $row['status'],
            'last_active' => $row['last_active']
        ];
    }

    echo json_encode([
        'success' => true,
        'cameras' => $cameras
    ]);
}

// Add new camera
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    $query = "INSERT INTO cameras (user_id, name, ip_address, location, status) 
              VALUES (:user_id, :name, :ip_address, :location, 'active')";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->bindParam(':name', $data->name);
    $stmt->bindParam(':ip_address', $data->ip_address);
    $stmt->bindParam(':location', $data->location);

    if ($stmt->execute()) {
        $cameraId = $db->lastInsertId();
        echo json_encode([
            'success' => true,
            'message' => 'Camera added successfully',
            'camera_id' => $cameraId
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to add camera'
        ]);
    }
}

// Update camera
elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents("php://input"));

    $query = "UPDATE cameras SET 
              name = :name, 
              ip_address = :ip_address, 
              location = :location, 
              status = :status 
              WHERE id = :id AND user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':name', $data->name);
    $stmt->bindParam(':ip_address', $data->ip_address);
    $stmt->bindParam(':location', $data->location);
    $stmt->bindParam(':status', $data->status);
    $stmt->bindParam(':id', $data->id);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Camera updated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update camera'
        ]);
    }
}

// Delete camera
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"));

    $query = "DELETE FROM cameras WHERE id = :id AND user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $data->id);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Camera deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete camera'
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