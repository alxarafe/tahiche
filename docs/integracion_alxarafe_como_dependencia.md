# Integración de Alxarafe como Dependencia en Tahiche

> **Fecha:** 2026-04-17  
> **Objetivo:** Evaluar si tahiche debe usar `alxarafe/alxarafe` v0.5.8 o v0.6.0 como dependencia Composer para sustituir su capa de base de datos (SQL directo → Eloquent) y su motor de plantillas (Twig → Blade).  
> **Premisa:** Tahiche NO se reemplaza. Tahiche añade alxarafe como librería para modernizar sus subsistemas internos.

---

## Índice

1. [Qué se quiere sustituir](#1-qué-se-quiere-sustituir)
2. [Qué aporta cada versión de Alxarafe](#2-qué-aporta-cada-versión-de-alxarafe)
3. [Análisis de compatibilidad con tahiche](#3-análisis-de-compatibilidad-con-tahiche)
4. [Base de datos: SQL directo → Eloquent](#4-base-de-datos-sql-directo--eloquent)
5. [Plantillas: Twig → Blade](#5-plantillas-twig--blade)
6. [Generación automática de formularios](#6-generación-automática-de-formularios)
7. [La carpeta Dinamic/: proxy classes generadas](#7-la-carpeta-dinamic-proxy-classes-generadas)
8. [Veredicto: v0.5.8 vs v0.6.0](#8-veredicto-v058-vs-v060)
9. [Plan de integración](#9-plan-de-integración)

---

## 1. Qué se quiere sustituir

Tahiche tiene dos subsistemas que se quieren modernizar usando alxarafe como proveedor:

| Subsistema actual | Clase principal | Problema | Sustituto vía alxarafe |
|---|---|---|---|
| **Base de datos** | `DataBase.php` (554 líneas) + `ModelClass.php` (516 líneas) | SQL directo vía `mysqli`/`pg_*`, sin ORM, sin migraciones, sin prepared statements | **Eloquent ORM** (`illuminate/database` v10.48) |
| **Motor de plantillas** | `Html.php` (392 líneas) + 63 plantillas `.html.twig` | Twig 3.x con 14 funciones custom, sin sistema de temas | **Blade** (`jenssegers/blade` v2.0) |

Además, alxarafe trae de forma transitiva otros servicios que podrían sustituir dependencias actuales de tahiche:

| Dependencia tahiche actual | Versión | Sustituto en alxarafe | Versión |
|---|---|---|---|
| `phpmailer/phpmailer` | 6.x | `symfony/mailer` | 7.x |
| `rospdf/pdf-php` | 0.12.x | `dompdf/dompdf` | 3.1 |
| `twig/twig` | 3.x | `jenssegers/blade` | 2.0 |
| *(no tiene)* | — | `firebase/php-jwt` | 7.0 |
| *(no tiene)* | — | `symfony/translation` | 6.4/7.0 |

---

## 2. Qué aporta cada versión de Alxarafe

### 2.1 Dependencias (idénticas en lo esencial)

Ambas versiones ofrecen las **mismas dependencias runtime**:

| Dependencia | v0.5.8 | v0.6.0 | Diferencia |
|---|---|---|---|
| `illuminate/database` | ^10.48 | ^10.48 | Ninguna |
| `illuminate/events` | ^10.48 | ^10.48 | Ninguna |
| `illuminate/view` | ^10.48 | ^10.48 | Ninguna |
| `jenssegers/blade` | ^2.0 | ^2.0 | Ninguna |
| `firebase/php-jwt` | ^7.0 | ^7.0 | Ninguna |
| `dompdf/dompdf` | ^3.1 | ^3.1 | Ninguna |
| `symfony/mailer` | ^7.4 | ^7.2 | v0.6.0 es más permisiva |
| `symfony/translation` | ^6.4 | ^6.4 \|\| ^7.0 | v0.6.0 acepta Symfony 7 |
| `symfony/yaml` | ^6.4 | ^6.4 \|\| ^7.0 | v0.6.0 acepta Symfony 7 |
| `psr/log` | *(implícito)* | ^3.0 | v0.6.0 lo explicita |

**Conclusión:** A nivel de dependencias transitivas, **ambas traen lo mismo**. La v0.6.0 es ligeramente más flexible con las versiones de Symfony.

### 2.2 Namespaces y estructura (LA diferencia clave)

| Aspecto | v0.5.8 | v0.6.0 |
|---|---|---|
| **Namespace raíz** | `Alxarafe\` → `src/Core/` | `Alxarafe\Domain\`, `Alxarafe\Application\`, `Alxarafe\Infrastructure\` |
| **Para importar un servicio** | `use Alxarafe\Base\Database;` | `use Alxarafe\Infrastructure\Persistence\Database;` |
| **Para importar un modelo** | `use Alxarafe\Base\Model;` | `use Alxarafe\Domain\Model\...;` |
| **Nº de namespaces PSR-4** | 4 (`Alxarafe\`, `Alxarafe\Scripts\`, `CoreModules\`, `Modules\`) | 6 (`Alxarafe\Domain\`, `Alxarafe\Application\`, `Alxarafe\Infrastructure\`, `Alxarafe\Scripts\`, `Modules\`) |
| **Estabilidad de la API pública** | Estable dentro de `Core/` | Los imports pueden cambiar entre patches mientras se estabiliza la hexagonal |

### 2.3 Lo que realmente usaría tahiche de alxarafe

Tahiche no necesita todo alxarafe. Solo necesita acceder a:

| Servicio | Clase/Interface en v0.5.8 | Clase/Interface en v0.6.0 |
|---|---|---|
| **Conexión Eloquent** | `Alxarafe\Base\Database` (o directamente `Illuminate\Database\Capsule\Manager`) | `Alxarafe\Infrastructure\Persistence\Database` |
| **Modelo base** | `Alxarafe\Base\Model` (extiende Eloquent Model) | `Alxarafe\Infrastructure\Persistence\Model\XnetModel` |
| **Renderizado Blade** | `Alxarafe\Service\BladeService` | `Alxarafe\Infrastructure\Service\BladeService` |
| **Servicio PDF** | `Alxarafe\Service\PdfService` | `Alxarafe\Infrastructure\Service\PdfService` |
| **Servicio Email** | `Alxarafe\Service\EmailService` | `Alxarafe\Infrastructure\Service\EmailService` |
| **Traducciones** | `Alxarafe\Tools\Translator` | `Alxarafe\Infrastructure\Tools\Translator` |

---

## 3. Análisis de compatibilidad con tahiche

### 3.1 Conflictos de dependencias Composer

| Dependencia tahiche | Versión tahiche | Conflicto con alxarafe | Solución |
|---|---|---|---|
| `php` | >=8.0 | ⚠️ Alxarafe requiere **>=8.2** | Subir PHP mínimo a 8.2 |
| `ext-mysqli` | * | ⚠️ Alxarafe usa `ext-pdo` en su lugar | Ambos pueden coexistir; PDO es para Eloquent, mysqli para código legacy |
| `twig/twig` | 3.x | ✅ Sin conflicto — pueden coexistir con Blade | Ambos motores en paralelo |
| `phpmailer/phpmailer` | 6.x | ✅ Sin conflicto — `symfony/mailer` es independiente | Migrar gradualmente |
| `rospdf/pdf-php` | 0.12.x | ✅ Sin conflicto — `dompdf` es independiente | Migrar gradualmente |
| `phpstan` (dev) | ^0.12 | ⚠️ Alxarafe usa ^2.1 | Subir PHPStan al integrar |

**Bloqueador principal:** PHP 8.0 → 8.2. Es el único cambio obligatorio antes de integrar.

### 3.2 Conflictos de namespaces

| Namespace tahiche | Namespace alxarafe | Conflicto |
|---|---|---|
| `Tahiche\Core\` | `Alxarafe\` (v0.5.8) o `Alxarafe\Domain\` (v0.6.0) | ✅ **Ninguno** |
| `Tahiche\Dinamic\` | — | ✅ **Ninguno** |
| `Tahiche\Plugins\` | `Modules\` (alxarafe) | ✅ **Ninguno** |

No hay colisión de namespaces. Ambos pueden coexistir en el mismo `vendor/`.

---

## 4. Base de datos: SQL directo → Eloquent

### 4.1 Cómo funciona hoy en tahiche

```php
// ModelClass::all() — SQL manual en cada operación
$sql = 'SELECT * FROM ' . static::tableName()
     . DataBaseWhere::getSQLWhere($where)
     . self::getOrderBy($order);
foreach (self::$dataBase->selectLimit($sql, $limit, $offset) as $row) {
    $modelList[] = new static($row);
}
```

- 85 modelos, todos heredan de `ModelClass`
- Todas las queries son strings SQL concatenados
- Sin prepared statements (usa `var2str()` para escapado)
- Sin relaciones declarativas, sin scopes, sin eager loading

### 4.2 Cómo quedaría con alxarafe como dependencia

Eloquent llega vía `illuminate/database` (transitiva de alxarafe). No importa si usas v0.5.8 o v0.6.0 — **Eloquent es el mismo en ambas**.

```php
// Paso 1: Inicializar Eloquent con la config existente de tahiche
// (se ejecuta una vez en el bootstrap de la aplicación)
use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => FS_DB_HOST,
    'database'  => FS_DB_NAME,
    'username'  => FS_DB_USER,
    'password'  => FS_DB_PASS,
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Paso 2: Crear modelos Eloquent que apunten a las tablas existentes
use Illuminate\Database\Eloquent\Model;

class FacturaCliente extends Model
{
    protected $table = 'facturascli';       // tabla existente de tahiche
    protected $primaryKey = 'idfactura';    // PK existente
    public $timestamps = false;             // tahiche no usa created_at/updated_at

    public function lineas() {
        return $this->hasMany(LineaFacturaCliente::class, 'idfactura');
    }
    public function cliente() {
        return $this->belongsTo(Cliente::class, 'codcliente', 'codcliente');
    }
}

// Paso 3: Usar consultas Eloquent en lugar de SQL directo
$facturas = FacturaCliente::where('codcliente', 'CLI001')
    ->with('lineas')      // eager loading (resuelve N+1)
    ->orderBy('fecha', 'desc')
    ->paginate(50);       // paginación automática
```

### 4.3 Impacto por versión de alxarafe

| Aspecto | v0.5.8 | v0.6.0 | Diferencia para tahiche |
|---|---|---|---|
| Eloquent ORM | `illuminate/database` ^10.48 | `illuminate/database` ^10.48 | **Ninguna** |
| Modelo base de alxarafe | `Alxarafe\Base\Model` | `Alxarafe\Infrastructure\Persistence\Model\XnetModel` | Solo si quieres heredar del modelo base de alxarafe (no es necesario) |
| Inicialización de conexión | Via `Alxarafe\Base\Database` | Via `Alxarafe\Infrastructure\Persistence\Database` | Solo cambia el import |

> **Conclusión DB:** Para la capa de base de datos, **da igual usar v0.5.8 o v0.6.0**. Tahiche puede usar `illuminate/database` directamente sin pasar por las clases wrapper de alxarafe. Eloquent es el mismo en ambas versiones.

---

## 5. Plantillas: Twig → Blade

### 5.1 Cómo funciona hoy en tahiche

`Html.php` inicializa Twig, registra 14 funciones custom, y carga plantillas desde `Core/View/` con overrides desde `Plugins/{name}/View/`:

```php
// Html::render() — punto de entrada
$templateVars = [
    'assetManager' => new AssetManager(),
    'debugBarRender' => Tools::config('debug') ? new DebugBar() : false,
    'i18n' => new Translator(),
    'log' => new MiniLog()
];
return self::twig()->render($template, array_merge($params, $templateVars));
```

63 plantillas Twig con sintaxis como:
```twig
{% extends "Master/PanelController.html.twig" %}
{% block body %}
    {{ trans('invoices') }}
    {% for factura in fsc.getListData() %}
        {{ money(factura.total, factura.coddivisa) }}
    {% endfor %}
{% endblock %}
```

### 5.2 Cómo quedaría con Blade vía alxarafe

Blade llega vía `jenssegers/blade` + `illuminate/view` (transitivas de alxarafe):

```php
// Inicialización de Blade
use Jenssegers\Blade\Blade;

$blade = new Blade(
    [FS_FOLDER . '/Core/View'],           // paths de vistas
    FS_FOLDER . '/MyFiles/Cache/Blade'     // caché compilada
);

// Registrar funciones equivalentes a las Twig custom
$blade->directive('trans', fn($key) => "<?php echo trans($key); ?>");
$blade->directive('money', fn($args) => "<?php echo money($args); ?>");
```

La misma plantilla en Blade:
```blade
@extends('Master.PanelController')
@section('body')
    {{ __('invoices') }}
    @foreach($fsc->getListData() as $factura)
        {{ money($factura->total, $factura->coddivisa) }}
    @endforeach
@endsection
```

### 5.3 Impacto por versión de alxarafe

| Aspecto | v0.5.8 | v0.6.0 | Diferencia para tahiche |
|---|---|---|---|
| Blade engine | `jenssegers/blade` ^2.0 | `jenssegers/blade` ^2.0 | **Ninguna** |
| Sistema de temas | Sí (via `BladeService`) | Sí (via `BladeService`) | Misma funcionalidad |
| Servicio wrapper | `Alxarafe\Service\BladeService` | `Alxarafe\Infrastructure\Service\BladeService` | Solo cambia el import |
| Caché por tema | Sí (desde v0.5.7) | Sí | Idéntico |

> **Conclusión plantillas:** Para el motor de plantillas, **da igual usar v0.5.8 o v0.6.0**. Blade es el mismo. La diferencia es solo la ruta del `use` si usas el wrapper de alxarafe.

### 5.4 Estrategia de convivencia Twig + Blade

La migración NO tiene que ser todo-o-nada. Ambos motores pueden coexistir:

```php
// En el controlador, decidir qué motor usar:
public function render(string $template, array $data): string
{
    if (str_ends_with($template, '.blade.php')) {
        return $this->blade->render($template, $data);
    }
    return Html::render($template, $data);  // Twig para lo existente
}
```

**Plan de convivencia:**

| Fase | Plantillas en Twig | Plantillas en Blade | Acción |
|:----:|:-------------------:|:-------------------:|--------|
| Inicio | 63 | 0 | Se instala Blade vía alxarafe |
| Mes 1 | 57 | 6 | Migrar Login, Error, Installer |
| Mes 2 | 49 | 14 | Migrar Master layouts |
| Mes 3 | 37 | 26 | Migrar Tabs |
| Mes 4 | 20 | 43 | Migrar Blocks y Macros → Componentes |
| Mes 5 | 0 | 63 | Eliminar dependencia `twig/twig` |

---

## 6. Generación automática de formularios

Este es un tercer subsistema clave que alxarafe puede mejorar significativamente.

### 6.1 Cómo funciona hoy en tahiche: XMLView + EditController

Tahiche genera formularios CRUD automáticamente definiendo la interfaz en **131 archivos XML** (`Core/XMLView/`):

```xml
<!-- Core/XMLView/EditPais.xml — Define el formulario de edición de país -->
<view>
    <columns>
        <group name="data">
            <column name="code" title="alfa-code-3" numcolumns="3" order="100">
                <widget type="text" fieldname="codpais" icon="fa-solid fa-hashtag"
                        maxlength="20" readonly="dinamic" required="true" />
            </column>
            <column name="name" numcolumns="6" order="120">
                <widget type="text" fieldname="nombre" required="true" />
            </column>
            <column name="latitude" order="130">
                <widget type="number" fieldname="latitude"/>
            </column>
            <column name="telephone-prefix" order="150">
                <widget type="text" maxlength="10" fieldname="telephone_prefix"/>
            </column>
        </group>
    </columns>
</view>
```

Y el controlador asociado es mínimo gracias a `EditController`:

```php
// Controller/EditPais.php — Solo declara qué modelo usar
class EditPais extends EditController
{
    public function getModelClassName(): string
    {
        return 'Pais';
    }
}
```

La cadena de herencia es:
```
EditController → PanelController → BaseController
     ↓                  ↓
 getModelClassName()  createViews() → addEditView('EditPais', 'Pais')
                         ↓
                   Carga EditPais.xml → parsea columnas → genera HTML con Twig
```

**Puntos fuertes del sistema XMLView de tahiche:**
- ✅ Sin escribir HTML: el formulario se genera desde XML
- ✅ Los plugins pueden extender vistas con archivos XML adicionales
- ✅ Widgets tipados: `text`, `number`, `select`, `checkbox`, `datetime`, `textarea`, `money`, `color`...
- ✅ Sistema maduro: 131 definiciones XML probadas en producción

**Puntos débiles:**
- ❌ XML no tiene autocompletado ni validación estática en IDEs
- ❌ No soporta lógica condicional (mostrar/ocultar campos según estado)
- ❌ El parser XML añade overhead en cada renderizado
- ❌ Los widgets custom requieren extender el parser XML
- ❌ Sin tipado: un error en `fieldname` no se detecta hasta runtime

### 6.2 Cómo funciona en alxarafe v0.5.8: ResourceController + AbstractField

Alxarafe sustituye los 131 XMLs por código PHP con una API fluida:

```php
// Controller que cubre TANTO listado como edición (unificado)
class PaisController extends ResourceController
{
    // El modelo Eloquent (ya define la tabla, PK, relaciones)
    protected string $modelClass = Pais::class;

    // Definición de campos del formulario EN PHP
    protected function fields(): array
    {
        return [
            Text::make('codpais', __('alfa-code-3'))
                ->required()
                ->maxLength(20)
                ->readonlyOnEdit()     // readonly en edición, editable en creación
                ->col('col-3'),

            Text::make('codiso', __('alfa-code-2'))
                ->maxLength(2)
                ->col('col-3'),

            Text::make('nombre', __('name'))
                ->required()
                ->col('col-6'),

            Number::make('latitude', __('latitude')),
            Number::make('longitude', __('longitude')),

            Text::make('telephone_prefix', __('telephone-prefix'))
                ->maxLength(10),

            Textarea::make('alias', __('alias'))
                ->col('col-12'),
        ];
    }
}
```

La cadena es más directa:
```
ResourceController (MODE_LIST o MODE_EDIT)
     ↓
 fields() → AbstractField[] → cada campo se auto-renderiza vía Blade
     ↓
 Blade: @foreach($fields as $field) @include('form/' . $field->getComponent()) @endforeach
```

**Puntos fuertes del ResourceController de alxarafe:**
- ✅ **Autocompletado del IDE**: `Text::make()`, `Select::make()` son clases PHP con métodos tipados
- ✅ **Un solo controlador** para listar Y editar (vs EditController + ListController en tahiche)
- ✅ **Lógica condicional**: `->visibleWhen(fn($model) => $model->activo)` en PHP puro
- ✅ **Actions por campo**: `->addAction('fas fa-globe', 'openMap()', 'Ver mapa')`
- ✅ **Dependencias de módulos**: `->module('crm')` oculta el campo si el módulo CRM no está activo
- ✅ **Validación en compilación**: un campo mal definido da error PHP inmediato
- ✅ **JsonSerializable**: los campos se pueden exportar como JSON para APIs o SPAs

### 6.3 Comparativa directa

| Aspecto | Tahiche (XMLView) | Alxarafe (ResourceController) |
|---|---|---|
| **Definición de formulario** | XML (`EditPais.xml`) | PHP (`fields()` method) |
| **Nº de archivos por entidad** | 3 (EditXxx.xml + ListXxx.xml + Controller) | 1 (ResourceController) |
| **Total archivos de vistas** | 131 XMLs | 0 (los campos se definen en el controlador) |
| **Autocompletado IDE** | ❌ No (XML) | ✅ Sí (PHP tipado) |
| **Detección de errores** | En runtime | En compilación |
| **Lógica condicional** | ❌ No soportada | ✅ `->visibleWhen(...)` |
| **Campo custom** | Extender el parser XML | Crear clase que extienda `AbstractField` |
| **Widgets disponibles** | ~15 tipos | ~10 tipos + extensible |
| **Extensión por plugins** | XML adicional en `Plugins/{name}/XMLView/` | Override de `fields()` o hooks |
| **Rendimiento** | Parse XML en cada request (con caché) | Ejecución PHP directa |
| **Filtros en listados** | XML: `<filter type="select" ...>` | PHP: `AbstractFilter` subclases |
| **Acciones por campo** | ❌ No nativo | ✅ `->addAction(icon, onclick, title)` |
| **Soporte JSON/API** | ❌ Solo HTML | ✅ `JsonSerializable` |

### 6.4 ¿Cómo ayudaría a tahiche?

El `ResourceController` de alxarafe podría **sustituir los 131 archivos XMLView + EditController + ListController** por controladores PHP únicos:

| Situación actual (tahiche) | Con ResourceController |
|---|---|
| `Core/XMLView/EditPais.xml` (72 líneas) | Eliminado |
| `Core/XMLView/ListPais.xml` (~40 líneas) | Eliminado |
| `Core/Controller/EditPais.php` (~15 líneas) | `PaisController extends ResourceController` (~30 líneas) |
| `Core/Controller/ListPais.php` (~25 líneas) | Se fusiona en el mismo `PaisController` |
| **Total: 4 archivos, ~152 líneas** | **Total: 1 archivo, ~30 líneas** |

Extrapolado a las 131 vistas XML: se eliminarían **~130 archivos XML** y se fusionarían **~100 controladores PHP** en ~50 ResourceControllers.

### 6.5 Impacto por versión

| Aspecto | v0.5.8 | v0.6.0 |
|---|---|---|
| `ResourceController` | `Alxarafe\Base\Controller\ResourceController` | `Alxarafe\Infrastructure\Http\Controller\ResourceController` |
| `AbstractField` | `Alxarafe\Component\AbstractField` | `Alxarafe\Domain\Component\AbstractField` |
| Tipos de campo | `Text`, `Number`, `Select`, `Textarea`, `Checkbox`, etc. | Los mismos |
| `ResourceInterface` | `MODE_LIST`, `MODE_EDIT` | Igual |
| Funcionalidad | Idéntica | Idéntica |

> **Conclusión formularios:** El `ResourceController` de alxarafe es una **mejora significativa** sobre el sistema XMLView de tahiche. Es el subsistema donde la diferencia es más grande y donde más código se eliminaría. Funcionalmente, **da igual v0.5.8 o v0.6.0** — el sistema de campos es el mismo.

---

## 7. La carpeta Dinamic/: proxy classes generadas

### 7.1 Qué es y cómo funciona

La carpeta `Dinamic/` es un directorio **generado automáticamente** por `Core/Internal/PluginsDeploy.php`. Contiene **238 archivos PHP** que son **clases proxy vacías**. Cada una simplemente hereda de la clase Core (o del plugin activo):

```php
// Dinamic/Model/Pais.php — generado automáticamente
<?php namespace Tahiche\Dinamic\Model;

class Pais extends \Tahiche\Core\Model\Pais
{
}
```

**Todo el código del Core NO importa directamente las clases de `Core\`**, sino las de `Dinamic\`:

```php
// Core/Controller/EditFacturaCliente.php
use Tahiche\Dinamic\Model\FacturaCliente;    // ← Dinamic, NO Core
use Tahiche\Dinamic\Lib\Calculator;           // ← Dinamic, NO Core
use Tahiche\Dinamic\Lib\ReceiptGenerator;     // ← Dinamic, NO Core
```

Esto se repite en **+586 imports** a lo largo de todo el proyecto.

### 7.2 ¿Por qué existe?

Es un mecanismo de **extensión por plugins** inventado por Tahiche. Permite que un plugin sobreescriba una clase del Core sin tocar los archivos originales:

```
1. Core/Model/Pais.php         → la clase original
2. Plugins/MiPlugin/Model/Pais.php  → el plugin añade/modifica algo
3. PluginsDeploy::run()        → genera Dinamic/Model/Pais.php
```

Cuando un plugin está activo, `PluginsDeploy` genera la clase proxy para que herede **del plugin** en vez del Core:

```php
// Dinamic/Model/Pais.php — cuando un plugin sobreescribe Pais
class Pais extends \Tahiche\Plugins\MiPlugin\Model\Pais
{
}
```

Así, todo el código que hace `use Tahiche\Dinamic\Model\Pais` siempre obtiene la versión correcta (Core o plugin) sin cambiar una línea.

### 7.3 El proceso de deploy

`PluginsDeploy::run()` ejecuta esto **cada vez que se activa/desactiva un plugin**:

1. **Borra** todo el contenido de `Dinamic/` (10 subcarpetas: Assets, Controller, Data, Error, Lib, Model, Table, View, Worker, XMLView)
2. **Escanea** los plugins activos (en orden inverso de prioridad)
3. **Escanea** `Core/` para cada subcarpeta
4. Para cada archivo `.php`, **genera** una clase proxy vacía que hereda de la fuente correcta
5. Para cada archivo `.xml`, **fusiona** el XML original con las extensiones XML de los plugins

### 7.4 Problemas del sistema Dinamic/

| Problema | Impacto |
|---|---|
| **238 archivos generados** que ensucian el proyecto | Confusión en búsquedas, Git, IDEs |
| **+586 imports indirectos** | El IDE no puede navegar a la implementación real (siempre llega a la clase vacía) |
| **Regeneración completa** al activar/desactivar plugins | Operación lenta y frágil |
| **Solo herencia simple** | Un plugin no puede extender 2 clases a la vez |
| **El autoload PSR-4 funciona**, pero apunta a clases vacías | Antipatrón de "empty proxy" |
| **Merge XML por DOM** | Código frágil (453 líneas en PluginsDeploy.php solo para esto) |
| **No soporta composición** | Solo herencia, no permite decoradores ni middleware |

### 7.5 Alternativas que ofrece alxarafe

Alxarafe no necesita `Dinamic/` porque resuelve la extensibilidad de plugins con mecanismos estándar de PHP:

#### Alternativa 1: Service Container (vía Illuminate)

Alxarafe ya trae `illuminate/events` (v0.5.8 y v0.6.0). Se puede usar el contenedor de servicios de Laravel para **vincular interfaces a implementaciones**:

```php
// Registro: el Core define la interfaz
$container->bind(PaisInterface::class, \Tahiche\Core\Model\Pais::class);

// Un plugin puede sobreescribir el binding:
$container->bind(PaisInterface::class, \MiPlugin\Model\PaisExtendido::class);

// Uso: siempre se obtiene la implementación correcta
$pais = $container->make(PaisInterface::class);
```

**Ventajas sobre Dinamic/:**
- ✅ Cero archivos generados
- ✅ El IDE navega directamente a la implementación real
- ✅ Soporta composición (decorators), no solo herencia
- ✅ Patrón estándar en Laravel, Symfony, y cualquier framework moderno

#### Alternativa 2: Event/Hook system

Para los casos donde un plugin solo necesita **añadir comportamiento** (no reemplazar una clase entera), alxarafe ofrece el sistema de eventos de Illuminate:

```php
// En el Core:
Event::dispatch('model.pais.saving', [$pais]);

// Un plugin escucha:
Event::listen('model.pais.saving', function(Pais $pais) {
    $pais->validarCodigoISO();  // lógica custom del plugin
});
```

#### Alternativa 3: Middleware/Decorators (para controladores)

En vez de que un plugin herede `EditPais extends \Core\Controller\EditPais`, con el `ResourceController` de alxarafe un plugin puede:

```php
// Añadir campos al formulario sin heredar
ResourceController::extending(PaisController::class, function($fields) {
    $fields[] = Text::make('codigo_aduanas', 'Código Aduanas')->col('col-3');
    return $fields;
});
```

### 7.6 Estrategia de eliminación de Dinamic/

| Fase | Acción | Impacto |
|:----:|--------|:------:|
| 1 | Crear un `ServiceContainer` que registre Core classes como default bindings | 🟡 |
| 2 | Cambiar los +586 imports de `Dinamic\Model\X` a resolver vía container: `app(PaisInterface::class)` o directamente `Core\Model\X` | 🟡 |
| 3 | Migrar la extensión XML a `fields()` en ResourceControllers (ya cubierto en §6) | 🟢 |
| 4 | Sustituir el `ExtensionsTrait` de los Controllers por eventos/hooks | 🟢 |
| 5 | Eliminar `PluginsDeploy.php` (453 líneas) y toda la carpeta `Dinamic/` | 🟢 |

**Resultado final:**
- Se eliminan **238 archivos proxy** de `Dinamic/`
- Se elimina `PluginsDeploy.php` (453 líneas de código de generación)
- Se eliminan las 131 definiciones XMLView (ya resuelto por ResourceController)
- Los plugins se registran via Service Container + Events, sin generar archivos
- El namespace `Tahiche\Dinamic\` desaparece del `composer.json`

```diff
  "autoload": {
      "psr-4": {
          "Tahiche\\Core\\": "Core/",
-         "Tahiche\\Dinamic\\": "Dinamic/",
          "Tahiche\\Plugins\\": "Plugins/",
          "Tahiche\\Test\\": "Test/"
      }
  }
```

### 7.7 La caché de Blade en alxarafe: alternativa directa a Dinamic/

Efectivamente, alxarafe tiene un mecanismo de caché que recuerda la filosofía de `Dinamic/` pero es **mucho más eficiente**. Funciona así:

#### Cómo funciona la caché de Blade

```php
// Template.php — la inicialización de Blade en alxarafe
$theme = Config::getConfig()?->main->theme ?? 'default';
$cachePath = BASE_PATH . '/../var/cache/blade/' . $theme;

$this->blade = new Blade($this->paths, $cachePath, $container);
```

Blade compila cada vista `.blade.php` a un archivo PHP plano optimizado y lo guarda en `var/cache/blade/{theme}/`:

```
templates/pages/pais/edit.blade.php          → var/cache/blade/default/abc123hash.php
templates/pages/pais/list.blade.php          → var/cache/blade/default/def456hash.php
templates/themes/modern/layouts/app.blade.php → var/cache/blade/modern/ghi789hash.php
```

**La clave:** Blade **no recompila** si el archivo cacheado ya existe y es más reciente que el fuente. Esto es el comportamiento nativo de `illuminate/view`:

```php
// Internamente, Illuminate\View\Compilers\BladeCompiler hace:
if ($this->isExpired($path)) {      // ¿el fuente es más reciente que el cache?
    $this->compile($path);           // solo entonces recompila
}
```

#### Publicación de assets: no sobreescribe si existe

El `ComposerScripts::postUpdate()` de alxarafe publica assets (CSS, JS, imágenes) desde los módulos a la carpeta pública. Pero para las **vistas Blade**, un módulo puede generar una vista base que el usuario luego personaliza:

```
1. Módulo genera:  templates/pages/pais/edit.blade.php  (vista por defecto)
2. El usuario la edita para personalizar el formulario
3. Al actualizar el módulo, la vista NO se sobreescribe (el usuario conserva sus cambios)
```

#### Comparativa directa: Dinamic/ vs Blade Cache

| Aspecto | Dinamic/ (tahiche) | Blade Cache (alxarafe) |
|---|---|---|
| **Qué genera** | Clases PHP vacías (proxy) | PHP compilado optimizado (vistas) |
| **Cuándo regenera** | Siempre (borra todo y regenera) | Solo si el fuente cambió |
| **Sobreescribe cambios del usuario** | ❌ Sí (borra y regenera todo) | ✅ No (respeta archivos existentes) |
| **Nº de archivos generados** | 238 (clases proxy) | Solo lo que hace falta (vistas compiladas) |
| **Contenido generado** | Vacío (`class X extends Y {}`) | PHP real optimizado |
| **Se puede personalizar** | ❌ No (se regenera al deploy) | ✅ Sí (editar el `.blade.php` fuente) |
| **Rendimiento** | Indirección en cada classload | Compilación una vez, PHP directo después |
| **Git** | ⚠️ 238 archivos en `.gitignore` | ✅ `var/cache/` está en `.gitignore` |
| **Requiere deploy manual** | Sí (`PluginsDeploy::run()`) | No (lazy compilation on first request) |

#### ¿Podría sustituir Dinamic/ directamente?

**Sí, parcialmente.** Para las **vistas** (XMLView → Blade), la caché de Blade sustituye Dinamic/ completamente:

| Función de Dinamic/ | Sustituto Blade Cache |
|---|---|
| `Dinamic/XMLView/*.xml` (vistas fusionadas) | Innecesario — las vistas son `.blade.php` en `templates/` y se cachean automáticamente |
| `Dinamic/View/*.html.twig` (vistas fusionadas) | Innecesario — Blade compila bajo demanda |
| `Dinamic/Assets/*` (recursos fusionados) | `ComposerScripts::publishThemes()` publica a `public/themes/` |
| `Dinamic/Controller/*.php` (proxies) | **No aplica** — necesita Service Container (§7.5 Alternativa 1) |
| `Dinamic/Model/*.php` (proxies) | **No aplica** — necesita Service Container (§7.5 Alternativa 1) |
| `Dinamic/Lib/*.php` (proxies) | **No aplica** — necesita Service Container (§7.5 Alternativa 1) |

**Conclusión:** La caché de Blade sustituye la parte de **vistas/plantillas/XMLView** de `Dinamic/` (~131 archivos XML + templates Twig). Para las **clases PHP** (Controller, Model, Lib), sigue siendo necesario el Service Container. Combinados:

- **Blade Cache** → elimina `Dinamic/XMLView/`, `Dinamic/View/`, `Dinamic/Assets/` (~131 archivos)
- **Service Container** → elimina `Dinamic/Controller/`, `Dinamic/Model/`, `Dinamic/Lib/` (~107 archivos)
- **Total eliminado:** los **238 archivos** de `Dinamic/`

> **Conclusión Dinamic/:** Es un mecanismo ingenioso pero obsoleto. Alxarafe lo hace innecesario gracias a la combinación de la caché de Blade (para vistas) y el Service Container de Illuminate (para clases). La eliminación de `Dinamic/` es el cambio con mayor impacto en limpieza de código: **-238 archivos, -453 líneas de generador, -586 imports indirectos**.

---

## 8. Veredicto: v0.5.8 vs v0.6.0

### 8.1 Para el propósito de tahiche, son funcionalmente idénticas

| Criterio | v0.5.8 | v0.6.0 | Ganador |
|---|---|---|---|
| **Eloquent ORM** | ^10.48 | ^10.48 | Empate |
| **Blade** | ^2.0 | ^2.0 | Empate |
| **DomPDF** | ^3.1 | ^3.1 | Empate |
| **Symfony Mailer** | ^7.4 | ^7.2 | Empate |
| **JWT** | ^7.0 | ^7.0 | Empate |
| **Flexibilidad Symfony** | Solo ^6.4 | ^6.4 \|\| ^7.0 | **v0.6.0** |
| **PSR-3 Logging** | Implícito | Explícito (`psr/log` ^3.0) | **v0.6.0** |

### 8.2 La diferencia real: estabilidad de la API

| Criterio | v0.5.8 | v0.6.0 |
|---|:---:|:---:|
| **Namespace estable** | ✅ `Alxarafe\*` — no va a cambiar | ⚠️ `Alxarafe\Domain\*` etc. — podría refinarse en v0.7+ |
| **Reestructuración reciente** | ✅ Estructura consolidada | ⚠️ Reorganización de v0.5.8 → v0.6.0 acaba de ocurrir |
| **Riesgo de breaking changes** | Bajo (estructura estable) | Medio (la hexagonal aún se está estabilizando) |
| **Imports que usaría tahiche** | Cortos: `use Alxarafe\Service\PdfService` | Largos: `use Alxarafe\Infrastructure\Service\PdfService` |

### 8.3 ¿Importa la estructura hexagonal para tahiche?

**No.** Tahiche usa alxarafe como **librería**, no como framework. A tahiche le da igual si internamente alxarafe organiza su código en `Core/` o en `Domain/`+`Application/`+`Infrastructure/`. Lo que importa es:

1. Que las clases que necesita (Eloquent, Blade, PDF, Email) estén accesibles
2. Que los imports no cambien entre releases
3. Que las dependencias transitivas no generen conflictos

### 8.4 Recomendación

| Escenario | Versión recomendada | Motivo |
|---|---|---|
| **Integración inmediata (producción)** | **v0.5.8** | Namespace más simple, estructura estabilizada, menor riesgo de breaking changes |
| **Proyecto nuevo o POC** | **v0.6.0** | PSR-3 logging explícito, Symfony 7 compatible, arquitectura más limpia |
| **A largo plazo** | **v0.6.0+** cuando tenga 2-3 releases de estabilización | La hexagonal será el futuro del framework |

> **Recomendación final:** Usar **v0.5.8** ahora para la integración en producción. Los imports son más cortos, la API es estable, y tahiche obtiene exactamente lo mismo (Eloquent, Blade, DomPDF, Mailer). Cuando v0.7+ o v1.0 salgan, migrar los `use Alxarafe\...` a los nuevos namespaces (cambio mecánico con buscar/reemplazar).

---

## 9. Plan de integración

### 9.1 Prerrequisito: PHP 8.2

```bash
# Verificar versión actual
php -v
# Si es < 8.2, actualizar antes de continuar
```

Actualizar `composer.json`:
```diff
- "php": ">=8.0",
+ "php": ">=8.2",
```

### 9.2 Instalar alxarafe como dependencia

```bash
composer require alxarafe/alxarafe:^0.5.8
```

Esto trae automáticamente:
- ✅ `illuminate/database` (Eloquent)
- ✅ `illuminate/view` (Blade engine)
- ✅ `jenssegers/blade` (Blade standalone)
- ✅ `dompdf/dompdf` (sustituto de rospdf)
- ✅ `symfony/mailer` (sustituto de PHPMailer)
- ✅ `symfony/translation` (i18n)
- ✅ `firebase/php-jwt` (JWT auth)

### 9.3 Crear el bootstrap de Eloquent

Crear `Core/Base/EloquentBootstrap.php`:

```php
<?php
namespace Tahiche\Core\Base;

use Illuminate\Database\Capsule\Manager as Capsule;

class EloquentBootstrap
{
    private static bool $booted = false;

    public static function boot(): void
    {
        if (self::$booted) return;

        $capsule = new Capsule;
        $capsule->addConnection([
            'driver'    => FS_DB_TYPE === 'postgresql' ? 'pgsql' : 'mysql',
            'host'      => FS_DB_HOST,
            'port'      => FS_DB_PORT,
            'database'  => FS_DB_NAME,
            'username'  => FS_DB_USER,
            'password'  => FS_DB_PASS,
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        self::$booted = true;
    }
}
```

Invocar en `Kernel.php`:
```php
EloquentBootstrap::boot();
```

### 9.4 Crear primeros modelos Eloquent

Empezar con catálogos simples (bajo riesgo):

```php
<?php
// Core/Model/Eloquent/Pais.php
namespace Tahiche\Core\Model\Eloquent;

use Illuminate\Database\Eloquent\Model;

class Pais extends Model
{
    protected $table = 'paises';
    protected $primaryKey = 'codpais';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}
```

### 9.5 Integrar Blade como motor secundario

```php
<?php
// Core/Base/BladeBootstrap.php
namespace Tahiche\Core\Base;

use Jenssegers\Blade\Blade;

class BladeBootstrap
{
    private static ?Blade $blade = null;

    public static function blade(): Blade
    {
        if (self::$blade === null) {
            self::$blade = new Blade(
                [FS_FOLDER . '/Core/View/Blade'],
                FS_FOLDER . '/MyFiles/Cache/Blade'
            );

            // Registrar equivalentes de las funciones Twig
            self::registerHelpers();
        }
        return self::$blade;
    }

    public static function render(string $view, array $data = []): string
    {
        return self::blade()->render($view, $data);
    }

    private static function registerHelpers(): void
    {
        // Las funciones como money(), trans(), settings() 
        // se registran como helpers globales en un archivo aparte
    }
}
```

### 9.6 Orden de migración recomendado

| Semana | Acción | Riesgo |
|:------:|--------|:------:|
| 1 | Subir PHP a 8.2 + `composer require alxarafe/alxarafe` | 🟡 |
| 2 | Crear `EloquentBootstrap` + 5 modelos Eloquent de catálogos | 🟢 |
| 3-4 | Crear modelos Eloquent para clientes, proveedores, contactos | 🟢 |
| 5-6 | Crear `BladeBootstrap` + convertir Login y Error a Blade | 🟢 |
| 7-8 | Modelos Eloquent para documentos (facturas, albtahichees) | 🟡 |
| 9-10 | Convertir Master layouts a Blade | 🟡 |
| 11-12 | Sustituir `rospdf` por `dompdf` vía alxarafe | 🟢 |
| 13-16 | Sustituir `phpmailer` por `symfony/mailer` vía alxarafe | 🟢 |
| 17-20 | Convertir resto de plantillas Twig a Blade | 🟡 |
| 21-24 | Eliminar dependencias obsoletas (`twig`, `rospdf`, `phpmailer`) | 🟢 |

### 9.7 Dependencias que se pueden eliminar al final

Una vez completada la integración:

```diff
  "require": {
      "php": ">=8.2",
      "ext-bcmath": "*",
      "ext-curl": "*",
      "ext-dom": "*",
      "ext-fileinfo": "*",
      "ext-gd": "*",
      "ext-json": "*",
-     "ext-mysqli": "*",
-     "ext-pgsql": "*",
+     "ext-pdo": "*",
      "ext-simplexml": "*",
      "ext-zip": "*",
+     "alxarafe/alxarafe": "^0.5.8",
      "mk-j/php_xlsxwriter": "0.39",
      "parsecsv/php-parsecsv": "1.*",
-     "phpmailer/phpmailer": "6.*",
-     "rospdf/pdf-php": "0.12.*",
-     "twig/twig": "3.*",
      "globalcitizen/php-iban": "4.*",
      "pragmarx/google2fa": "^8.0",
      "chillerlan/php-qrcode": "^4.0"
  }
```

**Resultado:** Se eliminan 3 dependencias directas (`twig`, `rospdf`, `phpmailer`) y 2 extensiones PHP (`ext-mysqli`, `ext-pgsql`), y se ganan todas las de alxarafe vía una sola línea: `"alxarafe/alxarafe": "^0.5.8"`.
