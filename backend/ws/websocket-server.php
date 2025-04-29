<?php

require __DIR__ . '/../vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class ChatWebSocketServer implements MessageComponentInterface
{
    protected $clients;
    protected $userConnections = [];

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        
        // Iniciar consumidor RabbitMQ para enviar mensagens via WebSocket
        $this->startRabbitMQConsumer();
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "Nova conexão: {$conn->resourceId}\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);
        
        if ($data && isset($data['type'])) {
            switch ($data['type']) {
                case 'auth':
                    // Autenticar conexão com token
                    if (isset($data['token']) && isset($data['userId'])) {
                        $this->userConnections[$data['userId']] = $from;
                        echo "Usuário {$data['userId']} autenticado\n";
                    }
                    break;
                
                case 'typing':
                    // Transmitir status de digitação
                    $this->broadcastMessage($from, [
                        'type' => 'typing',
                        'data' => $data['data']
                    ]);
                    break;
                
                default:
                    // Mensagens não processadas
                    echo "Mensagem recebida: " . $msg . "\n";
                    break;
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        
        // Remover conexão do usuário
        foreach ($this->userConnections as $userId => $connection) {
            if ($connection === $conn) {
                unset($this->userConnections[$userId]);
                echo "Conexão do usuário {$userId} fechada\n";
            }
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "Erro: {$e->getMessage()}\n";
        $conn->close();
    }
    
    protected function broadcastMessage(ConnectionInterface $from, array $message)
    {
        $encodedMessage = json_encode($message);
        
        foreach ($this->clients as $client) {
            if ($client !== $from) {
                $client->send($encodedMessage);
            }
        }
    }
    
    protected function sendToUser(string $userId, array $message)
    {
        if (isset($this->userConnections[$userId])) {
            $this->userConnections[$userId]->send(json_encode($message));
            return true;
        }
        return false;
    }
    
    protected function startRabbitMQConsumer()
    {
        $host = getenv('RABBITMQ_HOST') ?: 'rabbitmq';
        $port = getenv('RABBITMQ_PORT') ?: 5672;
        $exchange = 'chat_events';

        try {
            $connection = new AMQPStreamConnection($host, $port, 'guest', 'guest');
            $channel = $connection->channel();
            
            // Declarar exchange
            $channel->exchange_declare($exchange, 'topic', false, true, false);
            
            // Declarar fila para WebSocket
            $queueName = 'websocket_events';
            $channel->queue_declare($queueName, false, true, false, false);
            
            // Vincular fila a todos os eventos
            $channel->queue_bind($queueName, $exchange, '#');
            
            // Consumir mensagens de forma não bloqueante
            $channel->basic_consume(
                $queueName,
                '',
                false,
                true,
                false,
                false,
                function ($message) {
                    $content = json_decode($message->body, true);
                    
                    if (isset($content['event']) && isset($content['data'])) {
                        switch ($content['event']) {
                            case 'new_message':
                                // Transmitir nova mensagem
                                if ($content['data']['recipient_id'] === 'general') {
                                    // Mensagem para todos
                                    $this->broadcastMessage(null, [
                                        'type' => 'message',
                                        'data' => $content['data']
                                    ]);
                                } else {
                                    // Mensagem privada - enviar apenas para remetente e destinatário
                                    $this->sendToUser($content['data']['sender_id'], [
                                        'type' => 'message',
                                        'data' => $content['data']
                                    ]);
                                    
                                    $this->sendToUser($content['data']['recipient_id'], [
                                        'type' => 'message',
                                        'data' => $content['data']
                                    ]);
                                }
                                break;
                            
                            case 'user_login':
                            case 'user_registered':
                                // Notificar sobre novo usuário online
                                $this->broadcastMessage(null, [
                                    'type' => 'user_connected',
                                    'data' => $content['data']
                                ]);
                                break;
                            
                            case 'user_logout':
                                // Notificar sobre usuário offline
                                $this->broadcastMessage(null, [
                                    'type' => 'user_disconnected',
                                    'data' => $content['data']
                                ]);
                                break;
                        }
                    }
                }
            );
            
            // Processar mensagens RabbitMQ sem bloquear o loop WebSocket
            $websocketServer = $this;
            
            $loop = \React\EventLoop\Factory::create();
            $loop->addPeriodicTimer(0.5, function () use ($channel) {
                $channel->wait(null, true);
            });
            
            // Iniciar servidor WebSocket
            $server = IoServer::factory(
                new HttpServer(
                    new WsServer($websocketServer)
                ),
                8080,
                '0.0.0.0'
            );
            
            $server->loop = $loop;
            echo "WebSocket server iniciado na porta 8080\n";
            $server->run();
            
        } catch (\Exception $e) {
            die("RabbitMQ consumer error: " . $e->getMessage());
        }
    }
}

// Iniciar servidor
$server = new ChatWebSocketServer();