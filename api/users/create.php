<?php
// api/users/create.php
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

$data = $_POST;

try {
    // Validate input
    if (empty($data['username']) || empty($data['password']) || empty($data['email']) || 
        empty($data['full_name']) || empty($data['role'])) {
        throw new Exception('All fields are required');
    }

    // Hash password
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

    // Insert user
    $query = "INSERT INTO users (username, password, email, full_name, role) 
              VALUES (:username, :password, :email, :full_name, :role)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $data['username']);
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->bindParam(':email', $data['email']);
    $stmt->bindParam(':full_name', $data['full_name']);
    $stmt->bindParam(':role', $data['role']);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User created successfully']);
    } else {
        throw new Exception('Failed to create user');
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