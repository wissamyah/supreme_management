<?php
session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['username'])) {
    $_SESSION['username'] = $data['username'];
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}