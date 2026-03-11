#!/bin/bash

# =============================================================================
# Chat API - Script para Iniciar o Frontend
# =============================================================================
# Roda o frontend Next.js. Pode usar Node local ou Docker.
# =============================================================================

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
FRONTEND_DIR="$PROJECT_DIR/frontend"

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

cd "$FRONTEND_DIR"

# Verificar se Node está instalado localmente
if command -v node &> /dev/null; then
    echo -e "${BLUE}Iniciando frontend com Node.js local...${NC}"
    npm run dev
else
    echo -e "${YELLOW}Node.js não encontrado localmente. Usando Docker...${NC}"
    echo -e "${BLUE}Iniciando frontend via Docker...${NC}"

    docker run --rm -it \
        -u "$(id -u):$(id -g)" \
        -v "$(pwd):/app" \
        -w /app \
        -p 3000:3000 \
        --network host \
        node:20-alpine \
        npm run dev
fi
