<p align="center">
  <a href="https://tahiche.com">
    <img src="https://upload.wikimedia.org/wikipedia/commons/d/de/Logo-Tahiche.png" width="300" title="Tahiche Logo" alt="Tahiche Logo">
  </a>
</p>

<p align="center">
  <strong>Open Source ERP & Accounting Software</strong><br>
  Modern Hybrid Architecture (Legacy + Alxarafe Framework)
</p>

<p align="center">
  <a href="https://opensource.org/licenses/LGPL"><img src="https://img.shields.io/badge/license-LGPL-green.svg?color=2670c9&style=for-the-badge&label=License&logoColor=000000&labelColor=ececec" alt="License: LGPL"></a>
  <a href="https://github.com/Alxarafe/tahiche/releases/latest"><img src="https://img.shields.io/github/v/release/Alxarafe/tahiche?style=for-the-badge&logo=github&logoColor=white" alt="Latest Release"></a>
  <a href="https://github.com/Alxarafe/tahiche/pulls"><img alt="PRs Welcome" src="https://img.shields.io/badge/PRs_Welcome-brightgreen?style=for-the-badge"></a>
</p>

<p align="center">
  <a href="https://tahiche.com/probar-online">🚀 Try Demo</a> •
  <a href="#-documentation">📚 Documentation</a> •
  <a href="https://discord.gg/qKm7j9AaJT">💬 Discord</a> •
  <a href="README.es.md">🇪🇸 Español</a>
</p>

---

## 🎯 What is Tahiche?

Tahiche is a comprehensive **open-source ERP and accounting software** designed for small and medium businesses. It is currently undergoing a modernization process, transitioning from a monolithic architecture to a modern, modular structure based on the **Alxarafe Framework**.

### ✨ Key Features

- 🧾 **Modern Cloud Architecture** - Isolated entry point (`/public`) for enhanced security.
- 🔐 **Secure Configuration** - Environment-based configuration via `.env` files.
- 🚀 **Hybrid Routing** - Seamlessly runs legacy plugins while adopting modern Blade views and Eloquent ORM.
- 📦 **Inventory & CRM** - Robust management of stocks, customers, and suppliers.
- 📊 **Accounting** - Complete financial module (Legacy supported).
- 🔌 **Plugin System** - Maintains compatibility with existing FS plugins.

## 🏗️ New Architecture Notice

Tahiche has transitioned to a more secure structure:
- **Public Directory**: The web server **MUST** point to the `/public` directory.
- **Environment Variables**: Use `.env` for database and app credentials.
- **Modern Core**: Integration with Alxarafe for Auth, Routing, and modern UI.

## 🚀 Installation

### System Requirements
- PHP 8.2 or higher
- MySQL / MariaDB 10.11+
- Composer
- Web Server (Nginx or Apache) configured to point to `/public`

### Step-by-Step

1. **Clone & Install**
   ```bash
   git clone https://github.com/Alxarafe/tahiche.git
   cd tahiche
   composer install
   ```

2. **Configure Environment**
   ```bash
   cp .env.example .env
   # Edit .env with your database credentials
   ```

3. **Web Server Configuration**
   Point your document root to the `/public` folder of the project.

   **Nginx Example:**
   ```nginx
   root /var/www/tahiche/public;
   index index.php;
   location / {
       try_files $uri $uri/ /index.php?$query_string;
   }
   ```

## 📚 Documentation

- **User Guide**: [tahiche.com/ayuda](https://tahiche.com/ayuda)
- **Developer Documentation**: [tahiche.com/ayuda-dev](https://tahiche.com/ayuda-dev)

## 🧪 Development

```bash
# Run the development server (pointing to public)
php -S localhost:8000 -t public/
```

## 🔒 Security

For security vulnerabilities or sensitive issues, please contact the **Tahiche Team** at [tahiche@alxarafe.com](mailto:tahiche@alxarafe.com).

---

<p align="center">
  Made with ❤️ by the Tahiche community
</p>