<?php
// api/users/read.php
header('Content-Type: application/json');
session_start();

// Debug: Check session
error_log("Session data: " . print_r($_SESSION, true));

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Moderator'])) {
    http_response_code(403);
    error_log("Unauthorized access attempt. Session user_id: " . ($_SESSION['user_id'] ?? 'not set') . ", role: " . ($_SESSION['role'] ?? 'not set'));
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once '../../config/db.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("Database connection failed");
    }

    if (isset($_GET['id'])) {
        // Get specific user
        $query = "SELECT id, username, email, full_name, role, status FROM users WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_GET['id']);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo json_encode($user);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
    } else {
        // Get all users
        $query = "SELECT id, username, email, full_name, role, status FROM users ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug: Log the query results
        error_log("Users query result: " . print_r($users, true));
        
        if (empty($users)) {
            echo json_encode(['success' => false, 'message' => 'No users found']);
        } else {
            echo json_encode($users);
        }
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}