<?php
// api/inventory/create_loading_record.php

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once '../../config/db.php';

try {
    // Add request logging
    $rawInput = file_get_contents('php://input');
    error_log('Create Loading Record - Raw Input: ' . $rawInput);
    
    $data = json_decode($rawInput, true);
    error_log('Decoded data: ' . json_encode($data));
    
    if (empty($data['customer_id']) || empty($data['loading_date']) || empty($data['items'])) {
        throw new Exception('Missing required fields. Data received: ' . json_encode($data));
    }

    if (empty($data['plate_number'])) {
        throw new Exception('Plate number is required');
    }

    $database = new Database();
    $db = $database->getConnection();
    
    $db->beginTransaction();

    error_log('Starting item verification...');
    
    // First verify each item exists and get its details
    foreach ($data['items'] as $index => $item) {
        error_log("Processing item index {$index}: " . json_encode($item));
        
        // Verify order item exists and belongs to the customer
        $checkQuery = "SELECT 
                          oi.id,
                          oi.quantity as total_quantity,
                          COALESCE(oi.loaded_quantity, 0) as current_loaded,
                          oi.loading_status,
                          oi.product_id,
                          p.name as product_name,
                          p.physical_stock,
                          o.customer_id,
                          o.id as order_id
                      FROM order_items oi
                      JOIN orders o ON oi.order_id = o.id
                      JOIN products p ON oi.product_id = p.id
                      WHERE oi.id = :order_item_id
                      AND o.customer_id = :customer_id";
        
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':order_item_id', $item['order_item_id'], PDO::PARAM_INT);
        $checkStmt->bindParam(':customer_id', $data['customer_id'], PDO::PARAM_INT);
        
        error_log("Checking order item: {$item['order_item_id']} for customer: {$data['customer_id']}");
        
        $checkStmt->execute();
        $itemDetails = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$itemDetails) {
            throw new Exception("Order item not found or does not belong to customer. Item ID: {$item['order_item_id']}");
        }

        error_log('Found item details: ' . json_encode($itemDetails));

        // Calculate remaining quantity that can be loaded
        $remainingToLoad = $itemDetails['total_quantity'] - $itemDetails['current_loaded'];
        
        if ($item['quantity'] > $remainingToLoad) {
            throw new Exception(sprintf(
                'Cannot load %d bags of %s. Only %d bags remaining from ordered quantity.',
                $item['quantity'],
                $itemDetails['product_name'],
                $remainingToLoad
            ));
        }

        // Verify physical stock availability
        if ($item['quantity'] > $itemDetails['physical_stock']) {
            throw new Exception(sprintf(
                'Insufficient stock of %s. Requested: %d bags, Available: %d bags',
                $itemDetails['product_name'],
                $item['quantity'],
                $itemDetails['physical_stock']
            ));
        }
    }

    // Insert loading record
    $query = "INSERT INTO loading_records 
              (customer_id, loading_date, plate_number, waybill_number, driver_name, driver_phone, status) 
              VALUES 
              (:customer_id, :loading_date, :plate_number, :waybill_number, :driver_name, :driver_phone, 'Pending')";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':customer_id', $data['customer_id'], PDO::PARAM_INT);
    $stmt->bindParam(':loading_date', $data['loading_date']);
    $stmt->bindParam(':plate_number', $data['plate_number']);
    $stmt->bindParam(':waybill_number', $data['waybill_number']);
    $stmt->bindParam(':driver_name', $data['driver_name']);
    $stmt->bindParam(':driver_phone', $data['driver_phone']);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to create loading record');
    }

    $loadingId = $db->lastInsertId();

    // Insert loading items
    $insertItem = "INSERT INTO loading_items (loading_id, order_item_id, quantity) 
                   VALUES (:loading_id, :order_item_id, :quantity)";
    $itemStmt = $db->prepare($insertItem);

    foreach ($data['items'] as $item) {
        $itemStmt->bindParam(':loading_id', $loadingId, PDO::PARAM_INT);
        $itemStmt->bindParam(':order_item_id', $item['order_item_id'], PDO::PARAM_INT);
        $itemStmt->bindParam(':quantity', $item['quantity']);
        
        if (!$itemStmt->execute()) {
            throw new Exception('Failed to create loading item');
        }
    }

    // Update order items status
    $updateOrderItems = "UPDATE order_items oi
                        JOIN loading_items li ON oi.id = li.order_item_id
                        SET 
                            oi.loaded_quantity = COALESCE(
                                (SELECT SUM(quantity) 
                                 FROM loading_items 
                                 WHERE order_item_id = oi.id),
                                0
                            ),
                            oi.loading_status = CASE 
                                WHEN COALESCE(
                                    (SELECT SUM(quantity) 
                                     FROM loading_items 
                                     WHERE order_item_id = oi.id),
                                    0
                                ) >= oi.quantity THEN 'Fully Loaded'
                                ELSE 'Partially Loaded'
                            END
                        WHERE li.loading_id = :loading_id";

    $updateStmt = $db->prepare($updateOrderItems);
    $updateStmt->bindParam(':loading_id', $loadingId);
    
    if (!$updateStmt->execute()) {
        throw new Exception('Failed to update order items status');
    }

    $db->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Loading record created successfully',
        'id' => $loadingId
    ]);

} catch (Exception $e) {
    error_log('Error in create_loading_record.php: ' . $e->getMessage());
    if (isset($db)) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}