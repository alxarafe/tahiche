<?php

declare(strict_types=1);

namespace Tahiche\Infrastructure\Base;

/**
 * Registry centralizado para el sistema de Modificadores (Hexagonal).
 * Reemplaza la funcionalidad de `Dinamic` permitiendo que los plugins
 * alteren vistas, modelos o lógica de forma limpia, estática y predecible.
 */
class Registry
{
    /**
     * @var array<string, array<int, callable[]>>
     */
    private static array $modifiers = [];

    /**
     * Registra un nuevo modificador para un punto de extensión (hook).
     *
     * @param string   $hook     El nombre del punto de extensión (ej. 'fields_BankAccount').
     * @param callable $callback Función a ejecutar. Debe recibir el payload y devolverlo modificado.
     * @param int      $priority Orden de ejecución. Menor número se ejecuta primero.
     */
    public static function addModifier(string $hook, callable $callback, int $priority = 10): void
    {
        self::$modifiers[$hook][$priority][] = $callback;
        
        // Mantener ordenado por prioridad ascendente
        ksort(self::$modifiers[$hook]);
    }

    /**
     * Aplica los modificadores registrados sobre un payload.
     *
     * @param string $hook    El nombre del punto de extensión.
     * @param mixed  $payload Los datos originales que se van a modificar (ej. array de campos).
     *
     * @return mixed El payload final, tras haber pasado por todos los modificadores.
     */
    public static function apply(string $hook, $payload)
    {
        if (empty(self::$modifiers[$hook])) {
            return $payload;
        }

        foreach (self::$modifiers[$hook] as $callbacks) {
            foreach ($callbacks as $callback) {
                $payload = $callback($payload);
            }
        }

        return $payload;
    }
    
    /**
     * Limpia el registro (útil para tests).
     */
    public static function clear(): void
    {
        self::$modifiers = [];
    }
}
