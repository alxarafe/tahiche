#!/bin/bash
# ─────────────────────────────────────────────────────────────
# Tahiche — Inicio de contenedores de desarrollo
# ─────────────────────────────────────────────────────────────

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

# Colores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

clear
echo -e "${CYAN}Tahiche — Inicio de contenedores${NC}"
echo "─────────────────────────────────────────"

# Verificar si .env existe
if [ ! -f "$PROJECT_DIR/.env" ]; then
    echo -e "${YELLOW}Aviso: No se encuentra el archivo .env${NC}"
    if [ -f "$PROJECT_DIR/.env.example" ]; then
        echo "  Copiando .env.example a .env..."
        cp "$PROJECT_DIR/.env.example" "$PROJECT_DIR/.env"
    fi
fi

# Cargar variables de entorno
if [ -f "$PROJECT_DIR/.env" ]; then
    export $(grep -v '^#' "$PROJECT_DIR/.env" | grep -v '^$' | xargs)
fi

echo -e "Proyecto: ${GREEN}${PROJECT_NAME:-tahiche}${NC}"
echo ""

# Verificar contenedores activos
if docker compose -f "$PROJECT_DIR/docker-compose.yml" ps --services --filter "status=running" 2>/dev/null | grep -q .; then
    echo -e "${YELLOW}Los contenedores ya están en ejecución.${NC}"
else
    echo "Iniciando contenedores con docker compose..."
    docker compose -f "$PROJECT_DIR/docker-compose.yml" up -d --build
fi

echo ""
echo -e "${GREEN}Entorno listo:${NC}"
echo -e "  - Web App:    ${CYAN}http://localhost:${HTTP_PORT:-8081}${NC}"
echo -e "  - PhpMyAdmin: ${CYAN}http://localhost:${PHPMYADMIN_PORT:-9081}${NC}"
echo ""
echo -e "Para ver el estado: ${YELLOW}./bin/check_status.sh${NC}"
echo ""
