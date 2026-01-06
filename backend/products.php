<?php
/**
 * Product Management Backend
 * Handles product CRUD operations with image upload
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
        $stmt = $conn->prepare("
            SELECT p.*, c.name as category_name 
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.id = :id
        ");
        $stmt->bindParam(':id', $_GET['id'], PDO::PARAM_INT);
        $stmt->execute();
        $product = $stmt->fetch();
        
        if (!$product) {
            throw new Exception('Product not found');
        }
        
        echo json_encode([
            'success' => true,
            'product' => $product
        ]);
    } else if (isset($_GET['category_id'])) {
        // Get products by category
        $categoryId = $_GET['category_id'];
        
        if ($role === 'super') {
            $stmt = $conn->prepare("
                SELECT p.*, c.name as category_name 
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.category_id = :category_id
                ORDER BY p.name ASC
            ");
            $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
            $stmt->execute();
            $products = $stmt->fetchAll();
        } else {
            // Admin sees only assigned products
            $stmt = $conn->prepare("
                SELECT DISTINCT p.*, c.name as category_name 
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                INNER JOIN user_permissions up ON p.id = up.product_id
                WHERE up.user_id = :user_id AND p.category_id = :category_id
                ORDER BY p.name ASC
            ");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
            $stmt->execute();
            $products = $stmt->fetchAll();
        }
        
        echo json_encode([
            'success' => true,
            'products' => $products
        ]);
    } else {
        // Get all products
        if ($role === 'super') {
            $stmt = $conn->query("
                SELECT p.*, c.name as category_name 
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                ORDER BY p.name ASC
            ");
            $products = $stmt->fetchAll();
        } else {
            $stmt = $conn->prepare("
                SELECT DISTINCT p.*, c.name as category_name 
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                INNER JOIN user_permissions up ON p.id = up.product_id
                WHERE up.user_id = :user_id
                ORDER BY p.name ASC
            ");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $products = $stmt->fetchAll();
        }
        
        echo json_encode([
            'success' => true,
            'products' => $products
        ]);
    }
}

function handlePost($conn) {
    // Handle multipart form data for file upload
    if (!isset($_POST['name']) || !isset($_POST['category_id']) || !isset($_POST['sku']) || !isset($_POST['price'])) {
        throw new Exception('Name, category, SKU, and price are required');
    }
    
    $name = trim($_POST['name']);
    $categoryId = intval($_POST['category_id']);
    $sku = trim($_POST['sku']);
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $price = floatval($_POST['price']);
    $image = null;
    
    if (empty($name) || empty($sku) || $price <= 0) {
        throw new Exception('Invalid input data');
    }
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            throw new Exception('Invalid file type. Only JPG, PNG, and GIF allowed');
        }
        
        $fileName = uniqid('product_') . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
            $image = 'uploads/' . $fileName;
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO products (name, category_id, image, sku, description, price) VALUES (:name, :category_id, :image, :sku, :description, :price)");
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
    $stmt->bindParam(':image', $image);
    $stmt->bindParam(':sku', $sku);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':price', $price);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Product created successfully',
            'product_id' => $conn->lastInsertId()
        ]);
    } else {
        throw new Exception('Failed to create product');
    }
}

function handlePut($conn) {
    // Handle multipart form data for file upload
    if (!isset($_POST['id'])) {
        throw new Exception('Product ID is required');
    }
    
    $id = intval($_POST['id']);
    $updates = [];
    $params = [':id' => $id];
    
    if (isset($_POST['name']) && !empty(trim($_POST['name']))) {
        $updates[] = "name = :name";
        $params[':name'] = trim($_POST['name']);
    }
    
    if (isset($_POST['category_id'])) {
        $updates[] = "category_id = :category_id";
        $params[':category_id'] = intval($_POST['category_id']);
    }
    
    if (isset($_POST['sku']) && !empty(trim($_POST['sku']))) {
        $updates[] = "sku = :sku";
        $params[':sku'] = trim($_POST['sku']);
    }
    
    if (isset($_POST['description'])) {
        $updates[] = "description = :description";
        $params[':description'] = trim($_POST['description']);
    }
    
    if (isset($_POST['price'])) {
        $updates[] = "price = :price";
        $params[':price'] = floatval($_POST['price']);
    }
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            throw new Exception('Invalid file type');
        }
        
        $fileName = uniqid('product_') . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
            $updates[] = "image = :image";
            $params[':image'] = 'uploads/' . $fileName;
        }
    }
    
    if (empty($updates)) {
        throw new Exception('No fields to update');
    }
    
    $sql = "UPDATE products SET " . implode(', ', $updates) . " WHERE id = :id";
    $stmt = $conn->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Product updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update product');
    }
}

function handleDelete($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id'])) {
        throw new Exception('Product ID is required');
    }
    
    $id = $input['id'];
    
    // Get image path before deleting
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $product = $stmt->fetch();
    
    $stmt = $conn->prepare("DELETE FROM products WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        // Delete image file if exists
        if ($product && $product['image'] && file_exists('../' . $product['image'])) {
            unlink('../' . $product['image']);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete product');
    }
}
?>