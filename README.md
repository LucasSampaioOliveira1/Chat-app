# Chat App - Aplicação de Chat em Tempo Real

Aplicação de chat em tempo real desenvolvida com PHP, Angular, WebSocket, MySQL, Redis e RabbitMQ.

## Funcionalidades

- ✅ Autenticação de usuários com sessão via Redis
- ✅ Envio e recebimento de mensagens em tempo real via WebSockets
- ✅ Notificações assíncronas usando RabbitMQ
- ✅ Persistência de mensagens em MySQL
- ✅ Interface responsiva em Angular
- ✅ Indicador de digitação
- ✅ Status online/offline de usuários

## Requisitos

- Docker e Docker Compose
- Node.js e npm (para desenvolvimento frontend)

## Executando o projeto

### Com Docker (recomendado)

```bash
# Clonar o repositório
git clone https://github.com/seu-usuario/chat-app.git
cd chat-app

# Iniciar os containers
docker-compose up -d

# O frontend estará disponível em http://localhost:4200
# O backend estará disponível em http://localhost:80
# O WebSocket estará disponível em ws://localhost:8080
# O painel de administração do RabbitMQ estará disponível em http://localhost:15672 (guest/guest)
