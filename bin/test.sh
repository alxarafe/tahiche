#!/bin/bash
# ─────────────────────────────────────────────────────────────
# Tahiche — Ejecutar tests PHPUnit
# ─────────────────────────────────────────────────────────────

set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${CYAN}Tahiche — Ejecutando PHPUnit${NC}"
echo "─────────────────────────────────────────"

# Intentar dentro del contenedor Docker o localmente
if docker ps --format '{{.Names}}' | grep -q "^tahiche_php$"; then
    echo -e "  ${GREEN}✔${NC} Contenedor tahiche_php en ejecución. Lanzando tests..."
    docker exec tahiche_php ./vendor/bin/phpunit "$@"
elif [ -f "$PROJECT_DIR/vendor/bin/phpunit" ]; then
    echo -e "  ${YELLOW}⚠${NC} Sin contenedor Docker. Ejecutando localmente..."
    "$PROJECT_DIR/vendor/bin/phpunit" --configuration "$PROJECT_DIR/phpunit.xml" "$@"
else
    echo "Error: No se encuentra phpunit. Instálalo con:"
    echo "  composer require --dev phpunit/phpunit"
    exit 1
fi
