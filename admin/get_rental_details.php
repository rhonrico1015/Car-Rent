<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is admin
if (!is_admin()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['error' => 'Invalid rental ID']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT r.*, u.name as user_name, u.email as user_email, 
               c.make, c.model, c.year, c.color, c.license_plate
        FROM rentals r 
        LEFT JOIN users u ON r.user_id = u.id 
        LEFT JOIN cars c ON r.car_id = c.id 
        WHERE r.id = ?
    ");
    $stmt->execute([$id]);
    $rental = $stmt->fetch();
    
    if ($rental) {
        echo json_encode($rental);
    } else {
        echo json_encode(['error' => 'Rental not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error']);
}
?>
