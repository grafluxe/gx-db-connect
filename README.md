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
      - ";" = Helps to prevent new statement injections.
- Errors are caught and filtered through the GxConnException class.
  - Prevents leaking internal logic, which can happen when using standard PHP exception classes.
- By default, uncaught error echoing is silenced so to prevent leaking data through uncaught errors messages.
- All queries are "prepared" before being executed (with support for value binding).

## Classes

- GxConn
  - The main class you will use.
- GxConnException (nested in GxConn)
  - Extends the PHP Exception class to add a custom message format.
- GxConnDSNHelper
  - DSN helper. This class is filled with static methods that return DSN strings. Use it to simplify the database connection process.

## Public Members

### Properties

- **$conn** = The PDO object.
- **$col_whitelist** = A whitelist of columns that can be queried. Use in concert with the 'col_check' method.
- **$tbl_whitelist** = A whitelist of tables that can be queried. Use in concert with the 'tbl_check' method.
- **$get_last_stmt** = The statement you last queried.
- **$version** (static) = The release version.
- **$echoUncaughtErrors** (static) = Set to true to output uncaught errors. Defaults to false for better security.

### Methods

- **blacklist_add(...)** = Adds a value to your blacklist filter. Before any query is run, your statement will be checked for any blacklisted string. If a blacklisted string is found, the query will not be executed and a GxConnException exception will be thrown.
- **blacklist_remove(...)** = Removes a value from your blacklist filter.
- **blacklist_list()** = Returns your blacklist filters.
- **select_db()** = Selects a database.
- **col_check(...)** = Checks if a column in allowed to be used (via the column whitelist).
- **tbl_check(...)** = Checks if a table in allowed to be used (via the table whitelist).
- **bind_value(...)** = To be used as the bind argument in the 'query' method. Works like PDO's 'bindValue' method.
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

## Samples

This sample includes tight security.

Use value binding, a whitelist, and checker methods if you plan to construct your SQL statements with values coming from a form (or other user inputted method). Using these featured will help to prevent SQL attacks.

```
  include "./GxConn.class.php";
  include "./GxConnDSNHelper.class.php";

  try {
    $conn = new GxConn(GxConnDSNHelper::dsn_mysql("my_database"), "my_username", "my_pass");

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
  } catch(GxConnException $e) {
    exit($e);
  }
```

This sample includes a more simple use case.

```
  include "./GxConn.class.php";

  try {
    $conn = new GxConn("mysql:host=localhost;port=;dbname=my_database", "my_username", "my_pass");

    $data = $conn->query("
      SELECT first
      FROM names_table
      WHERE last = 'Doe'
    ");

    print_r($data);
  } catch(GxConnException $e) {
    exit($e);
  }
```

## License

Copyright (c) 2012-2016 Leandro Silva (http://grafluxe.com)

Released under the MIT License.

See LICENSE.md for entire terms.
