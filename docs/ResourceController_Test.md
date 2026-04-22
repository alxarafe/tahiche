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
