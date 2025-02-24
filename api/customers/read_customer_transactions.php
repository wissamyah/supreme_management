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
    if (!isset($_GET['customer_id'])) {
        throw new Exception('Customer ID is required');
    }

    $database = new Database();
    $db = $database->getConnection();

    // This query gets all transactions chronologically and properly calculates the running balance
    $query = "WITH all_transactions AS (
        -- Regular transactions from customer_transactions table
        SELECT 
            ct.id,
            ct.date,
            ct.type,
            ct.description,
            ct.amount,
            ct.reference_id,
            ct.deletable,
            ct.customer_id,
            ROW_NUMBER() OVER (ORDER BY ct.date ASC, ct.id ASC) as trans_order
        FROM customer_transactions ct
        WHERE ct.customer_id = :customer_id1
        
        UNION ALL
        
        -- Orders without corresponding transactions
        SELECT 
            o.id,
            o.order_date as date,
            'Order' as type,
            CONCAT('Order #', o.id) as description,
            o.total_amount as amount,
            o.id as reference_id,
            0 as deletable,
            o.customer_id,
            ROW_NUMBER() OVER (ORDER BY o.order_date ASC, o.id ASC) + 1000000 as trans_order
        FROM orders o 
        WHERE o.customer_id = :customer_id2
        AND NOT EXISTS (
            SELECT 1 FROM customer_transactions ct2 
            WHERE ct2.reference_id = o.id 
            AND ct2.type = 'Order'
        )
    ),
    
    running_calcs AS (
        SELECT 
            t.*,
            SUM(t.amount) OVER (ORDER BY t.date ASC, t.trans_order ASC ROWS UNBOUNDED PRECEDING) as running_balance
        FROM all_transactions t
    )
    
    SELECT 
        id,
        date,
        type,
        description,
        amount,
        reference_id,
        deletable,
        customer_id,
        running_balance,
        CASE WHEN type = 'Order' THEN FALSE ELSE deletable END as can_delete
    FROM running_calcs
    ORDER BY date DESC, trans_order DESC";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':customer_id1', $_GET['customer_id']);
    $stmt->bindParam(':customer_id2', $_GET['customer_id']);
    $stmt->execute();
    
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert types and format response
    array_walk($transactions, function(&$transaction) {
        $transaction['amount'] = floatval($transaction['amount']);
        $transaction['running_balance'] = floatval($transaction['running_balance']);
        $transaction['reference_id'] = $transaction['reference_id'] ? intval($transaction['reference_id']) : null;
        $transaction['can_delete'] = (bool)$transaction['can_delete'];
        $transaction['customer_id'] = intval($transaction['customer_id']);
    });

    echo json_encode($transactions);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}