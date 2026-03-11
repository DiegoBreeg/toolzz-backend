#!/bin/bash

# =============================================================================
# Chat API - Script para Sincronizar Variáveis de Ambiente
# =============================================================================
# Sincroniza as chaves do Reverb entre backend e frontend
# =============================================================================

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}Sincronizando variáveis de ambiente...${NC}"

cd "$PROJECT_DIR"

if [ ! -f .env ]; then
    echo "Erro: Arquivo .env não encontrado no backend"
    echo "Execute ./scripts/setup.sh primeiro"
    exit 1
fi

# Ler chaves do backend
reverb_app_key=$(grep "^REVERB_APP_KEY=" .env | cut -d'=' -f2)
reverb_host=$(grep "^REVERB_HOST=" .env | cut -d'=' -f2 | tr -d '"')
reverb_port=$(grep "^REVERB_PORT=" .env | cut -d'=' -f2)
reverb_scheme=$(grep "^REVERB_SCHEME=" .env | cut -d'=' -f2)

# Atualizar frontend/.env.local
cd "$PROJECT_DIR/frontend"

cat > .env.local << EOF
# API Backend
NEXT_PUBLIC_API_URL=http://localhost/api

# Laravel Reverb WebSocket (sincronizado com backend)
NEXT_PUBLIC_REVERB_APP_KEY=$reverb_app_key
NEXT_PUBLIC_REVERB_HOST=$reverb_host
NEXT_PUBLIC_REVERB_PORT=$reverb_port
NEXT_PUBLIC_REVERB_SCHEME=$reverb_scheme
EOF

echo -e "${GREEN}✅ Frontend .env.local sincronizado com backend${NC}"
echo ""
echo "Variáveis sincronizadas:"
echo "  REVERB_APP_KEY: $reverb_app_key"
echo "  REVERB_HOST: $reverb_host"
echo "  REVERB_PORT: $reverb_port"
echo "  REVERB_SCHEME: $reverb_scheme"
