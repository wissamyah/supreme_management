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
        // Get specific customer
        $query = "SELECT id, name, company_name, phone, state, balance FROM customers WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_GET['id']);
        $stmt->execute();
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($customer) {
            $customer['balance'] = floatval($customer['balance']);  // Convert to number first
            echo json_encode($customer);
        } else {
            echo json_encode(['success' => false, 'message' => 'Customer not found']);
        }
    } else {
        // For all customers
        $query = "SELECT id, name, company_name, phone, state, balance FROM customers ORDER BY name ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format all balances to Naira
        array_walk($customers, function(&$customer) {
            $customer['balance'] = floatval($customer['balance']);  // Convert to number first
        });
        
        echo json_encode($customers);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}