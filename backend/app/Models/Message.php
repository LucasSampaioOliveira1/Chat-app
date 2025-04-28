<?php
namespace App\Models;

class Message
{
    private $pdo;
    
    public function __construct()
    {
        $config = require __DIR__ . '/../../config/database.php';
        $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
        
        $this->pdo = new \PDO($dsn, $config['username'], $config['password'], [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    
    public function getAll()
    {
        $stmt = $this->pdo->query('
            SELECT m.id, m.content, m.created_at, u.name as sender_name, m.sender_id
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            ORDER BY m.created_at DESC
            LIMIT 100
        ');
        return $stmt->fetchAll();
    }
    
    public function save($senderId, $content)
    {
        $stmt = $this->pdo->prepare('INSERT INTO messages (sender_id, content) VALUES (?, ?)');
        $stmt->execute([$senderId, $content]);
        return $this->pdo->lastInsertId();
    }
}
