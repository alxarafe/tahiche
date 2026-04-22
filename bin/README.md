# Tahiche — bin/ Scripts

Directorio de scripts de automatización para el proyecto Tahiche.

## Docker

| Script | Descripción |
|--------|-------------|
| `docker_start.sh` | Arranca los contenedores de desarrollo |
| `docker_stop.sh` | Detiene los contenedores (datos preservados) |

## Calidad de Código

| Script | Descripción |
|--------|-------------|
| `check_standards.sh` | Verifica estándares PSR-12 con PHPCS |
| `test.sh` | Ejecuta tests PHPUnit (Unit + Feature) |
| `ci_local.sh` | Pipeline CI completo: PHPCS + PHPUnit |

## API Testing

| Script | Descripción |
|--------|-------------|
| `api_compare.sh` | Tests comparativos FacturaScripts vs Tahiche con Bruno |

## Estado

| Script | Descripción |
|--------|-------------|
| `check_status.sh` | Muestra el estado de repos, Docker, módulos y dependencias |

## Uso rápido

```bash
# Desarrollo
./bin/docker_start.sh      # Arrancar
./bin/docker_stop.sh       # Parar
./bin/check_status.sh      # Estado general

# Calidad
./bin/check_standards.sh   # Solo PHPCS
./bin/test.sh              # Solo PHPUnit
./bin/ci_local.sh          # Todo junto

# API
./bin/api_compare.sh help  # Ver opciones
./bin/api_compare.sh all   # Ejecutar comparación completa
```
