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
        throw new Exception('Supplier ID is required');
    }

    // Start building the update query
    $updateFields = [];
    $params = [':id' => $data['id']];

    if (!empty($data['name'])) {
        $updateFields[] = "name = :name";
        $params[':name'] = $data['name'];
    }

    if (isset($data['phone'])) {
        $updateFields[] = "phone = :phone";
        $params[':phone'] = $data['phone'];
    }

    if (isset($data['bank_details'])) {
        $updateFields[] = "bank_details = :bank_details";
        $params[':bank_details'] = $data['bank_details'];
    }

    if (isset($data['reference_person'])) {
        $updateFields[] = "reference_person = :reference_person";
        $params[':reference_person'] = $data['reference_person'];
    }

    if (isset($data['balance'])) {
        $updateFields[] = "balance = :balance";
        $params[':balance'] = $data['balance'];
    }

    if (empty($updateFields)) {
        throw new Exception('No fields to update');
    }

    $query = "UPDATE suppliers SET " . implode(', ', $updateFields) . " WHERE id = :id";
    $stmt = $db->prepare($query);

    if ($stmt->execute($params)) {
        echo json_encode(['success' => true, 'message' => 'Supplier updated successfully']);
    } else {
        throw new Exception('Failed to update supplier');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}