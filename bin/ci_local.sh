#!/bin/bash
# ─────────────────────────────────────────────────────────────
# Tahiche — CI Local (ejecuta todas las verificaciones)
#
# Equivale a lo que se ejecutaría en un pipeline de CI:
#   1. PHPCS (estándares de código)
#   2. PHPUnit (tests unitarios y de integración)
# ─────────────────────────────────────────────────────────────

set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

GREEN='\033[0;32m'
RED='\033[0;31m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

echo ""
echo -e "${BOLD}${CYAN}Tahiche — Pipeline CI Local${NC}"
echo "═══════════════════════════════════════"
echo ""

FAILED=0

# 1. PHPCS
echo -e "${BOLD}=== 1. Verificación de Estándares (PHPCS) ===${NC}"
if bash "$SCRIPT_DIR/check_standards.sh"; then
    echo -e "${GREEN}✅ Estándares OK${NC}"
else
    echo -e "${RED}❌ Errores de estándares detectados${NC}"
    FAILED=1
fi
echo ""

# 2. PHPStan
echo -e "${BOLD}=== 2. Análisis Estático (PHPStan) ===${NC}"
if bash "$SCRIPT_DIR/check_types.sh"; then
    echo -e "${GREEN}✅ PHPStan OK${NC}"
else
    echo -e "${RED}❌ Errores de PHPStan detectados${NC}"
    FAILED=1
fi
echo ""

# 3. PHPUnit
echo -e "${BOLD}=== 3. Tests (PHPUnit) ===${NC}"
if bash "$SCRIPT_DIR/test.sh" --testsuite Modern; then
    echo -e "${GREEN}✅ Tests OK${NC}"
else
    echo -e "${RED}❌ Tests fallidos${NC}"
    FAILED=1
fi
echo ""

# Resumen
echo "═══════════════════════════════════════"
if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✅ Todas las verificaciones pasaron correctamente.${NC}"
else
    echo -e "${RED}❌ Algunas verificaciones fallaron. Revisa los errores arriba.${NC}"
    exit 1
fi
echo ""
