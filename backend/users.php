<?php
/**
 * User Management Backend
 * Handles user CRUD operations (Super admin only)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
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
    if (isset($_GET['id'])) {
        $stmt = $conn->prepare("SELECT id, name, username, email, role FROM users WHERE id = :id");
        $stmt->bindParam(':id', $_GET['id'], PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception('User not found');
        }
        
        echo json_encode([
            'success' => true,
            'user' => $user
        ]);
    } else {
        $stmt = $conn->query("SELECT id, name, username, email, role, created_at FROM users ORDER BY created_at DESC");
        $users = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'users' => $users
        ]);
    }
}

function handlePost($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['name']) || !isset($input['username']) || !isset($input['password']) || !isset($input['email'])) {
        throw new Exception('All fields are required');
    }
    
    $name = trim($input['name']);
    $username = trim($input['username']);
    $password = trim($input['password']);
    $email = trim($input['email']);
    $role = isset($input['role']) ? $input['role'] : 'admin';
    
    if (empty($name) || empty($username) || empty($password) || empty($email)) {
        throw new Exception('All fields must be filled');
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (name, username, password, email, role) VALUES (:name, :username, :password, :email, :role)");
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':role', $role);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'User created successfully',
            'user_id' => $conn->lastInsertId()
        ]);
    } else {
        throw new Exception('Failed to create user');
    }
}

function handlePut($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id'])) {
        throw new Exception('User ID is required');
    }
    
    $id = $input['id'];
    $updates = [];
    $params = [':id' => $id];
    
    if (isset($input['name']) && !empty(trim($input['name']))) {
        $updates[] = "name = :name";
        $params[':name'] = trim($input['name']);
    }
    
    if (isset($input['email']) && !empty(trim($input['email']))) {
        $updates[] = "email = :email";
        $params[':email'] = trim($input['email']);
    }
    
    if (isset($input['password']) && !empty(trim($input['password']))) {
        $updates[] = "password = :password";
        $params[':password'] = password_hash(trim($input['password']), PASSWORD_DEFAULT);
    }
    
    if (isset($input['role'])) {
        $updates[] = "role = :role";
        $params[':role'] = $input['role'];
    }
    
    if (empty($updates)) {
        throw new Exception('No fields to update');
    }
    
    $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
    $stmt = $conn->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'User updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update user');
    }
}

function handleDelete($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id'])) {
        throw new Exception('User ID is required');
    }
    
    $id = $input['id'];
    
    // Prevent deleting self
    if ($id == $_SESSION['user_id']) {
        throw new Exception('Cannot delete your own account');
    }
    
    $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete user');
    }
}
?>