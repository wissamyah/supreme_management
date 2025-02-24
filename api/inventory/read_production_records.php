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

    if (isset($_GET['id'])) {
        // Get specific production record
        $query = "SELECT pr.*, 
                         p.name as product_name,
                         p.category
                  FROM production_records pr
                  JOIN products p ON pr.product_id = p.id
                  WHERE pr.id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_GET['id']);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$result) {
            throw new Exception('Production record not found');
        }
        
        // Format numerical values
        $result['quantity'] = floatval($result['quantity']);
        
    } else {
        // Get all production records with product details
        $query = "SELECT pr.*, 
                         p.name as product_name,
                         p.category
                  FROM production_records pr
                  JOIN products p ON pr.product_id = p.id
                  ORDER BY pr.production_date DESC, pr.id DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format numerical values
        array_walk($result, function(&$record) {
            $record['quantity'] = floatval($record['quantity']);
        });
    }

    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}