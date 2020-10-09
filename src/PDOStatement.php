<?php declare(strict_types=1);

namespace FFSPHP;

class PDOStatement extends \PDOStatement {
	protected $PDO = null;

	protected function __construct(PDO &$PDO) {
		$this->PDO = $PDO;
	}

	/**
	 * like upstream execute(), except that integers are bound
	 * as integers, so "LIMIT :foo" [foo=3] turns into "LIMIT 3"
	 * instead of "LIMIT '3'"
	 */
	public function execute($input_parameters = null) {
		if($input_parameters) {
			foreach($input_parameters as $name => $value) {
                if (is_bool($value)) {
                    $this->bindValue(':'.$name, $value, PDO::PARAM_BOOL);
                } elseif (is_int($value)) {
                    $this->bindValue(':'.$name, $value, PDO::PARAM_INT);
				} else {
					$this->bindValue(':'.$name, $value, PDO::PARAM_STR);
				}
			}
		}
		parent::execute();
	}
}
