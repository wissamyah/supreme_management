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

    // Parse filters
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    $productId = $_GET['product_id'] ?? null;
    $category = $_GET['category'] ?? null;

    // Build base query with parameters
    $params = [];
    $whereConditions = ["pr.production_date BETWEEN :start_date AND :end_date"];
    $params[':start_date'] = $startDate;
    $params[':end_date'] = $endDate;

    if ($productId) {
        $whereConditions[] = "pr.product_id = :product_id";
        $params[':product_id'] = $productId;
    }
    if ($category) {
        $whereConditions[] = "p.category = :category";
        $params[':category'] = $category;
    }

    $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

    // Get summary statistics
    $summaryQuery = "SELECT 
                     SUM(pr.quantity) as total,
                     SUM(CASE WHEN p.category = 'Head Rice' THEN pr.quantity ELSE 0 END) as head_rice,
                     SUM(CASE WHEN p.category = 'By-product' THEN pr.quantity ELSE 0 END) as by_product
                     FROM production_records pr
                     JOIN products p ON pr.product_id = p.id 
                     $whereClause";

    $summaryStmt = $db->prepare($summaryQuery);
    $summaryStmt->execute($params);
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

    // Get trend data
    $trendQuery = "SELECT 
                   pr.production_date,
                   SUM(pr.quantity) as daily_total
                   FROM production_records pr
                   JOIN products p ON pr.product_id = p.id 
                   $whereClause
                   GROUP BY pr.production_date
                   ORDER BY pr.production_date";

    $trendStmt = $db->prepare($trendQuery);
    $trendStmt->execute($params);
    $trendData = $trendStmt->fetchAll(PDO::FETCH_ASSOC);

    // Format trend data for chart
    $trend = [
        'dates' => array_column($trendData, 'production_date'),
        'quantities' => array_map('floatval', array_column($trendData, 'daily_total'))
    ];

    // Get detailed production records
    $detailsQuery = "SELECT 
                     pr.production_date,
                     p.name as product_name,
                     p.category,
                     pr.quantity
                     FROM production_records pr
                     JOIN products p ON pr.product_id = p.id 
                     $whereClause
                     ORDER BY pr.production_date DESC, p.name";

    $detailsStmt = $db->prepare($detailsQuery);
    $detailsStmt->execute($params);
    $details = $detailsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Format numerical values
    foreach ($summary as &$value) {
        $value = floatval($value);
    }

    foreach ($details as &$record) {
        $record['quantity'] = floatval($record['quantity']);
    }

    echo json_encode([
        'success' => true,
        'summary' => $summary,
        'trend' => $trend,
        'details' => $details
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}