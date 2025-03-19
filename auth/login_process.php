<?php
header('Content-Type: application/json');
session_start();

require_once '../config/db.php';

$database = new Database();
$db = $database->getConnection();

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

try {
    $query = "SELECT id, username, password, role, status FROM users WHERE username = :username";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] === 'Blocked') {
            echo json_encode(['success' => false, 'message' => 'Your account has been blocked']);
            exit();
        }
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}