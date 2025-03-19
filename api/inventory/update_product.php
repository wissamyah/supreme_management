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

    $formData = $_POST;
    
    if (empty($formData['id'])) {
        throw new Exception('Product ID is required');
    }

    $db->beginTransaction();

    // Get current product data
    $query = "SELECT * FROM products WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $formData['id']);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new Exception('Product not found');
    }

    // Build update query
    $updateFields = [];
    $params = [':id' => $formData['id']];

    if (!empty($formData['name']) && $formData['name'] !== $product['name']) {
        // Check if new name already exists
        $checkQuery = "SELECT id FROM products WHERE name = :name AND id != :check_id";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':name', $formData['name']);
        $checkStmt->bindParam(':check_id', $formData['id']);
        $checkStmt->execute();

        if ($checkStmt->fetch()) {
            throw new Exception('A product with this name already exists');
        }

        $updateFields[] = "name = :name";
        $params[':name'] = $formData['name'];
    }

    if (!empty($formData['category']) && $formData['category'] !== $product['category']) {
        if (!in_array($formData['category'], ['Head Rice', 'By-product'])) {
            throw new Exception('Invalid category');
        }
        $updateFields[] = "category = :category";
        $params[':category'] = $formData['category'];
    }

    // Handle stock adjustment if provided
    if (isset($formData['stock_adjustment']) && $formData['stock_adjustment'] != '') {
        $adjustment = floatval($formData['stock_adjustment']);
        if ($adjustment != 0) {
            $adjustmentType = $formData['adjustment_type'] ?? 'add';
            
            if ($adjustmentType === 'subtract' && $adjustment > $product['physical_stock']) {
                throw new Exception('Cannot subtract more than current physical stock');
            }

            $adjustmentAmount = $adjustmentType === 'add' ? $adjustment : -$adjustment;
            
            // Update physical stock
            $updateFields[] = "physical_stock = physical_stock + :adjustment";
            $params[':adjustment'] = $adjustmentAmount;

            // Record stock adjustment in production records if adding stock
            if ($adjustmentAmount > 0) {
                $prodQuery = "INSERT INTO production_records (product_id, quantity, production_date) 
                             VALUES (:prod_id, :prod_quantity, CURRENT_DATE)";
                $prodStmt = $db->prepare($prodQuery);
                $prodStmt->bindParam(':prod_id', $formData['id']);
                $prodStmt->bindParam(':prod_quantity', $adjustmentAmount);
                
                if (!$prodStmt->execute()) {
                    throw new Exception('Failed to record stock adjustment');
                }
            }
        }
    }

    if (empty($updateFields)) {
        throw new Exception('No changes to update');
    }

    $updateQuery = "UPDATE products SET " . implode(', ', $updateFields) . " WHERE id = :id";
    $updateStmt = $db->prepare($updateQuery);
    
    if (!$updateStmt->execute($params)) {
        throw new Exception('Failed to update product');
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Product updated successfully']);

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}