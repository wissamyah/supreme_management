<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
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
    if (empty($data['name']) || empty($data['phone']) || empty($data['state'])) {
        throw new Exception('Name, phone and state are required');
    }

    // Validate phone number (Nigerian format)
    if (!preg_match('/^(\+234|0)[0-9]{10}$/', $data['phone'])) {
        throw new Exception('Invalid Nigerian phone number format');
    }

    // Insert customer
    $query = "INSERT INTO customers (name, company_name, phone, state, balance) 
              VALUES (:name, :company_name, :phone, :state, 0.00)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':name', $data['name']);
    $stmt->bindParam(':company_name', $data['company_name']);
    $stmt->bindParam(':phone', $data['phone']);
    $stmt->bindParam(':state', $data['state']);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Customer created successfully']);
    } else {
        throw new Exception('Failed to create customer');
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}