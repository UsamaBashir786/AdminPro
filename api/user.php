<?php
/**
 * User API
 * Public endpoint for user operations
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../backend/db.php';
require_once '../backend/session_check.php';

$method = $_SERVER['REQUEST_METHOD'];
$db = new Database();
$conn = $db->getConnection();

try {
    switch ($method) {
        case 'GET':
            handleGetSession($conn);
            break;
        
        case 'POST':
            handleSignup($conn);
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

function handleGetSession($conn) {
    if (isset($_GET['action']) && $_GET['action'] === 'session') {
        $session = getUserSession();
        
        if ($session) {
            echo json_encode([
                'success' => true,
                'logged_in' => true,
                'user' => $session
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'logged_in' => false
            ]);
        }
    } else if (isset($_GET['action']) && $_GET['action'] === 'permissions') {
        requireLogin();
        
        $userId = $_SESSION['user_id'];
        $role = $_SESSION['role'];
        
        if ($role === 'super') {
            // Super admin has all permissions
            echo json_encode([
                'success' => true,
                'role' => 'super',
                'all_access' => true
            ]);
        } else {
            // Get admin permissions
            $stmt = $conn->prepare("
                SELECT category_id, product_id 
                FROM user_permissions 
                WHERE user_id = :user_id
            ");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $permissions = $stmt->fetchAll();
            
            $categoryIds = [];
            $productIds = [];
            
            foreach ($permissions as $perm) {
                if ($perm['category_id']) {
                    $categoryIds[] = $perm['category_id'];
                }
                if ($perm['product_id']) {
                    $productIds[] = $perm['product_id'];
                }
            }
            
            echo json_encode([
                'success' => true,
                'role' => 'admin',
                'all_access' => false,
                'categories' => array_unique($categoryIds),
                'products' => array_unique($productIds)
            ]);
        }
    } else {
        throw new Exception('Invalid action');
    }
}

function handleSignup($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['name']) || !isset($input['username']) || !isset($input['password']) || !isset($input['email'])) {
        throw new Exception('All fields are required');
    }
    
    $name = trim($input['name']);
    $username = trim($input['username']);
    $password = trim($input['password']);
    $email = trim($input['email']);
    
    if (empty($name) || empty($username) || empty($password) || empty($email)) {
        throw new Exception('All fields must be filled');
    }
    
    // Check if username exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    if ($stmt->fetch()) {
        throw new Exception('Username already exists');
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (name, username, password, email, role) VALUES (:name, :username, :password, :email, 'admin')");
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->bindParam(':email', $email);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Account created successfully. Please login.'
        ]);
    } else {
        throw new Exception('Failed to create account');
    }
}
?>