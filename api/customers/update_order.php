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
    
    if (empty($data['id']) || empty($data['items'])) {
        throw new Exception('Order ID and items are required');
    }

    $database = new Database();
    $db = $database->getConnection();

    $db->beginTransaction();

    // Get original order details
    $originalQuery = "SELECT o.total_amount, oi.product_id, oi.quantity, oi.loading_status, oi.loaded_quantity 
                     FROM orders o
                     JOIN order_items oi ON o.id = oi.order_id
                     WHERE o.id = :order_id";
    $originalStmt = $db->prepare($originalQuery);
    $originalStmt->bindParam(':order_id', $data['id']);
    $originalStmt->execute();
    $originalItems = $originalStmt->fetchAll(PDO::FETCH_ASSOC);
    $originalAmount = $originalItems[0]['total_amount'];

    $originalItemsLookup = [];
    foreach ($originalItems as $item) {
        $originalItemsLookup[$item['product_id']] = [
            'quantity' => $item['quantity'],
            'loading_status' => $item['loading_status'],
            'loaded_quantity' => $item['loaded_quantity']
        ];
    }

    // Release original booked stock
    $releaseStock = "UPDATE products 
                    SET booked_stock = booked_stock - :quantity 
                    WHERE id = :product_id";
    $releaseStmt = $db->prepare($releaseStock);
    
    foreach ($originalItems as $item) {
        $releaseStmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
        $releaseStmt->bindParam(':product_id', $item['product_id'], PDO::PARAM_INT);
        if (!$releaseStmt->execute()) {
            throw new Exception('Failed to release original stock');
        }
    }

    // Calculate new total and validate stock
    $newTotal = 0;
    foreach ($data['items'] as $item) {
        $originalData = $originalItemsLookup[$item['product_id']] ?? [
            'quantity' => 0,
            'loading_status' => 'Pending',
            'loaded_quantity' => 0
        ];
        
        // Check available stock
        $stockQuery = "SELECT p.*, (p.physical_stock - p.booked_stock) as available_stock
                      FROM products p WHERE p.id = :product_id FOR UPDATE";
        $stockStmt = $db->prepare($stockQuery);
        $stockStmt->bindParam(':product_id', $item['product_id'], PDO::PARAM_INT);
        $stockStmt->execute();
        $product = $stockStmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new Exception('Product not found');
        }

        $quantityDiff = $item['quantity'] - $originalData['quantity'];
        if ($quantityDiff > 0 && $product['available_stock'] < $quantityDiff) {
            throw new Exception("Insufficient stock for product: {$product['name']}");
        }

        // Update booked stock
        $updateStock = "UPDATE products 
                       SET booked_stock = booked_stock + :quantity 
                       WHERE id = :product_id";
        $updateStmt = $db->prepare($updateStock);
        $updateStmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
        $updateStmt->bindParam(':product_id', $item['product_id'], PDO::PARAM_INT);
        
        if (!$updateStmt->execute()) {
            throw new Exception('Failed to update product stock');
        }

        $newTotal += $item['quantity'] * $item['price'];
    }

    // Update order
    $orderQuery = "UPDATE orders 
                   SET order_date = :order_date,
                       total_amount = :total_amount 
                   WHERE id = :id";
    
    $orderStmt = $db->prepare($orderQuery);
    $orderStmt->bindParam(':id', $data['id'], PDO::PARAM_INT);
    $orderStmt->bindParam(':order_date', $data['order_date']);
    $orderStmt->bindParam(':total_amount', $newTotal);
    
    if (!$orderStmt->execute()) {
        throw new Exception('Failed to update order');
    }

    // Delete existing items
    $deleteItems = "DELETE FROM order_items WHERE order_id = :order_id";
    $deleteStmt = $db->prepare($deleteItems);
    $deleteStmt->bindParam(':order_id', $data['id'], PDO::PARAM_INT);
    $deleteStmt->execute();

    // Insert new items
    $itemQuery = "INSERT INTO order_items 
                  (order_id, product_id, product_name, quantity, price, subtotal,
                   loading_status, loaded_quantity) 
                  VALUES 
                  (:order_id, :product_id, 
                   (SELECT name FROM products WHERE id = :product_id2),
                   :quantity, :price, :subtotal,
                   :loading_status, :loaded_quantity)";
    
    $itemStmt = $db->prepare($itemQuery);

    foreach ($data['items'] as $item) {
        $originalData = $originalItemsLookup[$item['product_id']] ?? [
            'loading_status' => 'Pending',
            'loaded_quantity' => 0
        ];
        
        $subtotal = $item['quantity'] * $item['price'];

        $itemStmt->bindParam(':order_id', $data['id'], PDO::PARAM_INT);
        $itemStmt->bindParam(':product_id', $item['product_id'], PDO::PARAM_INT);
        $itemStmt->bindParam(':product_id2', $item['product_id'], PDO::PARAM_INT);
        $itemStmt->bindParam(':quantity', $item['quantity']);
        $itemStmt->bindParam(':price', $item['price']);
        $itemStmt->bindParam(':subtotal', $subtotal);
        $itemStmt->bindParam(':loading_status', $originalData['loading_status']);
        $itemStmt->bindParam(':loaded_quantity', $originalData['loaded_quantity']);
        
        if (!$itemStmt->execute()) {
            throw new Exception('Failed to create order item');
        }
    }

    // Update customer balance
    $balanceDiff = $newTotal - $originalAmount;
    if ($balanceDiff != 0) {
        $updateBalance = "UPDATE customers 
                         SET balance = balance + :amount 
                         WHERE id = :customer_id";
        
        $balanceStmt = $db->prepare($updateBalance);
        $balanceStmt->bindParam(':amount', $balanceDiff);
        $balanceStmt->bindParam(':customer_id', $data['customer_id'], PDO::PARAM_INT);
        
        if (!$balanceStmt->execute()) {
            throw new Exception('Failed to update customer balance');
        }
    }

    // Update transaction record
    $updateTransaction = "UPDATE customer_transactions 
                         SET amount = :new_amount,
                             date = :order_date
                         WHERE reference_id = :order_id 
                         AND type = 'Order'";
    
    $transactionStmt = $db->prepare($updateTransaction);
    $transactionStmt->bindParam(':new_amount', $newTotal);
    $transactionStmt->bindParam(':order_date', $data['order_date']);
    $transactionStmt->bindParam(':order_id', $data['id']);
    $transactionStmt->execute();

    // Insert if not exists
    if ($transactionStmt->rowCount() === 0) {
        $insertTransaction = "INSERT INTO customer_transactions 
                            (customer_id, date, type, description, amount, reference_id, deletable)
                            VALUES 
                            (:customer_id, :date, 'Order', :description, :amount, :order_id, 0)";
        
        $description = 'Order #' . $data['id'];
        
        $insertStmt = $db->prepare($insertTransaction);
        $insertStmt->bindParam(':customer_id', $data['customer_id']);
        $insertStmt->bindParam(':date', $data['order_date']);
        $insertStmt->bindParam(':description', $description);
        $insertStmt->bindParam(':amount', $newTotal);
        $insertStmt->bindParam(':order_id', $data['id']);
        
        if (!$insertStmt->execute()) {
            throw new Exception('Failed to create transaction record');
        }
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Order updated successfully']);

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}