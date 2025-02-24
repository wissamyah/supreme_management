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

    $query = "SELECT p.*, 
                     COALESCE(pr.today_production, 0) as today_production,
                     COALESCE(pr.total_production, 0) as total_production
              FROM products p
              LEFT JOIN (
                  SELECT 
                      product_id,
                      SUM(CASE WHEN production_date = CURRENT_DATE THEN quantity ELSE 0 END) as today_production,
                      SUM(quantity) as total_production
                  FROM production_records
                  GROUP BY product_id
              ) pr ON p.id = pr.product_id";

    if (isset($_GET['id'])) {
        $query .= " WHERE p.id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_GET['id']);
    } else {
        $query .= " ORDER BY p.name ASC";
        $stmt = $db->prepare($query);
    }

    $stmt->execute();

    if (isset($_GET['id'])) {
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$result) {
            throw new Exception('Product not found');
        }

        // Format numerical values
        $result['physical_stock'] = floatval($result['physical_stock']);
        $result['booked_stock'] = floatval($result['booked_stock']);
        $result['today_production'] = floatval($result['today_production']);
        $result['total_production'] = floatval($result['total_production']);
    } else {
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format numerical values for all products
        array_walk($result, function(&$product) {
            $product['physical_stock'] = floatval($product['physical_stock']);
            $product['booked_stock'] = floatval($product['booked_stock']);
            $product['today_production'] = floatval($product['today_production']);
            $product['total_production'] = floatval($product['total_production']);
        });
    }

    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}