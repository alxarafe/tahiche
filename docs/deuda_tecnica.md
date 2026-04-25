# Deuda Estructural — Tahiche Plugin Architecture

> [!IMPORTANT]
> Este documento registra todas las carencias arquitectónicas encontradas durante la sesión de refactorización del 25/04/2026. Cada punto es un problema real que **funciona hoy con parches**, pero que debería resolverse correctamente en futuras iteraciones.

---

## 1. EditSettings hardcodea pestañas de plugins

**Archivo**: [EditSettings.php](file:///home/rsanjose/Desarrollo/Alxarafe/tahiche/Core/Controller/EditSettings.php#L153-L170)

**Problema**: El controlador del Core carga directamente vistas XML de plugins:
- `EditIdentificadorFiscal` (Admin)
- `ListSecuenciaDocumento` (Admin)
- `ListEstadoDocumento` (Admin)
- `ListFormatoDocumento` (Admin)

Y accede a tablas de plugins en sus métodos:
- `loadSerie()` → tabla `series` (Admin)
- `loadPaymentMethodValues()` → tabla `formaspago` (Admin)
- `loadWarehouseValues()` → tabla `almacenes` (Trading)
- `checkTax()` → tabla `impuestos` (Accounting)

**Parche actual**: `file_exists()` en XMLViews y `tableExists()` en métodos de carga.

**Solución correcta**: Cada plugin debería registrar sus propias pestañas en `EditSettings` vía extensiones en su `Init.php`. El Core solo debería tener la pestaña `SettingsDefault` y `ListApiKey`. Ejemplo:

```php
// En Plugins/Admin/Init.php::init()
EditSettings::addExtension(function($controller) {
    $controller->createViewsIdFiscal();
    $controller->createViewSequences();
    // etc.
});
```

---

## 2. Stubs de modelos en Core/Model/ crean acoplamiento invisible

**Archivos**: 61 stubs en `Core/Model/` (Serie, FormaPago, Almacen, Cliente, etc.)

**Problema**: Estos stubs (`class Serie extends \Plugins\Admin\Model\Serie`) permiten que código legacy siga funcionando, pero:
- Cualquier instanciación dispara `DbUpdater::createOrUpdateTable()`.
- Si el XML de tabla no está en `Dinamic/Table/` ni `Core/Table/`, falla.
- `DbUpdater::getTableXmlLocation()` fue parcheado para buscar en `Plugins/*/Table/`, pero esto no debería ser necesario.

**Parche actual**: `getTableXmlLocation()` busca en las carpetas de plugins como fallback.

**Solución correcta**: A largo plazo, eliminar los stubs y que todo el código use `Dinamic\Model\*` o directamente el namespace del plugin. Los stubs solo deben existir durante un período de migración con `@deprecated` claro.

---

## 3. Admin es un plugin obligatorio de facto

**Problema**: El plugin Admin provee:
- **Controladores**: EditUser, ListUser, EditRole, EditEmpresa, ListEmpresa, EditDivisa, EditPais, ListPais, y 12 más.
- **Modelos**: FormaPago, Serie, EstadoDocumento, SecuenciaDocumento, FormatoDocumento, CuentaBanco, y 10 más.
- **Tablas XML**: 11 definiciones de tablas.

Sin Admin activo, no hay UI para gestionar usuarios, empresas, ni la configuración base del sistema.

**Parche actual**: Admin está activado como `"enabled": true` en `plugins.json`.

**Solución correcta**: Dos opciones a evaluar:
1. **Absorción parcial**: Mover al Core los controladores críticos (EditUser, ListUser, EditRole, EditEmpresa) y sus XMLViews. Dejar en Admin solo la configuración comercial (FormaPago, Serie, etc.).
2. **Plugin protegido**: Marcar Admin como `"hidden": true` y no permitir su desactivación desde la UI (similar a cómo WordPress impide desactivar wp-admin).

---

## 4. Dependencia circular Crm ↔ Trading

**Problema**:
- **Crm** usa `Dinamic\Model\Producto` (Trading) en EditCliente
- **Trading** usa `Dinamic\Model\Cliente`, `Dinamic\Model\Proveedor` (Crm) en EditProducto

No es un problema de runtime (Dinamic resuelve lazy), pero impide desactivar uno sin el otro.

**Declaración actual en INI**:
- Crm: `require = 'Admin'`
- Trading: `require = 'Crm'`

La dependencia de Trading hacia Crm está declarada, pero Crm hacia Trading no (porque no es estricta — es solo un filtro opcional).

**Solución correcta**: Evaluar si el acoplamiento se puede resolver con extensiones. Por ejemplo, que Trading registre la pestaña de Producto en EditCliente vía extensión, en lugar de que Crm la hardcodee.

---

## 5. DataSrc en el Core referencian modelos de plugins

**Archivos**:
- [Ejercicios.php](file:///home/rsanjose/Desarrollo/Alxarafe/tahiche/Core/DataSrc/Ejercicios.php) → `Dinamic\Model\Ejercicio` (Accounting)
- [Series.php](file:///home/rsanjose/Desarrollo/Alxarafe/tahiche/Core/DataSrc/Series.php) → `Dinamic\Model\Serie` (Admin)
- [Almacenes.php](file:///home/rsanjose/Desarrollo/Alxarafe/tahiche/Core/DataSrc/Almacenes.php) → `Dinamic\Model\Almacen` (Trading)
- [Agentes.php](file:///home/rsanjose/Desarrollo/Alxarafe/tahiche/Core/DataSrc/Agentes.php) → `Dinamic\Model\Agente` (Crm)

**Problema**: Estas clases DataSrc están en el Core pero dependen de modelos que viven en plugins. Si el plugin no está activo, la clase Dinamic no existe y falla.

**Parche actual**: Funcionan porque Admin está siempre activo y los stubs en `Core/Model/` redirigen.

**Solución correcta**: Los DataSrc deberían:
1. Moverse al plugin correspondiente, o
2. Usar `class_exists()` antes de instanciar el modelo, o
3. Aceptar que los plugins base (Admin, Crm, Trading, Accounting) son siempre necesarios.

---

## 6. Controladores-stub eliminados — inventario

Se eliminaron **71 stubs de controladores** de `Core/Controller/` que extendían controladores de plugins. Ejemplo:

```php
// ELIMINADO: Core/Controller/ListAlmacen.php
class ListAlmacen extends \Plugins\Trading\Controller\ListAlmacen {}
```

Estos causaban que el menú mostrara opciones de plugins desactivados porque `PluginsDeploy` los copiaba a `Dinamic/Controller/` y `initControllers()` los registraba en la tabla `pages`.

**Estado actual**: Eliminados. Los controladores ahora solo aparecen cuando su plugin está activo.

---

## Resumen de cambios realizados

| Cambio | Tipo | Archivos |
|---|---|---|
| Modelos fundacionales devueltos al Core | **Permanente** | User, Role, RoleAccess, RoleUser, Empresa, Divisa, Pais + 7 XMLs |
| Stubs en Plugin/Admin invertidos | **Permanente** | 7 archivos (ahora plugin extiende Core) |
| Stubs de controladores eliminados | **Permanente** | 71 archivos eliminados de Core/Controller/ |
| `min_version` corregido en INIs | **Permanente** | 6 plugins: 2021 → 2026 |
| Dependencias declaradas en INIs | **Permanente** | Accounting→Admin, Crm→Admin, Trading→Crm, etc. |
| `DbUpdater::getTableXmlLocation` busca en Plugins/ | **Parche** | Core/DbUpdater.php |
| `EditSettings` vistas condicionales | **Parche** | Core/Controller/EditSettings.php |
| `EditSettings` tableExists guards | **Parche** | Core/Controller/EditSettings.php |
| Admin activado como obligatorio | **Parche** | MyFiles/plugins.json |
