<?php
/**
 * Product API
 * Public endpoint for product data
 */

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
    $isLoggedIn = isLoggedIn();
    
    if (!isset($_GET['category_id'])) {
        throw new Exception('Category ID is required');
    }
    
    $categoryId = intval($_GET['category_id']);
    
    if ($isLoggedIn && $_SESSION['role'] === 'admin') {
        // Admin sees only assigned products
        $userId = $_SESSION['user_id'];
        $stmt = $conn->prepare("
            SELECT DISTINCT p.id, p.name, p.category_id, p.image, p.sku, p.description, p.price 
            FROM products p
            INNER JOIN user_permissions up ON p.id = up.product_id
            WHERE up.user_id = :user_id AND p.category_id = :category_id
            ORDER BY p.name ASC
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll();
    } else {
        // Public and super admin see all products
        $stmt = $conn->prepare("
            SELECT id, name, category_id, image, sku, description, price 
            FROM products 
            WHERE category_id = :category_id
            ORDER BY name ASC
        ");
        $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll();
    }
    
    // Hide price if not logged in
    if (!$isLoggedIn) {
        foreach ($products as &$product) {
            unset($product['price']);
        }
    }
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'show_price' => $isLoggedIn
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>