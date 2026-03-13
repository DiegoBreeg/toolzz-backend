# Chat API - Sistema de Mensagens em Tempo Real

API RESTful de chat em tempo real com frontend Next.js, utilizando WebSockets para comunicação instantânea e Meilisearch para busca avançada.

## Stack Tecnológica

### Backend
- **Laravel 12** - Framework PHP
- **PHP 8.4** - Runtime
- **PostgreSQL 18** - Banco de dados
- **Redis** - Cache e filas
- **Laravel Reverb** - WebSockets para tempo real
- **Meilisearch** - Busca full-text
- **Laravel Sanctum** - Autenticação via tokens
- **L5-Swagger** - Documentação OpenAPI

### Frontend
- **Next.js 16** - Framework React
- **React 19** - UI Library
- **TypeScript** - Type Safety
- **Tailwind CSS 4** - Styling
- **Laravel Echo** - WebSocket Client

## Requisitos

- **Docker** e **Docker Compose** (único requisito!)
- Git

> **Nota:** Não é necessário ter PHP, Composer ou Node.js instalados localmente. Tudo roda via Docker.

## Instalação Rápida (Recomendado)

### Opção 1: Script Automatizado

```bash
# Clonar o repositório
git clone git@github.com:DiegoBreeg/toolzz-backend.git
cd toolzz-backend

# Executar script de instalação
./scripts/setup.sh
```

O script irá:
1. Verificar se Docker está instalado e rodando
2. Verificar se as portas necessárias estão disponíveis
3. Instalar dependências do backend (Composer via Docker)
4. Configurar arquivos `.env`
5. Subir todos os containers
6. Gerar chave da aplicação
7. Executar migrações do banco de dados
8. Instalar dependências do frontend (npm via Docker)
9. Configurar ambiente do frontend

### Opção 2: Instalação Manual

#### 1. Clonar o repositório

```bash
git clone git@github.com:DiegoBreeg/toolzz-backend.git
cd toolzz-backend
```

#### 2. Instalar dependências do backend (sem PHP/Composer local)

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs
```

#### 3. Configurar ambiente do backend

```bash
cp .env.example .env
```

#### 4. Subir os containers

```bash
./vendor/bin/sail up -d
```

> **Conflito de portas?** Se você tiver serviços como nginx, redis ou postgresql rodando localmente, pare-os primeiro:
> ```bash
> sudo systemctl stop nginx redis-server postgresql
> ```

#### 5. Gerar chave e executar migrações

```bash
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
```

#### 6. Instalar dependências do frontend (sem Node local)

```bash
cd frontend

docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/app" \
    -w /app \
    node:20-alpine \
    npm install
```

#### 7. Configurar ambiente do frontend

```bash
cp .env.example .env.local
```

## Executando o Projeto

### Iniciar Backend (API + WebSocket + Serviços)

```bash
./scripts/start.sh
# ou
./vendor/bin/sail up -d
```

### Iniciar Frontend

**Com Node.js local:**
```bash
cd frontend && npm run dev
```

**Sem Node.js local (via Docker):**
```bash
./scripts/start-frontend.sh
```

### Parar Tudo

```bash
./scripts/stop.sh
# ou
./vendor/bin/sail down
```

## URLs dos Serviços

| Serviço | URL |
|---------|-----|
| Frontend | http://localhost:3000 |
| Backend API | http://localhost/api |
| Swagger Docs | http://localhost/api/documentation |
| WebSocket | ws://localhost:8080 |
| Mailpit (emails) | http://localhost:8025 |
| Meilisearch | http://localhost:7700 |

## Uso da API

### Autenticação

A API usa tokens Bearer via Laravel Sanctum. Após registrar ou fazer login, inclua o token no header:

```
Authorization: Bearer {seu-token}
```

### Endpoints

#### Autenticação

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/api/register` | Registrar novo usuário |
| POST | `/api/login` | Fazer login |
| POST | `/api/logout` | Fazer logout |
| GET | `/api/user` | Obter usuário autenticado |

#### Usuários

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/users` | Listar usuários |
| GET | `/api/users/{id}` | Ver perfil de usuário |
| PUT | `/api/user` | Atualizar próprio perfil |
| DELETE | `/api/user` | Deletar própria conta |

#### Mensagens

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/conversations` | Listar todas as conversas |
| GET | `/api/messages/{userId}` | Listar mensagens com usuário |
| POST | `/api/messages` | Enviar mensagem |
| GET | `/api/search/messages?q=termo` | Buscar mensagens |

## WebSocket (Tempo Real)

O projeto usa Laravel Reverb para WebSockets. Para receber mensagens em tempo real no frontend:

```javascript
import Echo from 'laravel-echo';

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
├── app/
│   ├── Events/
│   │   └── MessageSent.php           # Evento de broadcast
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── ChatController.php
│   │   │   │   └── UserController.php
│   │   │   └── Auth/
│   │   │       ├── AuthenticatedSessionController.php
│   │   │       └── RegisteredUserController.php
│   │   └── Requests/
│   ├── Models/
│   │   ├── Message.php
│   │   └── User.php
│   └── Services/
│       └── ChatService.php           # Lógica de negócio
├── config/
│   ├── reverb.php                    # Configuração WebSocket
│   └── scout.php                     # Configuração Meilisearch
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── frontend/                         # Aplicação Next.js
│   ├── src/
│   │   ├── app/                      # App Router (páginas)
│   │   ├── components/               # Componentes React
│   │   └── lib/                      # Utilitários (API, Echo)
│   ├── .env.example
│   └── package.json
├── routes/
│   ├── api.php                       # Rotas da API
│   └── channels.php                  # Canais WebSocket
├── scripts/
│   ├── setup.sh                      # Instalação automatizada
│   ├── start.sh                      # Iniciar serviços
│   ├── start-frontend.sh             # Iniciar frontend
│   └── stop.sh                       # Parar serviços
├── tests/
│   ├── Feature/
│   │   └── Api/
│   │       ├── ChatControllerTest.php
│   │       └── UserControllerTest.php
│   └── Unit/
│       ├── ChatServiceTest.php
│       └── MessageTest.php
├── .env.example
├── compose.yaml                      # Docker Compose
└── README.md
```

## Scripts Disponíveis

| Script | Descrição |
|--------|-----------|
| `./scripts/setup.sh` | Instalação completa do zero |
| `./scripts/start.sh` | Inicia todos os containers do backend |
| `./scripts/start-frontend.sh` | Inicia o frontend (Node local ou Docker) |
| `./scripts/stop.sh` | Para todos os containers |

## Comandos Úteis

```bash
# Ver logs dos containers
./vendor/bin/sail logs -f

# Acessar shell do container PHP
./vendor/bin/sail shell

# Acessar tinker (REPL)
./vendor/bin/sail artisan tinker

# Limpar cache
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:clear

# Regenerar documentação Swagger
./vendor/bin/sail artisan l5-swagger:generate

# Indexar dados no Meilisearch
./vendor/bin/sail artisan scout:import "App\Models\User"
./vendor/bin/sail artisan scout:import "App\Models\Message"

# Reiniciar WebSocket (Reverb)
./vendor/bin/sail artisan reverb:restart
```

## Variáveis de Ambiente

### Backend (`.env`)

As principais variáveis já vêm configuradas no `.env.example`. Ajuste conforme necessário:

```env
# Aplicação
APP_URL=http://localhost

# Banco de dados (PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password

# WebSocket
REVERB_HOST=localhost
REVERB_PORT=8080

# Meilisearch
MEILISEARCH_HOST=http://meilisearch:7700
```

### Frontend (`frontend/.env.local`)

```env
# API Backend
NEXT_PUBLIC_API_URL=http://localhost/api

# WebSocket
NEXT_PUBLIC_REVERB_APP_KEY=ocsshbytqcw8vk1o3bya
NEXT_PUBLIC_REVERB_HOST=localhost
NEXT_PUBLIC_REVERB_PORT=8080
NEXT_PUBLIC_REVERB_SCHEME=http
```

## Troubleshooting

### Erro: "address already in use"

Pare os serviços que estão usando as portas:

```bash
sudo systemctl stop nginx redis-server postgresql
# ou identifique o processo
sudo lsof -i :80
sudo lsof -i :6379
```

### Containers não sobem

Verifique se o Docker está rodando:

```bash
docker info
```

Recrie os containers:

```bash
./vendor/bin/sail down -v
./vendor/bin/sail up -d
```

### Frontend não conecta ao backend

1. Verifique se o backend está rodando: `curl http://localhost/api`
2. Verifique o arquivo `frontend/.env.local`
3. Reinicie o frontend após alterar variáveis de ambiente

## Licença

MIT
