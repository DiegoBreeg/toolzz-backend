# Chat API - Sistema de Mensagens em Tempo Real

API RESTful de chat em tempo real desenvolvida com Laravel 12, utilizando WebSockets para comunicação instantânea e Meilisearch para busca avançada.

## Tecnologias

- **Laravel 12** - Framework PHP
- **PHP 8.4** - Runtime
- **PostgreSQL 18** - Banco de dados
- **Redis** - Cache e filas
- **Laravel Reverb** - WebSockets para tempo real
- **Meilisearch** - Busca full-text
- **Laravel Sanctum** - Autenticação via tokens
- **Laravel Scout** - Integração com Meilisearch
- **L5-Swagger** - Documentação OpenAPI

## Requisitos

- Docker e Docker Compose
- Git

## Instalação

### 1. Clonar o repositório

```bash
git clone <url-do-repositorio>
cd backend
```

### 2. Instalar dependências

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs
```

### 3. Configurar ambiente

```bash
cp .env.example .env
```

Edite o arquivo `.env` e configure as variáveis necessárias:

```env
APP_NAME="Chat API"
APP_URL=http://localhost

DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password

BROADCAST_CONNECTION=reverb

MEILISEARCH_HOST=http://meilisearch:7700
MEILISEARCH_KEY=

REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
```

### 4. Subir os containers

```bash
./vendor/bin/sail up -d
```

### 5. Gerar chave da aplicação

```bash
./vendor/bin/sail artisan key:generate
```

### 6. Executar migrações

```bash
./vendor/bin/sail artisan migrate
```

### 7. Indexar dados no Meilisearch (opcional)

```bash
./vendor/bin/sail artisan scout:import "App\Models\User"
./vendor/bin/sail artisan scout:import "App\Models\Message"
```

### 8. Iniciar o servidor WebSocket

```bash
./vendor/bin/sail artisan reverb:start
```

## Uso da API

### Base URL

```
http://localhost/api
```

### Documentação Swagger

Acesse a documentação interativa em:

```
http://localhost/api/documentation
```

### Autenticação

A API usa tokens Bearer via Laravel Sanctum. Após registrar ou fazer login, inclua o token no header:

```
Authorization: Bearer {seu-token}
```

## Endpoints

### Autenticação

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/api/register` | Registrar novo usuário |
| POST | `/api/login` | Fazer login |
| POST | `/api/logout` | Fazer logout |

### Usuários

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/users` | Listar usuários |
| GET | `/api/users/{id}` | Ver perfil de usuário |
| PUT | `/api/user` | Atualizar próprio perfil |
| DELETE | `/api/user` | Deletar própria conta |

### Mensagens

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/conversations` | Listar todas as conversas |
| GET | `/api/messages/{userId}` | Listar mensagens com usuário |
| POST | `/api/messages` | Enviar mensagem |
| GET | `/api/search/messages?q=termo` | Buscar mensagens |

## WebSockets

Para receber mensagens em tempo real, conecte-se ao canal privado:

```javascript
Echo.private(`chat.${userId}`)
    .listen('MessageSent', (e) => {
        console.log('Nova mensagem:', e.message);
    });
```

## Testes

### Executar todos os testes

```bash
./vendor/bin/sail artisan test
```

### Executar testes com cobertura

```bash
./vendor/bin/sail artisan test --coverage
```

### Executar testes específicos

```bash
# Testes de feature
./vendor/bin/sail artisan test --filter=ChatControllerTest
./vendor/bin/sail artisan test --filter=UserControllerTest

# Testes unitários
./vendor/bin/sail artisan test --filter=ChatServiceTest
./vendor/bin/sail artisan test --filter=MessageTest
```

## Estrutura do Projeto

```
app/
├── Events/
│   └── MessageSent.php          # Evento de broadcast
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── ChatController.php
│   │   │   └── UserController.php
│   │   └── Auth/
│   │       ├── AuthenticatedSessionController.php
│   │       └── RegisteredUserController.php
│   └── Requests/
├── Models/
│   ├── Message.php
│   └── User.php
├── Services/
│   └── ChatService.php          # Lógica de negócio
config/
├── scout.php                     # Configuração Meilisearch
├── reverb.php                    # Configuração WebSocket
database/
├── factories/
│   ├── MessageFactory.php
│   └── UserFactory.php
├── migrations/
routes/
├── api.php                       # Rotas da API
├── channels.php                  # Canais WebSocket
tests/
├── Feature/
│   └── Api/
│       ├── ChatControllerTest.php
│       └── UserControllerTest.php
└── Unit/
    ├── ChatServiceTest.php
    └── MessageTest.php
```

## Comandos Úteis

```bash
# Parar containers
./vendor/bin/sail down

# Ver logs
./vendor/bin/sail logs -f

# Acessar shell do container
./vendor/bin/sail shell

# Acessar tinker
./vendor/bin/sail artisan tinker

# Limpar cache
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:clear

# Regenerar documentação Swagger
./vendor/bin/sail artisan l5-swagger:generate
```

## Licença

MIT
