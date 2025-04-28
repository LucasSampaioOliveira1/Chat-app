<?php
namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQService
{
    private $connection;
    private $channel;
    
    public function __construct()
    {
        $config = require __DIR__ . '/../../config/rabbitmq.php';
        
        $this->connection = new AMQPStreamConnection(
            $config['host'],
            $config['port'],
            $config['user'],
            $config['password'],
            $config['vhost']
        );
        
        $this->channel = $this->connection->channel();
        
        // Declarar exchange e filas
        $this->channel->exchange_declare('chat_exchange', 'direct', false, true, false);
        $this->channel->queue_declare('notifications', false, true, false, false);
        $this->channel->queue_bind('notifications', 'chat_exchange', 'new_message');
    }
    
    public function publish($message)
    {
        $msg = new AMQPMessage(
            json_encode($message),
            ['content_type' => 'application/json', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
        );
        
        $this->channel->basic_publish($msg, 'chat_exchange', 'new_message');
    }
    
    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }
}
