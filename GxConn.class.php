<?php
/**
 * @author Leandro Silva | Grafluxe, 2012
 */

/**
 * Execute commands on a database using PHP Data Objects. Many security features added.
 * Supports multiple database drivers. Note that methods prepended with "run_" execute
 * specific statements; use the query method for custom queries.
 *
 * Sample:
 * <pre>
 *   $conn = new GXConn(GXConnDSNHelper::dsn_mysql("my_database"), "my_username", "my_pass");
 *
 *   $conn->col_whitelist = array("first", "last");
 *   $conn->tbl_whitelist = array("names_table");
 *
 *   $f_name = $_GET["first_name"];
 *   $l_name = $_GET["last_name"];
 *   $table = $_GET["table_name"];
 *
 *   $data = $conn->query("
 *     SELECT {$conn->col_check($f_name)}
 *     FROM {$conn->tbl_check($table)}
 *     WHERE {$conn->col_check($l_name)} = :ln
 *     ",
 *     array(
 *       $conn->bind_value(":ln", "Doe")
 *     ),
 *     PDO::FETCH_NUM
 *   );
 *
 *   print_r($data);
 *
 *   try {
 *     $data2 = $conn->query("
 *       SELECT first
 *       FROM names_table
 *     ");
 *   } catch(GxConnException $e) {
 *     echo "error - " . $e;
 *   }
 *
 *   print_r($data2);
 * </pre>
 */
class GxConn {
  /** @var object The PDO object. */
  public $conn;

  /** @var array A whitelist of columns that can be queried. Use in concert with the col_check method. */
  public $col_whitelist;

  /** @var array A whitelist of tables that can be queried. Use in concert with the tbl_check method. */
  public $tbl_whitelist;

  /** @var string The statement you last queried. */
  public $get_last_stmt;

  /** @var array An array of forbidden clause words. Letter case does not matter. */
  public $blacklist = array("DROP", "DELETE", "--", "/*", "xp_");

  /** @var string The version. */
  public static $version = "2.2.0";

  /** @var string Set to true to output uncaught errors. Defaults to false for better security. */
  public static $echoUncaughtErrors = false;

  private $c;

  /**
   * Constructor.
   * @param string $dsn            The DSN string. You can use the GxConnHelper class to help setup this param.
   * @param string [$usr = "root"] The username.
   * @param string [$pw = "root"]  The password.
   * @param array  [$opts = NULL]  By default, ATTR_EMULATE_PREPARES is set to false and ATTR_ERRMODE is set to ERRMODE_EXCEPTION.
   */
  public function __construct($dsn, $usr = "root", $pw = "root", array $opts = NULL) {
    set_exception_handler(array(
      $this,
      "error_handler"
    ));

    try {
      $this->c = new PDO($dsn, $usr, $pw, $opts);
    }
    catch (PDOException $e) {
      throw new GxConnException($e->getMessage(), 1);
    }

    $this->c->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $this->c->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $this->conn = $this->c;
  }

  /**
    * @ignore
    */
  public static function error_handler($e) {
    if (self::$echoUncaughtErrors) {
      echo "[Uncaught GxConn Error]: " . $e->getMessage();
    } else {
      echo "[Uncaught GxConn Error]: To see uncaught errors, set 'GXConn::\$echoUncaughtErrors' to true.";
    }
  }

  private function blacklist_check($s) {
    if (isset($this->blacklist) && count($this->blacklist) > 0) {
      for ($i = 0; $i < count($this->blacklist); $i++) {
        if ($i > 0) {
          $regex .= "|";
        }
        $regex .= preg_quote($this->blacklist[$i]);
      }

      $regex = str_replace("/", "\/", $regex);

      //remove extra space
      $s = preg_replace("/\s+/", " ", $s);

      //remove all strings in order to test clauses
      $s = preg_replace("/\".*?\"|'.*?'/", "", $s);

      if (preg_match("/$regex/i", $s)) {
        throw new GXConnException("Your query statement contains a blacklisted clause.", 3, NULL);
      }
    }
  }

  private function semicolon_check($s) {
    //remove all strings in order to test clauses
    $s = preg_replace("/\".*?\"|'.*?'/", "", $s);

    if (preg_match("/;/", $s)) {
      throw new GXConnException("Your query statement cannot contain a semi-colon.", 4);
    }
  }

  /**
   * Selects a database.
   * @param string $db The database name.
   */
  public function select_db($db) {
    $this->conn->exec("USE $db");
  }

  /**
   * Checks if a column in allowed to be used (via the column whitelist).
   * @param  string $col The column name.
   * @return string The column name.
   */
  public function col_check($col) {
    if (isset($this->col_whitelist)) {
      if (!(array_search($col, $this->col_whitelist, true) !== false)) {
        throw new GXConnException("You do not have permission to query one of the columns in your statement.", 1);
      }
    }

    return $col;
  }

  /**
   * Checks if a table in allowed to be used (via the table whitelist).
   * @param  string $tbl The table to query.
   * @return string The table name.
   */
  public function tbl_check($tbl) {
    if (isset($this->tbl_whitelist)) {
      if (!(array_search($tbl, $this->tbl_whitelist, true) !== false)) {
        throw new GXConnException("You do not have permission to query one of the tables in your statement.", 2);
      }
    }

    return $tbl;
  }

  /**
   * To be used as the bind argument in the 'query' method. Works like PDO's bindValue method.
   * @param  mixed $parameter            The parameter identifier.
   * @param  mixed $value                The value to bind.
   * @param  integer [$data_type = NULL] The data type.
   * @return array The bind data.
   */
  public function bind_value($parameter, $value, $data_type = NULL) {
    return array(
      $parameter,
      $value,
      $data_type
    );
  }

  /**
   * Runs an SQL query. This is the primary method used to run queries.
   * @param  string $stmt                             Your query statement (use 'col_check' and 'tbl_check' with the whitelists for added security against SQL injecion)
   * @param  array    [$bind = NULL]                  An array filled with the 'bind_value' methods.
   * @param  integer   [$fetch_how = PDO::FETCH_ASSOC] How to return the results.
   * @return array|null Query results.
   */
  public function query($stmt, array $bind = NULL, $fetch_how = PDO::FETCH_ASSOC) {
    $this->semicolon_check($stmt);
    $this->blacklist_check($stmt);

    $q = $this->c->prepare($stmt . ";");
    $this->get_last_stmt = $q->queryString;

    if (isset($bind)) {
      for ($i = 0; $i < count($bind); $i++) {
        if ($bind[$i][2]) {
          $q->bindValue($bind[$i][0], $bind[$i][1], $bind[$i][2]);
        } else {
          $q->bindValue($bind[$i][0], $bind[$i][1]);
        }
      }
    }

    $q->closeCursor();
    $q->execute();

    try {
      return $q->fetchAll($fetch_how);
    }
    catch (PDOException $e) {
      return NULL;
    }
  }

  /**
   * Closes the database connection.
   */
  public function close() {
    $this->conn = NULL;
    $this->c = NULL;
  }

  /**
   * Returns a boolean determining whether a table exists.
   * @param  string $tbl The table to query.
   * @return boolean  Whether a table exists.
   */
  public function run_tbl_exists($tbl) {
    try {
      $q = $this->c->prepare("SELECT 1 FROM {$this->tbl_check($tbl)} LIMIT 1;");
      $this->get_last_stmt = $q->queryString;
      $q->closeCursor();
      $q->execute();

      return true;
    }
    catch (PDOException $e) {
      return false;
    }
  }

  /**
   * Returns the total column count.
   * @param  string $tbl The table to query.
   * @return integer The number of columns.
   */
  public function run_col_count($tbl) {
    $q = $this->c->prepare("SELECT * FROM {$this->tbl_check($tbl)} LIMIT 1;");
    $this->get_last_stmt = $q->queryString;

    $q->closeCursor();
    $q->execute();

    return $q->columnCount();
  }

  /**
   * Returns an array of associative arrays with column info.
   * @param  string $tbl The table to query.
   * @return array Column info.
   */
  public function run_col_info($tbl) {
    $q = $this->c->prepare("SHOW COLUMNS FROM {$this->tbl_check($tbl)};");
    $this->get_last_stmt = $q->queryString;

    $q->closeCursor();
    $q->execute();

    return $q->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Returns all of a columns data.
   * @param  string $col The column name.
   * @param  string $tbl The table to query.
   * @return array Columns data.
   */
  public function run_col_data($col, $tbl) {
    $q = $this->c->prepare("SELECT {$this->col_check($col)} FROM {$this->tbl_check($tbl)};");
    $this->get_last_stmt = $q->queryString;

    $q->closeCursor();
    $q->execute();

    $fin = array();
    foreach ($q as $r) {
      array_push($fin, $r[0]);
    }

    return $fin;
  }

  /**
   * Returns a boolean determining whether a column exists.
   * @param  string $col The column name.
   * @param  string $tbl The table to query.
   * @return boolean  Whether a column exists.
   */
  public function run_col_exists($col, $tbl) {
    try {
      $q = $this->c->prepare("SELECT {$this->col_check($col)} FROM {$this->tbl_check($tbl)} LIMIT 1;");
      $this->get_last_stmt = $q->queryString;
      $q->closeCursor();
      $q->execute();

      return true;
    }
    catch (PDOException $e) {
      return false;
    }
  }

  /**
   * Returns the total row count.
   * @param  string $tbl The table to query.
   * @return integer The row count.
   */
  public function run_row_total($tbl) {
    $q = $this->c->prepare("SELECT COUNT(*) FROM {$this->tbl_check($tbl)};");
    $this->get_last_stmt = $q->queryString;

    $q->closeCursor();
    $q->execute();

    return $q->fetchColumn();
  }

  /**
   * Returns data in the specified row.
   * @param  integer $row The row number.
   * @param  string $tbl  The table to query.
   * @return array The row data. Returns null if the specified row is greater than the total number of rows.
   */
  public function run_row_data($row, $tbl) {
    $row = (int) $row;
    $q = $this->c->prepare("SELECT * FROM {$this->tbl_check($tbl)};");
    $this->get_last_stmt = $q->queryString;

    $q->closeCursor();
    $q->execute();

    if ($row > $this->run_row_total($tbl)) {
      return NULL;
    } else {
      $arr = $q->fetchAll(PDO::FETCH_NUM);

      return $arr[$row];
    }
  }

  /**
   * Exports your table as a JSON formatted file.
   * @param  string   $tbl                    The table to export.
   * @param  boolean [$pretty_print = false]  Whether to pretty-print output (only valid on PHP versions >=5.4.0).
   * @param  string [$relative_dir = ""]      A save path relative to this file.
   * @return boolean Whether the output succeeded.
   */
  public function run_export($tbl, $pretty_print = false, $relative_dir = "") {
    $file_name = date("m-d-y") . "-" . $tbl . ".json";

    $obj["GxConn"] = self::$version;
    $obj["table"] = $tbl;
    $obj["colCount"] = $this->run_col_count($tbl);
    $obj["rowCount"] = $this->run_row_total($tbl);

    //cols
    $desc = $this->query("DESCRIBE $tbl");
    $keys = array_keys($desc[0]);

    for ($i = 0, $len = count($desc); $i < $len; $i++) {
      for ($k = 0, $lenK = count($keys); $k < $lenK; $k++) {
        $cols[$i][$keys[$k]] = isset($desc[$i][$keys[$k]]) ? utf8_encode($desc[$i][$keys[$k]]) : "";
      }
    }

    $obj["cols"] = $cols;

    //rows
    $q = $this->c->prepare("SELECT * FROM $tbl;");
    $q->closeCursor();
    $q->execute();

    $obj["rows"] = $q->fetchAll(PDO::FETCH_NUM);

    //
    $json = json_encode($obj, ($pretty_print && defined(JSON_PRETTY_PRINT) ? JSON_PRETTY_PRINT : 0));

    if (json_last_error() != JSON_ERROR_NONE) {
      die("There was a problem creating your JSON export: " . json_last_error_msg());
    }

    if ($relative_dir) {
      $relative_dir = trim($relative_dir, "/") . "/";

      if (!is_dir($relative_dir)) {
        mkdir($relative_dir);
      }
    }

    return file_put_contents($relative_dir . $file_name, $json) > 0;
  }

  /**
   * Echos an HTML table with your data.
   * @param string $stmt                    Your SQL query statement.
   * @param integer [$paginateAt = 0]       Paginate after N rows of data. Works with the $pgQueryName param.
   * @param string [$pgQueryName = "pg"]    The paginate HTML query string name.
   * @param boolean [$defaultStyles = true] Assigns default inline styles.
   */
  public function run_tbl_to_html($stmt, $paginateAt = 0, $pgQueryName = "pg", $defaultStyles = true) {
    $this->semicolon_check($stmt);
    $this->blacklist_check($stmt);

    $bg_color = "#CCC";
    $col_color_odd = "#F9F9F9";
    $col_color_even = "#F0F0F0";
    $header_color = "#9C9C9C";
    $alt_row = true;
    $row_num = 1;

    //col names
    $qNames = $this->c->prepare($stmt . ";");
    $qNames->closeCursor();
    $qNames->execute();

    $names = $qNames->fetchAll(PDO::FETCH_ASSOC);
    $col_names = array_keys($names[0]);

    //main query
    if ($paginateAt) {
      if (stristr($stmt, "LIMIT") || stristr($stmt, "OFFSET")) {
        throw new GXConnException("Your 'run_tbl_to_html' statement cannot have a LIMIT or OFFSET clause.", 5);
      }

      $totalPgs = ceil($qNames->rowCount() / $paginateAt);
      $pgQuery  = isset($_GET[$pgQueryName]) ? $_GET[$pgQueryName] : 1;

      if ($pgQuery > $totalPgs) {
        $pgQuery = $totalPgs;
      }

      $stmt .= " LIMIT $paginateAt OFFSET " . $paginateAt * ($pgQuery - 1);
    }

    $q = $this->c->prepare($stmt . ";");
    $q->closeCursor();
    $q->execute();

    $len = $q->columnCount();

    if ($defaultStyles) {
      $styles  = " style=\"width:100%; background-color:$bg_color; text-align:center\"";
      $styles2 = " style=\"padding:3px 12px; background-color:$header_color\"";
    }

    $echo = "<table class=\"GxConnTable\"$styles>\n<tr class=\"headerRow\"$styles2>\n";

    //heads
    for ($i = 0; $i < $len; $i++) {
      $col_num = $i + 1;

      $echo .= "<td class=\"col$col_num\">$col_names[$i]</td>\n";
    }

    $echo .= "</tr>\n";

    //data
    foreach ($q as $row) {
      for ($i = 0; $i < $len; $i++) {
        if ($i == 0) {
          if ($alt_row) {
            $alt_row       = false;
            $colColor      = $col_color_odd;
            $alt_row_class = "oddRow";
          } else {
            $alt_row       = true;
            $colColor      = $col_color_even;
            $alt_row_class = "evenRow";
          }
        }

        if ($defaultStyles) {
          $styles = " style=\"padding:3px 12px; background-color:$colColor;\"";
        }

        if (($i % $len) == 0) {
          $echo .= "<tr class=\"row$row_num $alt_row_class\"$styles>\n";
        }

        $col_num = $i + 1;

        $echo .= "<td class=\"col$col_num\">$row[$i]</td>\n";

        if ($i == ($len - 1)) {
          $echo .= "</tr>\n";
          $row_num++;
        }
      }
    }

    $echo .= "</table>";

    echo $echo;

    if ($paginateAt) {
      if ($totalPgs > 1) {
        $this->paginate($pgQuery, $pgQueryName, $totalPgs);
      }
    }
  }

  private function paginate($pg, $pgQueryName, $totalPgs) {
    $echo = "\n<p class=\"GxConnPagination\">\n" . ($pg > 1 ? "<a href=\"" . $this->updateQueryStr($pgQueryName, $pg - 1) . "\">&lt;</a> " : "&lt; ") . "\n";

    for ($i = 1; $i <= $totalPgs; $i++) {
      $echo .= ($pg == $i ? $i . " " : "<a href=\"" . $this->updateQueryStr($pgQueryName, $i) . "\">$i</a>") . "\n";
    }

    $echo .= ($pg < $totalPgs ? "<a href=\"" . $this->updateQueryStr($pgQueryName, $pg + 1) . "\">&gt;</a> " : "&gt;") . "\n</p>\n";

    echo $echo;
  }

  private function updateQueryStr($pgQueryName, $pg) {
    if (!strstr($_SERVER['QUERY_STRING'], $pgQueryName . "=")) {
      if (strpos($_SERVER['REQUEST_URI'], "?")) {
        $div = "&";
      } else {
        $div = "?";
      }

      return $_SERVER['REQUEST_URI'] . $div . "$pgQueryName=$pg";
    } else {
      return preg_replace("/" . preg_quote($pgQueryName) . "=\d*/", $pgQueryName . "=" . $pg, $_SERVER['REQUEST_URI']);
    }
  }


}

//---------------------

/**
 * Custom exception.
 */
class GxConnException extends Exception {
  /**
   * Constructor.
   * @param string $message The error message.
   * @param integer $code   The error code.
   */
  public function __construct($message, $code) {
    parent::__construct($message, $code);
  }

  /**
   * @return string Return a full stringified error message.
   */
  public function __toString() {
    return __CLASS__ . ": [error #{$this->code}] {$this->message}";
  }

  /**
   * @return string The error message.
   */
  public function message() {
    return $this->message;
  }

  /**
   * @return integer The error code.
   */
  public function code() {
    return $this->code;
  }

}

//---------------------

/**
  * DSN helper. This class is filled with static methods that return DSN strings. Use it to simplify the database connection process.
  */
class GXConnDSNHelper {
  /**
   * DSN for MySQL.
   * @param  string [$db = ""]
   * @param  string [$host = "localhost"]
   * @param  string [$port = ""]
   * @return string
   */
  public static function dsn_mysql($db = "", $host = "localhost", $port = "") {
    return "mysql:host=$host;port=$port;dbname=$db";
  }

  /**
   * DSN for MySQL Socket.
   * @param  string [$db = ""]
   * @param  string $socket
   * @return string
   */
  public static function dsn_mysqlSocket($db = "", $socket) {
    return "mysql:unix_socket=$socket;dbname=$db";
  }

  /**
   * DSN for sqlite.
   * @param  string [$db = ""]
   * @return string
   */
  public static function dsn_sqlite($db = "") {
    return "sqlite:$db";
  }

  /**
   * DSN for sqlite Memory.
   * @return string
   */
  public static function dsn_sqliteMemory() {
    return "sqlite::memory:";
  }

  /**
   * DSN for sqlite2.
   * @param  string [$db = ""]
   * @return string
   */
  public static function dsn_sqlite2($db = "") {
    return "sqlite2:$db";
  }

  /**
   * DSN for sqlite2 Memory.
   * @return string
   */
  public static function dsn_sqlite2Memory() {
    return "sqlite2::memory:";
  }

  /**
   * DSN for MS SQL.
   * @param  string [$db = ""]
   * @param  string [$host = "localhost"]
   * @return string
   */
  public static function dsn_mssql($db = "", $host = "localhost") {
    return "mssql:host=$host;dbname=$db";
  }

  /**
   * DSN for Sybase.
   * @param  string [$db = ""]
   * @param  string [$host = "localhost"]
   * @return string
   */
  public static function dsn_sybase($db = "", $host = "localhost") {
    return "sybase:host=$host;dbname=$db";
  }

  /**
   * DSN for DBLib.
   * @param  string [$db = ""]
   * @param  string [$host = "localhost"]
   * @return string
   */
  public static function dsn_dblib($db = "", $host = "localhost") {
    return "dblib:host=$host;dbname=$db";
  }

  /**
   * DSN for PgSQL.
   * @param  string [$db = ""]
   * @param  string [$host = "localhost"]
   * @param  string [$port = ""]
   * @return string
   */
  public static function dsn_pgsql($db = "", $host = "localhost", $port = "") {
    return "pgsql:host=$host;port=$port;dbname=$db";
  }

  /**
   * DSN for Firebird.
   * @param  string [$db = ""]
   * @param  string [$host = "localhost"]
   * @return string
   */
  public static function dsn_firebird($db = "", $host = "localhost") {
    return "firebird:host=$host;dbname=$db";
  }

  /**
   * DSN for OCI.
   * @param  string [$db = ""]
   * @return string
   */
  public static function dsn_oci($db = "") {
    return "oci:dbname=$db";
  }

  /**
   * DSN for Informix INI.
   * @param  string $ini
   * @return string
   */
  public static function dsn_informix_ini($ini) {
    return "informix:DSN=$ini";
  }

  /**
   * DSN for Informix.
   * @param  string [$db = ""]
   * @param  string $host
   * @param  string $service
   * @param  string $server
   * @param  string $protocol
   * @param  string $enable_scrollable_cursors
   * @return string
   */
  public static function dsn_informix($db = "", $host, $service, $server, $protocol, $enable_scrollable_cursors) {
    return "informix:host=$host;service=$service;database=$db;server=$server;protocol=$protocol;EnableScrollableCursors=$enable_scrollable_cursors";
  }

  /**
   * DSN for IBM INI.
   * @param  string $ini
   * @return string
   */
  public static function dsn_ibm_ini($ini) {
    return "ibm:DSN=$ini";
  }

  /**
   * DSN for IBM.
   * @param  string [$db = ""]
   * @return string
   */
  public static function dsn_ibm($db = "") {
    return "ibm:DRIVER=$driver;DATABASE=$db;HOSTNAME=$host;PORT=$port;PROTOCOL=$protocol";
  }

  /**
   * DSN for ODBC.
   * @param  string [$db = ""]
   * @param  string $driver
   * @param  string $protocol
   * @param  string $host
   * @param  string $port
   * @return string
   */
  public static function dsn_odbc($db = "", $driver, $protocol, $host, $port) {
    return "odbc:$db";
  }

  /**
   * DSN for ODBC DB2.
   * @param  string [$db = ""]
   * @param  string $driver
   * @param  string $protocol
   * @param  string $uid
   * @param  string $pw
   * @param  string $host
   * @param  string $port
   * @return string
   */
  public static function dsn_odbc_db2($db = "", $driver, $protocol, $uid, $pw, $host, $port) {
    return "odbc:DRIVER=$driver;HOSTNAME=$host;PORT=$port;DATABASE=$db;PROTOCOL=$protocol;UID=$uid;PWD=$pw";
  }

  /**
   * DSN for ODBC Access.
   * @param  string [$db = ""]
   * @param  string $driver
   * @param  string $uid
   * @return string
   */
  public static function dsn_odbc_access($db = "", $driver, $uid) {
    return "odbc:Driver=$driver;Dbq=$db;Uid=$uid";
  }

}

?>
