<?php

declare(strict_types=1);

require "vendor/autoload.php";

use FFSPHP\PDO;

class PDOTest extends \PHPUnit\Framework\TestCase
{
    public $dsn;

    public function setUp(): void
    {
        parent::setUp();
        //$this->dsn = getenv('DSN') ? getenv('DSN') : "sqlite::memory:";
        $this->dsn = getenv('DSN') ? getenv('DSN') : "sqlite:test.sqlite";
    }

    public function testConstructor()
    {
        $db = new PDO($this->dsn);
        $this->assertNotNull($db);
        return $db;
    }

    public function testConstructorWithAuth()
    {
        try {
            new PDO("test:user=foo;password=bar");
            $this->assertTrue(false);
        } catch (\PDOException $e) {
            // "can't find driver" is expected
            $this->assertTrue(true);
        }
    }

    /**
     * @depends testConstructor
     */
    public function testBools(PDO $db)
    {
        $db->execute("DROP TABLE IF EXISTS bools");
        $db->execute("CREATE TABLE bools (nt BOOLEAN, nf BOOLEAN, pt BOOLEAN, pf BOOLEAN)");
        $db->execute(
            "INSERT INTO bools VALUES (true, false, :true, :false)",
            ["true" => true, "false" => false]
        );
        $data = $db->execute("SELECT * FROM bools")->fetchAll();
        $this->assertTrue((bool) $data[0][0]);
        $this->assertFalse((bool) $data[0][1]);
        $this->assertTrue((bool) $data[0][2]);
        $this->assertFalse((bool) $data[0][3]);
        return $db;
    }

    /**
     * @depends testConstructor
     */
    public function testBaseData(PDO $db)
    {
        $db->execute("DROP TABLE IF EXISTS test");
        $db->execute("CREATE TABLE test (id INTEGER, textval TEXT, boolval BOOLEAN)");
        $db->execute(
            "INSERT INTO test VALUES (:id, :text, :bool)",
            ["id" => 1, "text" => "hello", "bool" => true]
        );
        $db->execute(
            "INSERT INTO test VALUES (:id, :text, :bool)",
            ["id" => 2, "text" => "world", "bool" => false]
        );
        // var_dump($db->execute("SELECT * FROM test")->fetchAll());
        $this->assertTrue(true);
        return $db;
    }

    /**
     * @depends testBaseData
     */
    public function testExecute(PDO $db)
    {
        $val = $db->execute("SELECT * FROM test WHERE id=:id", ["id" => 2])->fetch();
        $this->assertEquals("world", $val['textval']);
    }

    /**
     * @depends testBaseData
     */
    public function testBindingMetaInt(PDO $db)
    {
        // By default, mysql would bind this as the string '1' and then
        // throw a syntax error - let's check that that doesn't happen.
        $res = $db->execute("SELECT * FROM test LIMIT :limit", ["limit" => 1])->fetchAll();
        $this->assertCount(1, $res);
    }

    /**
     * @depends testBaseData
     */
    public function testBindingInt(PDO $db)
    {
        $res = $db->execute("SELECT * FROM test WHERE id=:id", ["id" => 2])->fetch();
        $this->assertEquals("world", $res['textval']);
    }

    /**
     * @depends testBaseData
     */
    public function testBindingStr(PDO $db)
    {
        $res = $db->execute("SELECT * FROM test WHERE textval=:textval", ["textval" => "hello"])->fetch();
        $this->assertEquals(1, $res['id']);
    }

    /**
     * @depends testBaseData
     */
    public function testBindingBool(PDO $db)
    {
        $res = $db->execute("SELECT * FROM test WHERE boolval=:boolval", ["boolval" => true])->fetch();
        $this->assertEquals(1, $res['id']);
        $res = $db->execute("SELECT * FROM test WHERE boolval=:boolval", ["boolval" => false])->fetch();
        $this->assertEquals(2, $res['id']);
    }

    private function _checkDescribe($expected, $actual): void
    {
        $raw = "";
        if(isset($actual['raw'])) {
            $raw = var_export($actual['raw'], true);
            unset($actual['raw']);    
        }
        $this->assertEquals($expected, $actual, $raw);
    }

    /**
     * @depends testConstructor
     */
    public function testTypes(PDO $db)
    {
        $db->execute("DROP TABLE IF EXISTS frn");
        $db->execute("CREATE TABLE frn (id INTEGER PRIMARY KEY)");
        $db->execute("DROP TABLE IF EXISTS types");
        $db->execute("CREATE TABLE types (
            id INTEGER PRIMARY KEY NOT NULL,
            textval TEXT NOT NULL,
            charval VARCHAR(10) NOT NULL UNIQUE,
            floatval FLOAT NOT NULL DEFAULT 4.2,
            boolval BOOLEAN DEFAULT FALSE,
            frn INTEGER REFERENCES frn(id) ON DELETE CASCADE
        )");
        $desc = $db->describe("types");
        $this->_checkDescribe(
            [
                'type' => 'INTEGER',
                'not_null' => true,
                'default' => null,
            ],
            $desc["id"],
        );
        $this->_checkDescribe(
            [
                'type' => 'TEXT',
                'not_null' => true,
                'default' => null,
            ],
            $desc["textval"],
        );
        $this->_checkDescribe(
            [
                'type' => 'VARCHAR(10)',
                'not_null' => true,
                'default' => null,
            ],
            $desc["charval"],
        );
        $this->_checkDescribe(
            [
                'type' => 'FLOAT',
                'not_null' => true,
                'default' => '4.2',
            ],
            $desc["floatval"],
        );
        $this->_checkDescribe(
            [
                'type' => 'BOOLEAN',
                'not_null' => false,
                'default' => 'FALSE',
            ],
            $desc["boolval"],
        );
        $this->_checkDescribe(
            [
                'type' => 'INTEGER',
                'not_null' => false,
                'default' => null,
            ],
            $desc["frn"],
        );
    }

}