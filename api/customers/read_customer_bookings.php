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

    // Base query structure
    $baseQuery = "SELECT oi.id, o.order_date as date,
                     oi.product_id, oi.product_name,
                     oi.quantity as booked_quantity, 
                     COALESCE(oi.loaded_quantity, 0) as loaded_quantity,
                     oi.loading_status as status,
                     o.id as order_id
              FROM order_items oi
              JOIN orders o ON oi.order_id = o.id";

    // If order_id is provided, filter by order, otherwise filter by customer
    if (isset($_GET['order_id'])) {
        $query = $baseQuery . " WHERE o.id = :id ORDER BY oi.id ASC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_GET['order_id']);
    } elseif (isset($_GET['customer_id'])) {
        $query = $baseQuery . " WHERE o.customer_id = :id ORDER BY o.order_date DESC, o.id DESC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_GET['customer_id']);
    } else {
        throw new Exception('Either order_id or customer_id is required');
    }

    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format numerical values
    array_walk($bookings, function(&$booking) {
        $booking['booked_quantity'] = floatval($booking['booked_quantity']);
        $booking['loaded_quantity'] = floatval($booking['loaded_quantity']);
        $booking['product_id'] = intval($booking['product_id']);
        $booking['order_id'] = intval($booking['order_id']);
        $booking['remaining'] = $booking['booked_quantity'] - $booking['loaded_quantity'];
    });

    echo json_encode($bookings);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}