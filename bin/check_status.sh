#!/bin/bash
# ─────────────────────────────────────────────────────────────
# Tahiche — Estado del sistema
# ─────────────────────────────────────────────────────────────

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

# Colores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BLUE='\033[0;34m'
NC='\033[0m'

# Cargar variables de entorno
if [ -f "$PROJECT_DIR/.env" ]; then
    export $(grep -v '^#' "$PROJECT_DIR/.env" | grep -v '^$' | xargs)
fi

clear
echo -e "${CYAN}Tahiche — Estado del Sistema${NC}"
echo "─────────────────────────────────────────"

# 1. Repositorios
echo -e "${BLUE}[Repositorios]${NC}"
if [ -d "$PROJECT_DIR/.git" ]; then
    BRANCH=$(git -C "$PROJECT_DIR" rev-parse --abbrev-ref HEAD)
    COMMIT=$(git -C "$PROJECT_DIR" log --oneline -1)
    echo -e "  Tahiche:          ${GREEN}OK${NC} (rama: $BRANCH)"
    echo -e "                    ${CYAN}$COMMIT${NC}"
else
    echo -e "  Tahiche:          ${RED}No es un repo git${NC}"
fi

# Alxarafe framework
ALXARAFE_DIR="${PROJECT_DIR}/../alxarafe"
if [ -d "$ALXARAFE_DIR/.git" ]; then
    A_BRANCH=$(git -C "$ALXARAFE_DIR" rev-parse --abbrev-ref HEAD)
    echo -e "  Alxarafe:         ${GREEN}OK${NC} (rama: $A_BRANCH)"
else
    echo -e "  Alxarafe:         ${YELLOW}No detectado como repo git${NC}"
fi

# FacturaScripts
FS_DIR="${FACTURASCRIPTS_PATH:-$PROJECT_DIR/../facturascripts}"
if [ -d "$FS_DIR" ]; then
    if [ -d "$FS_DIR/.git" ]; then
        FS_BRANCH=$(git -C "$FS_DIR" rev-parse --abbrev-ref HEAD)
        echo -e "  FacturaScripts:   ${GREEN}OK${NC} (rama: $FS_BRANCH)"
    else
        echo -e "  FacturaScripts:   ${GREEN}OK${NC} (sin git)"
    fi
else
    echo -e "  FacturaScripts:   ${YELLOW}No detectado${NC} (opcional, para API tests)"
fi

echo ""

# 2. Docker - Desarrollo
echo -e "${BLUE}[Docker — Desarrollo]${NC}"
if docker compose -f "$PROJECT_DIR/docker-compose.yml" ps --services --filter "status=running" 2>/dev/null | grep -q .; then
    echo -e "  Estado:   ${GREEN}En ejecución${NC}"
    echo -e "  Web App:  ${CYAN}http://localhost:${HTTP_PORT:-8081}${NC}"
    echo -e "  PMA:      ${CYAN}http://localhost:${PHPMYADMIN_PORT:-9081}${NC}"
    echo -e "  MariaDB:  ${CYAN}localhost:${MARIADB_PORT:-3399}${NC}"
else
    echo -e "  Estado:   ${RED}Parado${NC}"
    echo -e "  Arrancar: ${YELLOW}./bin/docker_start.sh${NC}"
fi

echo ""

# 3. Docker - API Test
echo -e "${BLUE}[Docker — API Test (Comparación)]${NC}"
if [ -f "$PROJECT_DIR/docker-compose.api-test.yml" ]; then
    if docker compose -f "$PROJECT_DIR/docker-compose.api-test.yml" ps --services --filter "status=running" 2>/dev/null | grep -q .; then
        echo -e "  Estado:         ${GREEN}En ejecución${NC}"
        echo -e "  FacturaScripts: ${CYAN}http://localhost:${APITEST_FS_PORT:-8070}${NC}"
        echo -e "  Tahiche:        ${CYAN}http://localhost:${APITEST_TAHICHE_PORT:-8071}${NC}"
    else
        echo -e "  Estado:   ${RED}Parado${NC}"
        echo -e "  Arrancar: ${YELLOW}./bin/api_compare.sh up${NC}"
    fi
else
    echo -e "  ${YELLOW}docker-compose.api-test.yml no encontrado${NC}"
fi

echo ""

# 4. Módulos migrados
echo -e "${BLUE}[Módulos migrados]${NC}"
MODULE_COUNT=$(find "$PROJECT_DIR/Modules" -mindepth 1 -maxdepth 1 -type d 2>/dev/null | wc -l)
CONTROLLER_COUNT=$(find "$PROJECT_DIR/Modules" -name "*Controller.php" 2>/dev/null | wc -l)
MODEL_COUNT=$(find "$PROJECT_DIR/Modules" -name "*.php" -path "*/Model/*" 2>/dev/null | wc -l)
echo -e "  Módulos:       ${GREEN}$MODULE_COUNT${NC}"
echo -e "  Controladores: ${GREEN}$CONTROLLER_COUNT${NC}"
echo -e "  Modelos:       ${GREEN}$MODEL_COUNT${NC}"

# Dinamic legacy
DINAMIC_COUNT=$(find "$PROJECT_DIR/Dinamic/Model" -name "*.php" 2>/dev/null | wc -l)
echo -e "  Dinamic/Model: ${YELLOW}$DINAMIC_COUNT${NC} (legacy, pendientes de migrar)"

echo ""

# 5. Dependencias
echo -e "${BLUE}[Dependencias]${NC}"
if [ -f "$PROJECT_DIR/composer.lock" ]; then
    ALXARAFE_VER=$(grep -A1 '"alxarafe/alxarafe"' "$PROJECT_DIR/composer.lock" | grep '"version"' | head -1 | sed 's/.*: "//;s/".*//')
    echo -e "  alxarafe/alxarafe: ${GREEN}${ALXARAFE_VER:-desconocida}${NC}"
fi

echo ""
echo "─────────────────────────────────────────"
echo -e "Usa ${CYAN}./bin/api_compare.sh help${NC} para opciones de API test."
echo ""
