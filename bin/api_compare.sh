#!/bin/bash
# ─────────────────────────────────────────────────────────────
# Tests de Compatibilidad API: FacturaScripts vs Tahiche
#
# Usa una copia de FacturaScripts clonada en ../facturascripts/
# y ejecuta los mismos tests Bruno contra ambas APIs para
# garantizar retrocompatibilidad.
#
# Uso:
#   ./bin/api_compare.sh up        → Levantar contenedores
#   ./bin/api_compare.sh test      → Ejecutar tests comparativos
#   ./bin/api_compare.sh update    → git pull de FacturaScripts
#   ./bin/api_compare.sh clean     → Limpiar datos de test
#   ./bin/api_compare.sh reset     → BD aséptica (destruir y recrear)
#   ./bin/api_compare.sh down      → Apagar contenedores
#   ./bin/api_compare.sh all       → clean + up + test
# ─────────────────────────────────────────────────────────────

set -e
set -o pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
COMPOSE_FILE="$PROJECT_DIR/docker-compose.api-test.yml"
COLLECTION_DIR="$PROJECT_DIR/api"
RESULTS_DIR="$PROJECT_DIR/var/api-test-results"

# Cargar variables de entorno desde .env si existe
if [ -f "$PROJECT_DIR/.env" ]; then
    export $(grep -v '^#' "$PROJECT_DIR/.env" | grep -v '^$' | xargs)
fi

FS_DIR="${FACTURASCRIPTS_PATH:-$PROJECT_DIR/../facturascripts}"

FS_URL="http://localhost:${APITEST_FS_PORT:-8070}"
TAHICHE_URL="http://localhost:${APITEST_TAHICHE_PORT:-8071}"

# API Key — se debe configurar después de la instalación
# FacturaScripts genera una API key durante el setup
APIKEY="${APITEST_APIKEY:-}"

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

mkdir -p "$RESULTS_DIR"

# ── Funciones auxiliares ──────────────────────────────────

check_facturascripts() {
    local fatal=${1:-true}

    if [ ! -d "$FS_DIR" ]; then
        echo -e "${RED}Error: No se encuentra FacturaScripts en: ${CYAN}$FS_DIR${NC}"
        echo ""
        if [ "$fatal" = "true" ]; then
            echo "Para ejecutar esta comparación necesitas clonar FacturaScripts:"
            echo ""
            echo "  git clone git@github.com:NeoRazorX/facturascripts.git $FS_DIR"
            echo ""
            echo "O define la variable FACTURASCRIPTS_PATH en .env"
            echo ""
            exit 1
        else
            echo -e "${YELLOW}Continuando solo con Tahiche (modo test)...${NC}"
            echo ""
            return 1
        fi
    fi
    return 0
}

fs_version() {
    if [ -d "$FS_DIR/.git" ]; then
        git -C "$FS_DIR" log --oneline -1 2>/dev/null
    else
        echo "sin git"
    fi
}

tahiche_version() {
    if [ -d "$PROJECT_DIR/.git" ]; then
        git -C "$PROJECT_DIR" log --oneline -1 2>/dev/null
    else
        echo "sin git"
    fi
}

wait_for_url() {
    local url=$1
    local name=$2
    local max_wait=${3:-120}
    local elapsed=0

    printf "  Esperando a ${BLUE}%s${NC} (%s)..." "$name" "$url"
    while [ $elapsed -lt $max_wait ]; do
        if curl -s -o /dev/null -w "%{http_code}" "$url" 2>/dev/null | grep -q "^[23]"; then
            printf " ${GREEN}OK${NC} (%ds)\n" "$elapsed"
            return 0
        fi
        sleep 2
        elapsed=$((elapsed + 2))
        printf "."
    done
    printf " ${RED}TIMEOUT${NC} (%ds)\n" "$max_wait"
    return 1
}

check_apikey() {
    if [ -z "$APIKEY" ]; then
        echo ""
        echo -e "${YELLOW}╔═══════════════════════════════════════════════════════╗${NC}"
        echo -e "${YELLOW}║  ⚠  API KEY no configurada                           ║${NC}"
        echo -e "${YELLOW}╠═══════════════════════════════════════════════════════╣${NC}"
        echo -e "${YELLOW}║  Añade APITEST_APIKEY=tu_api_key a tu .env           ║${NC}"
        echo -e "${YELLOW}║                                                       ║${NC}"
        echo -e "${YELLOW}║  Pasos:                                               ║${NC}"
        echo -e "${YELLOW}║  1. Instala FacturaScripts desde ${FS_URL}${NC}"
        echo -e "${YELLOW}║  2. Ve a Admin → API Keys → Crear nueva              ║${NC}"
        echo -e "${YELLOW}║  3. Habilita 'fullaccess' y copia la key             ║${NC}"
        echo -e "${YELLOW}║  4. Añade a .env: APITEST_APIKEY=<key>               ║${NC}"
        echo -e "${YELLOW}║  5. Ejecuta: ./bin/api_compare.sh test               ║${NC}"
        echo -e "${YELLOW}╚═══════════════════════════════════════════════════════╝${NC}"
        echo ""
        return 1
    fi
    return 0
}

# ── Comandos principales ─────────────────────────────────

do_update() {
    check_facturascripts true
    echo ""
    echo "╔═══════════════════════════════════════════════════════╗"
    echo "║  Actualizar FacturaScripts (referencia)               ║"
    echo "╚═══════════════════════════════════════════════════════╝"
    echo ""

    local before
    before=$(fs_version)
    echo -e "  Antes: ${YELLOW}${before}${NC}"

    # Fetch del upstream si existe, si no, pull normal
    if git -C "$FS_DIR" remote | grep -q upstream; then
        git -C "$FS_DIR" fetch upstream 2>&1
        git -C "$FS_DIR" merge upstream/master --ff-only 2>&1
    else
        git -C "$FS_DIR" pull --ff-only 2>&1
    fi

    local after
    after=$(fs_version)
    echo -e "  Ahora: ${GREEN}${after}${NC}"

    if [ "$before" = "$after" ]; then
        echo -e "  ${CYAN}Ya estaba actualizado.${NC}"
    else
        echo -e "  ${GREEN}✅ Actualizado correctamente.${NC}"
    fi
    echo ""
}

do_up() {
    check_facturascripts true
    echo ""
    echo "╔═══════════════════════════════════════════════════════╗"
    echo "║  Levantando entorno de tests API                      ║"
    echo "╠═══════════════════════════════════════════════════════╣"
    echo -e "║  FacturaScripts: ${CYAN}${FS_URL}${NC}"
    echo -e "║  Tahiche:        ${CYAN}${TAHICHE_URL}${NC}"
    echo "╚═══════════════════════════════════════════════════════╝"
    echo ""

    # Instalar dependencias de FS si no existen
    if [ ! -d "$FS_DIR/vendor" ]; then
        echo "  Instalando dependencias de FacturaScripts..."
        docker run --rm \
            -v "$FS_DIR:/app" -w /app \
            composer:latest \
            composer install --no-interaction --no-progress 2>&1
        echo ""
    fi

    # Instalar dependencias de Tahiche si no existen
    if [ ! -d "$PROJECT_DIR/vendor" ]; then
        echo "  Instalando dependencias de Tahiche..."
        docker run --rm \
            -v "$PROJECT_DIR:/app" -w /app \
            composer:latest \
            composer install --no-interaction --no-progress 2>&1
        echo ""
    fi

    docker compose -f "$COMPOSE_FILE" up -d --build 2>&1

    echo ""
    wait_for_url "$FS_URL" "FacturaScripts" 120
    wait_for_url "$TAHICHE_URL" "Tahiche" 120

    echo ""
    echo -e "  ${GREEN}✅ Entorno levantado.${NC}"
    echo ""

    if ! check_apikey; then
        echo -e "  ${YELLOW}Instala ambos sistemas y configura la API key antes de ejecutar tests.${NC}"
    fi
    echo ""
}

do_test() {
    if ! check_apikey; then
        exit 1
    fi

    echo ""
    echo "╔═══════════════════════════════════════════════════════╗"
    echo "║  Tests de Compatibilidad API                          ║"
    echo "║  FacturaScripts (referencia) vs Tahiche (impl.)       ║"
    echo "╚═══════════════════════════════════════════════════════╝"
    echo -e "  FacturaScripts: ${CYAN}$(fs_version)${NC}"
    echo -e "  Tahiche:        ${CYAN}$(tahiche_version)${NC}"
    echo ""

    local fs_result=0
    local tahiche_result=0

    # Suites de test a ejecutar
    local SUITES=("Status" "Currencies" "Warehouses" "Families" "Carriers" "Companies" "Customers" "Products")

    # ── FacturaScripts ───────────────────────────────────
    echo "━━━ FacturaScripts (referencia) ━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    for suite in "${SUITES[@]}"; do
        echo -e "  ── ${BLUE}${suite}${NC} ──"
        if docker run --rm --network apitest_tahiche_network \
            -v "$PROJECT_DIR:/work" -w /work/api \
            node:20-alpine \
            npx --yes @usebruno/cli run "$suite" \
            --env docker-fs \
            --env-var apiKey="$APIKEY" \
            --env-var baseUrl=http://apitest_fs_nginx 2>&1 | tee "$RESULTS_DIR/fs-${suite,,}.log"; then
            echo -e "  ${suite}: ${GREEN}✅ PASS${NC}"
        else
            fs_result=1
            echo -e "  ${suite}: ${RED}❌ FAIL${NC}"
        fi
        echo ""
    done

    if [ $fs_result -eq 0 ]; then
        echo -e "  FacturaScripts: ${GREEN}✅ PASS${NC}"
    else
        echo -e "  FacturaScripts: ${RED}❌ FAIL${NC}"
    fi

    echo ""

    # ── Tahiche ──────────────────────────────────────────
    echo "━━━ Tahiche (implementación) ━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    for suite in "${SUITES[@]}"; do
        echo -e "  ── ${BLUE}${suite}${NC} ──"
        if docker run --rm --network apitest_tahiche_network \
            -v "$PROJECT_DIR:/work" -w /work/api \
            node:20-alpine \
            npx --yes @usebruno/cli run "$suite" \
            --env docker-tahiche \
            --env-var apiKey="$APIKEY" \
            --env-var baseUrl=http://apitest_tahiche_nginx 2>&1 | tee "$RESULTS_DIR/tahiche-${suite,,}.log"; then
            echo -e "  ${suite}: ${GREEN}✅ PASS${NC}"
        else
            tahiche_result=1
            echo -e "  ${suite}: ${RED}❌ FAIL${NC}"
        fi
        echo ""
    done

    if [ $tahiche_result -eq 0 ]; then
        echo -e "  Tahiche:   ${GREEN}✅ PASS${NC}"
    else
        echo -e "  Tahiche:   ${RED}❌ FAIL${NC}"
    fi

    # ── Resumen ──────────────────────────────────────────
    echo ""
    echo "╔═══════════════════════════════════════════════════════╗"
    echo "║  RESULTADO                                            ║"
    echo "╠═══════════════════════════════════════════════════════╣"
    [ $fs_result -eq 0 ] \
        && echo -e "║  FacturaScripts:  ${GREEN}✅ PASS${NC}                              ║" \
        || echo -e "║  FacturaScripts:  ${RED}❌ FAIL${NC}                              ║"
    [ $tahiche_result -eq 0 ] \
        && echo -e "║  Tahiche:         ${GREEN}✅ PASS${NC}                              ║" \
        || echo -e "║  Tahiche:         ${RED}❌ FAIL${NC}                              ║"

    if [ $fs_result -eq 0 ] && [ $tahiche_result -eq 0 ]; then
        echo -e "║  ${GREEN}★ APIs CERTIFICADAS COMO COMPATIBLES ★${NC}               ║"
    fi
    echo "╚═══════════════════════════════════════════════════════╝"

    echo ""
    echo -e "  ${CYAN}Resultados guardados en: ${RESULTS_DIR}/${NC}"
    echo ""

    return $((fs_result + tahiche_result))
}

do_reset() {
    echo ""
    echo "╔═══════════════════════════════════════════════════════╗"
    echo "║  Reset: BD aséptica (DESTRUCTIVO)                     ║"
    echo "╚═══════════════════════════════════════════════════════╝"
    echo ""
    echo "  Destruyendo volúmenes de datos..."

    docker compose -f "$COMPOSE_FILE" down -v 2>&1

    echo ""
    echo -e "  ${GREEN}✅ BD eliminada. El próximo 'up' arranca con BD limpia.${NC}"
    echo -e "  ${YELLOW}⚠  Ambos sistemas necesitarán reinstalarse (wizard).${NC}"
    echo -e "  ${YELLOW}⚠  Deberás regenerar la API key y actualizar APITEST_APIKEY en .env${NC}"
    echo ""
}

do_clean() {
    echo ""
    echo "╔═══════════════════════════════════════════════════════╗"
    echo "║  Clean: Limpieza de datos transaccionales             ║"
    echo "╚═══════════════════════════════════════════════════════╝"
    echo ""
    echo "  Purgando datos de test (divisas, familias, almacenes de test)..."

    if docker exec -i apitest_tahiche_db mariadb -u root -proot apitest_db <<'SQL' 2>/dev/null
-- Limpiar registros de test (código 'TST')
DELETE FROM divisas WHERE coddivisa = 'TST';
DELETE FROM familias WHERE codfamilia = 'TST';
DELETE FROM almacenes WHERE codalmacen = 'TST';
DELETE FROM agenciastrans WHERE codtrans = 'TST';
DELETE FROM clientes WHERE cifnif = 'B12345678';
DELETE FROM productos WHERE referencia = 'APITEST001';
DELETE FROM variantes WHERE referencia = 'APITEST001';
SQL
    then
        echo -e "  ${GREEN}✅ Datos de test purgados.${NC}"
        echo -e "  ${GREEN}✅ Configuración y API keys preservadas.${NC}"
    else
        echo -e "  ${RED}❌ Error al limpiar la base de datos.${NC}"
        echo "  Asegúrate de que los contenedores están arrancados: ./bin/api_compare.sh up"
    fi
    echo ""
}

do_down() {
    echo "  Apagando entorno de tests (datos preservados)..."
    docker compose -f "$COMPOSE_FILE" down
    echo -e "  ${CYAN}Datos preservados. Usa 'clean' para purgar o 'reset' para BD aséptica.${NC}"
}

do_status() {
    echo ""
    echo "╔═══════════════════════════════════════════════════════╗"
    echo "║  Estado del entorno API Test                           ║"
    echo "╚═══════════════════════════════════════════════════════╝"
    echo ""

    # Check containers
    echo "  Contenedores:"
    for container in apitest_fs_nginx apitest_fs_php apitest_tahiche_nginx apitest_tahiche_php apitest_tahiche_db; do
        if docker ps --format '{{.Names}}' | grep -q "^${container}$"; then
            echo -e "    ${container}: ${GREEN}running${NC}"
        else
            echo -e "    ${container}: ${RED}stopped${NC}"
        fi
    done

    echo ""

    # Check URLs
    echo "  Endpoints:"
    for url_info in "FacturaScripts:$FS_URL" "Tahiche:$TAHICHE_URL"; do
        local name="${url_info%%:*}"
        local url="${url_info#*:}"
        local code=$(curl -s -o /dev/null -w "%{http_code}" "$url" 2>/dev/null || echo "000")
        if [[ "$code" =~ ^[23] ]]; then
            echo -e "    ${name}: ${GREEN}${url} (HTTP $code)${NC}"
        else
            echo -e "    ${name}: ${RED}${url} (HTTP $code)${NC}"
        fi
    done

    echo ""

    # Check API key
    if [ -n "$APIKEY" ]; then
        echo -e "  API Key: ${GREEN}configurada${NC}"
    else
        echo -e "  API Key: ${YELLOW}no configurada (APITEST_APIKEY)${NC}"
    fi

    echo ""

    # Versions
    echo "  Versiones:"
    echo -e "    FacturaScripts: ${CYAN}$(fs_version)${NC}"
    echo -e "    Tahiche:        ${CYAN}$(tahiche_version)${NC}"
    echo ""
}

# ── Main ──────────────────────────────────────────────────

case "${1:-help}" in
    update)  do_update ;;
    up)      do_up ;;
    test)    do_test ;;
    clean)   do_clean ;;
    reset)   do_reset ;;
    down)    do_down ;;
    status)  do_status ;;
    all)
        do_clean 2>/dev/null || do_reset
        do_up
        do_test
        ;;
    *)
        echo ""
        echo -e "${BOLD}Tahiche API — Tests de Compatibilidad con FacturaScripts${NC}"
        echo ""
        echo "Uso: $0 {up|test|clean|reset|update|down|status|all}"
        echo ""
        echo "  up      Levantar FacturaScripts + Tahiche dockerizados"
        echo "  test    Ejecutar tests Bruno contra ambas APIs"
        echo "  clean   Borra datos de test pero mantiene API KEY"
        echo "  reset   Destruir BD y empezar limpio (borra todo)"
        echo "  update  git pull del repo FacturaScripts"
        echo "  down    Apagar contenedores (datos preservados)"
        echo "  status  Ver estado del entorno"
        echo "  all     clean + up + test"
        echo ""
        echo "Variables de entorno (.env):"
        echo "  FACTURASCRIPTS_PATH    Ruta al repo de FacturaScripts (def: ../facturascripts)"
        echo "  APITEST_APIKEY         API key con fullaccess (requerida para tests)"
        echo "  APITEST_FS_PORT        Puerto FacturaScripts (def: 8070)"
        echo "  APITEST_TAHICHE_PORT   Puerto Tahiche (def: 8071)"
        echo "  APITEST_DB_PORT        Puerto MariaDB (def: 3470)"
        echo ""
        echo "Estructura requerida:"
        echo "  Alxarafe/"
        echo "  ├── alxarafe/        ← Framework Alxarafe"
        echo "  ├── facturascripts/  ← Repo original NeoRazorX/facturascripts"
        echo "  └── tahiche/         ← Este proyecto"
        echo ""
        echo "Primer uso:"
        echo "  1. git clone https://github.com/NeoRazorX/facturascripts ../facturascripts"
        echo "  2. ./bin/api_compare.sh up"
        echo "  3. Instalar FacturaScripts en http://localhost:8070"
        echo "  4. Crear API key con fullaccess en Admin → API Keys"
        echo "  5. echo 'APITEST_APIKEY=<tu_key>' >> .env"
        echo "  6. ./bin/api_compare.sh test"
        echo ""
        ;;
esac
