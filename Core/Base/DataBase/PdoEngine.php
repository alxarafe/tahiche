<?php

declare(strict_types=1);

namespace FacturaScripts\Core\Base\DataBase;

use FacturaScripts\Core\KernelException;
use RuntimeException;
use Tahiche\Infrastructure\Database\MysqlPdoConnection;
use Tahiche\Infrastructure\Database\DatabaseConnectionInterface;

/**
 * Adapter class to integrate the clean architecture PdoConnection 
 * with the legacy FacturaScripts DataBaseEngine via the Strangler Fig Pattern.
 */
class PdoEngine extends DataBaseEngine
{
    private MysqlQueries $utilsSQL;
    private DatabaseConnectionInterface $connection;

    public function __construct()
    {
        parent::__construct();
        $this->utilsSQL = new MysqlQueries(); // Reusing MySQL queries syntax for this example
    }

    public function beginTransaction($link): bool
    {
        return $this->connection->beginTransaction();
    }

    public function castInteger($link, $column): string
    {
        return 'CAST(' . $this->escapeColumn($link, $column) . ' AS unsigned)';
    }

    public function close($link): bool
    {
        $this->connection->disconnect();
        return true;
    }

    public function random(): string
    {
        return 'RAND()'; // Assuming MySQL
    }

    public function columnFromData($colData): array
    {
        $result = array_change_key_case($colData);
        if (isset($result['null'])) {
            $result['is_nullable'] = $result['null'];
            unset($result['null']);
        }
        if (isset($result['field'])) {
            $result['name'] = $result['field'];
            unset($result['field']);
        }
        return $result;
    }

    public function commit($link): bool
    {
        return $this->connection->commit();
    }

    public function connect(&$error)
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                FS_DB_HOST,
                (int)FS_DB_PORT,
                FS_DB_NAME,
                defined('FS_MYSQL_CHARSET') ? FS_MYSQL_CHARSET : 'utf8mb4'
            );

            $this->connection = new MysqlPdoConnection($dsn, FS_DB_USER, FS_DB_PASS);
            $this->connection->connect();
            
            // The $link returned here is just a reference we pass around, usually the PDO object itself,
            // or the connection object. Let's return the connection adapter so we can use it, 
            // though the methods in this class use $this->connection directly.
            return $this->connection;
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
            $this->lastErrorMsg = $error;
            throw new KernelException('DatabaseError', $error);
        }
    }

    public function errorMessage($link): string
    {
        return $this->connection->getErrorMessage() ?: $this->lastErrorMsg;
    }

    public function escapeColumn($link, $name): string
    {
        return $this->connection->escapeColumn($name);
    }

    public function escapeString($link, $str): string
    {
        return $this->connection->escape($str);
    }

    public function exec($link, $sql): bool
    {
        $this->lastErrorMsg = '';
        $success = $this->connection->execute($sql);
        if (!$success) {
            $this->lastErrorMsg = $this->connection->getErrorMessage();
        }
        return $success;
    }

    public function getSQL()
    {
        return $this->utilsSQL;
    }

    public function inTransaction($link): bool
    {
        return $this->connection->inTransaction();
    }

    public function listTables($link): array
    {
        $tables = [];
        $results = $this->connection->query('SHOW TABLES;');
        foreach ($results as $row) {
            $tables[] = reset($row);
        }
        return $tables;
    }

    public function rollback($link): bool
    {
        return $this->connection->rollback();
    }

    public function select($link, $sql): array
    {
        $this->lastErrorMsg = '';
        $results = $this->connection->query($sql);
        if ($this->connection->getErrorMessage()) {
            $this->lastErrorMsg = $this->connection->getErrorMessage();
        }
        return $results;
    }

    public function version($link): string
    {
        return $this->connection->getPdo()->getAttribute(\PDO::ATTR_SERVER_VERSION);
    }
}
