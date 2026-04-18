FFS, PHP
========
Because it's `$CURRENT_YEAR` and I'm still hitting the same PHP issues
as years ago...


Features
--------

**PDO**:

Example use:
```php
use \FFSPHP\PDO;
$db = new PDO($dsn, $driver_options);
var_dump($db->execute("SELECT * FROM mytable LIMIT :limit", ["limit"=>3])->fetchAll());
```

- Changed PDO constructor so that the MySQL backend accepts username and
  password as part of the DSN, just like every other backend.

- Added `PDO::execute($query, $parameters)`, so that one can write a
  templated query without needing to create a single-use prepared statement

- Made `PDOStatement::execute($parameters)` use typed bindings, so that
  `"LIMIT :foo", ["foo"=>3]` is rendered as `LIMIT 3` instead of `LIMIT '3'`

- Made bindings work for arrays, so that `WHERE id IN :ids` with
  `["ids"=>[1,2,3]]` is rendered as `WHERE id IN (1,2,3)` instead of
  `WHERE id IN '1,2,3'`

- Add `PDO::getTableNames()`, which returns an array of table names in the
  current database. (For Postgres, SQLite, and MySQL)

- Add `PDO::describe($table)`, which returns an array of column descriptions
  for the given table. (For Postgres, SQLite, and MySQL)

**Paths**:

```php
use \FFSPHP\Paths;

Paths::abspath("./foo");  # /my/dir/foo
Paths::relative_path("out/output.txt", "my/dir/input.txt");  # ../../out/output.txt
```

**VLQ**:

```php
use \FFSPHP\VLQ;

VLQ::decode_vlq_array("IAAM");  # [4,0,0,6]
VLQ::encode_vlq_array([4,0,0,6]);  # "IAAM"
```
