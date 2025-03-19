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
    
    // Validate input
    if (empty($_POST['name']) || empty($_POST['category']) || !isset($_POST['physical_stock'])) {
        throw new Exception('Missing required fields');
    }

    $name = trim($_POST['name']);
    $category = $_POST['category'];
    $physical_stock = floatval($_POST['physical_stock']);

    // Validate category
    if (!in_array($category, ['Head Rice', 'By-product'])) {
        throw new Exception('Invalid category');
    }

    // Validate stock quantity
    if ($physical_stock < 0) {
        throw new Exception('Physical stock cannot be negative');
    }

    // Begin transaction
    $db->beginTransaction();

    // Check if product name already exists
    $checkQuery = "SELECT id FROM products WHERE name = :name";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':name', $name);
    $checkStmt->execute();

    if ($checkStmt->fetch()) {
        throw new Exception('A product with this name already exists');
    }

    // Insert product
    $query = "INSERT INTO products (name, category, physical_stock, booked_stock) 
              VALUES (:name, :category, :physical_stock, 0)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':category', $category);
    $stmt->bindParam(':physical_stock', $physical_stock);

    if (!$stmt->execute()) {
        throw new Exception('Failed to create product');
    }

    $productId = $db->lastInsertId();

    // Record initial stock as production if stock > 0
    if ($physical_stock > 0) {
        $prodQuery = "INSERT INTO production_records (product_id, quantity, production_date) 
                      VALUES (:product_id, :quantity, CURRENT_DATE)";
        
        $prodStmt = $db->prepare($prodQuery);
        $prodStmt->bindParam(':product_id', $productId);
        $prodStmt->bindParam(':quantity', $physical_stock);

        if (!$prodStmt->execute()) {
            throw new Exception('Failed to record initial production');
        }
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Product created successfully']);

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}