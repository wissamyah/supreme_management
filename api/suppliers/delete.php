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

$data = json_decode(file_get_contents('php://input'), true);

try {
    if (empty($data['id'])) {
        throw new Exception('Supplier ID is required');
    }

    // Check if supplier has any transactions
    $query = "SELECT balance FROM suppliers WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $data['id']);
    $stmt->execute();
    $supplier = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($supplier['balance'] > 0) {
        throw new Exception('Cannot delete supplier with outstanding balance');
    }

    // Delete the supplier
    $query = "DELETE FROM suppliers WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $data['id']);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Supplier deleted successfully']);
    } else {
        throw new Exception('Failed to delete supplier');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}