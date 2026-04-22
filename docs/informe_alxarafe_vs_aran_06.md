# Informe Comparativo: Tahiche vs. Alxarafe/Alxarafe

> **Fecha:** 2026-04-17  
> **Autor:** Análisis automatizado  
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

#### Ecosistema inmaduro
- Solo 1 estrella en GitHub, sin forks, sin comunidad visible.
- Una sola release (v0.0.1-stable de 2018, aunque el código está activo).
- Sin marketplace de plugins ni extensiones de terceros.

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
| **Comunidad** | Hereda de Tahiche | Mínima (1 contribuidor activo) |

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

**La recomendación principal es el Escenario A** (mantener Tahiche con mejoras incrementales) como acción inmediata, con una **transición gradual al Escenario C** (migración hexagonal híbrida) a medio plazo.

El Escenario B (adopción completa de Alxarafe) solo se justifica si se está dispuesto a invertir más de un año de desarrollo con funcionalidad reducida, lo cual es un riesgo significativo para un producto en producción.

Los elementos más valiosos de Alxarafe que deberían adoptarse **sin importar el escenario elegido** son:
1. **Docker + CI/CD** (coste bajo, beneficio alto)
2. **Herramientas de calidad actualizadas** (PHPStan 2.x, etc.)
3. **Documentación con VitePress** (mejora la contribución)
4. **Sistema de temas** (mejora la experiencia de usuario)
