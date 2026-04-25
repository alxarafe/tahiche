# Mover punto de entrada a `public/`

> **Fase**: 0.1  
> **Prioridad**: Alta  
> **Riesgo**: 🟢 Bajo

---

## Estado actual

| Aspecto | Estado | Detalle |
|---------|--------|---------|
| `public/index.php` | ✅ Existe | Entry point moderno con Kernel hexagonal |
| Nginx Docker | ✅ Correcto | `root /var/www/html/public` |
| `config.php` en root | ⚠️ Riesgo | Credenciales DB accesibles si no hay proxy |
| `.htaccess` en `public/` | ✅ Existe | Rewrite rules correctas |
| `index.php` en root | ✅ No existe | No hay entry point legacy en root |
| `htaccess-sample` en root | ⚠️ Residuo | Archivo legacy que debería moverse o eliminarse |

## Qué falta por hacer

### 1. Protección del directorio raíz

Para entornos donde el usuario apunta Apache directamente al root (sin proxy), necesitamos un `index.php` en root que redirija a `public/`:

```php
<?php
// /index.php — Redirector de seguridad
// Este archivo redirige al punto de entrada real en public/
header('Location: public/index.php');
exit;
```

Y un `.htaccess` en root que proteja archivos sensibles:

```apache
# /.htaccess
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^$ public/ [L]
    RewriteRule (.*) public/$1 [L]
</IfModule>

# Denegar acceso a archivos sensibles
<FilesMatch "^(config\.php|\.env|composer\.(json|lock)|phpunit\.xml|phpstan\.neon)$">
    Require all denied
</FilesMatch>
```

### 2. Mover `config.php` a un lugar seguro

Opciones:
- **Opción A** (recomendada): Mantener `config.php` en root pero protegerlo con `.htaccess`. El Kernel ya lo carga con `APP_PATH . '/config.php'`.
- **Opción B**: Mover a `config/config.php` y actualizar la referencia en `Kernel.php`.
- **Opción C**: Migrar todo a `.env` (phpdotenv ya está como dependencia).

**Recomendación**: Opción A a corto plazo (cambio mínimo), migrar a Opción C a largo plazo.

### 3. Verificar rutas de assets

Las rutas actuales de assets pasan por el controlador `Files` de FS:
- `/Core/Assets/*` → `Controller\Files`
- `/Dinamic/Assets/*` → `Controller\Files`
- `/Plugins/*` → `Controller\Files`
- `/node_modules/*` → `Controller\Files`

Con nginx, estas rutas se reescriben a `public/index.php`. Verificar que los assets estáticos en `public/Assets/`, `public/js/`, `public/themes/` se sirven directamente sin pasar por PHP.

**Mejora nginx**:
```nginx
# Servir assets estáticos directamente
location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
    expires 30d;
    add_header Cache-Control "public, immutable";
    try_files $uri =404;
}
```

### 4. Actualizar `composer.json` dev-server

```json
"dev-server": "php -S localhost:8000 -t public public/index.php"
```

### 5. Limpiar residuos del root

| Archivo | Acción |
|---------|--------|
| `htaccess-sample` | Mover contenido a `public/.htaccess` y eliminar |
| `cookie.txt` | Eliminar (debug residual) |
| `scratch/` | Mover a `.gitignore` o eliminar |

---

## Checklist de implementación

- [ ] Crear `index.php` redirector en root
- [ ] Crear `.htaccess` protector en root
- [ ] Actualizar nginx para servir assets estáticos directamente
- [ ] Actualizar script `dev-server` en `composer.json`
- [ ] Eliminar `htaccess-sample`, `cookie.txt`
- [ ] Verificar que todas las rutas de assets funcionan
- [ ] Verificar que la instalación limpia funciona con el nuevo layout
- [ ] Documentar en README la nueva estructura
