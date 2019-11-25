<?php
namespace FFSPHP;

class PDO extends \PDO {
	/**
	 * A version of PDO which always accepts a standard DSN
	 *
	 * Why? Becuase while most PDO backends accept username and password inside
	 * the DSN, the MySQL backend requires them to be passed separately...
	 */
	public function __construct($dsn, $options = null) {
		$user=null;
		$pass=null;
		if (preg_match("/user=([^;]*)/", $dsn, $matches)) {
			$user=$matches[1];
		}
		if (preg_match("/password=([^;]*)/", $dsn, $matches)) {
			$pass=$matches[1];
		}

		parent::__construct($dsn, $user, $pass, $options);

		$this->setAttribute(\PDO::ATTR_STATEMENT_CLASS , ['\FFSPHP\PDOStatement', [&$this]]);
	}

	/**
	 * Like exec() except it also accepts parameters, saving the user from
	 * needing to create a prepared statement for a one-off query
	 */
	public function execute($query, $parameters=null) {
		$stmt = $this->prepare($query);
		$stmt->execute($parameters);
		return $stmt;
	}
}
