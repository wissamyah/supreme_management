<?php
// api/customers/read_customer_orders.php

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

    $customerId = isset($_GET['customer_id']) ? $_GET['customer_id'] : null;
    
    if (!$customerId) {
        throw new Exception('Customer ID is required');
    }

    // Modified query to include all orders, regardless of loading status
    $query = "SELECT o.id, o.order_date, o.total_amount,
                     oi.id as item_id,
                     oi.product_id,
                     oi.product_name as product,
                     oi.quantity,
                     COALESCE(oi.loaded_quantity, 0) as loaded_quantity,
                     oi.loading_status,
                     oi.price
              FROM orders o
              JOIN order_items oi ON o.id = oi.order_id
              WHERE o.customer_id = :customer_id
              ORDER BY o.order_date DESC, o.id DESC";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':customer_id', $customerId);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group by order
    $orders = [];
    foreach ($results as $row) {
        $orderId = $row['id'];
        if (!isset($orders[$orderId])) {
            $orders[$orderId] = [
                'id' => $orderId,
                'order_date' => $row['order_date'],
                'total_amount' => floatval($row['total_amount']),
                'items_array' => []
            ];
        }

        $orders[$orderId]['items_array'][] = [
            'id' => intval($row['item_id']),
            'product_id' => intval($row['product_id']),
            'product' => $row['product'],
            'quantity' => floatval($row['quantity']),
            'loaded_quantity' => floatval($row['loaded_quantity']),
            'loading_status' => $row['loading_status'] ?: 'Pending',
            'price' => floatval($row['price'])
        ];
    }

    echo json_encode(array_values($orders));

} catch (Exception $e) {
    error_log('Error in read_customer_orders.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}