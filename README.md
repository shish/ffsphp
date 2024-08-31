FFS, PHP
========
Because it's `$CURRENT_YEAR` and I'm still hitting the same PHP issues
as years ago...


Features
--------

**PDO**:

Example use:
```
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

**Paths**:

```
use \FFSPHP\Paths;

Paths::abspath("./foo");  # /my/dir/foo
Paths::relative_path("out/output.txt", "my/dir/input.txt");  # ../../out/output.txt
```

**VLQ**:

```
use \FFSPHP\VLQ;

VLQ::decode_vlq_array("IAAM");  # [4,0,0,6]
VLQ::encode_vlq_array([4,0,0,6]);  # "IAAM"
```
