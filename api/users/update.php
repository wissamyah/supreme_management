<?php
// api/users/update.php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Moderator'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once '../../config/db.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents('php://input'), true);

try {
    // Validate input
    if (empty($data['id'])) {
        throw new Exception('User ID is required');
    }

    // Start building the update query
    $updateFields = [];
    $params = [':id' => $data['id']];

    // Handle status update
    if (isset($data['status'])) {
        $updateFields[] = "status = :status";
        $params[':status'] = $data['status'];
    } else {
        // Handle other field updates
        if (!empty($data['username'])) {
            $updateFields[] = "username = :username";
            $params[':username'] = $data['username'];
        }
        
        if (!empty($data['email'])) {
            $updateFields[] = "email = :email";
            $params[':email'] = $data['email'];
        }
        
        if (!empty($data['full_name'])) {
            $updateFields[] = "full_name = :full_name";
            $params[':full_name'] = $data['full_name'];
        }
        
        if (!empty($data['role'])) {
            $updateFields[] = "role = :role";
            $params[':role'] = $data['role'];
        }
        
        if (!empty($data['password'])) {
            $updateFields[] = "password = :password";
            $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
    }

    if (empty($updateFields)) {
        throw new Exception('No fields to update');
    }

    $query = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :id";
    $stmt = $db->prepare($query);

    if ($stmt->execute($params)) {
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } else {
        throw new Exception('Failed to update user');
    }
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}