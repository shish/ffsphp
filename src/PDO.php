<?php

declare(strict_types=1);

namespace FFSPHP;

/**
 * @phpstan-import-type BindableValue from PDOStatement
 * @phpstan-import-type BindableParam from PDOStatement
 */
class PDO extends \PDO
{
    /**
     * A version of PDO which always accepts a standard DSN
     *
     * Why? Becuase while most PDO backends accept username and password inside
     * the DSN, the MySQL backend requires them to be passed separately...
     *
     * @param mixed[] $options
     */
    public function __construct(string $dsn, ?array $options = null)
    {
        $driver = explode(":", $dsn, 2)[0];
        if (!in_array($driver, \PDO::getAvailableDrivers(), true)) {
            throw new \PDOException("PDO driver not available: $driver (available drivers: " . implode(", ", \PDO::getAvailableDrivers()) . ")");
        }

        $user = null;
        $pass = null;
        if (preg_match("/user=([^;]*)/", $dsn, $matches)) {
            $user = $matches[1];
        }
        if (preg_match("/password=([^;]*)/", $dsn, $matches)) {
            $pass = $matches[1];
        }

        parent::__construct($dsn, $user, $pass, $options);

        $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->setAttribute(\PDO::ATTR_STATEMENT_CLASS, [PDOStatement::class, [&$this]]);
    }

    /**
     * Expand array placeholders in SQL statement
     *
     * @param array<string,BindableParam> $parameters
     * @return array{string, array<string,BindableValue>} [expanded SQL, flattened parameters]
     */
    private function expandArrayPlaceholders(string $sql, array $parameters): array
    {
        $flattened = [];
        foreach ($parameters as $name => $value) {
            if (is_array($value)) {
                // Expand :name in SQL to (:name_0, :name_1, :name_2) etc.
                // Always use numeric indices to prevent SQL injection via associative keys
                $placeholders = [];
                foreach (array_values($value) as $index => $item) {
                    $placeholders[] = ":{$name}_{$index}";
                    $flattened["{$name}_{$index}"] = $item;
                }
                $placeholderList = '(' . implode(', ', $placeholders) . ')';

                $sql = preg_replace('/(:' . preg_quote($name, '/') . ')(?!\w)/', $placeholderList, $sql);
                if (!$sql) {
                    throw new \Exception("Failed to expand array placeholder: $name");
                }
            } else {
                $flattened[$name] = $value;
            }
        }
        return [$sql, $flattened];
    }

    /**
     * Like exec() except it also accepts parameters, saving the user from
     * needing to create a prepared statement for a one-off query
     *
     * Arrays are expanded in SQL using numeric indices:
     * "WHERE id IN :ids" with ["ids" => [1, 2, 3]] becomes "WHERE id IN (:ids_0, :ids_1, :ids_2)"
     * Note: Associative arrays are converted to numeric indices for security
     *
     * @param array<string,BindableParam>|null $parameters
     */
    public function execute(string $query, ?array $parameters = null): PDOStatement
    {
        if ($parameters) {
            [$query, $parameters] = $this->expandArrayPlaceholders($query, $parameters);
        }

        /** @var PDOStatement|false */
        $stmt = $this->prepare($query);
        if (!$stmt) {
            throw new \Exception("Failed to prepare query: $query");
        }
        $stmt->execute($parameters);
        return $stmt;
    }

    /**
     * @return string[]
     */
    private function getTableNamesSqlite(): array
    {
        $tables = [];
        $stmt = $this->execute("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        foreach ($stmt->fetchAll() as $row) {
            $tables[] = (string)$row['name'];
        }
        return $tables;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function describeSqlite(string $table): array
    {
        $cols = [];
        $stmt = $this->execute("PRAGMA table_info($table)");
        foreach ($stmt->fetchAll() as $col) {
            $cols[(string)$col['name']] = [
                'type' => $col['type'],
                'not_null' => $col['notnull'] == 1,
                'default' => $col['dflt_value'],
                // 'primary_key' => $col['pk'] == 1,
                'raw' => $col,
            ];
        }
        return $cols;
    }

    /**
     * @return string[]
     */
    private function getTableNamesMysql(): array
    {
        $tables = [];
        $stmt = $this->execute("SHOW TABLES");
        foreach ($stmt->fetchAll() as $row) {
            $tables[] = (string)array_values($row)[0];
        }
        return $tables;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function describeMysql(string $table): array
    {
        $cols = [];
        $stmt = $this->execute("DESCRIBE $table");
        foreach ($stmt->fetchAll() as $col) {
            $type = strtoupper($col['Type']);
            $def = $col['Default'];
            $type = $type == "INT" ? "INTEGER" : $type;
            $type = $type == "TINYINT(1)" ? "BOOLEAN" : $type;
            $def = $type == "BOOLEAN" ? ($def == "0" ? "FALSE" : "TRUE") : $def;
            $cols[(string)$col['Field']] = [
                "type" => $type,
                "not_null" => $col['Null'] == "NO",
                "default" => $def,
                'raw' => $col,
            ];
        }
        return $cols;
    }

    /**
     * @return string[]
     */
    private function getTableNamesPgsql(): array
    {
        $tables = [];
        $stmt = $this->execute("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = 'public'");
        foreach ($stmt->fetchAll() as $row) {
            $tables[] = (string)$row['tablename'];
        }
        return $tables;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function describePgsql(string $table): array
    {
        $cols = [];
        $stmt = $this->execute("SELECT * FROM information_schema.columns WHERE table_name = :t", ["t" => $table]);
        foreach ($stmt->fetchAll() as $col) {
            $type = strtoupper($col['data_type']);
            $def = $col['column_default'];
            if ($type == "CHARACTER VARYING" && $col['character_maximum_length']) {
                $type = "VARCHAR(" . $col['character_maximum_length'] . ")";
            }
            $type = $type == "DOUBLE PRECISION" ? "FLOAT" : $type;
            $def = $type == "BOOLEAN" ? strtoupper($def) : $def;
            $cols[(string)$col['column_name']] = [
                "type" => $type,
                "not_null" => $col['is_nullable'] == "NO",
                "default" => $def,
                'raw' => $col,
            ];
        }
        return $cols;
    }

    /**
     * Returns a list of all tables in the database
     *
     * @return string[]
     */
    public function getTableNames(): array
    {
        /** @var string $driver */
        $driver = $this->getAttribute(PDO::ATTR_DRIVER_NAME);
        return match ($driver) {
            'sqlite' => $this->getTableNamesSqlite(),
            'mysql' => $this->getTableNamesMysql(),
            'pgsql' => $this->getTableNamesPgsql(),
            default => throw new \Exception("Unsupported driver: $driver"),
        };
    }

    /**
     * Returns an array describing the columns of a table
     *
     * @return array<string, array<string, mixed>>
     */
    public function describe(string $table): array
    {
        /** @var string $driver */
        $driver = $this->getAttribute(PDO::ATTR_DRIVER_NAME);
        return match ($driver) {
            'sqlite' => $this->describeSqlite($table),
            'mysql' => $this->describeMysql($table),
            'pgsql' => $this->describePgsql($table),
            default => throw new \Exception("Unsupported driver: $driver"),
        };
    }
}
