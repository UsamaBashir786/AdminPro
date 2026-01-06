<?php
/**
 * Permissions Management Backend
 * Handles user permission assignments
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db.php';
require_once 'session_check.php';

requireSuperAdmin();

$method = $_SERVER['REQUEST_METHOD'];
$db = new Database();
$conn = $db->getConnection();

try {
    switch ($method) {
        case 'GET':
            handleGet($conn);
            break;
        
        case 'POST':
            handlePost($conn);
            break;
        
        default:
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function handleGet($conn) {
    if (!isset($_GET['user_id'])) {
        throw new Exception('User ID is required');
    }
    
    $userId = intval($_GET['user_id']);
    
    // Get user permissions
    $stmt = $conn->prepare("
        SELECT category_id, product_id, permission_type
        FROM user_permissions 
        WHERE user_id = :user_id
    ");
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $permissions = $stmt->fetchAll();
    
    $categoryIds = [];
    $productIds = [];
    
    foreach ($permissions as $perm) {
        if ($perm['category_id'] && $perm['permission_type'] === 'category') {
            $categoryIds[] = intval($perm['category_id']);
        }
        if ($perm['product_id'] && $perm['permission_type'] === 'product') {
            $productIds[] = intval($perm['product_id']);
        }
    }
    
    echo json_encode([
        'success' => true,
        'categories' => array_unique($categoryIds),
        'products' => array_unique($productIds)
    ]);
}

function handlePost($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['user_id'])) {
        throw new Exception('User ID is required');
    }
    
    $userId = intval($input['user_id']);
    $categories = isset($input['categories']) ? $input['categories'] : [];
    $products = isset($input['products']) ? $input['products'] : [];
    
    // Log for debugging
    error_log("Saving permissions for user $userId");
    error_log("Categories: " . json_encode($categories));
    error_log("Products: " . json_encode($products));
    
    // Begin transaction
    $conn->beginTransaction();
    
    try {
        // Delete existing permissions
        $stmt = $conn->prepare("DELETE FROM user_permissions WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        // First, get the category_id for each product so we can store it
        $productCategories = [];
        if (!empty($products)) {
            $productIds = implode(',', array_map('intval', $products));
            $stmt = $conn->query("SELECT id, category_id FROM products WHERE id IN ($productIds)");
            while ($row = $stmt->fetch()) {
                $productCategories[$row['id']] = $row['category_id'];
            }
        }
        
        // Insert category permissions
        foreach ($categories as $categoryId) {
            $categoryId = intval($categoryId);
            if ($categoryId > 0) {
                $stmt = $conn->prepare("
                    INSERT INTO user_permissions (user_id, category_id, product_id, permission_type) 
                    VALUES (:user_id, :category_id, NULL, 'category')
                ");
                $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
                $stmt->execute();
            }
        }
        
        // Insert product permissions with their category_id
        foreach ($products as $productId) {
            $productId = intval($productId);
            if ($productId > 0) {
                $categoryId = isset($productCategories[$productId]) ? intval($productCategories[$productId]) : null;
                
                $stmt = $conn->prepare("
                    INSERT INTO user_permissions (user_id, category_id, product_id, permission_type) 
                    VALUES (:user_id, :category_id, :product_id, 'product')
                ");
                $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                
                if ($categoryId) {
                    $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue(':category_id', null, PDO::PARAM_NULL);
                }
                
                $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
                $stmt->execute();
            }
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Permissions updated successfully',
            'debug' => [
                'categories_saved' => count($categories),
                'products_saved' => count($products)
            ]
        ]);
        
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Permission save error: " . $e->getMessage());
        throw $e;
    }
}
?>