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
    if (!isset($_GET['id'])) {
        throw new Exception('Order ID is required');
    }

    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT o.*, c.name as customer_name,
                     oi.id as item_id, oi.product_id, oi.product_name, 
                     oi.quantity, oi.price, oi.loading_status,
                     oi.loaded_quantity, p.physical_stock,
                     (p.physical_stock - p.booked_stock + oi.quantity) as available_stock
              FROM orders o
              JOIN customers c ON o.customer_id = c.id
              JOIN order_items oi ON o.id = oi.order_id
              JOIN products p ON oi.product_id = p.id
              WHERE o.id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $_GET['id']);
    $stmt->execute();
    
    $order = null;
    $items = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!$order) {
            $order = [
                'id' => $row['id'],
                'customer_id' => $row['customer_id'],
                'customer_name' => $row['customer_name'],
                'order_date' => $row['order_date'],
                'total_amount' => floatval($row['total_amount']),
                'items' => []
            ];
        }

        $items[] = [
            'id' => $row['item_id'],
            'product_id' => $row['product_id'],
            'product_name' => $row['product_name'],
            'quantity' => floatval($row['quantity']),
            'price' => floatval($row['price']),
            'loading_status' => $row['loading_status'],
            'loaded_quantity' => floatval($row['loaded_quantity']),
            'available_stock' => floatval($row['available_stock']),
            'subtotal' => floatval($row['quantity'] * $row['price'])
        ];
    }

    if (!$order) {
        throw new Exception('Order not found');
    }

    $order['items'] = $items;
    echo json_encode($order);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}