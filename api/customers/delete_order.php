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
        throw new Exception('Order ID is required');
    }

    $database = new Database();
    $db = $database->getConnection();
    
    $db->beginTransaction();

    // Get current order details
    $orderQuery = "SELECT o.*, oi.product_id, oi.quantity, oi.loading_status 
                  FROM orders o
                  JOIN order_items oi ON o.id = oi.order_id 
                  WHERE o.id = :id";
    $orderStmt = $db->prepare($orderQuery);
    $orderStmt->bindParam(':id', $data['id'], PDO::PARAM_INT);
    $orderStmt->execute();
    
    $orderItems = $orderStmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($orderItems)) {
        throw new Exception('Order not found');
    }

    // Check if any items are already loaded
    foreach ($orderItems as $item) {
        if ($item['loading_status'] !== 'Pending') {
            throw new Exception('Cannot delete order - some items are already loaded');
        }
    }

    $order = [
        'customer_id' => $orderItems[0]['customer_id'],
        'total_amount' => $orderItems[0]['total_amount']
    ];

    // Release booked stock
    $updateStock = "UPDATE products 
                   SET booked_stock = booked_stock - :quantity 
                   WHERE id = :product_id";
    
    $stockStmt = $db->prepare($updateStock);
    
    foreach ($orderItems as $item) {
        $stockStmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
        $stockStmt->bindParam(':product_id', $item['product_id'], PDO::PARAM_INT);
        
        if (!$stockStmt->execute()) {
            throw new Exception('Failed to update product stock');
        }
    }

    // Delete order items
    $deleteItems = "DELETE FROM order_items WHERE order_id = :order_id";
    $deleteStmt = $db->prepare($deleteItems);
    $deleteStmt->bindParam(':order_id', $data['id'], PDO::PARAM_INT);
    
    if (!$deleteStmt->execute()) {
        throw new Exception('Failed to delete order items');
    }

    // Delete transaction records
    $deleteTransaction = "DELETE FROM customer_transactions 
                         WHERE reference_id = :order_id 
                         AND type = 'Order'";
    $transactionStmt = $db->prepare($deleteTransaction);
    $transactionStmt->bindParam(':order_id', $data['id'], PDO::PARAM_INT);
    $transactionStmt->execute();

    // Delete order
    $deleteOrder = "DELETE FROM orders WHERE id = :id";
    $orderStmt = $db->prepare($deleteOrder);
    $orderStmt->bindParam(':id', $data['id'], PDO::PARAM_INT);
    
    if (!$orderStmt->execute()) {
        throw new Exception('Failed to delete order');
    }

    // Update customer balance
    $updateBalance = "UPDATE customers 
                     SET balance = balance - :amount 
                     WHERE id = :customer_id";
    
    $balanceStmt = $db->prepare($updateBalance);
    $balanceStmt->bindParam(':amount', $order['total_amount']);
    $balanceStmt->bindParam(':customer_id', $order['customer_id'], PDO::PARAM_INT);
    
    if (!$balanceStmt->execute()) {
        throw new Exception('Failed to update customer balance');
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Order deleted successfully']);

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

