<?php
// backend/app/Controllers/MessageController.php
namespace App\Controllers;

class MessageController {
    private $messages = [];
    private $messagesFile;
    
    public function __construct() {
        $this->messagesFile = __DIR__ . '/../../data/messages.json';
        $this->loadMessages();
    }
    
    private function loadMessages() {
        if (file_exists($this->messagesFile)) {
            $content = file_get_contents($this->messagesFile);
            $this->messages = json_decode($content, true) ?: [];
        } else {
            // Mensagens iniciais
            $this->messages = [
                [
                    'id' => 1,
                    'content' => 'Bem-vindo ao chat!',
                    'sender_id' => '0',
                    'sender_name' => 'Sistema',
                    'created_at' => date('Y-m-d\TH:i:s.000\Z')
                ],
                [
                    'id' => 2,
                    'content' => 'Envie uma mensagem para começar a conversar.',
                    'sender_id' => '0',
                    'sender_name' => 'Sistema',
                    'created_at' => date('Y-m-d\TH:i:s.000\Z')
                ]
            ];
            
            // Criar diretório se necessário
            if (!is_dir(dirname($this->messagesFile))) {
                mkdir(dirname($this->messagesFile), 0777, true);
            }
            
            // Salvar mensagens iniciais
            $this->saveMessages();
        }
    }
    
    private function saveMessages() {
        file_put_contents($this->messagesFile, json_encode($this->messages, JSON_PRETTY_PRINT));
    }
    
    public function getMessages() {
        // Log para debug
        error_log('Retornando ' . count($this->messages) . ' mensagens');
        
        // Retornar mensagens como JSON
        header('Content-Type: application/json');
        echo json_encode($this->messages);
    }
    
    public function sendMessage() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['content'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Conteúdo da mensagem é obrigatório']);
                return;
            }
            
            // Obter informações do remetente
            $userId = isset($data['sender_id']) ? $data['sender_id'] : (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '1');
            $userName = isset($data['sender_name']) ? $data['sender_name'] : (isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Usuário');
            
            error_log("Mensagem recebida de $userName (ID: $userId): {$data['content']}");
            
            // Criar nova mensagem
            $message = [
                'id' => count($this->messages) + 1,
                'content' => htmlspecialchars($data['content']),
                'sender_id' => $userId,
                'sender_name' => $userName,
                'created_at' => date('Y-m-d\TH:i:s.000\Z')
            ];
            
            // Adicionar à lista
            $this->messages[] = $message;
            
            // Salvar mensagens
            $this->saveMessages();
            
            // Retornar resposta
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'message' => $message
            ]);
            
        } catch (\Exception $e) {
            error_log("Erro ao enviar mensagem: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao enviar mensagem: ' . $e->getMessage()]);
        }
    }
}
