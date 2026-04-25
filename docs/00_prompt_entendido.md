# Prompt tal como ha sido entendido

> **Documento de comprensión** — Refleja cómo ha sido interpretada la solicitud del usuario.
> Última actualización: 2026-04-25

---

## Contexto del proyecto

**Tahiche** es una refactorización progresiva (patrón *Strangler Fig*) de **FacturaScripts 2018**, un ERP/CRM open-source en PHP. El proyecto se encuentra en una fase de transición donde conviven dos capas:

| Capa | Ubicación | Copyright | Descripción |
|------|-----------|-----------|-------------|
| **Legacy** | `Core/`, `Dinamic/`, `Plugins/` | Carlos García Gómez (FacturaScripts) | Código original de FS, licencia LGPL |
| **Moderna** | `src/`, `Modules/` | Rafael San José (rsanjose@alxarafe.com) | Código nuevo del estrangulamiento, licencia LGPL |

La arquitectura moderna se apoya en el ecosistema **Alxarafe**, con paquetes como `alxarafe/resource-controller` y `alxarafe/resource-pdo` que proporcionan controladores declarativos CRUD y acceso a datos vía PDO.

**Regla fundamental**: Se debe mantener **compatibilidad total** con el repositorio FacturaScripts y su ecosistema de plugins hasta que el estrangulamiento se complete o se indique lo contrario.

---

## Objetivos solicitados — Mi interpretación

### 1. Mover punto de entrada a `public/` o `public_html/`

**Lo que entiendo**: Actualmente, el `index.php` del root se debe eliminar y el único punto de entrada web debe ser `public/index.php`. El servidor (nginx/apache) debe apuntar a `public/` como document root. Esto ya está parcialmente hecho (nginx apunta a `/var/www/html/public`), pero puede haber restos del index.php legacy en el root u otros archivos que expongan código fuente. El objetivo es aislar completamente el código PHP ejecutable del directorio accesible por web, siguiendo prácticas de seguridad modernas.

**Incluye**: Ajustar `.htaccess`, configuración de Docker, posible redireccionamiento en el root para entornos que no usen proxy inverso, y verificar que las rutas de assets (`Core/Assets`, `Dinamic/Assets`, `node_modules`) sigan funcionando correctamente a través de alias o controladores de archivos.

### 2. Limpieza de imagen corporativa

**Lo que entiendo**: Tahiche debe tener su propia identidad visual separada de FacturaScripts:
- **Nuevos logos**: Diseñar/generar logotipos propios para Tahiche.
- **Nuevos colores**: Paleta de colores propia, diferenciándose de la naranja de FS.
- **Nuevo nombre visible**: El nombre "Tahiche" debe aparecer como nombre principal del producto. En los créditos/about se mantendrá la mención "Tahiche, basado en FacturaScripts".
- Se deben respetar los copyrights originales en los archivos de `Core/` y `Plugins/`. No se deben modificar los archivos de `Plugins/`.

### 3. Limpieza del núcleo — Mover aplicaciones a Plugins

**Lo que entiendo**: El directorio `Core/` de FacturaScripts incluye controladores, modelos y vistas que pertenecen a dominios de negocio específicos (facturación, CRM, contabilidad, almacenes, etc.), no al núcleo del framework. El objetivo es:
- Identificar qué pertenece realmente al **núcleo** (autenticación, routing, plugins, base de datos, sesión, templates, etc.) y qué son **aplicaciones/módulos de negocio**.
- Mover la lógica de negocio a `Plugins/` (usando la estructura ya definida en `Modules/` como referencia).
- Los módulos ya esbozados en `Modules/` (Accounting, Admin, Crm, Sales, Trading) marcan la dirección: cada dominio es un módulo con sus propios Controller y Model.
- Mantener retrocompatibilidad: los namespaces `FacturaScripts\Core\Controller\*` y `FacturaScripts\Core\Model\*` deben seguir funcionando (alias, herencia o proxy).

### 4. Eliminar controladores mysqli y pgsql nativos en favor de PDO

**Lo que entiendo**: Actualmente existen tres motores de base de datos en `Core/Base/DataBase/`:
- `MysqlEngine.php` — usa `mysqli` directamente
- `PostgresqlEngine.php` — usa `pg_connect`/`pg_query` directamente
- `PdoEngine.php` — ya existente, usa PDO a través de `MysqlPdoConnection`

El objetivo es:
- Eliminar `MysqlEngine.php` y `PostgresqlEngine.php`.
- Hacer que `PdoEngine` soporte ambos motores (MySQL y PostgreSQL) dinámicamente a través de DSN.
- Actualizar `DataBase.php` para que solo use `PdoEngine`.
- Mantener soporte para los mismos motores de base de datos (MySQL/MariaDB y PostgreSQL), pero siempre a través de PDO.
- Crear también `PostgresqlPdoConnection` en `src/Infrastructure/Database/`.

### 5. Eliminar la carpeta `Dinamic/`

**Lo que entiendo**: La carpeta `Dinamic/` es un sistema de "overlay" que genera clases derivadas dinámicamente a partir de las clases del Core y los Plugins. Es un mecanismo de FacturaScripts para permitir que los plugins sobrescriban controladores y modelos. 

**Clarificación del usuario**: Dinamic **puede mantenerse** para que los plugins legacy sigan funcionando, pero el **código nuevo de Tahiche NO debe usar Dinamic en ningún caso**. Ya se ha probado que el `LegacyBridgeTrait` permite interceptar la funcionalidad de plugins legacy (ej: pestaña "Movimientos" de StockAvanzado en EditProducto) sin depender de Dinamic.

El objetivo es:
- **Inmediato**: No usar `FacturaScripts\Dinamic\*` en código nuevo de `src/` ni `Modules/`.
- **Medio plazo**: Refactorizar referencias existentes a Dinamic en código de Tahiche.
- **Largo plazo**: Cuando el estrangulamiento esté completo, evaluar la eliminación total.


### 6. Estudiar el paquete `alxarafe/resource-controller` para formularios

**Lo que entiendo**: El paquete `alxarafe/resource-controller` (actualmente v0.2.1) proporciona un sistema de componentes declarativos para construir formularios CRUD. El paquete ya incluye:
- Campos: `Boolean`, `Date`, `DateTime`, `Decimal`, `Hidden`, `Icon`, `Image`, `Integer`, `RelationList`, `Select`, `Select2`, `StaticText`, `Text`, `Textarea`, `Time`
- Contenedores: `Panel`, `Tab`, `TabGroup`, `Row`, `Separator`, `HtmlContent`
- Filtros: `AbstractFilter`
- Rendering: `DefaultRenderer`

Se pide:
- Analizar en profundidad qué funcionalidades faltan comparando con los XMLView del legacy.
- Identificar componentes que harían el paquete más eficiente (p.e. campos de tipo `Autocomplete`, `File/Upload`, `Color`, `Password`, `Number`, `Currency`, `Email`, `URL`, `Phone`, `Code/Barcode`).
- Proponer mejoras de arquitectura al paquete.

### 7. Plugin de códigos de barras para artículos

**Lo que entiendo**: Crear un nuevo plugin **con la nueva arquitectura** (sistema de módulos moderno, no el legacy XMLView) que gestione códigos de barras de productos. Requisitos funcionales:
- Cada artículo (producto) puede tener **múltiples códigos de barras** (relación 1:N).
- Cada código de barras tiene:
  - El código EAN/UPC en sí
  - El **tipo** de código (EAN-13, EAN-8, UPC-A, Code 128, etc.)
  - La **cantidad** que representa (por defecto 1 unidad)
- Ejemplo de caso de uso: Coca-Cola fabricada en España y Portugal tiene EAN diferente (mismo producto, distinto origen). Además, los packs de 6, 12 o 24 unidades tienen EAN distintos con cantidad diferente.
- **Búsqueda**: Al buscar un artículo por código de barras (en cualquier EAN asociado), el sistema debe encontrar el producto y la cantidad correspondiente.
- Debe integrarse como pestaña en la vista de edición de producto (similar a cómo lo hace StockAvanzado).

### 8. Analizar mejoras de compatibilidad con plugins existentes

**Lo que entiendo**: Hacer un análisis técnico de:
- Cómo funciona el actual sistema de extensiones/hooks de plugins (el `ExtensionsTrait`, el sistema de `Init`, las extensiones en `Extension/Controller/`, etc.).
- Identificar puntos de incompatibilidad potenciales al avanzar con la modernización.
- Proponer una capa de compatibilidad o bridge que permita que los plugins legacy (los que usan XMLView, extensiones, etc.) sigan funcionando con el núcleo modernizado.
- Documentar el mapa de migración para plugin developers.

### 9. Documentación completa en `docs/`

**Lo que entiendo**: Toda la planificación, decisiones arquitectónicas, análisis y roadmap debe quedar documentada en la carpeta `docs/` del proyecto para:
- Persistir el contexto entre sesiones de trabajo.
- Servir como referencia para contribuidores.
- Documentar decisiones técnicas y sus justificaciones.

---

## Restricciones y principios que aplico

1. **Compatibilidad ante todo**: Ningún cambio puede romper los plugins existentes de FacturaScripts mientras no se diga lo contrario.
2. **Copyrights diferenciados**: Archivos en `Core/` → copyright de Carlos García Gómez (FacturaScripts). Archivos en `src/` y `Modules/` → copyright de Rafael San José (Alxarafe). Siempre LGPL.
3. **Incrementalidad**: El patrón Strangler Fig obliga a trabajar de forma incremental. No se puede reescribir todo de golpe.
4. **Calidad**: PHPStan, PHPCS (PSR-12), PHPUnit, CI local — mantener los estándares de calidad existentes.
5. **El núcleo es el núcleo**: Solo infraestructura en el Core. La lógica de negocio va a módulos/plugins.

---

## Mi enfoque

Voy a crear la documentación estructurada en varios documentos dentro de `docs/`:

| Documento | Contenido |
|-----------|-----------|
| `00_prompt_entendido.md` | Este documento |
| `01_estado_actual.md` | Auditoría del estado actual del proyecto |
| `02_plan_maestro.md` | Plan maestro con todos los objetivos, fases y dependencias |
| `03_entry_point.md` | Detalle técnico: mover punto de entrada a public |
| `04_imagen_corporativa.md` | Plan de nueva imagen corporativa |
| `05_limpieza_nucleo.md` | Análisis y plan de separación core vs. módulos |
| `06_migracion_pdo.md` | Plan de eliminación de mysqli/pgsql en favor de PDO |
| `07_eliminacion_dinamic.md` | Análisis y plan de eliminación de Dinamic |
| `08_alxarafe_components.md` | Estudio del paquete de componentes |
| `09_plugin_barcodes.md` | Diseño del plugin de códigos de barras |
| `10_compatibilidad_plugins.md` | Análisis de compatibilidad con plugins existentes |
