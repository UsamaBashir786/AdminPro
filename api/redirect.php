<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$script_name = $_SERVER['SCRIPT_NAME'] ?? '';
$current_path = dirname($script_name);
$path_parts = explode('/', trim($current_path, '/'));
array_pop($path_parts);
$base_path = '/' . implode('/', $path_parts);
$base_path = rtrim($base_path, '/');
$frontend_url = $protocol . '://' . $host . $base_path . '/frontend/index.html';

// For debugging purpose
$response = [
    'success' => true,
    'redirect_url' => $frontend_url,
    'path_info' => [
        'current_path' => $current_path,
        'base_path' => $base_path,
        'host' => $host,
        'request_uri' => $request_uri
    ],
    'message' => 'Redirect to frontend/index.html from one step back'
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>