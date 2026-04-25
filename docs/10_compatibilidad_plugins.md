# Compatibilidad con plugins existentes

> **Fase**: Transversal (aplica a todas las fases)  
> **Prioridad**: Crítica  
> **Riesgo**: 🔴 Alto

---

## Principio fundamental

> **Ningún cambio en Tahiche puede romper los plugins existentes de FacturaScripts** mientras no se dicte lo contrario. La compatibilidad es el objetivo #1 de toda refactorización.

---

## Cómo funciona el sistema de plugins de FacturaScripts

### Estructura de un plugin

```
Plugins/NombrePlugin/
├── facturascripts.ini       # Metadatos obligatorios
├── Init.php                 # Punto de entrada (InitClass)
├── Controller/              # Controladores propios
├── Model/                   # Modelos propios
├── Extension/               # Extensiones a clases del Core
│   ├── Controller/          # Hooks en controladores existentes
│   └── Model/               # Hooks en modelos existentes
├── Lib/                     # Librerías propias
├── View/                    # Plantillas Twig
├── XMLView/                 # Definiciones de vistas XML
├── Table/                   # Definiciones de tablas XML
├── Translation/             # Traducciones i18n
├── Worker/                  # Workers de cola
├── CronJob/                 # Tareas programadas
└── Data/                    # Datos iniciales (seeds)
```

### Mecanismo `Init.php`

```php
class Init extends InitClass
{
    public function init(): void
    {
        // Se ejecuta en cada request
        // Registra extensiones, rutas, workers
        $this->loadExtension(new Extension\Controller\EditProducto());
        Kernel::addRoute('/api/3/custom', 'ApiCustom', -1);
        WorkQueue::addWorker('MyWorker', 'Model.Product.Save');
    }

    public function update(): void
    {
        // Se ejecuta al activar/actualizar el plugin
        // Crea tablas, roles, datos iniciales
        new MyModel(); // Fuerza la creación de la tabla
    }

    public function uninstall(): void
    {
        // Se ejecuta al desinstalar
    }
}
```

### Mecanismo de extensiones

Las extensiones usan `ExtensionsTrait` y closures para inyectar código en controladores y modelos existentes:

```php
// Extension/Controller/EditProducto.php
namespace FacturaScripts\Plugins\MyPlugin\Extension\Controller;

use Closure;

class EditProducto
{
    public function createViews(): Closure
    {
        return function () {
            // $this es el controlador EditProducto
            $this->addListView('ListMyModel', 'MyModel', 'my-tab', 'fas fa-icon');
            $this->addSearchFields('ListMyModel', ['field1', 'field2']);
        };
    }
}
```

### Mecanismo Dinamic (overlay)

El deploy de plugins genera clases proxy en `Dinamic/` que aplican las extensiones. El routing del Kernel legacy busca primero en `Dinamic\Controller\`.

---

## Puntos de compatibilidad críticos

### 1. Namespaces `FacturaScripts\*`

Los plugins usan estos namespaces:
- `FacturaScripts\Core\*` — clases del Core
- `FacturaScripts\Dinamic\*` — proxies generados
- `FacturaScripts\Plugins\*` — sus propias clases

**Riesgo**: Si movemos clases del Core, los `use` de los plugins fallarán.

**Mitigación**: Aliases con `class_alias()`:
```php
// Cuando se mueva EditProducto a Modules/Trading:
class_alias(
    \Modules\Trading\Controller\ProductsController::class,
    \FacturaScripts\Core\Controller\EditProducto::class
);
```

### 2. Sistema de extensiones (ExtensionsTrait)

Los plugins registran extensiones como closures que se ejecutan en el contexto del controlador. Este mecanismo es fundamental y **debe mantenerse operativo**.

**Estado actual**: El `LegacyBridgeTrait` ya intercepta las extensiones de plugins legacy y las traduce a pestañas/botones del nuevo sistema.

**Riesgo**: Si eliminamos `ExtensionsTrait` del Core, los plugins no podrán registrar extensiones.

**Mitigación**: Mantener `ExtensionsTrait` funcional en el Core. Los nuevos controladores (en Modules) usan `LegacyBridgeTrait` para consumir las extensiones.

### 3. XMLView

Los plugins definen vistas con archivos XML. Los controladores legacy (`ListController`, `EditController`) parsean estos XML.

**Riesgo**: Si reemplazamos los controladores legacy por `ResourceController`, los XMLView no se interpretarán.

**Mitigación**: Mientras los controladores legacy estén activos (routing por `Dinamic/Controller/`), los XMLView siguen funcionando. Para los nuevos controladores, las vistas se definen con componentes PHP.

### 4. Definiciones de tablas (Table/*.xml)

Los plugins definen tablas de base de datos con XML. El `DbUpdater` las procesa para crear/alterar tablas.

**Riesgo**: Bajo. Este mecanismo no depende de los controladores.

**Mitigación**: Mantener `DbUpdater` operativo.

### 5. Workers y WorkQueue

Los plugins registran workers para procesar eventos asincrónicos.

**Riesgo**: Bajo. `WorkQueue` es infraestructura del Core y no se modifica.

**Mitigación**: Ninguna necesaria.

### 6. API Routes

Los plugins registran rutas API con `Kernel::addRoute()`.

**Riesgo**: Si cambiamos el Kernel de routing, las rutas custom fallarán.

**Mitigación**: El Kernel hexagonal (`Tahiche\Infrastructure\Http\Kernel`) delega al `Core\Kernel::run()` para rutas legacy. Las rutas registradas por plugins siguen funcionando.

---

## Matriz de compatibilidad por fase

| Fase | Plugin Feature | Impacto | Mitigación |
|------|---------------|---------|------------|
| 0 (Entry point) | Rutas de assets | 🟢 Nulo | Nginx reescribe a public/index.php |
| 1 (PDO) | DataBase directa | 🟡 Bajo | `DataBase` sigue siendo la misma API |
| 2 (Componentes) | XMLView | 🟢 Nulo | No afecta al legacy |
| 3 (Barcodes) | Extensiones | 🟢 Nulo | Plugin nuevo, no interfiere |
| 4 (Core split) | Namespaces | 🔴 Alto | `class_alias()` obligatorio |
| 4 (Core split) | Extensiones | 🟡 Medio | `LegacyBridgeTrait` |
| 5 (Dinamic) | Proxies | 🔴 Alto | Requiere mecanismo alternativo |
| 5 (Dinamic) | Routing | 🔴 Alto | Nuevo sistema de resolución |

---

## Plan de verificación

### Plugins de test

Para verificar la compatibilidad, se deberían testear estos escenarios con un plugin de referencia (StockAvanzado):

| Test | Descripción | Estado |
|------|-------------|--------|
| Instalación | El plugin se instala correctamente | ⬜ |
| Activación | El plugin se activa y despliega | ⬜ |
| Init | `init()` se ejecuta sin errores | ⬜ |
| Extensiones | Las pestañas se inyectan en EditProducto | ✅ (LegacyBridgeTrait) |
| Modelos | Las tablas se crean correctamente | ⬜ |
| Controladores | Los controladores propios funcionan | ⬜ |
| API | Las rutas API custom funcionan | ⬜ |
| Workers | Los workers se registran y ejecutan | ⬜ |
| Cron | Las tareas programadas se ejecutan | ⬜ |

### CI automatizado

Añadir tests de regresión:
```bash
# phpunit-plugins.xml ya existe
./vendor/bin/phpunit --configuration phpunit-plugins.xml
```

---

## Mejoras propuestas para compatibilidad

### 1. Capa de compatibilidad centralizada

Crear `src/Infrastructure/Compatibility/LegacySupport.php`:

```php
class LegacySupport
{
    /**
     * Registra todos los aliases necesarios para que los plugins legacy
     * encuentren las clases en sus ubicaciones originales.
     */
    public static function registerAliases(): void
    {
        // Cuando un controlador se mueva de Core a Modules:
        // class_alias(NewClass::class, OldNamespace\OldClass::class);
    }
    
    /**
     * Verifica que un plugin legacy es compatible con la versión actual.
     */
    public static function checkPluginCompatibility(string $pluginName): array
    {
        $issues = [];
        // Verificar que todas las clases referenciadas existen
        // Verificar que las extensiones apuntan a controladores válidos
        return $issues;
    }
}
```

### 2. Herramienta de diagnóstico

Crear un comando CLI o controlador web que analice un plugin y reporte:
- Clases que referencia y si existen
- Extensiones registradas y si los targets están disponibles
- Tablas requeridas y si existen
- Rutas registradas y si hay conflictos

### 3. Guía de migración para plugin developers

Documentar para los desarrolladores de plugins:
- Cómo adaptar un plugin legacy para que funcione con ambas versiones
- Cómo crear un plugin con la nueva arquitectura (Modules)
- Tabla de equivalencias: old API → new API

---

## Checklist

- [ ] Verificar que StockAvanzado funciona completamente
- [ ] Crear `LegacySupport::registerAliases()` para la fase de Core split
- [ ] Crear herramienta de diagnóstico de compatibilidad
- [ ] Escribir guía de migración para plugin developers
- [ ] Añadir tests de regresión de plugins al CI
- [ ] Verificar compatibilidad después de cada fase
- [ ] Documentar breaking changes (si los hay) con workarounds
