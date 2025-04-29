<?php
// Substituir o require do vendor/autoload.php pelo nosso autoload personalizado
require_once __DIR__ . '/../autoload.php';

// Configuração para depuração
ini_set('display_errors', 0); // Desativa a exibição de erros para não quebrar o JSON
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php-errors.log');
error_reporting(E_ALL);

// Configuração de CORS - adicionando cabeçalhos necessários
if (isset($_SERVER['HTTP_ORIGIN']) && preg_match('/^http:\/\/localhost:\d+$/', $_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
} else {
    header("Access-Control-Allow-Origin: *");
}

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
            // Conexão com o banco de dados
            try {
                $pdo = new PDO('mysql:host=chat-app-mysql;dbname=chat', 'chatuser', 'chatpass');
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $recipient_id = $_GET['recipient_id'] ?? null;
                $sender_id = $_GET['sender_id'] ?? null;

                $sql = "SELECT * FROM messages";
                $params = [];
                
                // Se temos recipient_id e sender_id, buscamos mensagens entre eles
                if ($recipient_id && $sender_id) {
                    if ($recipient_id === 'general') {
                        // Para mensagens da sala geral
                        $sql .= " WHERE recipient_id = 'general'";
                    } else {
                        // Para mensagens privadas entre dois usuários
                        $sql .= " WHERE (sender_id = ? AND recipient_id = ?) OR (sender_id = ? AND recipient_id = ?)";
                        $params = [$sender_id, $recipient_id, $recipient_id, $sender_id];
                    }
                }

                $sql .= " ORDER BY created_at ASC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                header('Content-Type: application/json');
                echo json_encode($messages);
            } catch (PDOException $e) {
                error_log("Erro no banco de dados: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao buscar mensagens', 'details' => $e->getMessage()]);
            }
            exit;
        
        case $uri === '/api/messages' && $method === 'POST':
            try {
                $pdo = new PDO('mysql:host=chat-app-mysql;dbname=chat', 'chatuser', 'chatpass');
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $data = json_decode(file_get_contents('php://input'), true);
                $content = $data['content'] ?? null;
                $sender_id = $data['sender_id'] ?? null;
                $sender_name = $data['sender_name'] ?? null;
                $recipient_id = $data['recipient_id'] ?? 'general';

                if (!$content || !$sender_id || !$sender_name) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Dados incompletos']);
                    exit;
                }

                $stmt = $pdo->prepare("INSERT INTO messages (content, sender_id, sender_name, recipient_id, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$content, $sender_id, $sender_name, $recipient_id]);
                
                $lastId = $pdo->lastInsertId();
                
                // Busca a mensagem recém-inserida para retornar
                $stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ?");
                $stmt->execute([$lastId]);
                $message = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode(['status' => 'success', 'message' => $message]);
            } catch (PDOException $e) {
                error_log("Erro ao salvar mensagem: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao salvar mensagem', 'details' => $e->getMessage()]);
            }
            exit;
        
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
