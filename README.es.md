<p align="center">
  <a href="https://tahiche.com">
    <img src="https://upload.wikimedia.org/wikipedia/commons/d/de/Logo-Tahiche.png" width="300" title="Logo de Tahiche" alt="Logo de Tahiche">
  </a>
</p>

<p align="center">
  <strong>Software ERP y Contabilidad de Código Abierto</strong><br>
  Arquitectura Híbrida Moderna (Legacy + Alxarafe Framework)
</p>

<p align="center">
  <a href="https://opensource.org/licenses/LGPL"><img src="https://img.shields.io/badge/license-LGPL-green.svg?color=2670c9&style=for-the-badge&label=License&logoColor=000000&labelColor=ececec" alt="Licencia: LGPL"></a>
  <a href="https://github.com/Alxarafe/tahiche/releases/latest"><img src="https://img.shields.io/github/v/release/Alxarafe/tahiche?style=for-the-badge&logo=github&logoColor=white" alt="Última Versión"></a>
  <a href="https://github.com/Alxarafe/tahiche/pulls"><img alt="Se aceptan Pull Request" src="https://img.shields.io/badge/PRs_Welcome-brightgreen?style=for-the-badge"></a>
</p>

<p align="center">
  <a href="https://tahiche.com/probar-online">🚀 Probar Demo</a> •
  <a href="#-documentación">📚 Documentación</a> •
  <a href="https://discord.gg/qKm7j9AaJT">💬 Discord</a> •
  <a href="README.md">🇬🇧 English</a>
</p>

---

## 🎯 ¿Qué es Tahiche?

Tahiche es un **software ERP y de contabilidad de código abierto** integral diseñado para pequeñas y medianas empresas. Actualmente se encuentra en un proceso de modernización, transicionando de una arquitectura monolítica a una estructura modular moderna basada en el **Alxarafe Framework**.

### ✨ Características Principales

- 🧾 **Arquitectura Cloud Moderna** - Punto de entrada aislado (`/public`) para mayor seguridad.
- 🔐 **Configuración Segura** - Gestión de credenciales mediante variables de entorno (`.env`).
- 🚀 **Enrutamiento Híbrido** - Ejecuta plugins heredados mientras adopta vistas Blade modernas y Eloquent ORM.
- 📦 **Inventario y CRM** - Gestión robusta de stock, clientes y proveedores.
- 📊 **Contabilidad** - Módulo financiero completo (Soporte Legacy).
- 🔌 **Sistema de Plugins** - Mantiene la compatibilidad con los plugins existentes de FS.

## 🏗️ Aviso sobre la Nueva Arquitectura

Tahiche ha evolucionado a una estructura más segura:
- **Directorio Público**: El servidor web **DEBE** apuntar al directorio `/public`.
- **Variables de Entorno**: Utiliza `.env` para la base de datos y credenciales de la app.
- **Core Moderno**: Integración con Alxarafe para Auth, Routing e interfaz moderna.

## 🚀 Instalación

### Requisitos del Sistema
- PHP 8.2 o superior
- MySQL / MariaDB 10.11+
- Composer
- Servidor Web (Nginx o Apache) configurado para apuntar a `/public`

### Paso a Paso

1. **Clonar e Instalar**
   ```bash
   git clone https://github.com/Alxarafe/tahiche.git
   cd tahiche
   composer install
   ```

2. **Configurar Entorno**
   ```bash
   cp .env.example .env
   # Edita el archivo .env con tus credenciales de base de datos
   ```

3. **Configuración del Servidor Web**
   Establece la raíz de documentos (document root) en la carpeta `/public` del proyecto.

   **Ejemplo para Nginx:**
   ```nginx
   root /var/www/tahiche/public;
   index index.php;
   location / {
       try_files $uri $uri/ /index.php?$query_string;
   }
   ```

## 📚 Documentación

- **Guía de Usuario**: [tahiche.com/ayuda](https://tahiche.com/ayuda)
- **Documentación para Desarrolladores**: [tahiche.com/ayuda-dev](https://tahiche.com/ayuda-dev)

## 🧪 Desarrollo

```bash
# Iniciar el servidor de desarrollo (apuntando a public)
php -S localhost:8000 -t public/
```

## 🔒 Seguridad

Para vulnerabilidades de seguridad o temas sensibles, por favor contacta con el **Tahiche Team** en [tahiche@alxarafe.com](mailto:tahiche@alxarafe.com).

---

<p align="center">
  Hecho con ❤️ por la comunidad de Tahiche
</p>