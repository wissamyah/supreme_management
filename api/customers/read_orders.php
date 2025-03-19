<?php
// api/customers/read_orders.php
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

    $query = "SELECT o.id, o.order_date, o.total_amount, o.customer_id,
                     c.name as customer_name, c.company_name,
                     GROUP_CONCAT(
                         CONCAT(
                             oi.product_name, ':', 
                             oi.product_id, ':',
                             oi.quantity, '@₦', 
                             oi.price, ':', 
                             COALESCE(oi.loading_status, 'Pending'), ':', 
                             COALESCE(oi.loaded_quantity, 0)
                         ) SEPARATOR '||'
                     ) as items
              FROM orders o
              JOIN customers c ON o.customer_id = c.id
              JOIN order_items oi ON o.id = oi.order_id
              GROUP BY o.id, o.order_date, o.total_amount, o.customer_id, 
                       c.name, c.company_name
              ORDER BY o.order_date DESC";

    $stmt = $db->prepare($query);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    array_walk($orders, function(&$order) {
        $order['total_amount'] = floatval($order['total_amount']);
        $order['display_name'] = $order['company_name'] ?: $order['customer_name'];
        $order['items_array'] = [];
        
        if ($order['items']) {
            $order['items_array'] = array_map(function($item) {
                list($product, $product_id, $details, $status, $loaded) = explode(':', $item);
                list($qty, $price) = explode('@₦', $details);
                return [
                    'product' => $product,
                    'product_id' => intval($product_id),
                    'quantity' => intval($qty),
                    'price' => floatval($price),
                    'loading_status' => $status,
                    'loaded_quantity' => floatval($loaded)
                ];
            }, explode('||', $order['items']));
        }
        
        unset($order['items']);
    });

    echo json_encode($orders);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}