# GxConn

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
- Semicolons are not allowed so to protect against new statement injections.
- Errors are caught and filtered though the GxConnException class.
- Echoing is off by default to prevents leaking data through errors.
- All queries are prepared and executed (with support for value binding).

## Classes

- GxConn
  - The main class you will use.
- GxConnException
  - Extends the PHP Exception class to add custom exceptions.
- GXConnDSNHelper
  - DSN helper. This class is filled with static methods that return DSN strings. Use it to simplify the database connection process.

## Public Members

### Properties

- **$conn** = The PDO object.
- **$col_whitelist** = A whitelist of columns that can be queried. Use in concert with the col_check method.
- **$tbl_whitelist** = A whitelist of tables that can be queried. Use in concert with the tbl_check method.
- **$get_last_stmt** = The statement you last queried.
- **$blacklist** = An array of forbidden clause words. Letter case does not matter.
- **$version** (static) = The version.
- **$echoUncaughtErrors** (static) = Set to true to output uncaught errors. Defaults to false for better security.

### Methods

- **select_db()** = Selects a database.
- **col_check(...)** = Checks if a column in allowed to be used (via the column whitelist).
- **tbl_check(...)** = Checks if a table in allowed to be used (via the table whitelist).
- **bind_value(...)** = To be used as the bind argument in the 'query' method. Works like PDO's bindValue method.
- **query(...)** = Runs an SQL query. This is the primary method used to run queries.
- **close()** = Closes the database connection.
- **run_tbl_exists(...)** = Returns a boolean determining whether a table exists.
- **run_col_count(...)** = Returns the total column count.
- **run_col_info(...)** = Returns an array of associative arrays with column info.
- **run_col_data(...)** = Returns all of a columns data.
- **run_col_exists(...)** = Returns a boolean determining whether a column exists.
- **run_row_total(...)** = Returns the total row count.
- **run_row_data(...)** = Returns data in the specified row.
- **run_export(...)** = Exports your table as a JSON formatted file.
- **run_tbl_to_html(...)** = Echos an HTML table with your data.

For detailed documentation, see inline code in the source file.

## Sample

This first sample includes tight security.

Use value binding, a whitelist, and checker methods if you plan to construct your SQL statements with values coming from a form (or other user inputted method). Using these featured will help to prevent SQL injection.

```
$conn = new GXConn(GXConnDSNHelper::dsn_mysql("my_database"), "my_username", "my_pass");

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
```

This sample includes a more simple use case.

```
$conn = new GXConn(GXConnDSNHelper::dsn_mysql("my_database"), "my_username", "my_pass");

try {
  $data = $conn->query("
    SELECT first
    FROM names_table
  ");
} catch(GxConnException $e) {
  echo "error - " . $e;
}

print_r($data);
```

## License

Copyright (c) 2012 Leandro Silva (http://grafluxe.com)

Released under the MIT License.

See LICENSE.md for entire terms.
