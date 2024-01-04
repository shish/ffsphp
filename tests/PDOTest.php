<?php

declare(strict_types=1);

require "vendor/autoload.php";

use FFSPHP\PDO;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Depends;

class PDOTest extends TestCase
{
    public string $dsn;

    public function setUp(): void
    {
        parent::setUp();
        //$this->dsn = getenv('DSN') ? getenv('DSN') : "sqlite::memory:";
        $this->dsn = getenv('DSN') ? getenv('DSN') : "sqlite:test.sqlite";
    }

    public function testConstructor(): PDO
    {
        $db = new PDO($this->dsn);
        $this->assertNotNull($db);
        return $db;
    }

    public function testConstructorWithAuth(): void
    {
        try {
            new PDO("test:user=foo;password=bar");
            $this->assertTrue(false);
        } catch (\PDOException $e) {
            // "can't find driver" is expected
            $this->assertTrue(true);
        }
    }

    #[Depends("testConstructor")]
    public function testBools(PDO $db): PDO
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

    #[Depends("testConstructor")]
    public function testBaseData(PDO $db): PDO
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

    #[Depends("testBaseData")]
    public function testExecute(PDO $db): void
    {
        $val = $db->execute("SELECT * FROM test WHERE id=:id", ["id" => 2])->fetch();
        $this->assertEquals("world", $val['textval']);
    }

    #[Depends("testBaseData")]
    public function testBindingMetaInt(PDO $db): void
    {
        // By default, mysql would bind this as the string '1' and then
        // throw a syntax error - let's check that that doesn't happen.
        $res = $db->execute("SELECT * FROM test LIMIT :limit", ["limit" => 1])->fetchAll();
        $this->assertCount(1, $res);
    }

    #[Depends("testBaseData")]
    public function testBindingInt(PDO $db): void
    {
        $res = $db->execute("SELECT * FROM test WHERE id=:id", ["id" => 2])->fetch();
        $this->assertEquals("world", $res['textval']);
    }

    #[Depends("testBaseData")]
    public function testBindingStr(PDO $db): void
    {
        $res = $db->execute("SELECT * FROM test WHERE textval=:textval", ["textval" => "hello"])->fetch();
        $this->assertEquals(1, $res['id']);
    }

    #[Depends("testBaseData")]
    public function testBindingBool(PDO $db): void
    {
        $res = $db->execute("SELECT * FROM test WHERE boolval=:boolval", ["boolval" => true])->fetch();
        $this->assertEquals(1, $res['id']);
        $res = $db->execute("SELECT * FROM test WHERE boolval=:boolval", ["boolval" => false])->fetch();
        $this->assertEquals(2, $res['id']);
    }

    #[Depends("testBaseData")]
    public function testBindingArray(PDO $db): void
    {
        $this->expectException(\PDOException::class);
        $res = $db->execute(
            "SELECT * FROM test WHERE textval IN (:arrayval)",
            ["arrayval" => ["hello", "world"]]
        )->fetchAll();
        //$this->assertCount(2, $res);
        //$this->assertEquals(1, $res[0]['id']);
    }

    /**
     * @param mixed[] $expected
     * @param mixed[] $actual
     */
    private function _checkDescribe(array $expected, array $actual): void
    {
        $raw = "";
        if(isset($actual['raw'])) {
            $raw = var_export($actual['raw'], true);
            unset($actual['raw']);
        }
        $this->assertEquals($expected, $actual, $raw);
    }

    #[Depends("testConstructor")]
    public function testTypes(PDO $db): void
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
