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

    // Base query structure with customer information
    // Added WHERE clause to exclude fully loaded orders
    $query = "SELECT oi.id, o.order_date as date,
                     oi.product_id, oi.product_name,
                     oi.quantity as booked_quantity, 
                     COALESCE(oi.loaded_quantity, 0) as loaded_quantity,
                     oi.loading_status as status,
                     o.id as order_id,
                     c.name as customer_name,
                     c.company_name
              FROM order_items oi
              JOIN orders o ON oi.order_id = o.id
              JOIN customers c ON o.customer_id = c.id
              WHERE oi.loading_status != 'Fully Loaded'
              ORDER BY o.order_date DESC, o.id DESC";

    $stmt = $db->prepare($query);
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format numerical values
    array_walk($bookings, function(&$booking) {
        $booking['booked_quantity'] = floatval($booking['booked_quantity']);
        $booking['loaded_quantity'] = floatval($booking['loaded_quantity']);
        $booking['product_id'] = intval($booking['product_id']);
        $booking['order_id'] = intval($booking['order_id']);
        $booking['remaining'] = $booking['booked_quantity'] - $booking['loaded_quantity'];
        
        // Format customer name
        $booking['customer_display_name'] = $booking['company_name'] ? 
            $booking['customer_name'] . ' (' . $booking['company_name'] . ')' : 
            $booking['customer_name'];
    });

    echo json_encode($bookings);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}