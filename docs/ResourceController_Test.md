# Funcionalidades del ResourceController (TestController)

El `TestController` en el framework Alxarafe sirve como un excelente caso de uso para entender el potencial del gestor automático de plantillas mediante el `ResourceController`. En lugar de escribir HTML puro o plantillas de Blade/Twig complejas, el framework permite definir vistas basadas puramente en componentes (orientado a objetos).

A continuación se detallan todas las funcionalidades clave encontradas:

## 1. Declaración de Menú (Atributos PHP 8)
Usa atributos de PHP 8 (`#[Menu(...)]`) para registrar automáticamente el controlador en el menú principal (`main_menu`), indicando su etiqueta, icono, orden y visibilidad.

## 2. Definición Estructurada de Vistas (`getViewDescriptor()`)
El controlador anula el método `getViewDescriptor()` para devolver un arreglo asociativo que describe toda la estructura del formulario, botones y distribución de la interfaz. Esto reemplaza la necesidad de tener archivos `.html.twig` o `.blade.php` para operaciones estándar.

## 3. Arquitectura Basada en Componentes (Contenedores)
El formulario se divide lógicamente en contenedores modulares:
- **TabGroup y Tab:** Permite organizar la información en pestañas horizontales.
- **Panel:** Agrupa campos bajo un título común, generando tarjetas visuales (`cards`). Soporta configuración de grid Bootstrap (ej. `['col' => 'col-md-7']`) y clases CSS personalizadas (`class` => `shadow-lg border-primary`).
- **Row:** Agrupa campos en la misma fila sin dibujar una tarjeta o borde de panel. Útil para ubicar campos lado a lado.
- **Separator:** Permite dibujar una línea divisoria (opcionalmente con texto) entre grupos de campos.
- **HtmlContent:** Útil para incrustar HTML puro o scripts inline cuando los componentes por defecto no son suficientes (como se ve en la previsualización de Markdown).

## 4. Paneles Anidados (Nesting)
Soporta una jerarquía infinita de componentes. Un `Panel` puede contener dentro otro `Panel`, facilitando la construcción de agrupaciones lógicas de campos (e.g. Empresa -> Dirección Fiscal -> Contacto Principal).

## 5. Tipos de Campos Inteligentes
Incluye un vasto arsenal de componentes de campos listos para usar, que manejan automáticamente su renderizado HTML y clases de Bootstrap:
- **Datos Textuales:** `Text`, `Textarea`, `StaticText` (texto estático con iconos), `Hidden`.
- **Datos Numéricos:** `Integer`, `Decimal` (soporta configuración de `precision`).
- **Estados/Selecciones:** `Boolean` (toggles), `Select` (desplegables estándar), `Select2` (desplegables avanzados con búsqueda).
- **Multimedia/Estética:** `Icon` (selectores de iconos FontAwesome), `Image` (renderizado de imágenes con control de ancho).
- **Fechas/Tiempos:** `Date`, `DateTime`, `Time`.

## 6. Acciones en Línea para Campos (`addAction()`)
Se pueden inyectar botones de acción directamente junto a los campos (`ActionPosition::Left` o `Right`). Estos botones permiten incrustar código JavaScript para interactuar con los datos del formulario de manera rápida (por ejemplo: generar valores aleatorios, aumentar o disminuir montos, etc.).

## 7. Gestión de Acciones y Botones del Formulario
El arreglo devuelto por `getViewDescriptor()` permite configurar los botones principales (e.g. Guardar, Limpiar Caché).
Al interceptar solicitudes en `handleRequest()`, o definir métodos mágicos como `doClearCache()`, se conectan fácilmente los botones con la lógica de negocio.

## 8. Puntos de Integración AJAX (`jsonResponse()`)
Muestra cómo integrar funcionalidades AJAX mediante métodos con el prefijo `do...` (ej. `doRenderMarkdown()`), recuperando datos de la petición (POST/GET) y devolviendo una respuesta en JSON gracias a `$this->jsonResponse()`.

## 9. Modo Persistente y Demo Data
En este controlador se intercepta el guardado para almacenar el estado del formulario temporalmente en un archivo YAML, saltándose el uso de la base de datos para fines de demostración mediante la anulación de `handleRequest()`, `checkTableIntegrity()`, y `fetchRecordData()`.

## Resumen
El uso del `ResourceController` elimina la necesidad de mantener cientos de plantillas repetitivas (legacy), centralizando toda la construcción del frontend administrativo en el servidor mediante código PHP estructurado, asegurando un diseño estético consistente (Bootstrap 5) y reduciendo la deuda técnica de la interfaz de usuario.

---

## 10. Auditoría del estado actual en Tahiche (2026-04-25)

### 10.1 Versión y paquetes instalados

| Paquete | Versión | Descripción |
|---------|---------|-------------|
| `alxarafe/resource-controller` | 0.2.1 | Controladores CRUD declarativos, rendering, componentes |
| `alxarafe/resource-pdo` | 0.1.1 | Repositorio PDO para acceso a datos |

### 10.2 Inventario completo de componentes (v0.2.1)

#### Campos (`Component/Fields/`)
| Componente | Archivo | Uso en Tahiche |
|-----------|---------|----------------|
| `Boolean` | `Fields/Boolean.php` | ✅ Usado (ProductsController: sevende, secompra, bloqueado...) |
| `Date` | `Fields/Date.php` | ✅ Usado (fechaalta) |
| `DateTime` | `Fields/DateTime.php` | ✅ Usado (actualizado) |
| `Decimal` | `Fields/Decimal.php` | ✅ Usado (precio, stockfis) |
| `Hidden` | `Fields/Hidden.php` | ✅ Usado (idproducto) |
| `Icon` | `Fields/Icon.php` | ⚪ Disponible, no usado en Tahiche |
| `Image` | `Fields/Image.php` | ⚪ Disponible, no usado en Tahiche |
| `Integer` | `Fields/Integer.php` | ⚪ Disponible, no usado en Tahiche |
| `RelationList` | `Fields/RelationList.php` | ⚪ Disponible, no usado en Tahiche |
| `Select` | `Fields/Select.php` | ✅ Usado (codfamilia, codfabricante, codimpuesto) |
| `Select2` | `Fields/Select2.php` | ⚪ Disponible, no usado en Tahiche |
| `StaticText` | `Fields/StaticText.php` | ✅ Usado (tablas HTML relacionadas en ProductsController) |
| `Text` | `Fields/Text.php` | ✅ Usado (referencia, descripcion) |
| `Textarea` | `Fields/Textarea.php` | ✅ Usado (observaciones) |
| `Time` | `Fields/Time.php` | ⚪ Disponible, no usado en Tahiche |

#### Contenedores (`Component/Container/`)
| Componente | Archivo | Descripción |
|-----------|---------|-------------|
| `AbstractContainer` | Base abstracta para contenedores |
| `HtmlContent` | HTML puro inline |
| `Panel` | Tarjeta con título y campos agrupados |
| `Row` | Fila sin borde visual |
| `Separator` | Línea divisoria con texto opcional |
| `Tab` | Pestaña individual |
| `TabGroup` | Grupo de pestañas |

#### Enums
| Enum | Descripción |
|------|-------------|
| `ActionPosition` | Posición de acciones (Left, Right) |

#### Contratos (`Contracts/`)
| Contrato | Descripción | Implementación en Tahiche |
|----------|-------------|--------------------------|
| `RepositoryContract` | Acceso a datos CRUD | `TahicheRepository` (adapter al ORM legacy) |
| `TransactionContract` | Control transaccional | Implementado |
| `QueryContract` | Constructor de queries | `TahicheQuery` |
| `TranslatorContract` | Traducciones i18n | `TahicheTranslator` (bridge a FS Translator) |
| `MessageBagContract` | Mensajes flash | Implementado |
| `HookContract` | Hooks/extensiones | Implementado |
| `RendererContract` | Renderizado HTML | `DefaultRenderer` |
| `RelationContract` | Relaciones entre modelos | ⚪ No implementado en Tahiche |

### 10.3 Patrón de integración observado en Tahiche

El `ResourceController` en `src/Infrastructure/Http/ResourceController.php` extiende `AbstractResourceController` y proporciona:

1. **Adaptadores legacy**: `TahicheRepository`, `TahicheQuery`, `TahicheTranslator` hacen de bridge entre los contratos del framework y el ORM de FacturaScripts.
2. **LegacyBridgeTrait**: Permite que plugins legacy inyecten tabs y botones en los controladores modernos.
3. **Rendering híbrido**: El Kernel captura el output del ResourceController y lo envuelve con el layout Twig legacy (menú, CSS, JS) mediante `ResourceBridge.html.twig`.

### 10.4 Controladores modernos activos (Modules/)

Se han creado **43 controladores** modernos distribuidos en 5 módulos:

| Módulo | Nº Controllers | Ejemplo |
|--------|---------------|---------|
| Trading | 21 | `ProductsController`, `ManufacturersController` |
| Admin | 11 | `UsersController`, `RolesController` |
| Accounting | 7 | `AccountsController`, `FiscalYearsController` |
| Crm | 4 | `CustomersController`, `ContactsController` |
| Sales | 1 | `SalesInvoicesController` |

### 10.5 Gaps identificados — Componentes que faltan

Comparando con las funcionalidades del legacy XMLView y las necesidades del ERP, se detectan los siguientes gaps:

#### Componentes de campo necesarios
| Componente | Prioridad | Justificación |
|-----------|-----------|---------------|
| **Autocomplete** | 🔴 Alta | El legacy tiene `AutocompleteFilter` y widgets autocomplete. Esencial para buscar clientes, productos, cuentas contables, etc. |
| **File/Upload** | 🔴 Alta | Necesario para adjuntos (`AttachedFile`), imágenes de producto, importación CSV |
| **Money/Currency** | 🟡 Media | Campo decimal con formato de moneda y símbolo configurable |
| **Email** | 🟡 Media | Validación de email en frontend |
| **Url** | 🟡 Media | Validación y renderizado de URLs |
| **Phone** | 🟡 Media | Formato de teléfono |
| **Password** | 🟡 Media | Campo con toggle de visibilidad, necesario para usuarios |
| **Color** | 🟢 Baja | Selector de color (series, categorías) |
| **Code/Barcode** | 🟡 Media | Campo específico para EAN/UPC con validación — necesario para el plugin de códigos de barras |
| **Number** | 🟢 Baja | Alias de Integer/Decimal con formato localizado |
| **Radio** | 🟢 Baja | Grupo de opciones excluyentes |
| **Checkbox Group** | 🟢 Baja | Múltiple selección con checkboxes |
| **RichText** | 🟢 Baja | Editor WYSIWYG para descripciones largas |

#### Componentes de contenedor/layout necesarios
| Componente | Prioridad | Justificación |
|-----------|-----------|---------------|
| **DataTable** | 🔴 Alta | Las tablas relacionadas se construyen con HTML en `StaticText`. Debería haber un componente declarativo para tablas de datos con paginación, ordenación y edición inline |
| **Accordion** | 🟢 Baja | Para secciones colapsables |
| **Modal** | 🟡 Media | Diálogos modales para confirmaciones, formularios rápidos |
| **Wizard/Stepper** | 🟢 Baja | Para flujos multi-paso (instalación, configuración) |

#### Funcionalidades del trait/core que mejorar
| Funcionalidad | Prioridad | Estado actual |
|--------------|-----------|---------------|
| **Búsqueda global** | 🔴 Alta | Existe `$globalSearchFields` pero no está expuesto en la UI de forma estándar |
| **Exportación** (PDF, CSV, XLS) | 🟡 Media | No implementado en el ResourceController |
| **Impresión** | 🟡 Media | No implementado |
| **Bulk actions** | 🟡 Media | El legacy permite acciones masivas en listados |
| **Drag & drop** para reordenar | 🟢 Baja | Para listas ordenables |
| **Validación client-side** | 🟡 Media | Los campos tienen `maxlength`, `min`, `max` pero no validación JS integrada |
| **Permisos por campo** | 🟢 Baja | Visibilidad/edición condicional por rol de usuario |

### 10.6 Observaciones sobre `StaticText` como workaround

En `ProductsController.php`, las pestañas de datos relacionados (variantes, stock, proveedores) se construyen generando HTML crudo dentro de `StaticText`:

```php
private function buildRelatedTable(string $modelClass, string $foreignKey, $foreignValue): array
{
    $html = '<div class="card shadow-sm border-0 mt-3 mb-4"><div class="card-body p-0">';
    // ... genera tabla HTML manualmente ...
    return [new StaticText($html)];
}
```

Esto es un **anti-pattern** frente a la filosofía declarativa del ResourceController. Se debería crear un componente `DataTable` o `RelatedRecords` que acepte un modelo, clave foránea y columnas, y genere la tabla automáticamente con paginación y acciones CRUD.

### 10.7 Recomendaciones para el paquete

1. **Crear `DataTable` component**: Reemplazaría el patrón `buildRelatedTable()` con HTML crudo.
2. **Crear `Autocomplete` field**: Fundamental para el ERP — buscar entidades por nombre/código.
3. **Crear `File` field**: Para uploads con preview y validación de tipo/tamaño.
4. **Añadir `Barcode` field**: Para el plugin de códigos de barras — validación EAN, renderizado visual del código.
5. **Mejorar `RelationList`**: Actualmente no se usa en Tahiche. Debería poder funcionar como un `DataTable` embebido con CRUD inline.
6. **Export trait**: Añadir capacidad de exportación (PDF, CSV, XLS) al `ResourceTrait`.
7. **Validación JavaScript integrada**: Los metadatos de constraints ya se propagan al frontend; falta la librería JS que los consuma.
