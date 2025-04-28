<?php
namespace App\Controllers;

class AuthController {
    public function register() {
        // Log para depuração
        error_log("Método register() chamado");
        
        try {
            // Capturar dados do request
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['error' => 'JSON inválido']);
                return;
            }
            
            // Validação básica
            if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Nome, email e senha são obrigatórios']);
                return;
            }
            
            // Aqui você implementaria a lógica de cadastro real
            // Por enquanto, retornamos sucesso simulado
            
            // Resposta de sucesso simulada
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'message' => 'Usuário registrado com sucesso',
                'user' => [
                    'id' => 1,
                    'name' => $data['name'],
                    'email' => $data['email']
                ],
                'session_id' => 'test_session_' . time()
            ]);
            
        } catch (\Exception $e) {
            error_log("Erro no registro: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Erro interno no servidor']);
        }
    }

    public function login() {
        // Implementação simulada do login
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validação básica
            if (empty($data['email']) || empty($data['password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Email e senha são obrigatórios']);
                return;
            }
            
            // Simulação de login bem-sucedido
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'message' => 'Login realizado com sucesso',
                'user' => [
                    'id' => 1,
                    'name' => 'Usuário Teste',
                    'email' => $data['email']
                ],
                'session_id' => 'test_session_' . time()
            ]);
            
        } catch (\Exception $e) {
            error_log("Erro no login: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Erro interno no servidor']);
        }
    }

    public function logout() {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Logout realizado com sucesso']);
    }
}