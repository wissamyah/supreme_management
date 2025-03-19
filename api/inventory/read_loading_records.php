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

    // Modified query to avoid cardinality issues
    $query = "SELECT 
        lr.id,
        lr.customer_id,
        lr.loading_date,
        lr.plate_number,
        lr.waybill_number,
        lr.driver_name,
        lr.driver_phone,
        lr.status,
        lr.created_at,
        c.name as customer_name,
        c.company_name,
        GROUP_CONCAT(
            DISTINCT
            CONCAT_WS(':', 
                li.order_item_id,
                oi.product_name,
                li.quantity,
                oi.quantity,
                oi.loading_status,
                oi.id,
                (
                    SELECT COALESCE(SUM(li2.quantity), 0)
                    FROM loading_items li2
                    WHERE li2.order_item_id = oi.id
                    AND li2.loading_id != lr.id
                )
            )
            ORDER BY li.order_item_id
        ) as items
    FROM loading_records lr
    JOIN customers c ON lr.customer_id = c.id
    LEFT JOIN loading_items li ON lr.id = li.loading_id
    LEFT JOIN order_items oi ON li.order_item_id = oi.id";

    if (isset($_GET['id'])) {
        $query .= " WHERE lr.id = :id GROUP BY lr.id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_GET['id']);
    } else {
        $whereConditions = [];
        $params = [];

        if (isset($_GET['customer_id'])) {
            $whereConditions[] = "lr.customer_id = :customer_id";
            $params[':customer_id'] = $_GET['customer_id'];
        }

        if (isset($_GET['start_date'])) {
            $whereConditions[] = "lr.loading_date >= :start_date";
            $params[':start_date'] = $_GET['start_date'];
        }

        if (isset($_GET['end_date'])) {
            $whereConditions[] = "lr.loading_date <= :end_date";
            $params[':end_date'] = $_GET['end_date'];
        }

        if (isset($_GET['status'])) {
            $whereConditions[] = "lr.status = :status";
            $params[':status'] = $_GET['status'];
        }

        if (!empty($whereConditions)) {
            $query .= " WHERE " . implode(" AND ", $whereConditions);
        }

        $query .= " GROUP BY lr.id, lr.customer_id, lr.loading_date, lr.plate_number, 
                            lr.waybill_number, lr.driver_name, lr.driver_phone, 
                            lr.status, lr.created_at, c.name, c.company_name 
                   ORDER BY lr.loading_date DESC, lr.id DESC";
        
        $stmt = $db->prepare($query);
        
        foreach ($params as $key => &$value) {
            $stmt->bindParam($key, $value);
        }
    }

    $stmt->execute();
    $result = isset($_GET['id']) ? $stmt->fetch(PDO::FETCH_ASSOC) : $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Rest of the formatting code remains the same
    $formatRecord = function(&$record) {
        if ($record['items']) {
            $record['items_array'] = array_map(function($item) {
                list($order_item_id, $product_name, $loaded_qty, $total_qty, $status, $oi_id, $other_loaded) = explode(':', $item);
                return [
                    'order_item_id' => intval($order_item_id),
                    'product_name' => $product_name,
                    'loaded_quantity' => floatval($loaded_qty),
                    'total_quantity' => floatval($total_qty),
                    'loading_status' => $status,
                    'id' => intval($oi_id),
                    'other_loaded_quantity' => floatval($other_loaded)
                ];
            }, explode('||', $record['items']));
        } else {
            $record['items_array'] = [];
        }
        unset($record['items']);

        $record['customer_display_name'] = $record['company_name'] ? 
            $record['customer_name'] . ' (' . $record['company_name'] . ')' : 
            $record['customer_name'];

        $record['formatted_date'] = date('F j, Y', strtotime($record['loading_date']));

        $record['id'] = intval($record['id']);
        $record['customer_id'] = intval($record['customer_id']);
    };

    if (isset($_GET['id'])) {
        if ($result) {
            $formatRecord($result);
        }
    } else {
        array_walk($result, $formatRecord);
    }

    echo json_encode($result);

} catch (Exception $e) {
    error_log('Error in read_loading_records.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}