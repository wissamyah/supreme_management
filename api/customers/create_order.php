<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
session_start();

if (!isset($_SESSION['user_id'])) {
   http_response_code(403);
   echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
   exit();
}

require_once '../../config/db.php';

try {
   $data = json_decode(file_get_contents('php://input'), true);
   
   if (empty($data['customer_id']) || empty($data['order_date']) || empty($data['items'])) {
       throw new Exception('Missing required fields');
   }

   $database = new Database();
   $db = $database->getConnection();
   
   $db->beginTransaction();
   
   // Calculate total amount
   $totalAmount = 0;
   foreach ($data['items'] as $item) {
       $totalAmount += $item['quantity'] * $item['price'];
   }
   
   // Create order
   $orderQuery = "INSERT INTO orders (customer_id, order_date, total_amount) 
                  VALUES (:customer_id, :order_date, :total_amount)";
   
   $orderStmt = $db->prepare($orderQuery);
   $orderStmt->bindParam(':customer_id', $data['customer_id'], PDO::PARAM_INT);
   $orderStmt->bindParam(':order_date', $data['order_date']);
   $orderStmt->bindParam(':total_amount', $totalAmount);
   
   if (!$orderStmt->execute()) {
       throw new Exception('Failed to create order');
   }

   $orderId = $db->lastInsertId();

   // Insert order items
   $itemQuery = "INSERT INTO order_items 
                 (order_id, product_id, product_name, quantity, price, subtotal, loaded_quantity, loading_status) 
                 VALUES 
                 (:order_id, :product_id, (SELECT name FROM products WHERE id = :product_id2), 
                  :quantity, :price, :subtotal, 0, 'Pending')";
   
   $itemStmt = $db->prepare($itemQuery);

   foreach ($data['items'] as $item) {
       $subtotal = $item['quantity'] * $item['price'];
       $productId = $item['product_id'];
       
       $itemStmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
       $itemStmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
       $itemStmt->bindParam(':product_id2', $productId, PDO::PARAM_INT);
       $itemStmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
       $itemStmt->bindParam(':price', $item['price']);
       $itemStmt->bindParam(':subtotal', $subtotal);
       
       if (!$itemStmt->execute()) {
           throw new Exception('Failed to create order item');
       }

       // Update booked stock
       $updateStock = "UPDATE products 
                      SET booked_stock = booked_stock + :quantity
                      WHERE id = :product_id";
       $updateStmt = $db->prepare($updateStock);
       $updateStmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
       $updateStmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
       
       if (!$updateStmt->execute()) {
           throw new Exception('Failed to update product stock');
       }
   }

   // Update customer balance
   $updateBalance = "UPDATE customers 
                    SET balance = balance + :amount
                    WHERE id = :customer_id";
   
   $balanceStmt = $db->prepare($updateBalance);
   $balanceStmt->bindParam(':amount', $totalAmount);
   $balanceStmt->bindParam(':customer_id', $data['customer_id'], PDO::PARAM_INT);
   
   if (!$balanceStmt->execute()) {
       throw new Exception('Failed to update customer balance');
   }

   // Get current running balance
   $balanceQuery = "SELECT running_balance 
                    FROM customer_transactions 
                    WHERE customer_id = :customer_id 
                    ORDER BY date DESC, id DESC 
                    LIMIT 1";
   $balanceStmt = $db->prepare($balanceQuery);
   $balanceStmt->bindParam(':customer_id', $data['customer_id']);
   $balanceStmt->execute();
   $currentRunningBalance = $balanceStmt->fetchColumn();

   // Calculate new running balance
   $newRunningBalance = ($currentRunningBalance !== false ? floatval($currentRunningBalance) : 0) + $totalAmount;

   // Check if transaction already exists
   $checkQuery = "SELECT id FROM customer_transactions 
                 WHERE reference_id = :order_id 
                 AND type = 'Order' 
                 AND customer_id = :customer_id";

   $checkStmt = $db->prepare($checkQuery);
   $checkStmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
   $checkStmt->bindParam(':customer_id', $data['customer_id'], PDO::PARAM_INT);
   $checkStmt->execute();

   if (!$checkStmt->fetch()) {
       // Create transaction record with running balance
       $transactionQuery = "INSERT INTO customer_transactions 
                         (customer_id, date, type, description, amount, reference_id, deletable, running_balance) 
                         VALUES 
                         (:customer_id, :date, 'Order', :description, :amount, :order_id, 0, :running_balance)";

       $description = 'Order #' . $orderId;
       
       $transactionStmt = $db->prepare($transactionQuery);
       $transactionStmt->bindParam(':customer_id', $data['customer_id'], PDO::PARAM_INT);
       $transactionStmt->bindParam(':date', $data['order_date']);
       $transactionStmt->bindParam(':description', $description);
       $transactionStmt->bindParam(':amount', $totalAmount);
       $transactionStmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
       $transactionStmt->bindParam(':running_balance', $newRunningBalance);

       if (!$transactionStmt->execute()) {
           throw new Exception('Failed to record transaction');
       }
   }

   $db->commit();
   echo json_encode(['success' => true, 'message' => 'Order created successfully']);

} catch (Exception $e) {
   if (isset($db)) {
       $db->rollBack();
   }
   http_response_code(500);
   echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}