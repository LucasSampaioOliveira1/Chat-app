<?php
// backend/ws/server.php
// Servidor WebSocket simplificado para chat
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/websocket.log');

echo "Iniciando servidor WebSocket...\n";
file_put_contents(__DIR__ . '/../logs/websocket.log', date('[Y-m-d H:i:s] ') . "Iniciando servidor WebSocket...\n", FILE_APPEND);

// Configuração
$host = '0.0.0.0';
$port = 8081;

// Criar servidor socket
$server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if (!$server) {
    echo "Não foi possível criar o socket: " . socket_strerror(socket_last_error()) . "\n";
    exit(1);
}

socket_set_option($server, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($server, $host, $port);
socket_listen($server);

// Array de clientes conectados
$clients = [$server];

echo "Servidor WebSocket rodando em $host:$port\n";
file_put_contents(__DIR__ . '/../logs/websocket.log', date('[Y-m-d H:i:s] ') . "Servidor WebSocket rodando em $host:$port\n", FILE_APPEND);

// Loop principal
while (true) {
    // Configurar arrays para socket_select
    $read = $clients;
    $write = $except = null;
    
    // Monitorar todos os sockets
    if (socket_select($read, $write, $except, 0) < 1) {
        usleep(100000); // 100ms de pausa
        continue;
    }
    
    // Verificar novas conexões
    if (in_array($server, $read)) {
        $newClient = socket_accept($server);
        $clients[] = $newClient;
        
        // Ler cabeçalho HTTP
        $header = socket_read($newClient, 1024);
        
        // Realizar handshake WebSocket
        if (preg_match('/Sec-WebSocket-Key: (.*)\r\n/i', $header, $matches)) {
            $key = base64_encode(sha1($matches[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
            $headers = "HTTP/1.1 101 Switching Protocols\r\n";
            $headers .= "Upgrade: websocket\r\n";
            $headers .= "Connection: Upgrade\r\n";
            $headers .= "Sec-WebSocket-Accept: $key\r\n\r\n";
            socket_write($newClient, $headers, strlen($headers));
            
            // Registrar conexão
            socket_getpeername($newClient, $ip);
            echo "Nova conexão de $ip\n";
            file_put_contents(__DIR__ . '/../logs/websocket.log', date('[Y-m-d H:i:s] ') . "Nova conexão de $ip\n", FILE_APPEND);
            
            // Remover servidor da lista de leitura
            $key = array_search($server, $read);
            unset($read[$key]);
        }
    }
    
    // Loop por clientes existentes para ler dados
    foreach ($read as $client) {
        // Ler dados do cliente
        $data = @socket_recv($client, $buf, 1024, 0);
        
        // Verificar desconexão ou erro
        if ($data === false || $data === 0) {
            socket_getpeername($client, $ip);
            echo "Cliente desconectado: $ip\n";
            file_put_contents(__DIR__ . '/../logs/websocket.log', date('[Y-m-d H:i:s] ') . "Cliente desconectado: $ip\n", FILE_APPEND);
            
            $key = array_search($client, $clients);
            unset($clients[$key]);
            socket_close($client);
            continue;
        }
        
        // Decodificar mensagem WebSocket
        $message = decodeWebSocketMessage($buf);
        if ($message !== false) {
            // Log da mensagem recebida
            socket_getpeername($client, $ip);
            echo "Mensagem de $ip: $message\n";
            file_put_contents(__DIR__ . '/../logs/websocket.log', date('[Y-m-d H:i:s] ') . "Mensagem de $ip: $message\n", FILE_APPEND);
            
            // Broadcast para todos os outros clientes
            foreach ($clients as $send_socket) {
                if ($send_socket !== $server && $send_socket !== $client) {
                    $encoded = encodeWebSocketMessage($message);
                    @socket_write($send_socket, $encoded, strlen($encoded));
                }
            }
        }
    }
}

// Funções auxiliares para WebSocket

// Decodifica uma mensagem WebSocket
function decodeWebSocketMessage($data) {
    $firstByte = ord($data[0]);
    $secondByte = ord($data[1]);
    $masked = ($secondByte & 0x80) ? true : false;
    $dataLength = $secondByte & 0x7F;
    
    $maskingKeyStartPos = 2;
    
    if ($dataLength >= 126) {
        if ($dataLength == 126) {
            $dataLength = (ord($data[2]) << 8) + ord($data[3]);
            $maskingKeyStartPos = 4;
        } else {
            // Ignorar mensagens muito longas para simplificar
            return false;
        }
    }
    
    if (!$masked) {
        return false; // Todas as mensagens do cliente devem ser mascaradas
    }
    
    // Extrair a chave de mascaramento (4 bytes)
    $mask = substr($data, $maskingKeyStartPos, 4);
    $dataStartPos = $maskingKeyStartPos + 4;
    
    // Decodificar os dados mascarados
    $decoded = '';
    for ($i = $dataStartPos; $i < strlen($data); $i++) {
        $decoded .= $data[$i] ^ $mask[($i - $dataStartPos) % 4];
    }
    
    return $decoded;
}

// Codifica uma mensagem para o formato WebSocket
function encodeWebSocketMessage($text) {
    $firstByte = 0x81; // Texto = opcode 1, FIN = 1
    $length = strlen($text);
    
    // Construir cabeçalho
    if ($length <= 125) {
        $header = pack('CC', $firstByte, $length);
    } elseif ($length < 65536) {
        $header = pack('CCn', $firstByte, 126, $length);
    } else {
        $header = pack('CCNN', $firstByte, 127, 0, $length);
    }
    
    // Retornar cabeçalho + texto
    return $header . $text;
}
