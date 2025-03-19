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
    // Validate input
    if (empty($data['id'])) {
        throw new Exception('Customer ID is required');
    }

    // Start building the update query
    $updateFields = [];
    $params = [':id' => $data['id']];

    if (!empty($data['name'])) {
        $updateFields[] = "name = :name";
        $params[':name'] = $data['name'];
    }

    if (isset($data['company_name'])) {
        $updateFields[] = "company_name = :company_name";
        $params[':company_name'] = $data['company_name'];
    }

    if (!empty($data['phone'])) {
        if (!preg_match('/^(\+234|0)[0-9]{10}$/', $data['phone'])) {
            throw new Exception('Invalid Nigerian phone number format');
        }
        $updateFields[] = "phone = :phone";
        $params[':phone'] = $data['phone'];
    }

    if (!empty($data['state'])) {
        $updateFields[] = "state = :state";
        $params[':state'] = $data['state'];
    }

    if (isset($data['balance'])) {
        $updateFields[] = "balance = :balance";
        $params[':balance'] = $data['balance'];
    }

    if (empty($updateFields)) {
        throw new Exception('No fields to update');
    }

    $query = "UPDATE customers SET " . implode(', ', $updateFields) . " WHERE id = :id";
    $stmt = $db->prepare($query);

    if ($stmt->execute($params)) {
        echo json_encode(['success' => true, 'message' => 'Customer updated successfully']);
    } else {
        throw new Exception('Failed to update customer');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}