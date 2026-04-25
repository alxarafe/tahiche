# Limpieza del núcleo — Separación Core vs. Módulos

> **Fase**: 4  
> **Prioridad**: Alta  
> **Riesgo**: 🔴 Alto

---

## Principio

> El Core solo debe contener **infraestructura**: autenticación, routing, base de datos, sesión, plugins, templates, logging, caching y utilidades genéricas. Todo lo demás es **lógica de negocio** y debe vivir en `Plugins/` o `Modules/`.

## Clasificación de controladores del Core

### Infraestructura (se queda en Core) — 15 controladores

| Controlador | Responsabilidad |
|-------------|----------------|
| `Root` | Página raíz / redirect |
| `Login` | Autenticación |
| `Installer` | Instalación inicial |
| `Deploy` | Despliegue de plugins |
| `Updater` | Actualizaciones del sistema |
| `Cron` | Tareas programadas |
| `Files` | Servir archivos estáticos |
| `Myfiles` | Gestión de archivos subidos |
| `MegaSearch` | Búsqueda global |
| `Dashboard` | Panel principal |
| `AdminPlugins` | Gestión de plugins |
| `EditSettings` | Configuración general |
| `ConfigEmail` | Configuración de email |
| `SendMail` | Envío de correos |
| `CopyModel` | Utilidad de copia de registros |
| `DocumentStitcher` | Utilidad de unión de documentos |

### Admin → `Plugins/Admin` o `Modules/Admin` — 11 controladores

| Controlador | Dominio |
|-------------|---------|
| `EditUser` / `ListUser` | Gestión de usuarios |
| `EditRole` | Roles y permisos |
| `EditApiKey` | Claves API |
| `EditCronJob` | Trabajos programados |
| `EditLogMessage` / `ListLogMessage` | Logs |
| `EditPageOption` | Personalización de páginas |
| `EditEmailNotification` / `EditEmailSent` | Notificaciones email |
| `EditWorkEvent` | Eventos de trabajo |
| `ListAttachedFile` / `EditAttachedFile` | Archivos adjuntos |
| `About` | Información del sistema |
| `EditEmpresa` / `ListEmpresa` | Empresas |

### Trading → `Plugins/Trading` o `Modules/Trading` — 35+ controladores

| Grupo | Controladores |
|-------|--------------|
| **Productos** | `EditProducto`, `ListProducto`, `EditFabricante`, `ListFabricante`, `EditFamilia`, `ListFamilia`, `EditAtributo`, `ListAtributo`, `EditTarifa`, `ListTarifa` |
| **Geografía** | `EditPais`, `ListPais`, `EditProvincia`, `EditCiudad`, `EditCodigoPostal`, `EditPuntoInteresCiudad` |
| **Configuración comercial** | `EditDivisa`, `EditFormaPago`, `ListFormaPago`, `EditImpuesto`, `ListImpuesto`, `EditRetencion`, `EditSerie`, `ListSerie`, `EditAlmacen`, `ListAlmacen`, `EditAgenciaTransporte`, `ListAgenciaTransporte`, `EditFormatoDocumento`, `EditSecuenciaDocumento`, `EditEstadoDocumento`, `EditGrupoClientes` |

### Accounting → `Plugins/Accounting` o `Modules/Accounting` — 12+ controladores

| Controlador | Dominio |
|-------------|---------|
| `EditAsiento` / `ListAsiento` | Asientos contables |
| `EditCuenta` / `ListCuenta` | Plan de cuentas |
| `EditSubcuenta` | Subcuentas |
| `EditCuentaBanco` | Cuentas bancarias |
| `EditCuentaEspecial` | Cuentas especiales |
| `EditConceptoPartida` | Conceptos de partida |
| `EditDiario` | Diarios contables |
| `EditEjercicio` / `ListEjercicio` | Ejercicios fiscales |

### CRM → `Plugins/Crm` o `Modules/Crm` — 8+ controladores

| Controlador | Dominio |
|-------------|---------|
| `EditCliente` / `ListCliente` | Clientes |
| `EditProveedor` / `ListProveedor` | Proveedores |
| `EditContacto` | Contactos |
| `EditAgente` / `ListAgente` | Agentes comerciales |

### Sales → `Plugins/Sales` o `Modules/Sales` — 16+ controladores

| Controlador | Dominio |
|-------------|---------|
| `EditPresupuestoCliente` / `ListPresupuestoCliente` | Presupuestos |
| `EditPedidoCliente` / `ListPedidoCliente` | Pedidos de cliente |
| `EditAlbaranCliente` / `ListAlbaranCliente` | Albaranes de venta |
| `EditFacturaCliente` / `ListFacturaCliente` | Facturas de venta |
| `EditReciboCliente` | Recibos de cliente |
| Equivalentes para proveedor (`*Proveedor`) | Compras |

### API → Se mantiene en Core como infraestructura

| Controlador | Nota |
|-------------|------|
| `ApiRoot`, `ApiPlugins` | Infraestructura API |
| `ApiAttachedFiles`, `ApiUploadFiles` | Infraestructura de archivos |
| `ApiCreateDocument`, `ApiExportDocument` | ⚠️ Discutible, quizás mover a Sales |
| `ApiPagarFactura*` | ⚠️ Mover a Sales |
| `ApiProductoImagen` | ⚠️ Mover a Trading |

## Estrategia de migración

### Paso 1: Crear aliases de compatibilidad

```php
// Core/Controller/EditProducto.php (después de migrar)
namespace FacturaScripts\Core\Controller;

// Backward compatibility alias
class_alias(
    \Modules\Trading\Controller\ProductsController::class, 
    EditProducto::class
);
```

### Paso 2: Mantener `Dinamic/` funcional durante la transición

El sistema de deploy de plugins copia controladores a `Dinamic/Controller/`. Mientras Dinamic exista, los aliases deben funcionar también para `FacturaScripts\Dinamic\Controller\EditProducto`.

### Paso 3: Migrar módulo por módulo

1. **Trading** primero (ya tiene 21 controladores en `Modules/Trading/`)
2. **Admin** segundo (ya tiene 11 en `Modules/Admin/`)
3. **Accounting** (ya tiene 7 en `Modules/Accounting/`)
4. **CRM** (ya tiene 4 en `Modules/Crm/`)
5. **Sales** último (más complejo por la lógica de documentos comerciales)

### Paso 4: Actualizar el sistema de menú

El `MenuManager` actual lee las páginas de la base de datos (tabla `pages`). Al mover controladores, hay que actualizar los registros de página para que apunten al nuevo controlador o mantener el nombre legacy.

## Modelos: ¿Mover o no?

Los modelos son más delicados porque están fuertemente acoplados:
- Muchos modelos se referencian entre sí (ej: `FacturaCliente` usa `Cliente`, `FormaPago`, `Serie`)
- Los plugins legacy hacen `use FacturaScripts\Dinamic\Model\*` directamente

**Estrategia recomendada**:
1. Los modelos se quedan en `Core/Model/` a corto plazo
2. Crear modelos nuevos en `Modules/*/Model/` que extienden los del Core
3. Los modelos legacy se convierten en thin wrappers o aliases a largo plazo

## Checklist

- [ ] Crear documento de clasificación definitiva (requiere revisión humana)
- [ ] Implementar sistema de aliases `class_alias` para retrocompatibilidad
- [ ] Migrar Trading (controladores que no están en Modules aún)
- [ ] Migrar Admin
- [ ] Migrar Accounting
- [ ] Migrar CRM
- [ ] Migrar Sales (último, más complejo)
- [ ] Actualizar MenuManager
- [ ] Verificar todos los plugins con el nuevo layout
- [ ] Actualizar tests
