> **🚀 Portfolio Showcase & Architectural Modernization**
> Este repositorio es un proyecto de demostración técnica liderado y desarrollado por **Rafael San José**.
> Recrea la modernización integral de un sistema PHP monolítico legado (**FacturaScripts**) hacia una plataforma híbrida desacoplada y segura.
> **Habilidades Clave Mostradas:** Arquitectura Hexagonal, Patrón Strangler Fig, Modernización de Legado, Integración con Alxarafe Framework y DevOps con Docker.

![PHP Version](https://img.shields.io/badge/PHP-8.2+-blueviolet?style=flat-square)
![CI](https://github.com/Alxarafe/tahiche/actions/workflows/ci.yml/badge.svg)
![Static Analysis](https://img.shields.io/badge/static%20analysis-PHPStan-blue?style=flat-square)
![License: LGPL](https://img.shields.io/badge/license-LGPL-green.svg?color=2670c9&style=flat-square)

*[Read in English](README.md)*

**Tahiche** es un **ERP/CRM y software de contabilidad** de código abierto inmerso en una ambiciosa modernización arquitectónica. 
El proyecto nace a partir de [FacturaScripts](https://facturascripts.com/), pero está siendo reestructurado progresivamente utilizando el **Alxarafe Framework** para implementar una arquitectura moderna, segura y basada en estándares actuales de desarrollo.

La estrategia principal de Tahiche es permitir una transición suave mediante un **enfoque híbrido**. Mantiene la compatibilidad con el ecosistema de plugins legados de FacturaScripts mientras traslada el núcleo del sistema a una estructura de directorio público (`/public`), configuración basada en entornos (`.env`) y controladores modernos desacoplados.

## 🎯 Objetivos
- **Seguridad**: Aislar el punto de entrada al directorio `/public`, evitando la exposición de archivos sensibles.
- **Modernización**: Reemplazar progresivamente los controladores y vistas legadas por componentes de **Alxarafe Resource Controller**.
- **Desacople**: Separar la lógica de infraestructura (Base de Datos, Mailer, Auth) mediante adaptadores específicos en `src/Infrastructure`.
- **Compatibilidad**: Mantener el soporte para el vasto ecosistema de plugins de FacturaScripts mediante una capa de retrocompatibilidad.
- **Calidad**: Implementar pipelines de CI locales para asegurar estándares PSR-12, análisis estático con PHPStan y tests automatizados.

## 🏗️ Arquitectura Híbrida
Tahiche utiliza el patrón **Strangler Fig** para migrar funcionalidades:
- **Capa Moderna**: Ubicada en `src/` y `Modules/`, utiliza controladores declarativos y adaptadores de infraestructura.
- **Capa Legacy**: Mantenida en `Core/` y `Dinamic/`, proporcionando soporte a las clases y lógica de FacturaScripts original.
- **Adaptadores**: Puentes en `src/Infrastructure/Adapter` que conectan el motor moderno con las librerías de FacturaScripts (como `TahicheRepository` para el ORM legado).

## 🚀 Instalación y Desarrollo

### Requisitos
- PHP 8.2+
- Composer
- Docker y Docker Compose (recomendado)

### Desarrollo con Docker (Recomendado)
Tahiche incluye un entorno Docker optimizado:

1. **Clonar el repositorio**:
   ```bash
   git clone https://github.com/Alxarafe/tahiche.git
   cd tahiche
   ```
2. **Configurar entorno**:
   ```bash
   cp .env.example .env
   ```
3. **Iniciar servicios**:
   ```bash
   ./bin/docker_start.sh
   ```
4. **Instalar dependencias**:
   ```bash
   docker exec tahiche_php composer install
   ```
5. **Acceder**: El sistema estará disponible en [http://localhost:8082](http://localhost:8082).

### Verificación de Calidad (CI Local)
Puedes ejecutar todo el pipeline de verificación local (Estilos, PHPStan y Tests) con un solo comando:
```bash
./bin/ci_local.sh
```

## 📚 Documentación
- **Entorno Docker**: [docs/docker.md](docs/docker.md)
- **Guía de Migración**: [docs/migration.md](docs/migration.md)

## 🤝 Contribuir
¡Las contribuciones son bienvenidas! Si encuentras un error o tienes una mejora, por favor abre un issue o envía un pull request.

## ⚖️ Licencia
Tahiche se distribuye bajo la licencia **GNU Lesser General Public License (LGPL)**. Consulta el archivo [LICENSE](LICENSE) para más detalles.

---
<p align="center">
  Modernizando el ERP de código abierto, paso a paso.
</p>