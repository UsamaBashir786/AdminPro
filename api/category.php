<?php
//Category API
//Public endpoint for category data
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../backend/db.php';
require_once '../backend/session_check.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

try {
    // Check if user is logged in to filter categories
    $isLoggedIn = isLoggedIn();
    
    if ($isLoggedIn && $_SESSION['role'] === 'admin') {
        // Admin sees only assigned categories
        $userId = $_SESSION['user_id'];
        $stmt = $conn->prepare("
            SELECT DISTINCT c.* 
            FROM categories c
            INNER JOIN user_permissions up ON c.id = up.category_id
            WHERE up.user_id = :user_id
            ORDER BY c.id ASC, c.name ASC
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $categories = $stmt->fetchAll();
    } else {
        // Public and super admin see all categories
        $stmt = $conn->query("SELECT * FROM categories ORDER BY name ASC");
        $categories = $stmt->fetchAll();
    }
    
    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>