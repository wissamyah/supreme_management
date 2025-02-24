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
        throw new Exception('Customer ID is required');
    }

    // Check if customer has any related records (orders/payments)
    $query = "SELECT COUNT(*) as count FROM orders WHERE customer_id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $data['id']);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] > 0) {
        throw new Exception('Cannot delete customer with existing orders');
    }

    $query = "DELETE FROM customers WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $data['id']);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Customer deleted successfully']);
    } else {
        throw new Exception('Failed to delete customer');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}