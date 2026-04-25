# Migración a PDO — Eliminación de mysqli y pg_*

> **Fase**: 1  
> **Prioridad**: Alta  
> **Riesgo**: 🟡 Medio

---

## Estado actual

### Motores existentes

```
Core/Base/DataBase/
├── DataBaseEngine.php       # Clase abstracta base
├── DataBaseQueries.php      # Interface de consultas SQL
├── DataBaseWhere.php        # Constructor de WHERE (legacy)
├── MysqlEngine.php          # ⚠️ Usa ext-mysqli directamente
├── MysqlQueries.php         # SQL específico de MySQL
├── PostgresqlEngine.php     # ⚠️ Usa ext-pgsql (pg_*) directamente
├── PostgresqlQueries.php    # SQL específico de PostgreSQL
└── PdoEngine.php            # ✅ Usa PDO via MysqlPdoConnection
```

### Infraestructura moderna

```
src/Infrastructure/Database/
├── DatabaseConnectionInterface.php   # Contrato limpio
└── MysqlPdoConnection.php           # Implementación PDO para MySQL
```

### Configuración actual
```php
// config.php
define('FS_DB_TYPE', 'pdo-mysql');  // Ya usa PDO ✅
```

### Selector de motor (DataBase.php)
```php
switch (self::$type) {
    case 'postgresql':     → PostgresqlEngine    // ⚠️ Legacy
    case 'pdo-mysql':
    case 'pdo':            → PdoEngine           // ✅ Activo
    default:               → MysqlEngine          // ⚠️ Legacy
}
```

## Plan de migración

### Paso 1: Crear `PostgresqlPdoConnection`

Crear `src/Infrastructure/Database/PostgresqlPdoConnection.php` que implemente `DatabaseConnectionInterface` con DSN de PostgreSQL:

```php
class PostgresqlPdoConnection implements DatabaseConnectionInterface
{
    public function __construct(string $dsn, string $username, string $password)
    {
        // DSN: pgsql:host=localhost;port=5432;dbname=tahiche
    }
    
    public function escapeColumn(string $column): string
    {
        // PostgreSQL usa comillas dobles: "column"
        return '"' . $column . '"';
    }
    
    // ... mismos métodos que MysqlPdoConnection
}
```

### Paso 2: Hacer `PdoEngine` dinámico

Actualmente `PdoEngine` hardcodea `MysqlQueries` y `MysqlPdoConnection`. Debe detectar el driver y usar la implementación correcta:

```php
class PdoEngine extends DataBaseEngine
{
    private DataBaseQueries $utilsSQL;
    private DatabaseConnectionInterface $connection;
    private string $driver;

    public function __construct()
    {
        parent::__construct();
        $this->driver = $this->detectDriver();
        $this->utilsSQL = match ($this->driver) {
            'pgsql' => new PostgresqlQueries(),
            default => new MysqlQueries(),
        };
    }

    public function connect(&$error)
    {
        $dsn = match ($this->driver) {
            'pgsql' => sprintf('pgsql:host=%s;port=%d;dbname=%s', ...),
            default => sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', ...),
        };
        
        $connectionClass = match ($this->driver) {
            'pgsql' => PostgresqlPdoConnection::class,
            default => MysqlPdoConnection::class,
        };
        
        $this->connection = new $connectionClass($dsn, FS_DB_USER, FS_DB_PASS);
        $this->connection->connect();
        return $this->connection;
    }

    private function detectDriver(): string
    {
        $type = strtolower(Tools::config('db_type', 'mysql'));
        return match (true) {
            str_contains($type, 'pgsql'), str_contains($type, 'postgres') => 'pgsql',
            default => 'mysql',
        };
    }
    
    // Métodos que difieren por driver:
    public function random(): string
    {
        return $this->driver === 'pgsql' ? 'RANDOM()' : 'RAND()';
    }
    
    public function castInteger($link, $column): string
    {
        $escaped = $this->escapeColumn($link, $column);
        return $this->driver === 'pgsql' 
            ? "CAST({$escaped} AS integer)" 
            : "CAST({$escaped} AS unsigned)";
    }
}
```

### Paso 3: Actualizar `DataBase.php`

```php
public function __construct()
{
    if (Tools::config('db_name') && self::$link === null) {
        self::$miniLog = new MiniLog(self::CHANNEL);
        self::$type = strtolower(Tools::config('db_type'));
        // Siempre PDO, sin importar el tipo configurado
        self::$engine = new PdoEngine();
    }
}
```

### Paso 4: Actualizar valores de configuración

| Valor antiguo | Valor nuevo | Comportamiento |
|---------------|-------------|----------------|
| `mysql` | `mysql` | PDO con driver MySQL |
| `pdo-mysql` | `mysql` | Alias, PDO MySQL |
| `pdo` | `mysql` | Alias, PDO MySQL |
| `postgresql` | `pgsql` | PDO con driver PostgreSQL |

Mantener compatibilidad con los valores antiguos en `PdoEngine::detectDriver()`.

### Paso 5: Eliminar motores legacy

Una vez que todos los tests pasen con PdoEngine:

1. Eliminar `Core/Base/DataBase/MysqlEngine.php`
2. Eliminar `Core/Base/DataBase/PostgresqlEngine.php`
3. Limpiar imports en `Core/Base/DataBase.php`
4. Eliminar `ext-mysqli` de `composer.json` (si no lo usan otros paquetes)

### Paso 6: Actualizar `composer.json`

```json
"require": {
    "ext-pdo": "*",
    "ext-pdo_mysql": "*",
    // ext-pdo_pgsql es opcional, solo si se usa PostgreSQL
}
```

## Diferencias clave MySQL vs PostgreSQL en PDO

| Funcionalidad | MySQL PDO | PostgreSQL PDO |
|--------------|-----------|----------------|
| Escape columna | `` `column` `` | `"column"` |
| Random | `RAND()` | `RANDOM()` |
| Cast integer | `CAST(x AS unsigned)` | `CAST(x AS integer)` |
| Boolean | `tinyint(1)` | `boolean` nativo |
| Auto-increment | `AUTO_INCREMENT` | `SERIAL` |
| Last insert ID | `PDO::lastInsertId()` | Secuencias, `RETURNING` |
| REGEXP | `REGEXP` | `~` |
| Date style | `Y-m-d` nativo | Requiere `SET DATESTYLE` |

Estas diferencias se manejan en `PostgresqlQueries` vs `MysqlQueries` (que ya existen) y en la `DatabaseConnectionInterface`.

## Archivos afectados

| Archivo | Cambio |
|---------|--------|
| `src/Infrastructure/Database/PostgresqlPdoConnection.php` | **NUEVO** |
| `Core/Base/DataBase/PdoEngine.php` | Modificar: soporte dual MySQL/PostgreSQL |
| `Core/Base/DataBase.php` | Modificar: eliminar switch, siempre PdoEngine |
| `Core/Base/DataBase/MysqlEngine.php` | **ELIMINAR** |
| `Core/Base/DataBase/PostgresqlEngine.php` | **ELIMINAR** |
| `Core/Controller/Installer.php` | Revisar: referencias a mysqli/pg |
| `Core/DbUpdater.php` | Revisar: posibles usos directos |
| `composer.json` | Actualizar ext requirements |

## Tests necesarios

- [ ] Conexión PDO MySQL — crear, leer, actualizar, eliminar
- [ ] Conexión PDO PostgreSQL — crear, leer, actualizar, eliminar
- [ ] Transacciones — begin, commit, rollback
- [ ] Escape de strings y columnas
- [ ] Listado de tablas
- [ ] Queries SQL específicos (MysqlQueries vs PostgresqlQueries)
- [ ] Migración de esquema (`DbUpdater`)
- [ ] Compatibilidad con plugins que usan `DataBase` directamente

## Checklist

- [ ] Crear `PostgresqlPdoConnection`
- [ ] Actualizar `PdoEngine` con detección de driver
- [ ] Actualizar `DataBase.php` — siempre PdoEngine
- [ ] Tests con MySQL
- [ ] Tests con PostgreSQL (requiere Docker con PG)
- [ ] Eliminar `MysqlEngine.php`
- [ ] Eliminar `PostgresqlEngine.php`
- [ ] Actualizar `composer.json`
- [ ] Actualizar `Installer.php` si es necesario
- [ ] Verificar que StockAvanzado y otros plugins funcionan
