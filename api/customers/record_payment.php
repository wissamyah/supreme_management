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
    if (empty($_POST['customer_id']) || empty($_POST['amount']) || empty($_POST['payment_date'])) {
        throw new Exception('Missing required fields');
    }

    $customerId = intval($_POST['customer_id']);
    $amount = floatval($_POST['amount']);
    $paymentDate = $_POST['payment_date'];
    $paymentMethod = $_POST['payment_method'];
    $reference = $_POST['reference'] ?? '';

    if ($amount <= 0) {
        throw new Exception('Payment amount must be greater than zero');
    }

    $db->beginTransaction();

    // Get current balance
    $balanceQuery = "SELECT balance FROM customers WHERE id = :customer_id FOR UPDATE";
    $balanceStmt = $db->prepare($balanceQuery);
    $balanceStmt->bindParam(':customer_id', $customerId);
    $balanceStmt->execute();
    $currentBalance = floatval($balanceStmt->fetchColumn());

    // Calculate new balance
    $newBalance = $currentBalance - $amount;

    // Update customer balance
    $updateBalance = "UPDATE customers 
                     SET balance = :new_balance 
                     WHERE id = :customer_id";
    
    $balanceStmt = $db->prepare($updateBalance);
    $balanceStmt->bindParam(':new_balance', $newBalance);
    $balanceStmt->bindParam(':customer_id', $customerId);
    
    if (!$balanceStmt->execute()) {
        throw new Exception('Failed to update customer balance');
    }

    // Record transaction
    $description = "Payment received via " . $paymentMethod;
    if ($reference) {
        $description .= " (Ref: " . $reference . ")";
    }

    $transactionQuery = "INSERT INTO customer_transactions 
                        (customer_id, date, type, description, amount, running_balance, deletable) 
                        VALUES 
                        (:customer_id, :date, 'Payment', :description, :amount, :running_balance, TRUE)";

    $transactionStmt = $db->prepare($transactionQuery);
    $transactionStmt->bindParam(':customer_id', $customerId);
    $transactionStmt->bindParam(':date', $paymentDate);
    $transactionStmt->bindParam(':description', $description);
    $negativeAmount = -$amount; // Payment reduces balance
    $transactionStmt->bindParam(':amount', $negativeAmount);
    $transactionStmt->bindParam(':running_balance', $newBalance);

    if (!$transactionStmt->execute()) {
        throw new Exception('Failed to record transaction');
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Payment recorded successfully']);

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}