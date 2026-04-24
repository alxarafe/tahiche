<?php

declare(strict_types=1);

namespace Tahiche\Infrastructure\Database;

use PDO;
use PDOException;
use RuntimeException;

class MysqlPdoConnection implements DatabaseConnectionInterface
{
    private ?PDO $pdo = null;
    private string $dsn;
    private string $username;
    private string $password;
    private string $lastError = '';

    public function __construct(string $dsn, string $username, string $password)
    {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
    }

    public function connect(): void
    {
        if ($this->pdo !== null) {
            return;
        }

        try {
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            $this->pdo = new PDO($this->dsn, $this->username, $this->password, $options);

            // Disable foreign key checks if defined in FacturaScripts
            if (defined('FS_DB_FOREIGN_KEYS') && false === FS_DB_FOREIGN_KEYS) {
                $this->pdo->exec('SET foreign_key_checks = 0;');
            }
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            throw new RuntimeException('Database connection failed: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public function disconnect(): void
    {
        $this->pdo = null;
    }

    public function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $this->connect();
        }
        return $this->pdo;
    }

    public function query(string $sql): array
    {
        $this->lastError = '';
        try {
            $stmt = $this->getPdo()->query($sql);
            return $stmt !== false ? $stmt->fetchAll() : [];
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    public function execute(string $sql): bool
    {
        $this->lastError = '';
        try {
            $this->getPdo()->exec($sql);
            return true;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function escape(mixed $value): string
    {
        $quoted = $this->getPdo()->quote((string) $value);
        if ($quoted === false) {
            return (string) $value;
        }

        // Remove the outer quotes added by PDO::quote(), since FacturaScripts already wraps variables in single quotes
        return substr($quoted, 1, -1);
    }

    public function escapeColumn(string $column): string
    {
        if (str_contains($column, '.')) {
            $parts = explode('.', $column);
            return '`' . implode('`.`', $parts) . '`';
        }
        return '`' . $column . '`';
    }

    public function beginTransaction(): bool
    {
        try {
            return $this->getPdo()->beginTransaction();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function commit(): bool
    {
        try {
            return $this->getPdo()->commit();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function rollback(): bool
    {
        try {
            return $this->getPdo()->rollBack();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function inTransaction(): bool
    {
        return $this->getPdo()->inTransaction();
    }

    public function getErrorMessage(): string
    {
        return $this->lastError;
    }
}
