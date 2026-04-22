#!/bin/bash
# ─────────────────────────────────────────────────────────────
# Tahiche — Análisis estático (PHPStan)
# ─────────────────────────────────────────────────────────────

set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

GREEN='\033[0;32m'
RED='\033[0;31m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${CYAN}Tahiche — Análisis estático (PHPStan)${NC}"
echo "─────────────────────────────────────────"

# Intentar dentro del contenedor Docker o localmente
if docker ps --format '{{.Names}}' | grep -q "^tahiche_php$"; then
    echo "  Ejecutando PHPStan dentro del contenedor..."
    docker exec -e PHPSTAN_RUNNING=1 tahiche_php ./vendor/bin/phpstan analyse -c phpstan.neon --memory-limit=1G --no-progress
elif [ -f "$PROJECT_DIR/vendor/bin/phpstan" ]; then
    echo "  Ejecutando PHPStan localmente..."
    PHPSTAN_RUNNING=1 "$PROJECT_DIR/vendor/bin/phpstan" analyse -c "$PROJECT_DIR/phpstan.neon" --memory-limit=1G --no-progress
else
    echo -e "${RED}Error: No se encuentra phpstan. Instálalo con:${NC}"
    echo "  composer require --dev phpstan/phpstan"
    exit 1
fi

echo ""
echo -e "${GREEN}✅ Análisis estático completado.${NC}"
echo ""
