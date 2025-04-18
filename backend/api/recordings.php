<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/database.php';
require_once '../includes/auth.php';

requireAuth();

$database = new Database();
$db = $database->getConnection();

// Get recordings with filters
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $cameraId = $_GET['camera_id'] ?? null;
    $date = $_GET['date'] ?? null;
    $eventType = $_GET['event_type'] ?? null;
    $limit = $_GET['limit'] ?? 20;

    $query = "SELECT r.*, c.name as camera_name 
              FROM recordings r
              JOIN cameras c ON r.camera_id = c.id
              WHERE c.user_id = :user_id";
    
    $params = [':user_id' => $_SESSION['user_id']];

    if ($cameraId) {
        $query .= " AND r.camera_id = :camera_id";
        $params[':camera_id'] = $cameraId;
    }

    if ($date) {
        $query .= " AND DATE(r.start_time) = :date";
        $params[':date'] = $date;
    }

    if ($eventType) {
        $query .= " AND r.event_type = :event_type";
        $params[':event_type'] = $eventType;
    }

    $query .= " ORDER BY r.start_time DESC LIMIT :limit";

    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();

    $recordings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $recordings[] = [
            'id' => $row['id'],
            'camera_id' => $row['camera_id'],
            'camera_name' => $row['camera_name'],
            'file_path' => $row['file_path'],
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time'],
            'duration' => $row['duration'],
            'event_type' => $row['event_type'],
            'thumbnail_path' => $row['thumbnail_path']
        ];
    }

    echo json_encode([
        'success' => true,
        'recordings' => $recordings
    ]);
}

// Upload new recording (from camera)
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['video']) || !isset($_POST['camera_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing video file or camera ID'
        ]);
        exit;
    }

    $cameraId = $_POST['camera_id'];
    $eventType = $_POST['event_type'] ?? 'motion';
    $startTime = $_POST['start_time'] ?? date('Y-m-d H:i:s');
    $endTime = $_POST['end_time'] ?? date('Y-m-d H:i:s');
    $duration = strtotime($endTime) - strtotime($startTime);

    // Verify camera belongs to user
    $stmt = $db->prepare("SELECT id FROM cameras WHERE id = :id AND user_id = :user_id");
    $stmt->bindParam(':id', $cameraId);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Camera not found or access denied'
        ]);
        exit;
    }

    // Create uploads directory if not exists
    $uploadDir = '../uploads/recordings/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Generate unique filename
    $filename = uniqid('recording_') . '.mp4';
    $filepath = $uploadDir . $filename;

    // Move uploaded file
    if (move_uploaded_file($_FILES['video']['tmp_name'], $filepath)) {
        // Generate thumbnail (simplified - in real app use FFMPEG)
        $thumbnailFilename = uniqid('thumbnail_') . '.jpg';
        $thumbnailPath = $uploadDir . $thumbnailFilename;
        
        // This would be replaced with actual thumbnail generation code
        file_put_contents($thumbnailPath, file_get_contents($_FILES['video']['tmp_name']));

        // Save to database
        $query = "INSERT INTO recordings 
                  (camera_id, file_path, start_time, end_time, duration, event_type, thumbnail_path) 
                  VALUES 
                  (:camera_id, :file_path, :start_time, :end_time, :duration, :event_type, :thumbnail_path)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':camera_id', $cameraId);
        $stmt->bindParam(':file_path', $filename);
        $stmt->bindParam(':start_time', $startTime);
        $stmt->bindParam(':end_time', $endTime);
        $stmt->bindParam(':duration', $duration);
        $stmt->bindParam(':event_type', $eventType);
        $stmt->bindParam(':thumbnail_path', $thumbnailFilename);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Recording saved successfully',
                'recording_id' => $db->lastInsertId(),
                'file_path' => $filename,
                'thumbnail_path' => $thumbnailFilename
            ]);
        } else {
            unlink($filepath); // Clean up if DB insert failed
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to save recording to database'
            ]);
        }
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to upload video file'
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