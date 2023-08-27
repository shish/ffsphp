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

    public function describe($table)
    {
        $cols = [];
        $driver = $this->getAttribute(PDO::ATTR_DRIVER_NAME);
        switch ($driver) {
            case 'sqlite':
                $stmt = $this->execute("PRAGMA table_info($table)");
                foreach ($stmt->fetchAll() as $col) {
                    $cols[$col['name']] = [
                        'type' => $col['type'],
                        'not_null' => $col['notnull'] == 1,
                        'default' => $col['dflt_value'],
                        'pk' => $col['pk'] == 1,
                    ];
                }
                break;
            case 'mysql':
                $stmt = $this->execute("DESCRIBE $table");
                foreach ($stmt->fetchAll() as $col) {
                    $cols[$col['Field']] = $col;
                }
                break;
            case 'pgsql':
                $stmt = $this->execute("SELECT * FROM information_schema.columns WHERE table_name = :t", ["t"=>$table]);
                foreach ($stmt->fetchAll() as $col) {
                    $cols[$col['column_name']] = [
                        "type" => $col['data_type'],
                        "not_null" => $col['is_nullable'] == "NO",
                        "default" => $col['column_default'],
                        "pk" => $col['column_key'] == "PRI",
                    ];
                }
                break;
            default:
                throw new \Exception("Unsupported driver: $driver");
        }
        return $cols;
    }
}