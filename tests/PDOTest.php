<?php

require "vendor/autoload.php";

use FFSPHP\PDO;

class PDOTestCase extends \PHPUnit\Framework\TestCase {
	var $dsn;

	public function setUp(): void {
		parent::setUp();
		$this->dsn = getenv('DSN') ? getenv('DSN') : "sqlite::memory:";
	}

	public function testConstructor() {
		$db = new PDO($this->dsn);
		$this->assertNotNull($db);
		return $db;
	}

	public function testConstructorWithAuth() {
		try {
			new PDO("test:user=foo;password=bar");
			$this->assertTrue(False);
		}
		catch(\PDOException $e) {
			// "can't find driver" is expected
			$this->assertTrue(True);
		}
	}

	/**
	 * @depends testConstructor
	 */
	public function testBaseData(PDO $db) {
		$db->execute("DROP TABLE IF EXISTS test");
		$db->execute("CREATE TABLE test (id INTEGER, value TEXT)");
		$db->execute("INSERT INTO test VALUES (1, 'hello')");
		$db->execute("INSERT INTO test VALUES (2, 'world')");
		$this->assertTrue(True);
		return $db;
	}

	/**
	 * @depends testBaseData
	 */
	public function testExecute(PDO $db) {
		$val = $db->execute("SELECT * FROM test WHERE id=:id", ["id"=>2])->fetch()['value'];
		$this->assertEquals("world", $val);
	}

	/**
	 * @depends testBaseData
	 */
	public function testBindingInt(PDO $db) {
		// By default, mysql would bind this as the string '1' and then
		// throw a syntax error - let's check that that doesn't happen.
		$res = $db->execute("SELECT * FROM test LIMIT :limit", ["limit"=>1]);
		$this->assertCount(1, $res);
	}

	/**
	 * @depends testBaseData
	 */
	public function testBindingStr(PDO $db) {
		$res = $db->execute("SELECT * FROM test WHERE value=:value", ["value"=>"hello"]);
		$this->assertCount(1, $res);
	}
}
