<?php

namespace App\Controllers;

use App\Models\User;
use App\Services\RedisService;

class UserController
{
    private $userModel;
    private $redisService;

    public function __construct()
    {
        $this->userModel = new User();
        $this->redisService = new RedisService();
    }

    public function getUsers()
    {
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = str_replace('Bearer ', '', $auth);
        
        if (!$token) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $currentUser = $this->redisService->getUserSession($token);
        
        if (!$currentUser) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid token']);
            return;
        }

        $users = $this->userModel->getAllExcept($currentUser['id']);
        
        // Remover senhas
        foreach ($users as &$user) {
            unset($user['password']);
            // Verificar se o usuário está online usando Redis
            $user['online'] = $this->redisService->isUserOnline($user['id']);
        }

        echo json_encode(['users' => $users]);
    }
}