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

    $query = "SELECT p.id, 
                     p.name,
                     p.category,
                     p.physical_stock,
                     p.booked_stock,
                     (p.physical_stock - p.booked_stock) as available_stock,
                     COALESCE(pr.daily_production, 0) as today_production,
                     COALESCE(pr.weekly_production, 0) as week_production,
                     COALESCE(pr.monthly_production, 0) as month_production
              FROM products p
              LEFT JOIN (
                  SELECT product_id,
                         SUM(CASE WHEN production_date = CURRENT_DATE THEN quantity ELSE 0 END) as daily_production,
                         SUM(CASE WHEN production_date >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY) THEN quantity ELSE 0 END) as weekly_production,
                         SUM(CASE WHEN production_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY) THEN quantity ELSE 0 END) as monthly_production
                  FROM production_records
                  GROUP BY product_id
              ) pr ON p.id = pr.product_id";

    // Add product-specific filter if ID is provided
    if (isset($_GET['id'])) {
        $query .= " WHERE p.id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_GET['id']);
    } else {
        $stmt = $db->prepare($query);
    }

    $stmt->execute();
    
    if (isset($_GET['id'])) {
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$result) {
            throw new Exception('Product not found');
        }
    } else {
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Format numerical values
    $formatFields = ['physical_stock', 'booked_stock', 'available_stock', 
                    'today_production', 'week_production', 'month_production'];
    
    if (isset($_GET['id'])) {
        foreach ($formatFields as $field) {
            $result[$field] = floatval($result[$field]);
        }
    } else {
        foreach ($result as &$product) {
            foreach ($formatFields as $field) {
                $product[$field] = floatval($product[$field]);
            }
        }
    }

    echo json_encode(['success' => true, 'data' => $result]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}