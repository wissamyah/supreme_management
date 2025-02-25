<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once '../../config/db.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if (isset($_GET['id'])) {
        // Get specific supplier
        $query = "SELECT id, name, phone, bank_details, reference_person, balance FROM suppliers WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_GET['id']);
        $stmt->execute();
        $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($supplier) {
            $supplier['balance'] = floatval($supplier['balance']);  // Convert to number
            echo json_encode($supplier);
        } else {
            echo json_encode(['success' => false, 'message' => 'Supplier not found']);
        }
    } else {
        // For all suppliers
        $query = "SELECT id, name, phone, bank_details, reference_person, balance FROM suppliers ORDER BY name ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format all balances to Naira
        array_walk($suppliers, function(&$supplier) {
            $supplier['balance'] = floatval($supplier['balance']);  // Convert to number
        });
        
        echo json_encode($suppliers);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}