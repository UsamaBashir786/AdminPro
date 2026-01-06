<?php
/**
 * Category Management Backend
 * Handles category CRUD operations
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db.php';
require_once 'session_check.php';

requireLogin();

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
        
        case 'PUT':
            handlePut($conn);
            break;
        
        case 'DELETE':
            handleDelete($conn);
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
    $userId = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    if (isset($_GET['id'])) {
        $stmt = $conn->prepare("SELECT * FROM categories WHERE id = :id");
        $stmt->bindParam(':id', $_GET['id'], PDO::PARAM_INT);
        $stmt->execute();
        $category = $stmt->fetch();
        
        if (!$category) {
            throw new Exception('Category not found');
        }
        
        echo json_encode([
            'success' => true,
            'category' => $category
        ]);
    } else {
        // Super admin sees all categories
        if ($role === 'super') {
            $stmt = $conn->query("SELECT * FROM categories ORDER BY name ASC");
            $categories = $stmt->fetchAll();
        } else {
            // Admin sees only assigned categories
            $stmt = $conn->prepare("
                SELECT DISTINCT c.* 
                FROM categories c
                INNER JOIN user_permissions up ON c.id = up.category_id
                WHERE up.user_id = :user_id
                ORDER BY c.name ASC
            ");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $categories = $stmt->fetchAll();
        }
        
        echo json_encode([
            'success' => true,
            'categories' => $categories
        ]);
    }
}

function handlePost($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['name'])) {
        throw new Exception('Category name is required');
    }
    
    $name = trim($input['name']);
    $description = isset($input['description']) ? trim($input['description']) : '';
    
    if (empty($name)) {
        throw new Exception('Category name cannot be empty');
    }
    
    $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (:name, :description)");
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':description', $description);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Category created successfully',
            'category_id' => $conn->lastInsertId()
        ]);
    } else {
        throw new Exception('Failed to create category');
    }
}

function handlePut($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id'])) {
        throw new Exception('Category ID is required');
    }
    
    $id = $input['id'];
    $updates = [];
    $params = [':id' => $id];
    
    if (isset($input['name']) && !empty(trim($input['name']))) {
        $updates[] = "name = :name";
        $params[':name'] = trim($input['name']);
    }
    
    if (isset($input['description'])) {
        $updates[] = "description = :description";
        $params[':description'] = trim($input['description']);
    }
    
    if (empty($updates)) {
        throw new Exception('No fields to update');
    }
    
    $sql = "UPDATE categories SET " . implode(', ', $updates) . " WHERE id = :id";
    $stmt = $conn->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Category updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update category');
    }
}

function handleDelete($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id'])) {
        throw new Exception('Category ID is required');
    }
    
    $id = $input['id'];
    
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete category');
    }
}
?>