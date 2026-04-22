# Informe Comparativo: Tahiche vs. Alxarafe/Alxarafe

> **Fecha:** 2026-04-17 (actualizado)  
> **Autor:** Análisis automatizado  
> **Versión Alxarafe analizada:** v0.5.8 / v0.6.0 (última release: 29 Mar 2026)  
> **Objetivo:** Evaluar las posibilidades y mejoras que tendría el repositorio `tahiche` si adoptase el framework `alxarafe/alxarafe`, analizando puntos fuertes, puntos débiles, y la viabilidad de una migración a arquitectura hexagonal.

---

## Índice

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Descripción de los Repositorios](#2-descripción-de-los-repositorios)
3. [Análisis del Repositorio Tahiche (Estado Actual)](#3-análisis-del-repositorio-tahiche-estado-actual)
4. [Análisis del Repositorio Alxarafe/Alxarafe](#4-análisis-del-repositorio-alxarafealxarafe)
5. [Comparativa Técnica Detallada](#5-comparativa-técnica-detallada)
6. [Qué Mejoraría con Alxarafe](#6-qué-mejoraría-con-alxarafe)
7. [Qué Empeoraría con Alxarafe](#7-qué-empeoraría-con-alxarafe)
8. [Análisis de Arquitectura Hexagonal](#8-análisis-de-arquitectura-hexagonal)
9. [Recomendaciones](#9-recomendaciones)
10. [Matrices de Decisión](#10-matrices-de-decisión)
11. [Análisis Detallado: Migración de Base de Datos](#11-análisis-detallado-migración-de-base-de-datos)
12. [Análisis Detallado: Migración del Sistema de Plantillas](#12-análisis-detallado-migración-del-sistema-de-plantillas)
13. [Estrategia de Migración en 2 Fases (v0.5.8 → v0.6.0+)](#13-estrategia-de-migración-en-2-fases-v058--v060)
14. [Apéndice: Historial de Releases de Alxarafe](#14-apéndice-historial-de-releases-de-alxarafe)

---

## 1. Resumen Ejecutivo

**Tahiche** es un fork modificado de Tahiche, un ERP PHP open-source maduro con una arquitectura monolítica basada en un patrón MVC propio con sistema de plugins. Su fortaleza reside en la completitud funcional (contabilidad, facturación, CRM, inventario) y la estabilidad de un producto con años de desarrollo.

**Alxarafe/Alxarafe** es un microframework PHP desarrollado desde cero con arquitectura hexagonal (Domain-Driven Design), que usa componentes modernos de Laravel/Illuminate y Symfony. Está diseñado para ser una base sobre la cual construir aplicaciones, no un ERP completo.

La adopción de Alxarafe significaría una **reescritura sustancial** del core del ERP, ganando en arquitectura, testabilidad y modernidad, pero perdiendo temporalmente en funcionalidad y estabilidad.

---

## 2. Descripción de los Repositorios

### 2.1 Tahiche (Fork de Tahiche)

| Aspecto | Detalle |
|---------|---------|
| **Origen** | Fork de `Alxarafe/tahiche` |
| **Namespace raíz** | `Tahiche\Core`, `Tahiche\Dinamic`, `Tahiche\Plugins` |
| **PHP mínimo** | 8.0 |
| **Licencia** | LGPL-3.0 |
| **Motor de plantillas** | Twig 3.x |
| **Base de datos** | MySQL / PostgreSQL (acceso directo via `mysqli`/`pg_*`) |
| **Modelos** | ~85 modelos de dominio (contabilidad, facturación, CRM, etc.) |
| **Controladores** | ~111 controladores (Edit*, List*, Api*, etc.) |
| **Sistema de plugins** | Sí, con carpeta `Plugins/` + `Dinamic/` para overrides |
| **Arquitectura** | MVC monolítico con sistema de deploy de plugins |

### 2.2 Alxarafe/Alxarafe

| Aspecto | Detalle |
|---------|---------|
| **Tipo** | Microframework / Library |
| **Namespace raíz** | `Alxarafe\Domain`, `Alxarafe\Application`, `Alxarafe\Infrastructure` |
| **PHP mínimo** | 8.2 |
| **Licencia** | GPL-3.0 |
| **Motor de plantillas** | Blade (vía `jenssegers/blade`) |
| **Base de datos** | Eloquent ORM (`illuminate/database`) + PDO adapters |
| **ORM** | Eloquent (Laravel) |
| **Arquitectura** | Hexagonal (Puertos y Adaptadores) + DDD |
| **Dependencias clave** | Illuminate Database/Events/View, Symfony Translation/Mailer/YAML, Firebase JWT, DomPDF |
| **Frontend** | TypeScript + Webpack |
| **Docker** | Sí, con soporte completo (MariaDB, Nginx, PHP-FPM) |
| **CI/CD** | GitHub Actions (CI, tests, deploy docs, deploy app) |
| **Última versión** | v0.6.0 (29 Mar 2026) |
| **Releases totales** | 21+ (desde v0.4.0 en Feb 2026 hasta v0.6.0 en Mar 2026) |
| **Ritmo de desarrollo** | ~10 releases/mes (extremadamente activo) |
| **Creado en GitHub** | 14 Dic 2018 (repo), desarrollo activo intensivo desde Feb 2026 |
| **Instalación** | `composer create-project alxarafe/alxarafe-template` o `composer require alxarafe/alxarafe` |
| **Autor** | Rafael San José (rsanjoseo — mismo autor que tahiche/alixar) |

---

## 3. Análisis del Repositorio Tahiche (Estado Actual)

### 3.1 Puntos Fuertes ✅

#### Funcionalidad completa de ERP
- **85 modelos de dominio** que cubren: facturación (clientes/proveedores), contabilidad (asientos, partidas, subcuentas), inventario (almacenes, stocks, variantes), CRM (clientes, contactos, agentes), documentos comerciales (presupuestos, pedidos, albtahichees, facturas).
- Flujos de negocio completos con transformaciones de documentos (presupuesto → pedido → albarán → factura).
- Sistema de recibos y pagos automatizado.
- Regularización de impuestos (IVA, IRPF).

#### Sistema de plugins robusto
- Mecanismo maduro de extensibilidad vía `Plugins.php` con gestión de dependencias, orden de carga, deploy automático.
- Patrón `Dinamic/` que permite override de cualquier clase del Core sin modificar el original.
- Workers y WorkQueue para procesamiento asíncrono de eventos de negocio.

#### API REST integrada
- API versión 3 con endpoints especializados: creación de documentos, exportación, pagos, gestión de plugins, subida de archivos.
- Sistema de API Keys con control de acceso granular.

#### Routing maduro
- Sistema de rutas con soporte de wildcards, prioridades y rutas personalizadas persistidas en JSON.
- Rebuild automático de rutas al desplegar plugins.

#### Estabilidad probada
- Código base heredado de Tahiche con años de uso en producción.
- Amplia cobertura de edge cases en la lógica de negocio.

#### Internacionalización
- Sistema de traducciones completo con `Translator.php`.
- Soporte para múltiples idiomas y formatos de moneda/fecha.

### 3.2 Puntos Débiles ❌

#### Acoplamiento fuerte entre capas
- Los modelos acceden directamente a la base de datos sin capa de abstracción intermedia (sin repositorios).
- Los controladores mezclan lógica de presentación, autenticación y negocio en un solo `run()`.
- La clase `Tools.php` (843 líneas) es una "god class" que mezcla formateo de fechas, gestión de archivos, configuración, traducciones y logging.

#### Capa de persistencia primitiva
- Acceso a BBDD vía extensiones nativas `mysqli`/`pg_*`, sin beneficiarse de un ORM moderno.
- SQL escrito manualmente en los modelos sin query builder.
- Sin migraciones formales de base de datos (se usa `DbUpdater` con XMLs).
- No hay soporte para conexiones múltiples ni connection pooling.

#### Ausencia de inyección de dependencias
- No hay contenedor de servicios ni DI container.
- Las dependencias se resuelven vía `new Class()` directamente, creando acoplamiento rígido.
- Imposible hacer mocking efectivo para tests unitarios.

#### Testing insuficiente
- PHPUnit configurado pero sin evidencia de amplia cobertura.
- PHPStan en versión `0.12.93` (obsoleta, actual es 2.x).
- La arquitectura monolítica dificulta los tests unitarios aislados.

#### Motor de plantillas acoplado
- Twig 3.x funciona bien, pero las plantillas mezclan lógica de negocio con presentación.
- Sin sistema de temas ni layouts reutilizables modernos.

#### Sin soporte Docker nativo
- No incluye configuración Docker para desarrollo local.
- Dependencia de un servidor web externo configurado manualmente.

#### Dependencias obsoletas
- `rospdf/pdf-php: 0.12.*` — biblioteca de PDF poco mantenida.
- PHPStan 0.12 — varias versiones major por detrás.

#### Sin arquitectura de eventos de domino
- WorkQueue ofrece una cola básica pero no hay un bus de eventos/comandos formal.
- Los "mods" (Calculator mods, Sales mods) son un sistema ad-hoc sin estándar.

---

## 4. Análisis del Repositorio Alxarafe/Alxarafe

### 4.1 Puntos Fuertes ✅

#### Arquitectura Hexagonal bien definida
```
src/
├── Domain/           ← Modelos de dominio, Puertos, Eventos
│   ├── Model/        ← AggregateRoot, DomainEvent, EntityId
│   └── Port/         ← Driven (Auth, Logger, Mailer, Persistence)
│                       Driving (CommandBus)
├── Application/      ← Casos de uso, Bus de Comandos/Queries
│   ├── Bus/          ← Command, Query, SimpleCommandBus
│   └── Service/      ← TransactionalService
└── Infrastructure/   ← Adaptadores, HTTP, Persistencia, Servicios
    ├── Adapter/      ← Auth, Logger, Mailer, Persistence
    ├── Component/    ← Fields, Filters, Containers, Workflows
    ├── Http/         ← Controllers, Router, Routes
    ├── Persistence/  ← Config, Database, Model, Template
    ├── Service/      ← API, Email, Hook, Markdown, PDF
    └── Tools/        ← Debug, Dispatcher, ModuleManager
```

- Clara separación de Domain, Application e Infrastructure.
- Puertos e Interfaces bien definidos (`AuthPort`, `LoggerPort`, `MailerPort`, `PersistencePort`, `CommandBusPort`).
- Los adaptadores implementan los puertos, permitiendo intercambiar implementaciones.

#### ORM moderno (Eloquent)
- Uso de `illuminate/database` (Eloquent) para la capa de persistencia.
- Query builder potente, migraciones, seeders, relaciones.
- Soporte para múltiples drivers (MySQL, PostgreSQL, SQLite).

#### Bus de Comandos y Queries (CQRS light)
- `SimpleCommandBus` para despacho de comandos con handlers registrados.
- Separación de `Command` vs `Query` para lectura/escritura.
- `TransactionalService` para envolver operaciones en transacciones.

#### Sistema de Componentes declarativo
- Campos (`Text`, `Select`, `Boolean`, `Date`, `Image`, `Icon`, etc.).
- Filtros (`TextFilter`, `SelectFilter`, `DateRangeFilter`, `RelationFilter`, etc.).
- Contenedores (`Tab`, `TabGroup`, `Panel`, `Row`, `Separator`).
- Workflows (`StatusTransition`, `StatusWorkflow`).

#### Herramientas de calidad de código
- PHPStan 2.x, Psalm 6.x, PHPMD, PHPMetrics.
- PHPCS con reglas personalizadas.
- Tests unitarios y de integración.

#### Soporte Docker completo
- `docker-compose.yml` con MariaDB, Nginx, PHP-FPM.
- Scripts de inicio, parada, limpieza y migraciones.
- Archivo `.env.example` para configuración por entorno.

#### CI/CD automatizado
- GitHub Actions con pipelines de CI, tests, deploy de docs y deploy de aplicación.

#### Frontend moderno
- TypeScript + Webpack para assets frontend.
- Sistema de temas con múltiples opciones (default, cyberpunk, vintage, high-contrast, alternative).
- DebugBar integrada para desarrollo.

#### Documentación extensa
- Documentación bilingüe (inglés/español) con VitePress.
- Guías de arquitectura, ciclo de vida, API development, testing, temas, etc.
- Documentación PHPDoc generada automáticamente.

#### Sistema de módulos dinámico
- `ModuleManager` que descubre y registra módulos automáticamente.
- Atributos PHP 8 para enrutamiento (`#[ApiRoute]`, `#[Menu]`, `#[ModuleInfo]`).
- Migraciones y seeders por módulo.

### 4.2 Puntos Débiles ❌

#### No es un ERP completo
- Solo proporciona un skeleton con un módulo de ejemplo (Agenda/Contactos).
- No tiene modelos de facturación, contabilidad, inventario ni CRM.
- Toda la lógica de negocio del ERP tendría que ser reimplementada.

#### Ecosistema en fase de arranque
- 1 estrella en GitHub, 4 suscriptores, sin forks externos.
- **21+ releases** publicadas entre febrero y marzo de 2026, con un ritmo de ~10 releases/mes. Última versión: **v0.6.0** (29 Mar 2026) con "Pure Hexagonal Architecture".
- Desarrollo intensivo por un solo contribuidor activo (el mismo autor de tahiche).
- Sin marketplace de plugins ni extensiones de terceros aún.
- Documentación oficial desplegada en [docs.alxarafe.com](https://docs.alxarafe.com).

#### Complejidad innecesaria para ciertas operaciones
- La indirección hexagonal añade capas (Puerto → Adaptador → Implementación) que ralentizan el desarrollo de features simples.
- El bus de comandos puede ser overkill para CRUDs simples.

#### Blade vs Twig
- Blade es popular en el ecosistema Laravel, pero Twig es más maduro para aplicaciones standalone.
- Migrar todas las plantillas Twig existentes a Blade sería un esfuerzo considerable.

#### Dependencia del ecosistema illuminate/Laravel
- Aunque no usa Laravel completo, depende de componentes illuminate (database, events, view).
- Estas dependencias pueden entrar en conflicto si se necesitan versiones específicas.

---

## 5. Comparativa Técnica Detallada

| Característica | Tahiche (Tahiche) | Alxarafe |
|---|---|---|
| **Arquitectura** | MVC monolítico | Hexagonal (DDD) |
| **PHP mínimo** | 8.0 | 8.2 |
| **ORM** | Ninguno (SQL directo) | Eloquent (illuminate/database) |
| **Query Builder** | No (SQL manual) | Sí (Eloquent Builder) |
| **Migraciones DB** | XML + DbUpdater | Clases PHP (estilo Laravel) |
| **Inyección Dependencias** | No | Sí (`ServiceContainer`) |
| **Bus de Comandos** | No | Sí (CQRS light) |
| **Eventos de Dominio** | WorkQueue básica | `DomainEvent` + EventBus |
| **Plantillas** | Twig 3.x | Blade (jenssegers/blade) |
| **API** | REST v3 custom | REST con `#[ApiRoute]` attributes |
| **Autenticación** | Cookies + LogKey | JWT + Session Adapter |
| **PDF** | rospdf/pdf-php (0.12) | DomPDF (3.1) |
| **Email** | PHPMailer 6.x | Symfony Mailer 7.x |
| **Frontend** | Bootstrap 5 + Twig | TypeScript + Webpack + Blade |
| **Docker** | No | Sí (completo) |
| **CI/CD** | No | GitHub Actions |
| **Análisis estático** | PHPStan 0.12 | PHPStan 2.x + Psalm 6.x + PHPMD |
| **Temas** | No | Sí (5 temas incluidos) |
| **Debug** | DebugBar propio | php-debugbar integrado |
| **Documentación** | README básico | VitePress bilingüe completo |
| **Modelos de negocio** | 85+ (ERP completo) | ~5 (skeleton demo) |
| **Controladores** | 111+ | ~10 (framework base) |
| **Plugins/Módulos** | Sistema maduro con .ini | Módulos con atributos PHP 8 |
| **Comunidad** | Hereda de Tahiche | 1 contribuidor activo (mismo autor) |
| **Releases recientes** | N/A (fork estable) | 21+ en 2 meses (muy activo) |
| **Instalación** | Clon manual + composer | `composer create-project` (estándar) |

---

## 6. Qué Mejoraría con Alxarafe

### 6.1 Calidad Arquitectónica (Impacto: Alto)
- **Separación de responsabilidades:** La lógica de negocio quedaría aislada en `Domain/`, completamente desacoplada de la base de datos, HTTP y presentación.
- **Testabilidad:** Los puertos/interfaces permiten inyectar mocks fácilmente, logrando tests unitarios reales sin base de datos.
- **Mantenibilidad a largo plazo:** Los cambios en infraestructura (ej: cambiar de MySQL a PostgreSQL, o de Blade a otro motor) no afectan al dominio.

### 6.2 Productividad en CRUD (Impacto: Alto)
- **Eloquent ORM:** Eliminación de SQL manual, relaciones declarativas, eager loading, scopes reutilizables.
- **Migraciones:** Versionado de esquema de BBDD con rollback, sin XMLs frágiles.
- **Componentes declarativos:** Los campos de formulario, filtros y layouts se definen con clases PHP tipadas en lugar de XML/Twig complejo.

### 6.3 Infraestructura de Desarrollo (Impacto: Medio-Alto)
- **Docker:** Entorno de desarrollo reproducible y consistente para todos los contribuidores.
- **CI/CD:** Tests automáticos, deploy continuo, quality gates obligatorios.
- **Análisis estático moderno:** PHPStan 2.x + Psalm detectan más bugs en tiempo de desarrollo.

### 6.4 Seguridad (Impacto: Medio)
- **JWT para API:** Más robusto y stateless que cookies para endpoints API.
- **Symfony Mailer:** Más maduro y seguro que PHPMailer directo.
- **DomPDF 3.1:** Más mantenido y seguro que rospdf 0.12.

### 6.5 Frontend (Impacto: Medio)
- **TypeScript:** Tipado estático para JavaScript, menos errores en runtime.
- **Webpack:** Bundling optimizado, tree-shaking, minificación automática.
- **Sistema de temas:** Personalización visual sin modificar código base.

### 6.6 Extensibilidad (Impacto: Medio)
- **Atributos PHP 8:** `#[ApiRoute]`, `#[Menu]`, `#[RequireRole]` son más declarativos y autodocumentados que convenciones de nombres de archivos.
- **Hooks system:** `HookService` con `HookPoints` definidos para extensiones de terceros.
- **Bus de comandos:** Permite interceptar y modificar flujos de negocio sin herencia.

---

## 7. Qué Empeoraría con Alxarafe

### 7.1 Pérdida de Funcionalidad (Impacto: Crítico)
- **Todo el ERP tiene que reimplementarse:** 85 modelos, 111 controladores, plantillas, API, workers — NADA de esto existe en Alxarafe.
- **Tiempo estimado:** Una reimplementación completa podría llevar **12-24 meses** con un equipo pequeño.
- **Riesgo de regresiones:** Cada flujo de negocio reimplementado puede introducir bugs que el código actual ya tenía resueltos.

### 7.2 Incompatibilidad de Plugins (Impacto: Crítico)
- **Todos los plugins existentes dejarían de funcionar.** El sistema de plugins de Tahiche (`.ini`, `Dinamic/`, deploy) es completamente diferente al de Alxarafe (módulos con atributos PHP 8).
- Los contribuidores/usuarios con plugins desarrollados perderían su inversión.

### 7.3 Curva de Aprendizaje (Impacto: Alto)
- **DDD y Hexagonal:** Los desarrolladores familiarizados con el patrón MVC simple de Tahiche necesitarán formación en puertos, adaptadores, comandos, queries, agregados.
- **Eloquent:** Aunque popular, tiene un paradigma diferente al SQL directo actual.
- **TypeScript + Webpack:** Nuevo stack frontend que requiere conocimientos adicionales.

### 7.4 Complejidad para Features Simples (Impacto: Medio)
- **Overhead de capas:** Un CRUD simple requiere: Modelo (Domain) → Repositorio (Port/Adapter) → Comando/Query (Application) → Controlador (Infrastructure) → Template (Blade). En tahiche, es: Modelo → Controlador → Template.
- **Más archivos por feature:** Cada entidad puede requerir 5-8 archivos en lugar de 2-3.

### 7.5 Migración de Datos (Impacto: Medio)
- **Esquema de BBDD diferente:** Los nombres de tablas, columnas y relaciones pueden cambiar con Eloquent.
- **Scripts de migración de datos:** Necesarios para cada tabla existente.

### 7.6 Dependencias Más Pesadas (Impacto: Bajo)
- **illuminate/database + events + view:** Mayor footprint en `vendor/`, más dependencias transitivas.
- **Symfony Translation + Mailer + YAML:** Añade peso al paquete.

---

## 8. Análisis de Arquitectura Hexagonal

### 8.1 Ventajas de Migrar a Hexagonal

#### Independencia del framework
El dominio del ERP (facturas, asientos, stocks) quedaría en `Domain/` sin ninguna dependencia de framework. Si en el futuro se quiere cambiar de Blade a Twig, de Eloquent a Doctrine, o de MySQL a MongoDB, solo se cambian los adaptadores en `Infrastructure/`.

#### Testabilidad real
```php
// Actualmente en Tahiche (no testeable unitariamente):
class FacturaCliente extends ModelClass {
    public function save(): bool {
        // Escribe directamente a la DB
        return $this->saveUpdate(['total' => $this->total, ...]);
    }
}

// Con Hexagonal (100% testeable):
class CrearFacturaHandler implements CommandHandler {
    public function __construct(private FacturaRepository $repo) {}
    
    public function handle(CrearFacturaCommand $cmd): void {
        $factura = Factura::crear($cmd->cliente(), $cmd->lineas());
        $this->repo->save($factura); // Puerto inyectado, mockeable
    }
}
```

#### Evolución independiente
Los módulos de negocio (Contabilidad, Facturación, CRM, Inventario) pueden evolucionar independientemente con contratos claros entre ellos.

#### Mejor para equipos
Los límites claros entre capas permiten que diferentes desarrolladores trabajen en paralelo sin pisarse.

### 8.2 Desventajas de Migrar a Hexagonal

#### Inversión inicial masiva
| Concepto | Estimación |
|----------|-----------|
| Reescritura del Domain (85 modelos) | 3-6 meses |
| Implementar Puertos y Adaptadores | 1-2 meses |
| Reimplementar Controladores (111) | 3-4 meses |
| Migrar Plantillas (Twig → Blade) | 1-2 meses |
| Migrar API REST | 1 mes |
| Migrar Sistema de Plugins | 1-2 meses |
| Scripts de Migración de Datos | 1 mes |
| Testing de Regresión | 1-2 meses |
| **TOTAL ESTIMADO** | **12-20 meses** |

#### Overhead en desarrollo diario
Para cada nueva feature del ERP:
- **Con tahiche actual:** 1 Model + 1 Controller + 1 Template + 1 XMLView = 4 archivos
- **Con hexagonal completo:** 1 Entity + 1 Repository Interface + 1 Repository Implementation + 1 Command + 1 CommandHandler + 1 Controller + 1 Template = 7 archivos

#### Over-engineering para un ERP PYME
La arquitectura hexagonal brilla en sistemas con múltiples puertos de entrada (web, API, CLI, eventos) y que cambian frecuentemente de infraestructura. Un ERP PYME principalmente tiene una entrada web y una API, con infraestructura estable.

### 8.3 Alternativa: Hexagonal Pragmático

Una opción intermedia sería adoptar principios hexagonales **gradualmente** sin reescribir todo:

1. **Fase 1:** Introducir interfaces/contratos para los servicios clave (persistencia, email, PDF).
2. **Fase 2:** Migrar gradualmente los modelos a usar un ORM (Eloquent o Doctrine) detrás de repositorios.
3. **Fase 3:** Implementar un bus de eventos simple para desacoplar módulos.
4. **Fase 4:** Separar la lógica de negocio de los controladores en servicios de aplicación.

Esta aproximación preserva la funcionalidad existente mientras mejora la arquitectura incrementalmente.

---

## 9. Recomendaciones

### Escenario A: Mantener Tahiche + Mejoras Incrementales (⭐ Recomendado)

**Tiempo:** 3-6 meses | **Riesgo:** Bajo | **Impacto:** Medio-Alto

1. **Actualizar dependencias:**
   - PHPStan 0.12 → 2.x
   - rospdf → DomPDF 3.x
   - PHP 8.0 → 8.2 mínimo

2. **Introducir contenedor de servicios** ligero (ej: `league/container` o PSR-11 simple).

3. **Añadir Docker** para desarrollo local (se puede tomar el docker-compose de Alxarafe como referencia).

4. **Implementar migraciones PHP** para nuevas tablas (mantener XMLs para las existentes).

5. **Mejorar testing:** Añadir PHPStan 2.x, aumentar cobertura de tests, añadir tests de integración.

6. **Extraer `Tools.php`** en clases especializadas (DateFormatter, FileManager, Config, etc.).

### Escenario B: Adoptar Alxarafe como Base + Reimplementar ERP

**Tiempo:** 12-24 meses | **Riesgo:** Alto | **Impacto:** Muy Alto

Solo recomendable si:
- Se planea una versión 2.0 del producto con breaking changes aceptados.
- Hay un equipo de al menos 3-4 desarrolladores dedicados.
- No hay presión por mantener compatibilidad con plugins existentes.
- Se busca atraer nuevos contribuidores que prefieran arquitecturas modernas.

### Escenario C: Migración Hexagonal Gradual (híbrido)

**Tiempo:** 6-12 meses | **Riesgo:** Medio | **Impacto:** Alto

Tomar los mejores elementos de Alxarafe e integrarlos en Tahiche:
1. Adoptar `illuminate/database` (Eloquent) como ORM, coexistiendo con el acceso DB actual.
2. Implementar puertos/adaptadores solo para servicios transversales (email, PDF, logging).
3. Introducir el sistema de módulos con atributos PHP 8 para nuevos módulos, manteniendo el sistema de plugins para los existentes.
4. Añadir Docker, CI/CD y herramientas de calidad de Alxarafe.

---

## 10. Matrices de Decisión

### 10.1 Matriz de Impacto vs. Esfuerzo

```
                        ALTO IMPACTO
                             │
     Introducir ORM          │     Reescritura completa
     (Escenario C)           │     (Escenario B)
                             │
  ─────── BAJO ESFUERZO ─────┼───── ALTO ESFUERZO ──────
                             │
     Actualizar deps         │     Migrar plantillas
     Docker + CI/CD          │     Twig → Blade
     (Escenario A)           │
                             │
                        BAJO IMPACTO
```

### 10.2 Matriz de Riesgo

| Factor de Riesgo | Escenario A | Escenario B | Escenario C |
|---|:---:|:---:|:---:|
| Pérdida funcional | 🟢 Ninguna | 🔴 Total temporal | 🟡 Mínima |
| Incompatibilidad plugins | 🟢 Ninguna | 🔴 Total | 🟡 Parcial |
| Regresiones | 🟢 Bajo | 🔴 Alto | 🟡 Medio |
| Curva de aprendizaje | 🟢 Baja | 🔴 Alta | 🟡 Media |
| Deuda técnica futura | 🟡 Persiste | 🟢 Mínima | 🟢 Se reduce |
| Atracción de talento | 🟡 Neutral | 🟢 Alta | 🟢 Media-Alta |

### 10.3 Resumen de Valoración

| Criterio (peso) | Escenario A | Escenario B | Escenario C |
|---|:---:|:---:|:---:|
| Velocidad de entrega (25%) | ⭐⭐⭐⭐⭐ | ⭐ | ⭐⭐⭐ |
| Calidad arquitectónica (20%) | ⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ |
| Riesgo de proyecto (20%) | ⭐⭐⭐⭐⭐ | ⭐ | ⭐⭐⭐ |
| Mantenibilidad largo plazo (15%) | ⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ |
| Preservación funcional (10%) | ⭐⭐⭐⭐⭐ | ⭐ | ⭐⭐⭐⭐ |
| Atracción contribuidores (10%) | ⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐ |
| **TOTAL PONDERADO** | **3.7** | **2.7** | **3.4** |

---

## Conclusión

> **Nota importante:** Ambos repositorios comparten el mismo autor principal (Rafael San José / rsanjoseo), lo que facilita enormemente cualquier estrategia de migración o integración, ya que no hay barreras de comunicación ni diferencias de visión de producto.

**La recomendación principal es el Escenario A** (mantener Tahiche con mejoras incrementales) como acción inmediata, con una **transición gradual al Escenario C** (migración hexagonal híbrida) a medio plazo.

El Escenario B (adopción completa de Alxarafe) solo se justifica si se está dispuesto a invertir más de un año de desarrollo con funcionalidad reducida, lo cual es un riesgo significativo para un producto en producción. Sin embargo, el ritmo de desarrollo extremadamente alto de Alxarafe (21+ releases en 2 meses) indica que el framework está madurando rápidamente y podría alcanzar la estabilidad necesaria para una adopción completa antes de lo esperado.

Los elementos más valiosos de Alxarafe que deberían adoptarse **sin importar el escenario elegido** son:
1. **Docker + CI/CD** (coste bajo, beneficio alto)
2. **Herramientas de calidad actualizadas** (PHPStan 2.x, etc.)
3. **Documentación con VitePress** (mejora la contribución)
4. **Sistema de temas** (mejora la experiencia de usuario)

---

## 11. Análisis Detallado: Migración de Base de Datos

### 11.1 Estado Actual en Tahiche: SQL Directo

Tahiche accede a la base de datos mediante extensiones nativas PHP (`mysqli` / `pg_*`) envueltas en la clase `DataBase.php` (554 líneas). Los modelos heredan de `ModelClass` y construyen SQL manualmente:

```php
// Tahiche — ModelClass::all() (Core/Model/Base/ModelClass.php)
public static function all(array $where = [], array $order = [], int $offset = 0, int $limit = 50): array
{
    $sql = 'SELECT * FROM ' . static::tableName()
         . DataBaseWhere::getSQLWhere($where)
         . self::getOrderBy($order);
    foreach (self::$dataBase->selectLimit($sql, $limit, $offset) as $row) {
        $modelList[] = new static($row);
    }
    return $modelList;
}

// Tahiche — ModelClass::saveInsert()
$sql = 'INSERT INTO ' . static::tableName()
     . ' (' . implode(',', $insertFields) . ')'
     . ' VALUES (' . implode(',', $insertValues) . ');';
self::$dataBase->exec($sql);

// Tahiche — ModelClass::delete()
$sql = 'DELETE FROM ' . static::tableName()
     . ' WHERE ' . static::primaryColumn()
     . ' = ' . self::$dataBase->var2str($this->primaryColumnValue()) . ';';
```

#### Problemas concretos de este enfoque

| Problema | Impacto | Ejemplo en código |
|----------|---------|-------------------|
| **SQL Injection potencial** | Alto | `var2str()` es la única barrera; no usa prepared statements |
| **Sin relaciones declarativas** | Alto | Las JOINs se escriben a mano en cada modelo |
| **Sin migraciones** | Medio | El esquema se define en XMLs (`Table/*.xml`) y se sincroniza vía `DbUpdater` |
| **Sin query builder** | Medio | `DataBaseWhere` es un mini-builder limitado (solo WHERE) |
| **Sin transacciones declarativas** | Medio | `beginTransaction()`/`commit()` manuales, sin rollback automático |
| **Acoplamiento al motor** | Bajo | `MysqlEngine` y `PostgresqlEngine` como únicos drivers |
| **Sin eager/lazy loading** | Alto | Problema N+1 en listados con datos relacionados |

### 11.2 Qué ofrece Alxarafe v0.5.8: Eloquent ORM

Alxarafe v0.5.8 usa `illuminate/database` v10.48 (Eloquent ORM de Laravel). El equivalente de los ejemplos anteriores sería:

```php
// Alxarafe v0.5.8 — Equivalente de all()
$facturas = FacturaCliente::where('codcliente', '=', 'CLI001')
    ->orderBy('fecha', 'desc')
    ->offset(0)->limit(50)
    ->get();

// Alxarafe v0.5.8 — Equivalente de saveInsert()
$factura = new FacturaCliente();
$factura->codcliente = 'CLI001';
$factura->total = 1500.00;
$factura->save();  // INSERT automático con prepared statements

// Alxarafe v0.5.8 — Equivalente de delete()
$factura->delete();  // DELETE con prepared statements

// Relaciones declarativas (Tahiche NO tiene esto)
class FacturaCliente extends Model {
    public function lineas() {
        return $this->hasMany(LineaFacturaCliente::class, 'idfactura');
    }
    public function cliente() {
        return $this->belongsTo(Cliente::class, 'codcliente', 'codcliente');
    }
}

// Eager loading — resuelve el problema N+1
$facturas = FacturaCliente::with(['lineas', 'cliente'])->get();
```

### 11.3 Comparativa Línea a Línea

| Operación | Tahiche (SQL directo) | Alxarafe v0.5.8 (Eloquent) |
|-----------|-------------------|---------------------------|
| **SELECT con filtros** | `'SELECT * FROM ' . tableName() . DataBaseWhere::getSQLWhere($where)` | `Model::where('campo', '=', $valor)->get()` |
| **INSERT** | Concatenación manual de campos/valores + `exec($sql)` | `$model->save()` (auto-detect insert) |
| **UPDATE** | `'UPDATE table SET' + loop campos + exec($sql)` | `$model->save()` (auto-detect update) |
| **DELETE** | `'DELETE FROM table WHERE pk = ' . var2str($val)` | `$model->delete()` |
| **COUNT** | `'SELECT COUNT(1) AS total FROM ' . tableName()` | `Model::count()` o `Model::where(...)->count()` |
| **JOIN** | SQL manual embebido en el modelo | `Model::with('relacion')` o `Model::join(...)` |
| **Transacción** | `$db->beginTransaction()` ... `$db->commit()` | `DB::transaction(fn() => ...)` |
| **Migración** | XMLs en `Table/*.xml` + `DbUpdater` | Clases PHP con `up()`/`down()` + rollback |
| **Paginación** | `selectLimit($sql, $limit, $offset)` manual | `Model::paginate(50)` |
| **Scopes** | No existe | `scopeActivas()`, `scopeDelMes()`, reutilizables |
| **Soft deletes** | No existe | `SoftDeletes` trait — `deleted_at` automático |
| **Timestamps** | Manual (`fechaalta`, etc.) | Automático (`created_at`, `updated_at`) |
| **Protección SQL Injection** | `var2str()` — escape manual | PDO prepared statements automáticos |

### 11.4 Estrategia de Migración de Base de Datos

#### Fase 1: Coexistencia (semanas 1-4)

Instalar Eloquent vía Composer **sin tocar los modelos existentes**:

```bash
composer require illuminate/database:^10.48
```

Crear un `EloquentBootstrap.php` que inicialice la conexión usando la misma configuración de `config.php`:

```php
use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => FS_DB_HOST,
    'database'  => FS_DB_NAME,
    'username'  => FS_DB_USER,
    'password'  => FS_DB_PASS,
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();
```

> **Punto clave:** Eloquent puede funcionar contra las **mismas tablas** que usa tahiche actualmente. No requiere cambiar el esquema.

#### Fase 2: Modelos nuevos en Eloquent (semanas 5-12)

Crear modelos Eloquent que apunten a las tablas existentes:

```php
// Nuevo modelo Eloquent que usa la tabla existente de tahiche
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacturaCliente extends Model
{
    protected $table = 'facturascli';        // misma tabla que tahiche
    protected $primaryKey = 'idfactura';     // misma PK
    public $timestamps = false;              // tahiche no usa timestamps Eloquent
    protected $guarded = [];

    public function lineas()
    {
        return $this->hasMany(LineaFacturaCliente::class, 'idfactura');
    }
}
```

#### Fase 3: Migración progresiva de consultas (meses 3-6)

Reemplazar las llamadas SQL directas por consultas Eloquent, modelo por modelo, empezando por los menos críticos:

| Prioridad | Modelos | Motivo |
|-----------|---------|--------|
| 1 (bajo riesgo) | `Pais`, `Divisa`, `Idioma`, `Serie` | Catálogos simples, fáciles de validar |
| 2 (medio) | `Cliente`, `Proveedor`, `Contacto`, `Agente` | Entidades con relaciones 1:N |
| 3 (alto valor) | `FacturaCliente`, `AlbtahicheCliente`, etc. | Documentos complejos con líneas |
| 4 (crítico) | `Asiento`, `Partida`, `Subcuenta` | Lógica contable — migrar al final |

#### Fase 4: Migraciones PHP para nuevas tablas (en paralelo)

Para cualquier tabla nueva, usar migraciones Eloquent en lugar de XMLs:

```php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Schema;

Schema::schema()->create('audit_logs', function (Blueprint $table) {
    $table->id();
    $table->string('model_type');
    $table->unsignedBigInteger('model_id');
    $table->string('action');  // create, update, delete
    $table->json('old_values')->nullable();
    $table->json('new_values')->nullable();
    $table->timestamps();
});
```

### 11.5 Riesgos y Mitigaciones

| Riesgo | Probabilidad | Mitigación |
|--------|:------------:|-----------|
| Conflicto de conexiones (DataBase + Eloquent) | Media | Usar la misma conexión PDO subyacente |
| Diferencias de escape entre `var2str()` y PDO | Baja | Tests de comparación de resultados |
| Modelos tahiche y Eloquent operan la misma tabla simultáneamente | Alta | Definir regla: **no mezclar** ambos en una misma transacción |
| Eloquent no detecta columnas con nombres raros (`codcliente`) | Baja | `$primaryKey`, `$table` explícitos en cada modelo |
| Performance con Eloquent vs SQL directo | Baja | Eloquent genera SQL optimizado; usar `toSql()` para auditar |

---

## 12. Análisis Detallado: Migración del Sistema de Plantillas

### 12.1 Estado Actual en Tahiche: Twig 3.x

Tahiche usa **63 plantillas `.html.twig`** organizadas en:

```
Core/View/
├── Block/              ← Bloques reutilizables (AccountNoteInfo, etc.)
├── Email/              ← Plantillas de email
├── Error/              ← Páginas de error (AccessDenied, etc.)
├── Installer/          ← Wizard de instalación
├── Login/              ← Login, TwoFactor
├── Master/             ← Layouts base (Edit, List, PanelController, etc.)
├── Macro/              ← Macros Twig reutilizables
├── Section/            ← Secciones parciales
├── Tab/                ← Tabs especializados (SalesDocument, DocFiles, etc.)
└── *.html.twig         ← Páginas completas (Dashboard, MegaSearch, etc.)
```

La clase `Html.php` (392 líneas) gestiona todo el sistema de renderizado Twig con **14 funciones custom** registradas:

```php
// Funciones Twig custom registradas en Html.php:
{{ asset('path') }}              // Generar URLs de assets
{{ attachedFile(id) }}           // Cargar archivo adjunto
{{ cache('key') }}               // Leer caché
{{ config('key') }}              // Leer configuración
{{ executionTime() }}            // Tiempo de ejecución
{{ fixHtml(text) }}              // Escapar/desescapar HTML
{{ formToken() }}                // Token anti-CSRF
{{ getIncludeViews(file, pos) }} // Extension views de plugins
{{ money(1500, 'EUR') }}         // Formatear moneda
{{ myFilesUrl('path') }}         // URLs firmadas
{{ number(1500.5, 2) }}          // Formatear número
{{ settings('default', 'key') }} // Leer settings
{{ trans('invoice') }}           // Traducción i18n
{{ bytes(1024) }}                // Formatear bytes
```

El sistema de **override por plugins** funciona así:
1. Los plugins colocan plantillas en `Plugins/{name}/View/`
2. En modo debug, Twig busca primero en la carpeta del plugin
3. `getIncludeViews()` permite inyectar fragmentos en posiciones específicas de otras plantillas

### 12.2 Qué ofrece Alxarafe v0.5.8: Blade

Alxarafe v0.5.8 usa **Blade** (vía `jenssegers/blade`) con un sistema de temas:

```
skeleton/
├── resources/views/
│   └── themes/
│       ├── default/        ← Tema por defecto
│       ├── cyberpunk/      ← Tema alternativo
│       ├── vintage/        ← Tema alternativo
│       ├── high-contrast/  ← Accesibilidad
│       └── alternative/    ← Variante adicional
```

Características clave:
- **Herencia de layouts** con `@extends`, `@section`, `@yield`
- **Componentes** con `@component` / `<x-component>`
- **Directivas custom** (`@auth`, `@guest`, `@can`...)
- **Caché por tema** (v0.5.7+: cada tema tiene su propio directorio de caché)

### 12.3 Equivalencia de Sintaxis Twig → Blade

| Concepto | Twig (Tahiche) | Blade (Alxarafe) |
|----------|------------|------------------|
| **Mostrar variable** | `{{ variable }}` | `{{ $variable }}` |
| **Escapar HTML** | `{{ variable\|raw }}` | `{!! $variable !!}` |
| **Condicional** | `{% if x %} ... {% endif %}` | `@if($x) ... @endif` |
| **Bucle** | `{% for item in items %} ... {% endfor %}` | `@foreach($items as $item) ... @endforeach` |
| **Herencia** | `{% extends "base.html.twig" %}` | `@extends('layouts.base')` |
| **Bloques/secciones** | `{% block content %} ... {% endblock %}` | `@section('content') ... @endsection` |
| **Inclusión** | `{% include "partial.html.twig" %}` | `@include('partial')` |
| **Comentarios** | `{# comentario #}` | `{{-- comentario --}}` |
| **Filtros** | `{{ name\|upper }}` | `{{ strtoupper($name) }}` |
| **Macros** | `{% macro input(name) %}...{% endmacro %}` | `<x-input :name="$name" />` (componentes) |
| **Traducción** | `{{ trans('key') }}` | `{{ __('key') }}` o `@lang('key')` |

### 12.4 Mapeo de Funciones Twig Custom → Blade

Las 14 funciones Twig custom de `Html.php` necesitan equivalentes en Blade:

| Función Twig | Equivalente Blade | Implementación |
|-------------|------------------|----------------|
| `{{ asset('js/app.js') }}` | `{{ asset('js/app.js') }}` | Ya nativo en Blade |
| `{{ trans('key') }}` | `{{ __('key') }}` | Nativo vía `symfony/translation` en alxarafe |
| `{{ money(1500, 'EUR') }}` | `{{ money(1500, 'EUR') }}` | Registrar como `Blade::directive('money', ...)` |
| `{{ number(val, 2) }}` | `{{ number(val, 2) }}` | Helper global o directiva |
| `{{ config('key') }}` | `{{ config('key') }}` | Nativo en Laravel/illuminate |
| `{{ settings('g', 'k') }}` | `{{ settings('g', 'k') }}` | Helper global |
| `{{ formToken() }}` | `@csrf` | **Nativo en Blade** |
| `{{ fixHtml(text) }}` | `{!! $text !!}` | Sintaxis nativa |
| `{{ executionTime() }}` | `{{ execution_time() }}` | Helper global |
| `{{ getIncludeViews() }}` | `@hook('position')` | Usar `HookService` de alxarafe |
| `{{ bytes(size) }}` | `{{ bytes($size) }}` | Helper global |
| `{{ cache('key') }}` | `{{ cache('key') }}` | Helper nativo de Laravel |
| `{{ myFilesUrl() }}` | Ruta firmada | `URL::signedRoute()` o helper |
| `{{ attachedFile(id) }}` | `AttachedFile::find($id)` | Directamente en la vista o vía `@inject` |

### 12.5 Estrategia de Migración de Plantillas

#### Opción A: Coexistencia Twig + Blade (recomendada para transición)

Mantener Twig para las vistas existentes y usar Blade solo para vistas nuevas:

```php
// En el Router o Controller, decidir qué motor usar:
if (str_ends_with($template, '.blade.php')) {
    return Blade::render($template, $data);
} else {
    return Html::render($template, $data);  // Twig existente
}
```

**Ventaja:** Cero riesgo de regresión en plantillas existentes.
**Desventaja:** Dos motores de plantillas en paralelo = mayor complejidad.

#### Opción B: Conversión automatizada con script

Crear un script PHP que transforme la sintaxis Twig a Blade:

```php
// Transformaciones mecánicas aplicables con regex:
$blade = $twig;
$blade = preg_replace('/\{%\s*extends\s+"(.+?)\.html\.twig"\s*%\}/', "@extends('$1')", $blade);
$blade = preg_replace('/\{%\s*block\s+(\w+)\s*%\}/', "@section('$1')", $blade);
$blade = preg_replace('/\{%\s*endblock\s*%\}/', "@endsection", $blade);
$blade = preg_replace('/\{%\s*if\s+(.+?)\s*%\}/', '@if($1)', $blade);
$blade = preg_replace('/\{%\s*endif\s*%\}/', '@endif', $blade);
$blade = preg_replace('/\{%\s*for\s+(\w+)\s+in\s+(\w+)\s*%\}/', '@foreach(\$$2 as \$$1)', $blade);
$blade = preg_replace('/\{%\s*endfor\s*%\}/', '@endforeach', $blade);
$blade = preg_replace('/\{%\s*include\s+"(.+?)\.html\.twig"\s*%\}/', "@include('$1')", $blade);
$blade = preg_replace('/\{\{\s*(.+?)\s*\}\}/', '{{ $$$1 }}', $blade);
// NOTA: Requiere revisión manual posterior para filtros, macros y funciones custom
```

**Ventaja:** Convierte las 63 plantillas rápidamente.
**Desventaja:** Las macros Twig no tienen equivalente directo; requieren conversión manual a componentes Blade.

#### Opción C: Reescritura progresiva por sección

Convertir plantillas por prioridad funcional:

| Prioridad | Plantillas | Nº archivos | Esfuerzo |
|-----------|-----------|:-----------:|----------|
| 1 | Login, Installer, Error | 6 | 1-2 días |
| 2 | Master layouts (Edit, List, Panel) | 8 | 3-5 días |
| 3 | Tabs especializados (Sales, Purchases, Accounting) | 12 | 5-7 días |
| 4 | Bloques y macros | 15 | 3-5 días |
| 5 | Resto de vistas | 22 | 5-7 días |
| **TOTAL** | | **63** | **~4 semanas** |

### 12.6 Elementos que NO tienen equivalente directo

| Twig (Tahiche) | Problema | Solución en Blade |
|-------------|----------|------------------|
| **Macros Twig** (`{% macro %}`) | Blade no tiene macros | Convertir a **componentes Blade** (`<x-macro-name />`) |
| **`getIncludeViews()`** | Sistema de extension views por plugins | Usar **`HookService`** de alxarafe con `@hook('position')` |
| **Override Dinamic/View** | Los plugins pueden reemplazar vistas del core | Usar **prioridad de namespaces** en Blade (`View::first(['plugin.vista', 'core.vista'])`) |
| **Filtros Twig** (`|upper`, `|date`, `|length`) | No existen en Blade | Usar funciones PHP directamente (`strtoupper()`, `date()`, `count()`) |

### 12.7 Impacto en el Volumen de Código

| Métrica | Tahiche (Twig) | Alxarafe v0.5.8 (Blade) |
|---------|:-----------:|:------------------------:|
| Motor de renderizado | `Html.php` (392 líneas) | `BladeService` (~150 líneas) |
| Funciones custom | 14 (registradas una a una) | ~5 (helpers globales + directivas) |
| Plantillas del core | 63 `.html.twig` | ~20 `.blade.php` (framework) |
| Sistema de temas | No | Sí (5 temas incluidos) |
| Override por plugins | `Dinamic/View/` + `Extension/View/` | Namespaces Blade + Hooks |
| Caché | `MyFiles/Cache/Twig/` | `storage/cache/views/{tema}/` |

---

## 13. Estrategia de Migración en 2 Fases (v0.5.8 → v0.6.0+)

> **Contexto clave:** La v0.5.8 tiene estructura `src/Core/` (plana, similar a tahiche). La v0.6.0 tiene estructura `src/Domain/` + `src/Application/` + `src/Infrastructure/` (hexagonal pura). Ambas versiones ofrecen las mismas capacidades funcionales (Eloquent, Blade, módulos, hooks).

### Fase 1: Adoptar v0.5.8 como dependencia (meses 1-3)

**Objetivo:** Ganar Eloquent + Blade sin reestructurar el código.

```
tahiche/
├── Core/                    ← Se mantiene intacto
├── Plugins/                 ← Se mantiene intacto
├── vendor/
│   └── alxarafe/alxarafe/   ← v0.5.8 como librería Composer
│       └── src/Core/        ← Estructura plana (familiar)
├── composer.json            ← Añadir "alxarafe/alxarafe": "^0.5.8"
└── bootstrap_eloquent.php   ← Inicializar Eloquent con config de tahiche
```

#### Acciones concretas:

| Semana | Acción | Archivos afectados |
|:------:|--------|-------------------|
| 1 | `composer require alxarafe/alxarafe:^0.5.8` | `composer.json` |
| 1 | Crear `bootstrap_eloquent.php` que conecte Eloquent a la misma DB | 1 archivo nuevo |
| 2-3 | Crear modelos Eloquent para tablas catálogo (`paises`, `divisas`, `series`) | ~10 modelos nuevos |
| 4-6 | Crear modelos Eloquent para entidades principales (`clientes`, `facturascli`) | ~20 modelos nuevos |
| 7-8 | Registrar helpers Blade equivalentes a las funciones Twig custom | 1 archivo (`BladeHelpers.php`) |
| 9-10 | Convertir primeras plantillas (Login, Error, Installer) de Twig a Blade | ~6 plantillas |
| 11-12 | Validar coexistencia Twig + Blade en producción | Tests de integración |

#### Resultado de Fase 1:
- ✅ Eloquent disponible para nuevos modelos y consultas
- ✅ Blade disponible para nuevas vistas
- ✅ Twig sigue funcionando para las 63 vistas existentes
- ✅ Los 85 modelos SQL actuales siguen funcionando
- ✅ Zero breaking changes

### Fase 2: Migrar a v0.6.0+ cuando esté estable (meses 4-9)

**Objetivo:** Adoptar la arquitectura hexagonal progresivamente.

```
tahiche/
├── src/
│   ├── Domain/              ← Modelos Eloquent migrados aquí
│   │   ├── Model/           ← Entidades: Factura, Cliente, Asiento...
│   │   └── Port/            ← Interfaces de repositorios
│   ├── Application/         ← Casos de uso
│   │   └── Service/         ← FacturacionService, ContabilidadService
│   └── Infrastructure/      ← Adaptadores
│       ├── Persistence/     ← Repositorios Eloquent
│       └── Http/            ← Controladores
├── Core/                    ← Legacy (se va vaciando)
├── vendor/
│   └── alxarafe/alxarafe/   ← v0.6.0+ (hexagonal)
│       └── src/
│           ├── Domain/
│           ├── Application/
│           └── Infrastructure/
└── resources/views/         ← Todas las vistas en Blade
```

#### Criterios para pasar a Fase 2:
- [ ] v0.6.0+ ha tenido al menos 3 releases de estabilización
- [ ] Los modelos Eloquent de Fase 1 están probados en producción
- [ ] Se ha convertido >50% de las plantillas a Blade
- [ ] El equipo está familiarizado con Eloquent y Blade

### Resumen Visual

```
Mes 1-3 (Fase 1 — v0.5.8)              Mes 4-9 (Fase 2 — v0.6.0+)
─────────────────────────               ──────────────────────────

┌───────────────────────┐               ┌──────────────────────────┐
│     tahiche (Core/)      │               │  src/Domain/             │
│  ┌─────────────────┐  │               │  ├── Model/Factura.php   │
│  │ ModelClass (SQL) │──┼──→ convive    │  └── Port/FacturaRepo   │
│  │ Html.php (Twig)  │  │    con        │                          │
│  └─────────────────┘  │               │  src/Application/        │
│  ┌─────────────────┐  │               │  └── FacturacionService  │
│  │ Eloquent (nuevo) │  │               │                          │
│  │ Blade (nuevo)    │  │    ────→      │  src/Infrastructure/     │
│  └─────────────────┘  │               │  ├── EloquentFacturaRepo │
│  vendor/alxarafe 0.5.8│               │  └── Http/Controllers    │
└───────────────────────┘               │                          │
                                        │  resources/views/ (Blade)│
                                        │  vendor/alxarafe 0.6.0+  │
                                        └──────────────────────────┘
```

---

## 14. Apéndice: Historial de Releases de Alxarafe

El siguiente historial demuestra el ritmo de desarrollo intensivo del framework:

| Versión | Fecha | Descripción |
|---------|-------|-------------|
| **v0.6.0** | 29 Mar 2026 | Pure Hexagonal Architecture + Auth lock fixes |
| **v0.5.8** | 21 Mar 2026 | Conditional tabs, field module deps, enhanced hooks, tab badges, status workflow |
| **v0.5.7** | 8 Mar 2026 | Fix logout, theme-specific Blade cache, menu organization |
| **v0.5.6** | 7 Mar 2026 | Final cleanup Extrafields logic, PHPStan type hints |
| **v0.5.5** | 7 Mar 2026 | Fix demo reset, config discovery, eliminate Restler → Native Attribute API |
| **v0.5.4** | 5 Mar 2026 | Enforce module activation check in base Controller |
| **v0.5.3** | 5 Mar 2026 | App-level core module support via `ModuleInfo::core` flag |
| **v0.5.2** | 5 Mar 2026 | Admin baseline menus — always-visible Inicio with admin sidebar |
| **v0.5.1** | 5 Mar 2026 | Module-aware menu filtering with role-based persistent cache |
| **v0.5.0** | 4 Mar 2026 | Module dependency system with auto-detection (`DependencyResolver`) |
| **v0.4.9** | 4 Mar 2026 | 6 Alixar feedback proposals (Dictionary, Module, Email, PDF, AuditLog) |
| **v0.4.8** | 4 Mar 2026 | Implement Alixar feedback proposals |
| **v0.4.7** | 3 Mar 2026 | HasWorkflow trait, Extrafields UI automation |
| **v0.4.6** | 3 Mar 2026 | Parent and class fields for hierarchical menus |
| **v0.4.5** | 1 Mar 2026 | Prohibit external core modifications |
| **v0.4.4** | 1 Mar 2026 | Config page security, password leak fix, button dispatch fixes |
| **v0.4.3** | 1 Mar 2026 | Update documentation URLs |
| **v0.4.2** | 1 Mar 2026 | Agenda module UI/i18n improvements, alxarafe-template recommendation |
| **v0.4.1** | 28 Feb 2026 | Languages table, full i18n (7 languages), extensible tabs, FTP deploy |
| **v0.4.0** | 26 Feb 2026 | VitePress docs, sidebar updates, translations |

> **Observación:** El ritmo de ~10 releases/mes indica un desarrollo muy activo enfocado en estabilizar la arquitectura hexagonal, el sistema de módulos y la infraestructura de admin. Las versiones recientes (v0.5.x, v0.6.0) se centran en solidificar el sistema de dependencias entre módulos, caché de menús por roles, y la pureza de la arquitectura hexagonal.
