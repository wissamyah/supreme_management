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
   
   // First, get the column definition to understand its constraints
   $columnDefQuery = "SELECT NUMERIC_PRECISION, NUMERIC_SCALE 
                     FROM INFORMATION_SCHEMA.COLUMNS 
                     WHERE TABLE_SCHEMA = DATABASE() 
                     AND TABLE_NAME = 'order_items' 
                     AND COLUMN_NAME = 'subtotal'";
   $columnDefStmt = $db->prepare($columnDefQuery);
   $columnDefStmt->execute();
   $columnDef = $columnDefStmt->fetch(PDO::FETCH_ASSOC);
   
   $precision = isset($columnDef['NUMERIC_PRECISION']) ? (int)$columnDef['NUMERIC_PRECISION'] : 10;
   $scale = isset($columnDef['NUMERIC_SCALE']) ? (int)$columnDef['NUMERIC_SCALE'] : 2;
   
   // Calculate max value allowed for this decimal field
   $maxIntegerDigits = $precision - $scale;
   $maxValue = pow(10, $maxIntegerDigits) - pow(10, -$scale);
   
   $db->beginTransaction();
   
   // Calculate total amount with validation
   $totalAmount = 0;
   foreach ($data['items'] as $index => $item) {
       $quantity = (int)$item['quantity'];
       $price = (float)$item['price'];
       $itemSubtotal = $quantity * $price;
       
       // Validate subtotal against column constraints
       if ($itemSubtotal > $maxValue) {
           throw new Exception("Subtotal value for item #" . ($index + 1) . " exceeds database limits ($maxValue). Please reduce quantity or price.");
       }
       
       $totalAmount += $itemSubtotal;
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
       $productId = (int)$item['product_id'];
       $quantity = (int)$item['quantity'];
       $price = (float)$item['price'];
       
       // Calculate subtotal with precision control
       $subtotal = $quantity * $price;
       // Ensure we don't exceed the scale
       $subtotal = round($subtotal, $scale);
       
       $itemStmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
       $itemStmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
       $itemStmt->bindParam(':product_id2', $productId, PDO::PARAM_INT);
       $itemStmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
       $itemStmt->bindParam(':price', $price);
       $itemStmt->bindParam(':subtotal', $subtotal);
       
       if (!$itemStmt->execute()) {
           throw new Exception('Failed to create order item: ' . json_encode($itemStmt->errorInfo()));
       }

       // Update booked stock
       $updateStock = "UPDATE products 
                      SET booked_stock = booked_stock + :quantity
                      WHERE id = :product_id";
       $updateStmt = $db->prepare($updateStock);
       $updateStmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
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