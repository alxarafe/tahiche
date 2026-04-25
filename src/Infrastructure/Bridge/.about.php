<?php

declare(strict_types=1);

/**
 * Bridge — Zona Autorizada de Contacto Legacy
 *
 * Esta carpeta es el ÚNICO punto del código Hexagonal (src/ y Modules/)
 * que tiene permiso para referenciar namespaces de FacturaScripts y Dinamic.
 *
 * Aquí se colocan:
 * - Adaptadores que traducen las interfaces del Dominio/Aplicación a
 *   implementaciones concretas de FacturaScripts (modelos, servicios, etc.)
 * - Fábricas (Factories) que instancian clases legacy y las envuelven
 *   en contratos modernos.
 *
 * REGLAS:
 * 1. El Dominio y la Aplicación NO deben importar nada de esta carpeta directamente.
 *    Deben usar interfaces (Contracts/Ports) que el Bridge implementa.
 * 2. Solo la capa de Infraestructura puede instanciar las clases del Bridge.
 * 3. Cualquier nuevo adaptador legacy debe crearse aquí, NUNCA en Domain/ ni Application/.
 */
