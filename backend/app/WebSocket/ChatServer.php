<?php
namespace App\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use App\Services\RedisService;

class ChatServer implements MessageComponentInterface
{
    protected $clients;
    protected $users = [];
    protected $redisService;
    
    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->redisService = new RedisService();
    }
    
    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "Nova conexão! ({$conn->resourceId})\n";
    }
    
    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg);
        
        if (!isset($data->type)) {
            return;
        }
        
        switch ($data->type) {
            case 'auth':
                $this->handleAuth($from, $data);
                break;
                
            case 'message':
                $this->handleMessage($from, $data);
                break;
                
            case 'typing':
                $this->handleTyping($from, $data);
                break;
        }
    }
    
    protected function handleAuth($conn, $data)
    {
        if (isset($data->sessionId)) {
            $userId = $this->redisService->get("session:{$data->sessionId}");
            
            if ($userId) {
                $this->users[$conn->resourceId] = [
                    'user_id' => $userId,
                    'session_id' => $data->sessionId
                ];
                
                $conn->send(json_encode([
                    'type' => 'auth',
                    'status' => 'success'
                ]));
                
                return;
            }
        }
        
        $conn->send(json_encode([
            'type' => 'auth',
            'status' => 'error',
            'message' => 'Autenticação falhou'
        ]));
    }
    
    protected function handleMessage($from, $data)
    {
        if (!isset($this->users[$from->resourceId])) {
            $from->send(json_encode([
                'type' => 'error',
                'message' => 'Você precisa se autenticar primeiro'
            ]));
            return;
        }
        
        if (!isset($data->content) || empty($data->content)) {
            return;
        }
        
        $message = [
            'type' => 'message',
            'user_id' => $this->users[$from->resourceId]['user_id'],
            'content' => $data->content,
            'timestamp' => time()
        ];
        
        foreach ($this->clients as $client) {
            $client->send(json_encode($message));
        }
    }
    
    protected function handleTyping($from, $data)
    {
        if (!isset($this->users[$from->resourceId])) {
            return;
        }
        
        $typing = [
            'type' => 'typing',
            'user_id' => $this->users[$from->resourceId]['user_id'],
            'isTyping' => $data->isTyping ?? false
        ];
        
        foreach ($this->clients as $client) {
            if ($from !== $client) {
                $client->send(json_encode($typing));
            }
        }
    }
    
    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        
        if (isset($this->users[$conn->resourceId])) {
            unset($this->users[$conn->resourceId]);
        }
        
        echo "Conexão {$conn->resourceId} foi fechada\n";
    }
    
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "Erro: {$e->getMessage()}\n";
        $conn->close();
    }
}
