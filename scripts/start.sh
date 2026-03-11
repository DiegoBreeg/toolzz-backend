#!/bin/bash

# =============================================================================
# Chat API - Script para Iniciar Todos os Serviços
# =============================================================================

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}Iniciando serviços...${NC}"

cd "$PROJECT_DIR"

# Iniciar containers do backend
./vendor/bin/sail up -d

echo ""
echo -e "${GREEN}✅ Backend iniciado${NC}"
echo ""
echo "Serviços disponíveis:"
echo "  • API:          http://localhost/api"
echo "  • Swagger:      http://localhost/api/documentation"
echo "  • WebSocket:    ws://localhost:8080"
echo "  • Mailpit:      http://localhost:8025"
echo ""
echo "Para iniciar o frontend:"
echo "  ./scripts/start-frontend.sh"
echo "  ou: cd frontend && npm run dev"
