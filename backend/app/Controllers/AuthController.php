<?php
namespace App\Controllers;

class AuthController {
    public function register() {
        try {
            // Obter dados JSON
            $data = json_decode(file_get_contents('php://input'), true);
            error_log("Dados de registro recebidos: " . json_encode($data));
            
            // Validação básica
            if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Nome, email e senha são obrigatórios']);
                return;
            }
            
            // Resposta de simulação para teste
            echo json_encode([
                'status' => 'success',
                'message' => 'Usuário registrado com sucesso',
                'user' => [
                    'id' => 1,
                    'name' => $data['name'],
                    'email' => $data['email']
                ],
                'token' => 'token_' . time()
            ]);
        } catch (\Exception $e) {
            error_log("Erro no registro: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Erro interno: ' . $e->getMessage()]);
        }
    }

    public function login() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            error_log("Dados de login recebidos: " . json_encode($data));
            
            // Validação básica
            if (empty($data['email']) || empty($data['password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Email e senha são obrigatórios']);
                return;
            }
            
            // IMPORTANTE: Para o propósito de teste, aceite qualquer credencial
            // Em um sistema real, você verificaria no banco de dados
            
            // Resposta deve ter a mesma estrutura que o registro
            echo json_encode([
                'status' => 'success',
                'message' => 'Login realizado com sucesso',
                'user' => [
                    'id' => 1,
                    'name' => 'Usuário Teste',
                    'email' => $data['email']
                ],
                'session_id' => 'session_' . time() // Mudando token para session_id
            ]);
            
        } catch (\Exception $e) {
            error_log("Erro no login: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Erro interno no servidor']);
        }
    }

    public function logout() {
        echo json_encode([
            'status' => 'success',
            'message' => 'Logout realizado com sucesso'
        ]);
    }
}
