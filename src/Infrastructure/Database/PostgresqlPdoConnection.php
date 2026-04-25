<?php

declare(strict_types=1);

namespace Tahiche\Infrastructure\Database;

use PDO;
use PDOException;
use RuntimeException;

class PostgresqlPdoConnection implements DatabaseConnectionInterface
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
            if ($stmt === false) {
                return [];
            }
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    public function execute(string $sql): bool
    {
        $this->lastError = '';
        try {
            $pdo = $this->getPdo();
            $statements = [];
            $currentStmt = '';
            $inSingleQuote = false;
            $inDoubleQuote = false;

            $len = strlen($sql);
            for ($i = 0; $i < $len; $i++) {
                $char = $sql[$i];
                if ($char === "'" && !$inDoubleQuote) {
                    if ($i === 0 || $sql[$i - 1] !== '\\') {
                        $inSingleQuote = !$inSingleQuote;
                    }
                } elseif ($char === '"' && !$inSingleQuote) {
                    if ($i === 0 || $sql[$i - 1] !== '\\') {
                        $inDoubleQuote = !$inDoubleQuote;
                    }
                }

                if ($char === ';' && !$inSingleQuote && !$inDoubleQuote) {
                    $stmt = trim($currentStmt);
                    if ($stmt !== '') {
                        $statements[] = $stmt;
                    }
                    $currentStmt = '';
                } else {
                    $currentStmt .= $char;
                }
            }
            $stmt = trim($currentStmt);
            if ($stmt !== '') {
                $statements[] = $stmt;
            }

            foreach ($statements as $s) {
                $pdo->exec($s);
            }
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
            return '"' . implode('"."', $parts) . '"';
        }
        return '"' . $column . '"';
    }

    public function beginTransaction(): bool
    {
        try {
            if ($this->getPdo()->inTransaction()) {
                return true;
            }
            return $this->getPdo()->beginTransaction();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function commit(): bool
    {
        try {
            if (!$this->getPdo()->inTransaction()) {
                return true;
            }
            return $this->getPdo()->commit();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function rollback(): bool
    {
        try {
            if (!$this->getPdo()->inTransaction()) {
                return true;
            }
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
