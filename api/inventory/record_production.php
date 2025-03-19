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

    // Validate input
    if (empty($_POST['production_date']) || 
        empty($_POST['product_id']) || 
        empty($_POST['quantity'])) {
        throw new Exception('Missing required fields');
    }

    $productIds = $_POST['product_id'];
    $quantities = $_POST['quantity'];
    $productionDate = $_POST['production_date'];

    // Validate date
    if (strtotime($productionDate) > strtotime('today')) {
        throw new Exception('Production date cannot be in the future');
    }

    // Begin transaction
    $db->beginTransaction();

    // Prepare statements
    $productionQuery = "INSERT INTO production_records (product_id, quantity, production_date) 
                        VALUES (:product_id, :quantity, :production_date)";
    $productionStmt = $db->prepare($productionQuery);

    $updateStockQuery = "UPDATE products 
                        SET physical_stock = physical_stock + :quantity 
                        WHERE id = :product_id";
    $updateStockStmt = $db->prepare($updateStockQuery);

    // Loop through each product and quantity
    for ($i = 0; $i < count($productIds); $i++) {
        $productId = $productIds[$i];
        $quantity = floatval($quantities[$i]);

        // Validate quantity
        if ($quantity <= 0) {
            throw new Exception('Invalid quantity for product ID: ' . $productId);
        }

        // Record production
        $productionStmt->bindParam(':product_id', $productId);
        $productionStmt->bindParam(':quantity', $quantity);
        $productionStmt->bindParam(':production_date', $productionDate);
        
        if (!$productionStmt->execute()) {
            throw new Exception('Failed to record production for product ID: ' . $productId);
        }

        // Update product stock
        $updateStockStmt->bindParam(':quantity', $quantity);
        $updateStockStmt->bindParam(':product_id', $productId);
        
        if (!$updateStockStmt->execute()) {
            throw new Exception('Failed to update stock for product ID: ' . $productId);
        }
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Production recorded successfully']);

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}