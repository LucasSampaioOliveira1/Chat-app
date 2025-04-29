-- Criar banco de dados se não existir
CREATE DATABASE IF NOT EXISTS chat;

-- Criar usuário e conceder privilégios
CREATE USER IF NOT EXISTS 'chatuser'@'%' IDENTIFIED BY 'chatpass';
GRANT ALL PRIVILEGES ON chat.* TO 'chatuser'@'%';
FLUSH PRIVILEGES;

USE chat;

-- Criar tabelas
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    recipient_id VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX sender_idx (sender_id),
    INDEX recipient_idx (recipient_id),
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Usuários de teste
INSERT INTO users (name, email, password) VALUES
('Admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'), -- password: admin123
('Maria Silva', 'maria@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'), -- password: maria123
('João Santos', 'joao@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); -- password: joao123