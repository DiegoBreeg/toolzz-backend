#!/bin/bash

# =============================================================================
# Chat API - Script de Instalação
# =============================================================================
# Este script configura o projeto do zero usando apenas Docker.
# Não é necessário ter PHP, Composer ou Node.js instalados localmente.
# =============================================================================

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_step() {
    echo -e "\n${BLUE}==>${NC} ${GREEN}$1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

# Verificar se Docker está instalado
check_docker() {
    print_step "Verificando Docker..."
    if ! command -v docker &> /dev/null; then
        print_error "Docker não encontrado. Por favor, instale o Docker primeiro."
        echo "  → https://docs.docker.com/get-docker/"
        exit 1
    fi

    if ! docker info &> /dev/null; then
        print_error "Docker não está rodando. Por favor, inicie o Docker."
        exit 1
    fi

    print_success "Docker está instalado e rodando"
}

# Verificar se Docker Compose está disponível
check_docker_compose() {
    print_step "Verificando Docker Compose..."
    if ! docker compose version &> /dev/null; then
        print_error "Docker Compose não encontrado."
        exit 1
    fi
    print_success "Docker Compose está disponível"
}

# Parar serviços que podem conflitar com as portas
check_ports() {
    print_step "Verificando portas disponíveis..."

    # Se os containers do Sail já estão rodando, pular verificação
    if docker ps --format '{{.Names}}' 2>/dev/null | grep -q "backend-laravel.test"; then
        print_warning "Containers do Sail já estão rodando. Parando para reinstalar..."
        cd "$PROJECT_DIR"
        ./vendor/bin/sail down 2>/dev/null || true
        sleep 2
    fi

    local ports_in_use=""

    for port in 80 5432 6379 7700 8080; do
        # Verifica apenas processos ESCUTANDO (LISTEN) na porta, excluindo docker-proxy
        if ss -ltnp 2>/dev/null | grep -vE "docker-proxy" | grep -qE ":$port\b"; then
            ports_in_use="$ports_in_use $port"
        fi
    done

    if [ -n "$ports_in_use" ]; then
        print_warning "As seguintes portas estão em uso:$ports_in_use"
        echo "  Você pode precisar parar serviços como nginx, redis, postgresql, etc."
        echo "  Exemplo: sudo systemctl stop nginx redis-server postgresql"
        echo ""
        read -p "Deseja continuar mesmo assim? (s/N) " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Ss]$ ]]; then
            exit 1
        fi
    else
        print_success "Todas as portas necessárias estão disponíveis"
    fi
}

# Instalar dependências do backend via Docker
install_backend_dependencies() {
    print_step "Instalando dependências do backend (Composer)..."

    cd "$PROJECT_DIR"

    docker run --rm \
        -u "$(id -u):$(id -g)" \
        -v "$(pwd):/var/www/html" \
        -w /var/www/html \
        laravelsail/php84-composer:latest \
        composer install --ignore-platform-reqs --no-interaction --prefer-dist

    print_success "Dependências do backend instaladas"
}

# Gerar string aleatória
generate_random_string() {
    local length=${1:-20}
    cat /dev/urandom | tr -dc 'a-z0-9' | fold -w "$length" | head -n 1
}

# Configurar arquivo .env do backend
setup_backend_env() {
    print_step "Configurando ambiente do backend..."

    cd "$PROJECT_DIR"

    if [ ! -f .env ]; then
        cp .env.example .env
        
        # Gerar chaves únicas para Reverb
        local reverb_app_id=$(shuf -i 100000-999999 -n 1)
        local reverb_app_key=$(generate_random_string 20)
        local reverb_app_secret=$(generate_random_string 20)
        
        sed -i "s/REVERB_APP_ID=.*/REVERB_APP_ID=$reverb_app_id/" .env
        sed -i "s/REVERB_APP_KEY=.*/REVERB_APP_KEY=$reverb_app_key/" .env
        sed -i "s/REVERB_APP_SECRET=.*/REVERB_APP_SECRET=$reverb_app_secret/" .env
        
        print_success "Arquivo .env criado com chaves únicas"
    else
        print_warning "Arquivo .env já existe, mantendo configuração atual"
    fi
}

# Subir containers Docker
start_containers() {
    print_step "Construindo imagem Docker do Laravel Sail..."

    cd "$PROJECT_DIR"
    ./vendor/bin/sail build

    print_step "Iniciando containers Docker..."
    ./vendor/bin/sail up -d

    print_success "Containers iniciados"

    # Aguardar serviços ficarem saudáveis
    print_step "Aguardando serviços ficarem prontos..."

    local max_attempts=30
    local attempt=0

    # Aguardar PostgreSQL
    while [ $attempt -lt $max_attempts ]; do
        if ./vendor/bin/sail exec pgsql pg_isready -q -d "${DB_DATABASE:-laravel}" -U "${DB_USERNAME:-sail}" 2>/dev/null; then
            break
        fi
        attempt=$((attempt + 1))
        sleep 1
    done

    # Aguardar Meilisearch
    attempt=0
    while [ $attempt -lt $max_attempts ]; do
        if docker exec toolzz-backend-meilisearch-1 wget --no-verbose --spider http://127.0.0.1:7700/health 2>/dev/null; then
            break
        fi
        attempt=$((attempt + 1))
        sleep 1
    done

    if [ $attempt -ge $max_attempts ]; then
        print_warning "Meilisearch demorou para iniciar. Verifique os logs com: ./vendor/bin/sail logs meilisearch"
    fi

    print_success "Serviços prontos"
}

# Gerar chave da aplicação
generate_app_key() {
    print_step "Gerando chave da aplicação..."

    cd "$PROJECT_DIR"
    ./vendor/bin/sail artisan key:generate --force

    print_success "Chave gerada"
}

# Executar migrações
run_migrations() {
    print_step "Executando migrações do banco de dados..."

    cd "$PROJECT_DIR"
    ./vendor/bin/sail artisan migrate --force

    print_success "Migrações executadas"
}

# Configurar índices do Meilisearch
setup_meilisearch() {
    print_step "Configurando índices do Meilisearch..."

    cd "$PROJECT_DIR"
    ./vendor/bin/sail artisan scout:sync-index-settings

    print_success "Índices do Meilisearch configurados"
}

# Instalar dependências do frontend via Docker
install_frontend_dependencies() {
    print_step "Instalando dependências do frontend (Node.js)..."

    cd "$PROJECT_DIR/frontend"

    docker run --rm \
        -u "$(id -u):$(id -g)" \
        -v "$(pwd):/app" \
        -w /app \
        node:20-alpine \
        npm install

    print_success "Dependências do frontend instaladas"
}

# Configurar arquivo .env do frontend
setup_frontend_env() {
    print_step "Configurando ambiente do frontend..."

    cd "$PROJECT_DIR/frontend"

    # Ler chaves do backend
    local reverb_app_key=$(grep "^REVERB_APP_KEY=" "$PROJECT_DIR/.env" | cut -d'=' -f2)
    local reverb_host=$(grep "^REVERB_HOST=" "$PROJECT_DIR/.env" | cut -d'=' -f2 | tr -d '"')
    local reverb_port=$(grep "^REVERB_PORT=" "$PROJECT_DIR/.env" | cut -d'=' -f2)
    local reverb_scheme=$(grep "^REVERB_SCHEME=" "$PROJECT_DIR/.env" | cut -d'=' -f2)

    # Criar .env.local com as chaves do backend
    cat > .env.local << EOF
# API Backend
NEXT_PUBLIC_API_URL=http://localhost/api

# Laravel Reverb WebSocket (sincronizado com backend)
NEXT_PUBLIC_REVERB_APP_KEY=$reverb_app_key
NEXT_PUBLIC_REVERB_HOST=$reverb_host
NEXT_PUBLIC_REVERB_PORT=$reverb_port
NEXT_PUBLIC_REVERB_SCHEME=$reverb_scheme
EOF

    print_success "Arquivo .env.local criado (sincronizado com backend)"
}

# Exibir resumo final
show_summary() {
    echo ""
    echo -e "${GREEN}=============================================${NC}"
    echo -e "${GREEN}   🎉 Instalação concluída com sucesso!${NC}"
    echo -e "${GREEN}=============================================${NC}"
    echo ""
    echo -e "${BLUE}Serviços disponíveis:${NC}"
    echo "  • Backend API:      http://localhost/api"
    echo "  • Swagger Docs:     http://localhost/api/documentation"
    echo "  • WebSocket:        ws://localhost:8080"
    echo "  • Mailpit:          http://localhost:8025"
    echo "  • Meilisearch:      http://localhost:7700"
    echo ""
    echo -e "${BLUE}Para iniciar o frontend:${NC}"
    echo "  ./scripts/start-frontend.sh"
    echo "  ou: cd frontend && npm run dev"
    echo ""
    echo -e "${BLUE}Frontend disponível em:${NC}"
    echo "  http://localhost:3000"
    echo ""
    echo -e "${BLUE}Comandos úteis:${NC}"
    echo "  • Parar tudo:       ./scripts/stop.sh"
    echo "  • Iniciar tudo:     ./scripts/start.sh"
    echo "  • Ver logs:         ./vendor/bin/sail logs -f"
    echo "  • Rodar testes:     ./vendor/bin/sail artisan test"
    echo ""
}

# =============================================================================
# Main
# =============================================================================

main() {
    echo ""
    echo -e "${BLUE}=============================================${NC}"
    echo -e "${BLUE}   Chat API - Instalação Automatizada${NC}"
    echo -e "${BLUE}=============================================${NC}"

    check_docker
    check_docker_compose
    check_ports
    install_backend_dependencies
    setup_backend_env
    start_containers
    generate_app_key
    run_migrations
    setup_meilisearch
    install_frontend_dependencies
    setup_frontend_env
    show_summary
}

main "$@"
