<?php
require __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$config = require __DIR__ . '/../config/rabbitmq.php';

try {
    $connection = new AMQPStreamConnection(
        $config['host'],
        $config['port'],
        $config['user'],
        $config['password'],
        $config['vhost']
    );
    
    $channel = $connection->channel();
    
    $channel->queue_declare('notifications', false, true, false, false);
    
    echo " [*] Esperando por mensagens. Para sair pressione CTRL+C\n";
    
    $callback = function ($msg) {
        $data = json_decode($msg->body, true);
        echo " [x] Recebido: " . json_encode($data) . "\n";
        
        // Aqui você pode processar notificações, enviar e-mails, etc.
        
        $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
    };
    
    $channel->basic_qos(null, 1, null);
    $channel->basic_consume('notifications', '', false, false, false, false, $callback);
    
    while (count($channel->callbacks)) {
        $channel->wait();
    }
} catch (\Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
