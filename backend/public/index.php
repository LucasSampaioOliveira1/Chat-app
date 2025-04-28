<?php
// Substituir o require do vendor/autoload.php pelo nosso autoload personalizado
require_once __DIR__ . '/../autoload.php';

// Configuração para depuração
ini_set('display_errors', 0); // Desativa a exibição de erros para não quebrar o JSON
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php-errors.log');
error_reporting(E_ALL);

// Configuração de CORS - adicionando cabeçalhos necessários
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

// Responder imediatamente para OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Crie o diretório de logs se não existir
if (!file_exists(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0777, true);
}

try {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Adicionando log para depuração
    error_log("Requisição: $method $uri");
    
    // Roteamento básico
    switch (true) {
        // Auth routes
        case $uri === '/api/register' && $method === 'POST':
            $requestData = json_decode(file_get_contents('php://input'), true);
            error_log("Dados de registro: " . json_encode($requestData));
            
            $controller = new App\Controllers\AuthController();
            $controller->register();
            break;
        
        // Outras rotas...
        case $uri === '/api/login' && $method === 'POST':
            $controller = new App\Controllers\AuthController();
            $controller->login();
            break;
        
        case $uri === '/api/logout' && $method === 'POST':
            $controller = new App\Controllers\AuthController();
            $controller->logout();
            break;
        
        // Message routes
        case $uri === '/api/messages' && $method === 'GET':
            $controller = new App\Controllers\MessageController();
            $controller->getMessages();
            break;
        
        case $uri === '/api/messages' && $method === 'POST':
            $controller = new App\Controllers\MessageController();
            $controller->sendMessage();
            break;
        
        // Default - rota de teste
        default:
            echo json_encode(['status' => 'ok', 'message' => 'API funcionando!', 'path' => $uri]);
            break;
    }
} catch (Exception $e) {
    // Log do erro
    error_log("Erro na API: " . $e->getMessage());
    
    // Retornar erro genérico para não expor detalhes internos
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor', 'message' => $e->getMessage()]);
}
