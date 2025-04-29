# Mini Chat em Tempo Real

Este projeto é uma aplicação de chat em tempo real desenvolvida como parte de um desafio técnico. A aplicação permite o envio e recebimento de mensagens instantâneas, com histórico e notificações assíncronas.

## Tecnologias Utilizadas

- **Backend**: PHP 7.4
- **Frontend**: Angular
- **Banco de Dados**: MySQL
- **Cache de Sessões**: Redis
- **Mensageria**: RabbitMQ
- **Comunicação em Tempo Real**: WebSockets
- **Conteinerização**: Docker e Docker Compose

## Funcionalidades

- Autenticação de usuários
- Envio e recebimento de mensagens em tempo real
- Histórico de conversas
- Chat global e mensagens privadas
- Notificações assíncronas
- Indicador de usuários online

## Como executar o projeto

### Pré-requisitos

- Docker e Docker Compose instalados
- Git

### Passos para execução

1. Clone o repositório:
   ```
   git clone https://github.com/LucasSampaioOliveira1/Chat-app.git
   cd Chat-app
   ```

2. Inicie os contêineres com Docker Compose:
   ```
   docker-compose up -d
   ```

3. Instale as dependências do frontend (caso ainda não tenha feito):
   ```
   cd frontend
   npm install
   cd ..
   ```

4. Execute o frontend Angular:
   ```
   cd frontend
   ng serve
   ```

5. Acesse a aplicação:
   - Frontend: http://localhost:4200
   - API Backend: http://localhost:8080
   - RabbitMQ Management: http://localhost:15672 (usuário: guest, senha: guest)

## Estrutura do Projeto

- `backend/`: API REST em PHP e servidor WebSocket
  - `app/`: Código fonte principal
    - `Controllers/`: Controladores da aplicação
    - `Models/`: Modelos de dados
    - `Services/`: Serviços para Redis, RabbitMQ, etc.
    - `WebSocket/`: Implementação do servidor WebSocket
  - `ws/`: Scripts de WebSocket e consumidores de filas
  - `public/`: Ponto de entrada da API REST

- `frontend/`: Aplicação Angular
  - `src/app/`: Componentes, serviços e páginas

- `docker/`: Arquivos de configuração Docker
  - `php/`: Dockerfile para PHP
  - `mysql/`: Scripts de inicialização do MySQL
  - `nginx/`: Configuração do Nginx

## Arquitetura

A aplicação segue uma arquitetura de microsserviços:

1. **Frontend Angular**: Interface de usuário que se comunica com o backend via REST API e WebSockets
2. **API REST PHP**: Gerencia autenticação, usuários e persistência de mensagens
3. **Servidor WebSocket**: Gerencia comunicação em tempo real
4. **RabbitMQ**: Processamento assíncrono de notificações
5. **MySQL**: Armazenamento persistente de mensagens e usuários
6. **Redis**: Cache de sessões e gerenciamento de status online

## Autor

Lucas Sampaio Oliveira
