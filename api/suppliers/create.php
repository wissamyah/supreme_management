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
    if (empty($data['name'])) {
        throw new Exception('Name is required');
    }

    // Insert supplier
    $query = "INSERT INTO suppliers (name, phone, bank_details, reference_person) 
              VALUES (:name, :phone, :bank_details, :reference_person)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':name', $data['name']);
    $stmt->bindParam(':phone', $data['phone']);
    $stmt->bindParam(':bank_details', $data['bank_details']);
    $stmt->bindParam(':reference_person', $data['reference_person']);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Supplier created successfully']);
    } else {
        throw new Exception('Failed to create supplier');
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}