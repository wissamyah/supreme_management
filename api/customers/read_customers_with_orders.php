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

    // Add debugging
    error_log('Starting read_customers_with_orders.php');

    // Get customers who have orders with unloaded items
    $query = "SELECT DISTINCT 
                c.id, 
                c.name, 
                c.company_name,
                GROUP_CONCAT(
                    CONCAT(p.name, ':', 
                           oi.quantity - COALESCE(oi.loaded_quantity, 0), 
                           ' bags') 
                    SEPARATOR '||') as pending_items
              FROM customers c
              JOIN orders o ON c.id = o.customer_id
              JOIN order_items oi ON o.id = oi.order_id
              JOIN products p ON oi.product_id = p.id
              WHERE (oi.loading_status = 'Pending' 
                    OR oi.loading_status = 'Partially Loaded'
                    OR oi.loading_status IS NULL)
                AND oi.quantity > COALESCE(oi.loaded_quantity, 0)
              GROUP BY c.id, c.name, c.company_name
              ORDER BY c.name ASC";

    error_log('Query: ' . $query);
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process the results to format the pending items
    foreach ($customers as &$customer) {
        if (isset($customer['pending_items'])) {
            $items = explode('||', $customer['pending_items']);
            $customer['pending_items'] = array_map(function($item) {
                list($name, $quantity) = explode(':', $item);
                return [
                    'name' => $name,
                    'quantity' => (int)$quantity
                ];
            }, $items);
        } else {
            $customer['pending_items'] = [];
        }
    }

    error_log('Found customers: ' . json_encode($customers));
    echo json_encode($customers);

} catch (Exception $e) {
    error_log('Error in read_customers_with_orders.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}