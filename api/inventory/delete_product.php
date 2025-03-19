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
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id'])) {
        throw new Exception('Product ID is required');
    }

    $database = new Database();
    $db = $database->getConnection();
    
    $db->beginTransaction();

    // Check for existing orders using this product
    $orderQuery = "SELECT COUNT(*) FROM order_items oi 
                   JOIN orders o ON oi.order_id = o.id 
                   WHERE oi.product_name = (SELECT name FROM products WHERE id = :id)";
    $orderStmt = $db->prepare($orderQuery);
    $orderStmt->bindParam(':id', $data['id']);
    $orderStmt->execute();
    
    if ($orderStmt->fetchColumn() > 0) {
        throw new Exception('Cannot delete product with existing orders');
    }

    // Check for production records
    $prodQuery = "SELECT COUNT(*) FROM production_records WHERE product_id = :id";
    $prodStmt = $db->prepare($prodQuery);
    $prodStmt->bindParam(':id', $data['id']);
    $prodStmt->execute();
    
    if ($prodStmt->fetchColumn() > 0) {
        throw new Exception('Cannot delete product with production history');
    }

    // Check for booked stock
    $stockQuery = "SELECT booked_stock FROM products WHERE id = :id";
    $stockStmt = $db->prepare($stockQuery);
    $stockStmt->bindParam(':id', $data['id']);
    $stockStmt->execute();
    $product = $stockStmt->fetch(PDO::FETCH_ASSOC);

    if ($product && $product['booked_stock'] > 0) {
        throw new Exception('Cannot delete product with booked stock');
    }

    // Delete the product
    $deleteQuery = "DELETE FROM products WHERE id = :id";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->bindParam(':id', $data['id']);
    
    if (!$deleteStmt->execute()) {
        throw new Exception('Failed to delete product');
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}