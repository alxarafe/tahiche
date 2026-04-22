#!/bin/bash
# ─────────────────────────────────────────────────────────────
# Tahiche — Detener contenedores de desarrollo
# ─────────────────────────────────────────────────────────────

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

CYAN='\033[0;36m'
GREEN='\033[0;32m'
NC='\033[0m'

echo -e "${CYAN}Tahiche — Deteniendo contenedores${NC}"
echo "─────────────────────────────────────────"

if [ -f "$PROJECT_DIR/docker-compose.yml" ]; then
    docker compose -f "$PROJECT_DIR/docker-compose.yml" down
    echo ""
    echo -e "${GREEN}Contenedores detenidos. Datos preservados.${NC}"
else
    echo "No se encontró docker-compose.yml en $PROJECT_DIR"
fi
echo ""
