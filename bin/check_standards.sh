#!/bin/bash
# ─────────────────────────────────────────────────────────────
# Tahiche — Verificación de estándares de código (PHPCS)
# ─────────────────────────────────────────────────────────────

set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

GREEN='\033[0;32m'
RED='\033[0;31m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${CYAN}Tahiche — Verificación de estándares de código${NC}"
echo "─────────────────────────────────────────"

# Intentar ejecutar dentro del contenedor Docker, o localmente si no hay contenedor
if docker ps --format '{{.Names}}' | grep -q "^tahiche_php$"; then
    echo "  Ejecutando PHPCS dentro del contenedor..."
    docker exec tahiche_php ./vendor/bin/phpcs \
        --tab-width=4 \
        --encoding=utf-8 \
        --standard=phpcs.xml \
        Core Modules src -s
elif [ -f "$PROJECT_DIR/vendor/bin/phpcs" ]; then
    echo "  Ejecutando PHPCS localmente..."
    "$PROJECT_DIR/vendor/bin/phpcs" \
        --tab-width=4 \
        --encoding=utf-8 \
        --standard="$PROJECT_DIR/phpcs.xml" \
        "$PROJECT_DIR/Core" "$PROJECT_DIR/Modules" "$PROJECT_DIR/src" -s
else
    echo -e "${RED}Error: No se encuentra phpcs. Instálalo con:${NC}"
    echo "  composer require --dev squizlabs/php_codesniffer"
    exit 1
fi

echo ""
echo -e "${GREEN}✅ Verificación de estándares completada.${NC}"
echo ""
