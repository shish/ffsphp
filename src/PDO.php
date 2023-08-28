<?php

declare(strict_types=1);

namespace FFSPHP;

class PDO extends \PDO
{
    /**
     * A version of PDO which always accepts a standard DSN
     *
     * Why? Becuase while most PDO backends accept username and password inside
     * the DSN, the MySQL backend requires them to be passed separately...
     */
    public function __construct($dsn, $options = null)
    {
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
        $this->setAttribute(\PDO::ATTR_STATEMENT_CLASS, ['\FFSPHP\PDOStatement', [&$this]]);
    }

    /**
     * Like exec() except it also accepts parameters, saving the user from
     * needing to create a prepared statement for a one-off query
     */
    public function execute($query, $parameters = null)
    {
        $stmt = $this->prepare($query);
        $stmt->execute($parameters);
        return $stmt;
    }

    private function describeSqlite($table)
    {
        $cols = [];
        $stmt = $this->execute("PRAGMA table_info($table)");
        foreach ($stmt->fetchAll() as $col) {
            $cols[$col['name']] = [
                'type' => $col['type'],
                'not_null' => $col['notnull'] == 1,
                'default' => $col['dflt_value'],
                // 'primary_key' => $col['pk'] == 1,
                'raw' => $col,
            ];
        }
        return $cols;
    }

    private function describeMysql($table)
    {
        $cols = [];
        $stmt = $this->execute("DESCRIBE $table");
        foreach ($stmt->fetchAll() as $col) {
            $type = strtoupper($col['Type']);
            $def = $col['Default'];
            $type = $type == "INT" ? "INTEGER" : $type;
            $type = $type == "TINYINT(1)" ? "BOOLEAN" : $type;
            $def = $type == "BOOLEAN" ? ($def == "0" ? "FALSE" : "TRUE") : $def;
            $cols[$col['Field']] = [
                "type" => $type,
                "not_null" => $col['Null'] == "NO",
                "default" => $def,
                'raw' => $col,
            ];
        }
        return $cols;
    }

    private function describePgsql($table)
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
            $cols[$col['column_name']] = [
                "type" => $type,
                "not_null" => $col['is_nullable'] == "NO",
                "default" => $def,
                'raw' => $col,
            ];
        }
        return $cols;
    }

    public function describe($table)
    {
        $driver = $this->getAttribute(PDO::ATTR_DRIVER_NAME);
        return match ($driver) {
            'sqlite' => $this->describeSqlite($table),
            'mysql' => $this->describeMysql($table),
            'pgsql' => $this->describePgsql($table),
            default => throw new \Exception("Unsupported driver: $driver"),
        };
    }
}
