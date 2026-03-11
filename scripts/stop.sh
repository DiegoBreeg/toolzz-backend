#!/bin/bash

# =============================================================================
# Chat API - Script para Parar Todos os Serviços
# =============================================================================

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}Parando serviços...${NC}"

cd "$PROJECT_DIR"

# Parar containers do backend
./vendor/bin/sail down

echo ""
echo -e "${GREEN}✅ Todos os serviços foram parados${NC}"
