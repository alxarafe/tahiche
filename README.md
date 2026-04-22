<p align="center">
  <a href="https://tahiche.com">
    <img src="https://upload.wikimedia.org/wikipedia/commons/d/de/Logo-Tahiche.png" width="300" title="Tahiche Logo" alt="Tahiche Logo">
  </a>
</p>

---

> **🚀 Portfolio Showcase & Architectural Modernization**
> This repository is a technical demonstration project led and developed by **Rafael San José**.
> It recreates the comprehensive modernization of a legacy PHP monolithic system (**FacturaScripts**) into a decoupled and secure hybrid platform.
> **Key Skills Shown:** Hexagonal Architecture, Strangler Fig Pattern, Legacy Modernization, Alxarafe Framework Integration, and Docker-based DevOps.

![PHP Version](https://img.shields.io/badge/PHP-8.2+-blueviolet?style=flat-square)
![CI](https://github.com/Alxarafe/tahiche/actions/workflows/ci.yml/badge.svg)
![Static Analysis](https://img.shields.io/badge/static%20analysis-PHPStan-blue?style=flat-square)
![License: LGPL](https://img.shields.io/badge/license-LGPL-green.svg?color=2670c9&style=flat-square)

*[Leer en Español](README.es.md)*

**Tahiche** is an open-source **ERP/CRM and accounting software** undergoing an ambitious architectural modernization.
Based on [FacturaScripts](https://facturascripts.com/), the project is being progressively restructured using the **Alxarafe Framework** to adopt a modern, secure, and standards-compliant architecture.

Tahiche's primary strategy is to enable a smooth transition through a **hybrid approach**. It maintains compatibility with the vast FacturaScripts legacy plugin ecosystem while moving the system core to a public directory structure (`/public`), environment-based configuration (`.env`), and decoupled modern controllers.

## 🎯 Objectives
- **Security**: Isolate the entry point to the `/public` directory, preventing exposure of sensitive files.
- **Modernization**: Progressively replace legacy controllers and views with **Alxarafe Resource Controller** components.
- **Decoupling**: Separate infrastructure logic (Database, Mailer, Auth) through specific adapters in `src/Infrastructure`.
- **Compatibility**: Maintain support for existing FacturaScripts plugins through a backward-compatibility layer.
- **Quality**: Implement local CI pipelines for PSR-12 standards, static analysis with PHPStan, and automated testing.

## 🏗️ Hybrid Architecture
Tahiche uses the **Strangler Fig** pattern for feature migration:
- **Modern Layer**: Located in `src/` and `Modules/`, utilizing declarative controllers and infrastructure adapters.
- **Legacy Layer**: Kept in `Core/` and `Dinamic/`, providing support for original FacturaScripts classes and logic.
- **Adapters**: Bridges in `src/Infrastructure/Adapter` connecting the modern engine with legacy FacturaScripts libraries (e.g., `TahicheRepository` for the legacy ORM).

## 🚀 Installation & Development

### Requirements
- PHP 8.2+
- Composer
- Docker and Docker Compose (recommended)

### Docker-based Development (Recommended)
Tahiche includes an optimized Docker environment:

1. **Clone the repository**:
   ```bash
   git clone https://github.com/Alxarafe/tahiche.git
   cd tahiche
   ```
2. **Configure environment**:
   ```bash
   cp .env.example .env
   ```
3. **Start services**:
   ```bash
   ./bin/docker_start.sh
   ```
4. **Install dependencies**:
   ```bash
   docker exec tahiche_php composer install
   ```
5. **Access**: The system will be available at [http://localhost:8082](http://localhost:8082).

### Quality Verification (Local CI)
You can run the entire local verification pipeline (Styles, PHPStan, and Tests) with a single command:
```bash
./bin/ci_local.sh
```

## 📚 Documentation
- **Docker Environment**: [docs/docker.md](docs/docker.md)
- **Migration Guide**: [docs/migration.md](docs/migration.md)

## 🤝 Contributing
Contributions are welcome! If you find a bug or have an improvement, please open an issue or submit a pull request.

## ⚖️ License
Tahiche is distributed under the **GNU Lesser General Public License (LGPL)**. See the [LICENSE](LICENSE) file for details.

---
<p align="center">
  Modernizing the open-source ERP, step by step.
</p>