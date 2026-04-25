# Estado actual del proyecto Tahiche

> Auditoría técnica del estado del repositorio a fecha 2026-04-25

---

## Estructura de directorios

```
tahiche/
├── Core/                    # Legacy FacturaScripts (966 archivos)
│   ├── Assets/              # CSS, JS, imágenes del legacy
│   ├── Base/                # DataBase, Controller base, MiniLog
│   │   └── DataBase/        # MysqlEngine, PostgresqlEngine, PdoEngine
│   ├── Controller/          # 111 controladores legacy
│   ├── Model/               # 85+ modelos legacy
│   ├── Lib/                 # Librerías (Calculator, Export, ExtendedController, Widgets...)
│   ├── Template/            # Clases base abstractas (InitClass, etc.)
│   ├── View/                # Plantillas Twig
│   ├── XMLView/             # Definiciones XML de vistas
│   ├── Kernel.php           # Router/dispatcher legacy
│   └── Plugins.php          # Gestor de plugins
│
├── Dinamic/                 # Overlay dinámico (1129 archivos)
│   ├── Controller/          # Proxies generados para controladores
│   ├── Model/               # Proxies generados para modelos
│   ├── Lib/                 # Proxies generados para librerías
│   ├── Assets/              # Assets procesados
│   └── ...                  # Mirrors de Core/ con clases generadas
│
├── Modules/                 # Nuevos módulos modernos (88 archivos PHP)
│   ├── Accounting/          # Contabilidad (7 controllers, models)
│   ├── Admin/               # Administración (11 controllers)
│   ├── Crm/                 # CRM (4 controllers)
│   ├── Sales/               # Ventas (1 controller + migración)
│   └── Trading/             # Comercio (21 controllers, 20 models)
│
├── Plugins/                 # Plugins legacy
│   └── StockAvanzado/       # Plugin ejemplo: stock avanzado
│
├── src/                     # Capa moderna (Strangler Fig)
│   └── Infrastructure/
│       ├── Adapter/         # TahicheRepository, TahicheQuery, TahicheTranslator
│       ├── Base/            # Clases base modernas
│       ├── Component/       # Componentes UI
│       ├── Database/        # MysqlPdoConnection, DatabaseConnectionInterface
│       ├── Http/            # Kernel hexagonal, ResourceController, LegacyBridgeTrait
│       └── View/            # Vistas modernas
│
├── public/                  # Punto de entrada web
│   ├── index.php            # Punto de entrada principal (✓ ya existe)
│   ├── .htaccess            # Rewrite rules
│   ├── Assets/              # Assets públicos
│   ├── alxarafe/            # Assets del framework Alxarafe
│   ├── js/                  # JavaScript compilado
│   └── themes/              # Temas visuales
│
├── config/                  # Configuración
│   └── config.json          # Configuración JSON
├── config.php               # Configuración legacy (define constantes FS_*)
├── .env                     # Variables de entorno Docker
├── docker-compose.yml       # Servicios Docker
└── composer.json            # Dependencias PHP
```

## Punto de entrada actual

### Docker (Nginx)
- **Document root**: `/var/www/html/public` ✅ (ya apunta a public)
- **Entry point**: `public/index.php` ✅

### `public/index.php`
```php
require_once __DIR__ . '/../vendor/autoload.php';
define('APP_PATH', dirname(__DIR__));
define('BASE_PATH', __DIR__);
$kernel = new \Tahiche\Infrastructure\Http\Kernel();
$kernel->handle($url);
```

### Archivos en el root que podrían exponerse
- `config.php` — credenciales DB en texto plano ⚠️
- `composer.json`, `composer.lock` — información de dependencias ⚠️
- `.env` — variables de entorno ⚠️
- No hay `index.php` en root ✅ (bien, pero falta protección para entornos sin proxy)

## Capa de base de datos

### Motores disponibles en `Core/Base/DataBase/`

| Motor | Archivo | Driver nativo | Estado |
|-------|---------|--------------|--------|
| MySQL | `MysqlEngine.php` | `mysqli` | ❌ Eliminado |
| PostgreSQL | `PostgresqlEngine.php` | `pg_*` | ❌ Eliminado |
| PDO | `PdoEngine.php` | PDO (`FS_DB_TYPE`) | ✅ Único motor en uso |

### Selección de motor (`Core/Base/DataBase.php`)
```php
switch (self::$type) {
    case 'postgresql':
    case 'pdo-mysql':
    case 'pdo':
    default:
        $this->engine = new PdoEngine(); // Motor unificado
        break;
}
```

### Configuración actual
- `config.php`: `FS_DB_TYPE = 'pdo-mysql'` → ya usa PDO ✅
- Pero los motores legacy siguen presentes y funcionales

## Carpeta Dinamic

La carpeta `Dinamic/` contiene **1129 archivos** que son "proxies" generados por el sistema de deploy de plugins. Su contenido replica la estructura de `Core/` pero con clases que extienden las originales, permitiendo que los plugins las sobrescriban.

### Ejemplo de clase Dinamic
```php
// Dinamic/Controller/EditProducto.php
namespace FacturaScripts\Dinamic\Controller;
class EditProducto extends \FacturaScripts\Core\Controller\EditProducto {}
```

### Dependencias de Dinamic
- **111 controladores** en `Core/Controller/` → rutas se resuelven vía `Dinamic/Controller/`
- **85+ modelos** — muchos archivos hacen `use FacturaScripts\Dinamic\Model\*`
- **Librerías** — `Dinamic/Lib/` incluye filtros, widgets, PDF, email...
- El Kernel legacy busca primero en `Dinamic\Controller\`, luego en `Core\Controller\`

## Sistema de plugins

### Estructura de un plugin legacy (ej: StockAvanzado)
```
Plugins/StockAvanzado/
├── facturascripts.ini       # Metadatos (nombre, versión, compatibilidad)
├── Init.php                 # Punto de entrada: init(), update(), uninstall()
├── Controller/              # Controladores propios
├── Model/                   # Modelos propios
├── Extension/               # Extensiones a controladores/modelos del Core
├── Lib/                     # Librerías propias
├── View/                    # Plantillas Twig
├── XMLView/                 # Definiciones XML de vistas
├── Table/                   # Definiciones XML de tablas
├── Translation/             # Traducciones
└── Worker/                  # Workers de cola
```

### Mecanismo de extensiones
Los plugins extienden controladores del Core mediante el patrón `Extension/Controller/EditProducto`:
```php
// Extension/Controller/EditProducto.php
$this->addExtension('createViews', function () {
    $this->addListView('ListMovimientoStock', 'MovimientoStock', 'stock-movements');
});
```

## Módulos modernos (Modules/)

### Inventario de módulos

| Módulo | Controllers | Models | Migraciones | Estado |
|--------|------------|--------|-------------|--------|
| **Accounting** | 7 | 0 | 0 | Controladores creados |
| **Admin** | 11 | 0 | 0 | Controladores creados |
| **Crm** | 4 | 0 | 0 | Controladores creados |
| **Sales** | 1 | 2 | 1 | Parcialmente implementado |
| **Trading** | 21 | 20 | 0 | Más avanzado, con modelos |

### Patrón de controlador moderno (ej: ProductsController)
```php
class ProductsController extends ResourceController
{
    use LegacyBridgeTrait;    // Bridge con plugins legacy
    
    protected function getModelClassName(): string { return Product::class; }
    public function getListColumns(): array { return ['referencia', 'descripcion', ...]; }
    public function getEditFields(): array {
        // Campos declarativos con componentes del resource-controller
        return ['producto' => ['label' => ..., 'fields' => [
            new Text('referencia', ...),
            new Select('codfamilia', ..., $familias),
            ...
        ]]];
    }
}
```

## Paquetes Alxarafe instalados

| Paquete | Versión | Descripción |
|---------|---------|-------------|
| `alxarafe/resource-controller` | 0.2.1 | Controladores CRUD declarativos |
| `alxarafe/resource-pdo` | 0.1.1 | Repositorio PDO |

### Componentes disponibles en resource-controller

**Campos**: Boolean, Date, DateTime, Decimal, Hidden, Icon, Image, Integer, RelationList, Select, Select2, StaticText, Text, Textarea, Time

**Contenedores**: Panel, Tab, TabGroup, Row, Separator, HtmlContent

**Contratos**: RepositoryContract, TransactionContract, QueryContract, TranslatorContract, MessageBagContract, HookContract, RendererContract, RelationContract

## Docker

| Servicio | Imagen | Puerto |
|----------|--------|--------|
| Nginx | `nginx:alpine` | 8086 → 80 |
| PHP | Custom (8.3) | 9000 (FastCGI) |
| MariaDB | `mariadb:10.11` | 3406 → 3306 |
| Node | `node:20-slim` | — (webpack watch) |
| phpMyAdmin | `phpmyadmin` | 9086 → 80 |

## Resumen de riesgos y deuda técnica resuelta

| Riesgo | Estado | Descripción |
|--------|-----------|-------------|
| Dinamic masivo | 🟡 En proceso | 1129 archivos generados. Desacoplándose paulatinamente mediante Modules y pipes nativos. |
| Drivers DB duplicados | ✅ Resuelto | Se eliminaron `MysqlEngine` y `PostgresqlEngine`. Solo se usa PDO activamente mediante `PdoEngine`. |
| Core acoplado | 🟡 En proceso | Controladores de negocio migrándose a la arquitectura hexagonal (Modules/). |
| Imagen FS | ✅ Resuelto | Implementada la identidad visual Tahiche ERP. |

## Progreso de Sprints (Completados)
- **Sprint 1**: Hardening e Identidad Visual (Logos, CSS Tahiche).
- **Sprint 2**: Unificación de la capa de DB a PDO (Eliminación de engines legacy, soporte unificado MySQL/PostgreSQL).
- **Sprint 3**: Nuevos Componentes UI en ResourceController (`Autocomplete`, `Number`, `Money`, `Percentage`, `Barcode`).
- **Sprint 4**: Módulo Barcodes completamente integrado en la arquitectura, con inyección nativa al Core mediante el nuevo hook `loadFromCodeBefore` implementado en `ModelClass`.
