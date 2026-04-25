# Limpieza de imagen corporativa

> **Fase**: 0.2  
> **Prioridad**: Media  
> **Riesgo**: 🟢 Bajo

---

## Identidad actual vs. objetivo

| Aspecto | FacturaScripts (actual) | Tahiche (objetivo) |
|---------|------------------------|-------------------|
| **Nombre** | FacturaScripts | **Tahiche** |
| **Créditos** | — | "Tahiche, basado en FacturaScripts" |
| **Color primario** | Naranja `#FF6B35` | Por definir (propuesta: azul/teal profundo) |
| **Color secundario** | Gris | Por definir (propuesta: dorado/ámbar) |
| **Logo** | FS logo naranja | Nuevo logo Tahiche |
| **Favicon** | FS favicon | Nuevo favicon |
| **Fuente** | System fonts | Inter / Outfit (moderna) |

## Propuesta de paleta de colores

Inspiración: Tahiche es un pueblo de Lanzarote, asociado con César Manrique, naturaleza volcánica y el mar.

| Rol | Color | Hex | Uso |
|-----|-------|-----|-----|
| **Primario** | Azul océano profundo | `#1A5276` | Navegación, headers, botones principales |
| **Primario claro** | Azul cielo | `#2E86C1` | Hover, links, acentos |
| **Secundario** | Ámbar volcánico | `#D4A017` | Badges, alertas, acentos dorados |
| **Éxito** | Verde lanzaroteño | `#27AE60` | Confirmaciones, estados positivos |
| **Peligro** | Rojo volcánico | `#C0392B` | Errores, eliminaciones |
| **Fondo** | Blanco cálido | `#FAFAFA` | Background general |
| **Texto** | Gris oscuro | `#2C3E50` | Texto principal |
| **Sutil** | Gris suave | `#BDC3C7` | Bordes, divisores |

## Cambios requeridos

### 1. Logos y favicon
- Generar logo principal de Tahiche (SVG + PNG)
- Generar favicon (ICO + PNG 192x192 para PWA)
- Generar logo para login page
- Ubicación: `public/Assets/Images/` o `public/themes/tahiche/`

### 2. CSS / Tema
- Crear tema CSS propio en `public/themes/tahiche/`
- Override de variables Bootstrap:
  ```css
  :root {
      --bs-primary: #1A5276;
      --bs-secondary: #D4A017;
      --bs-success: #27AE60;
      --bs-danger: #C0392B;
      --bs-body-font-family: 'Inter', sans-serif;
  }
  ```

### 3. Plantillas Twig
- Actualizar `MenuTemplate` (o su equivalente) para usar el nuevo logo
- Actualizar página de login con branding Tahiche
- Actualizar footer con créditos: "Tahiche, basado en FacturaScripts"
- Actualizar página "About" con información de Tahiche

### 4. Metadatos
- `<title>` → "Tahiche ERP"
- Meta description → "Tahiche — ERP/CRM open-source moderno"
- Open Graph tags con logo de Tahiche

### 5. README y documentación
- Actualizar README.md con nuevo nombre y descripción
- Actualizar badges (ya dicen "Tahiche")

## Reglas de copyright en código

| Ubicación | Copyright | Acción |
|-----------|-----------|--------|
| `Core/**` | Carlos García Gómez (FacturaScripts) | **No modificar** |
| `src/**` | Rafael San José (Alxarafe) | Mantener |
| `Modules/**` | Rafael San José (Alxarafe) | Mantener |
| Nuevos archivos | Rafael San José (Alxarafe) | LGPL header |
| Plantillas visuales | Rafael San José (Alxarafe) | Nuevo branding |

## Checklist de implementación

- [ ] Definir paleta de colores definitiva
- [ ] Generar logos (principal, login, favicon)
- [ ] Crear archivo CSS de tema Tahiche
- [ ] Actualizar plantilla de login
- [ ] Actualizar MenuTemplate con nuevo logo/colores
- [ ] Actualizar página About con créditos
- [ ] Actualizar metadatos HTML
- [ ] Verificar que no se modifica copyright de archivos Core
