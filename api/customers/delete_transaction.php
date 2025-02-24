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
    
    if (!isset($data['transaction_id'])) {
        throw new Exception('Transaction ID is required');
    }

    $database = new Database();
    $db = $database->getConnection();

    $db->beginTransaction();

    // Get transaction details
    $query = "SELECT customer_id, amount, type FROM customer_transactions 
              WHERE id = :id AND deletable = TRUE";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $data['transaction_id']);
    $stmt->execute();
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        throw new Exception('Transaction not found or cannot be deleted');
    }

    // This effectively reverses the original transaction
    $reversalAmount = -$transaction['amount'];  // Negate the original amount to reverse it
    
    $updateBalance = "UPDATE customers 
                     SET balance = balance + :amount 
                     WHERE id = :customer_id";
    
    $stmt = $db->prepare($updateBalance);
    $stmt->bindParam(':amount', $reversalAmount);
    $stmt->bindParam(':customer_id', $transaction['customer_id']);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update customer balance');
    }

    // Delete the transaction
    $deleteQuery = "DELETE FROM customer_transactions WHERE id = :id";
    $stmt = $db->prepare($deleteQuery);
    $stmt->bindParam(':id', $data['transaction_id']);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to delete transaction');
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => $transaction['type'] . ' deleted successfully']);

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}