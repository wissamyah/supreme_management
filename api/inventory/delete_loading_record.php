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
    $data = json_decode(file_get_contents('php://input'), true);
    error_log('Delete Loading Request: ' . json_encode($data));
    
    if (empty($data['id'])) {
        throw new Exception('Loading record ID is required');
    }

    $database = new Database();
    $db = $database->getConnection();
    
    $db->beginTransaction();

    // First get all items that need to be processed
    $getItemsQuery = "SELECT 
        li.id as loading_item_id,
        li.order_item_id,
        li.quantity as loaded_quantity,
        oi.quantity as order_quantity,
        oi.product_id,
        COALESCE((
            SELECT SUM(li2.quantity) 
            FROM loading_items li2 
            WHERE li2.order_item_id = oi.id 
            AND li2.loading_id != :loading_id1
        ), 0) as other_loaded_quantity
    FROM loading_items li
    JOIN order_items oi ON li.order_item_id = oi.id
    WHERE li.loading_id = :loading_id2";

    $getItemsStmt = $db->prepare($getItemsQuery);
    $getItemsStmt->bindParam(':loading_id1', $data['id']);
    $getItemsStmt->bindParam(':loading_id2', $data['id']);
    $getItemsStmt->execute();
    $items = $getItemsStmt->fetchAll(PDO::FETCH_ASSOC);

    error_log('Items to process: ' . json_encode($items));

    foreach ($items as $item) {
        // Update order item status only (stock update is handled by trigger)
        $newLoadedQuantity = $item['other_loaded_quantity'];
        $newStatus = $newLoadedQuantity <= 0 ? 'Pending' : 
                    ($newLoadedQuantity >= $item['order_quantity'] ? 'Fully Loaded' : 'Partially Loaded');

        $updateOrderQuery = "UPDATE order_items 
            SET loaded_quantity = :loaded_quantity,
                loading_status = :status
            WHERE id = :order_item_id";

        $updateOrderStmt = $db->prepare($updateOrderQuery);
        $updateOrderStmt->bindParam(':loaded_quantity', $newLoadedQuantity);
        $updateOrderStmt->bindParam(':status', $newStatus);
        $updateOrderStmt->bindParam(':order_item_id', $item['order_item_id']);
        
        if (!$updateOrderStmt->execute()) {
            throw new Exception('Failed to update order item status');
        }
    }

    // Delete all loading items (trigger will handle stock updates)
    $deleteItemsQuery = "DELETE FROM loading_items WHERE loading_id = :loading_id";
    $deleteItemsStmt = $db->prepare($deleteItemsQuery);
    $deleteItemsStmt->bindParam(':loading_id', $data['id']);
    
    if (!$deleteItemsStmt->execute()) {
        throw new Exception('Failed to delete loading items');
    }

    // Delete the loading record
    $deleteRecordQuery = "DELETE FROM loading_records WHERE id = :id";
    $deleteRecordStmt = $db->prepare($deleteRecordQuery);
    $deleteRecordStmt->bindParam(':id', $data['id']);
    
    if (!$deleteRecordStmt->execute()) {
        throw new Exception('Failed to delete loading record');
    }

    $db->commit();
    error_log('Successfully deleted loading record ' . $data['id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Loading record deleted successfully'
    ]);

} catch (Exception $e) {
    error_log('Error in delete_loading_record.php: ' . $e->getMessage());
    if (isset($db)) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}