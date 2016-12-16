# GxDBConnect

Easily and **securely** execute commands on a database using PHP Data Objects (PDO).

## Security Features

To help prevent SQL injection, the following security measures are included:

- Whitelisting for both table and column names.
- Backlisting for statement clause words (or any unwanted strings).
  - By default, the following strings are blacklisted:
      - "DROP" = Disallows DROP statements.
      - "DELETE" = Disallows DELETE statements.
      - "--" = Helps to project against comment attacks.
      - "/*" =  Helps to project against comment attacks.
      - "xp_" = Helps to prevent calls to SQL Server extended stored procedures.
      - ";" = Helps to prevent new statement injections.
- Errors are caught and filtered through the GxDBException class.
  - Prevents leaking internal logic, which can happen when using standard PHP exception classes.
- By default, uncaught error echoing is silenced so to prevent leaking data through uncaught errors messages.
- All queries are "prepared" before being executed (with support for value binding).

## Classes

- GxDBConnect
  - The main class you will use.
- GxDBException (nested in GxDBConnect)
  - Extends the PHP Exception class to add a custom message format.
- DSNHelper
  - Data Source Name helper. This class is filled with static methods that return DSN strings. Use it to simplify the database connection process.

## Samples

This sample includes tight security.

Use value binding, a whitelist, and checker methods if you plan to construct your SQL statements with values coming from a form (or other user inputted method). Using these featured will help to prevent SQL attacks.

```
include "./GxDBConnect.class.php";
include "./DSNHelper.class.php";

try {
  $conn = new GxDBConnect(DSNHelper::mysql("my_database"), "my_username", "my_pass");

  $conn->col_whitelist = array("first", "last");
  $conn->tbl_whitelist = array("names_table");

  $f_name = $_GET["first_name"];
  $l_name = $_GET["last_name"];
  $table = $_GET["table_name"];

  $data = $conn->query("
    SELECT {$conn->col_check($f_name)}
    FROM {$conn->tbl_check($table)}
    WHERE {$conn->col_check($l_name)} = :ln
    ",
    array(
      $conn->bind_value(":ln", "Doe")
    ),
    PDO::FETCH_NUM
  );

  print_r($data);
} catch(GxDBException $e) {
  exit($e);
}
```

This sample includes a more simple use case.

```
include "./GxDBConnect.class.php";

try {
  $conn = new GxDBConnect("mysql:host=localhost;dbname=my_database", "my_username", "my_pass");

  $data = $conn->query("
    SELECT first
    FROM names_table
    WHERE last = 'Doe'
  ");

  print_r($data);
} catch(GxDBException $e) {
  exit($e);
}
```

## Documentation

See the documentation online at <http://grafluxe.com/doc/php/GxDBConnect>.

If you need to *generate* the documentation yourself, run the generate-docs script by doing the following:

- Open your favorite CLI.
- Run the following command: `php path/to/this/projects/gen/generate-docs`

Note: In order to automate the documentation process, the generate-docs script will initiate steps to download two files to your machine ([gendoc.php](https://gist.github.com/Grafluxe/b6521901216a3c8e09f36cc31988a66c) and [apigen.phar](https://github.com/ApiGen/ApiGen.github.io)).


## License

Copyright (c) 2012-2016 Leandro Silva (http://grafluxe.com)

Released under the MIT License.

See LICENSE.md for entire terms.
