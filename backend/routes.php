<?php
// backend/routes.php
require_once __DIR__ . '/app/Controllers/UserController.php';
require_once __DIR__ . '/app/Controllers/MessageController.php';

$route = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Adicionar cabeçalhos CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tratar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Configuração de rotas
if (strpos($route, '/api/register') !== false && $method === 'POST') {
    $controller = new App\Controllers\UserController();
    $controller->register();
} 
else if (strpos($route, '/api/login') !== false && $method === 'POST') {
    $controller = new App\Controllers\UserController();
    $controller->login();
} 
else if (strpos($route, '/api/logout') !== false && $method === 'POST') {
    $controller = new App\Controllers\UserController();
    $controller->logout();
}
else if (strpos($route, '/api/users') !== false && $method === 'GET') {
    $controller = new App\Controllers\UserController();
    $controller->getUsers();
}
else if (strpos($route, '/api/messages') !== false && $method === 'GET') {
    $controller = new App\Controllers\MessageController();
    $controller->getMessages();
} 
else if (strpos($route, '/api/messages') !== false && $method === 'POST') {
    $controller = new App\Controllers\MessageController();
    $controller->sendMessage();
} 
else {
    // Rota não encontrada
    http_response_code(404);
    echo json_encode(['error' => 'Rota não encontrada']);
}