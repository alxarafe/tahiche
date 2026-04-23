<?php

declare(strict_types=1);

namespace Tahiche\Infrastructure\Database;

use PDO;

interface DatabaseConnectionInterface
{
    /**
     * Establishes a database connection.
     *
     * @return void
     * @throws \RuntimeException if connection fails.
     */
    public function connect(): void;

    /**
     * Closes the database connection.
     */
    public function disconnect(): void;

    /**
     * Returns the underlying PDO instance.
     *
     * @return PDO
     */
    public function getPdo(): PDO;

    /**
     * Executes a query and returns the fetched results as an associative array.
     *
     * @param string $sql
     * @return array
     */
    public function query(string $sql): array;

    /**
     * Executes an SQL statement and returns true on success.
     *
     * @param string $sql
     * @return bool
     */
    public function execute(string $sql): bool;

    /**
     * Escapes a string for use in a query.
     *
     * @param string $value
     * @return string
     */
    public function escape(string $value): string;

    /**
     * Escapes a column identifier.
     *
     * @param string $column
     * @return string
     */
    public function escapeColumn(string $column): string;

    public function beginTransaction(): bool;

    public function commit(): bool;

    public function rollback(): bool;

    public function inTransaction(): bool;

    public function getErrorMessage(): string;
}
