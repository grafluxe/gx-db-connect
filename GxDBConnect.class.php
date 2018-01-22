<?php
/**
 * @author Leandro Silva
 * @copyright 2012, 2016 Leandro Silva (http://grafluxe.com)
 * @license MIT
 *
 * @classdesc Securely execute commands on a database using PHP Data Objects — many security
 * features added. Note that methods prepended with 'run_' execute specific statements; use
 * the 'query' method for custom queries.
 *
 * @example
 * This sample includes tight security.
 *
 * Use value binding, a whitelist, and checker methods if you plan to construct your SQL statements
 * with values coming from a form (or other user inputted method). Using these featured will help
 * to prevent SQL attacks.
 *
 * <pre>
 *   include "./GxDBConnect.class.php";
 *   include "./DSNHelper.class.php";
 *
 *   try {
 *     $conn = new GxDBConnect(DSNHelper::mysql("my_database"), "my_username", "my_pass");
 *
 *     $conn->col_whitelist = array("first", "last");
 *     $conn->tbl_whitelist = array("names_table");
 *
 *     $f_name = $_GET["first_name"];
 *     $l_name = $_GET["last_name"];
 *     $table = $_GET["table_name"];
 *
 *     $data = $conn->query("
 *       SELECT {$conn->col_check($f_name)}
 *       FROM {$conn->tbl_check($table)}
 *       WHERE {$conn->col_check($l_name)} = :ln
 *       ",
 *       array(
 *         $conn->bind_value(":ln", "Doe")
 *       ),
 *       PDO::FETCH_NUM
 *     );
 *
 *     print_r($data);
 *   } catch(GxDBException $e) {
 *     exit($e);
 *   }
 * </pre>
 *
 * This sample includes a more simple use case.
 *
 * <pre>
 *   include "./GxDBConnect.class.php";
 *
 *   try {
 *     $conn = new GxDBConnect("mysql:host=localhost;dbname=my_database", "my_username", "my_pass");
 *
 *     $data = $conn->query("
 *       SELECT first
 *       FROM names_table
 *       WHERE last = 'Doe'
 *     ");
 *
 *     print_r($data);
 *   } catch(GxDBException $e) {
 *     exit($e);
 *   }
 * </pre>
 *
 */

class GxDBConnect {
  /** @var object The PDO connection object. */
  public $conn;

  /** @var array A whitelist of columns that can be queried. Use in concert with the 'col_check' method. */
  public $col_whitelist;

  /** @var array A whitelist of tables that can be queried. Use in concert with the 'tbl_check' method. */
  public $tbl_whitelist;

  /** @var string The statement you last queried. */
  public $get_last_stmt;

  /** @var string The release version. */
  public static $version = "3.2.0";

  /** @var string Set to true to output uncaught errors. Defaults to false for better security. */
  public static $echo_uncaught_errors = false;

  private $blacklist = array("DROP", "DELETE", "--", "#", "/*", "xp_", ";");

 /**
   * Constructor. By default, the PDO attribute ATTR_EMULATE_PREPARES is set to false and ATTR_ERRMODE is set to ERRMODE_EXCEPTION.
   * @param string $dsn        The DSN string. You can use the GxDBConnectHelper class to help setup this param.
   * @param string $usr="root" The username.
   * @param string $pw="root"  The password.
   * @param array  $opts=null  Connection options.
   * @throws GxDBException
   * @return object The PDO object.
   */
  public function __construct($dsn, $usr = "root", $pw = "root", array $opts = null) {
    set_exception_handler(array($this, "on_uncaught_exception"));
    set_error_handler(array($this, "on_uncaught_error"));

    try {
      $this->conn = new PDO($dsn, $usr, $pw, $opts);
    } catch (PDOException $e) {
      throw new GxDBException($e->getMessage(), 1);
    }

    $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  }

  /**
    * @ignore
    */
  public static function on_uncaught_exception($e) {
    if (self::$echo_uncaught_errors) {
      exit("[Uncaught GxDBConnect Error]: " . $e->getMessage());
    } else {
      exit("[Uncaught GxDBConnect Error]: To see uncaught errors, set 'GxDBConnect::\$echo_uncaught_errors' to true.");
    }
  }

  /**
    * @ignore
    */
  public static function on_uncaught_error($err_num, $err_msg) {
    if (self::$echo_uncaught_errors) {
      exit("[Uncaught GxDBConnect Error]: " . $err_msg);
    } else {
      exit("[Uncaught GxDBConnect Error]: To see uncaught errors, set 'GxDBConnect::\$echo_uncaught_errors' to true.");
    }
  }

  private function blacklist_check($s) {
    $regex = "";

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
        throw new GxDBException("Your query statement contains a blacklisted value. See blacklist_list, index " . ($i - 1) . ".", 2);
      }
    }
  }

  /**
   * Adds a value to your blacklist filter. Before any query is run, your statement will be checked for
   * any blacklisted strings. If a blacklisted string is found, the query will not be executed and a
   * GxDBException exception will be thrown. By default, the blacklist filter contains the following:
   * ["DROP", "DELETE", "--", "#", "/*", "xp_", ";"]
   * @param string $str A string to blacklist. Letter case does not matter.
   */
  public function blacklist_add($str) {
    $str = (string) $str;
    $at = array_search($str, $this->blacklist);

    if(!$at){
      array_push($this->blacklist, $str);
    }
  }

  /**
   * Removes a value from your blacklist filter.
   * @param string $str The word to remove from the your blacklist. Letter case does not matter.
   */
  public function blacklist_remove($str) {
    $at = array_search(strtolower((string) $str), array_map("strtolower", $this->blacklist));

    if($at !== false){
      array_splice($this->blacklist, $at, 1);
    }
  }

  /**
   * Returns your blacklist filters.
   * @return array The current blacklisted strings.
   */
  public function blacklist_list() {
    return $this->blacklist;
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
   * @throws GxDBException
   * @return string The column name.
   */
  public function col_check($col) {
    if (isset($this->col_whitelist)) {
      if (!(array_search($col, $this->col_whitelist, true) !== false)) {
        throw new GxDBException("You do not have permission to query one of the columns in your statement.", 3);
      }
    }

    return $col;
  }

  /**
   * Checks if a table in allowed to be used (via the table whitelist).
   * @param  string $tbl The table name.
   * @throws GxDBException
   * @return string The table name.
   */
  public function tbl_check($tbl) {
    if (isset($this->tbl_whitelist)) {
      if (!(array_search($tbl, $this->tbl_whitelist, true) !== false)) {
        throw new GxDBException("You do not have permission to query one of the tables in your statement.", 4);
      }
    }

    return $tbl;
  }

  /**
   * To be used as the bind argument in the 'query' method. Works like PDO's 'bindValue' method.
   * @param  mixed $parameter        The parameter identifier.
   * @param  mixed $value            The value to bind.
   * @param  integer $data_type=null The data type.
   * @return array The bind data.
   */
  public function bind_value($parameter, $value, $data_type = null) {
    return array(
      $parameter,
      $value,
      $data_type
    );
  }

  /**
   * Runs an SQL query. This is the primary method used to run queries.
   * @param  string $stmt                          Your query statement (use 'col_check' and 'tbl_check' with the whitelists for added security against SQL injecion)
   * @param  array     $bind=null                  An array filled with the 'bind_value' methods.
   * @param  integer   $fetch_how=PDO::FETCH_ASSOC How to return the results.
   * @throws GxDBException
   * @return array Query results.
   */
  public function query($stmt, array $bind = null, $fetch_how = PDO::FETCH_ASSOC) {
    $this->blacklist_check($stmt);

    try {
      $q = $this->conn->prepare($stmt . ";");
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

      return $q->fetchAll($fetch_how);
    } catch (PDOException $e) {
      throw new GxDBException($e->getMessage(), 5);
    }
  }

  /**
   * Closes the database connection.
   */
  public function close() {
    $this->conn = null;
  }

  /**
   * Returns a boolean determining whether a table exists.
   * @param  string $tbl The table to query.
   * @return boolean  Whether a table exists.
   */
  public function run_tbl_exists($tbl) {
    try {
      $q = $this->conn->prepare("SELECT 1 FROM {$this->tbl_check($tbl)} LIMIT 1;");
      $this->get_last_stmt = $q->queryString;
      $q->closeCursor();
      $q->execute();

      return true;
    } catch (PDOException $e) {
      return false;
    }
  }

  /**
   * Returns the total column count.
   * @param  string $tbl The table to query.
   * @return integer The number of columns.
   */
  public function run_col_count($tbl) {
    $q = $this->conn->prepare("SELECT * FROM {$this->tbl_check($tbl)} LIMIT 1;");
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
    $q = $this->conn->prepare("SHOW COLUMNS FROM {$this->tbl_check($tbl)};");
    $this->get_last_stmt = $q->queryString;

    $q->closeCursor();
    $q->execute();

    return $q->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Returns all of a columns data.
   * @param  string $col The column name.
   * @param  string $tbl The table to query.
   * @return array Column data.
   */
  public function run_col_data($col, $tbl) {
    $q = $this->conn->prepare("SELECT {$this->col_check($col)} FROM {$this->tbl_check($tbl)};");
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
      $q = $this->conn->prepare("SELECT {$this->col_check($col)} FROM {$this->tbl_check($tbl)} LIMIT 1;");
      $this->get_last_stmt = $q->queryString;
      $q->closeCursor();
      $q->execute();

      return true;
    } catch (PDOException $e) {
      return false;
    }
  }

  /**
   * Returns the total row count.
   * @param  string $tbl The table to query.
   * @return integer The row count.
   */
  public function run_row_total($tbl) {
    $q = $this->conn->prepare("SELECT COUNT(*) FROM {$this->tbl_check($tbl)};");
    $this->get_last_stmt = $q->queryString;

    $q->closeCursor();
    $q->execute();

    return $q->fetchColumn();
  }

  /**
   * Returns data in the specified row.
   * @param  integer $row The row number.
   * @param  string $tbl  The table to query.
   * @return array|null The row data. Returns null if the specified row is greater than the total number of rows.
   */
  public function run_row_data($row, $tbl) {
    $row = (int) $row;
    $q = $this->conn->prepare("SELECT * FROM {$this->tbl_check($tbl)};");
    $this->get_last_stmt = $q->queryString;

    $q->closeCursor();
    $q->execute();

    if ($row > $this->run_row_total($tbl)) {
      return null;
    } else {
      $arr = $q->fetchAll(PDO::FETCH_NUM);

      return $arr[$row];
    }
  }

  /**
   * Exports your table as a JSON formatted file.
   * @param  string   $tbl                The table to export.
   * @param  boolean $pretty_print=false  Whether to pretty-print output (only valid on PHP versions >=5.4.0).
   * @param  string $relative_dir=""      A save path relative to this file.
   * @return boolean Whether the output succeeded.
   */
  public function run_export($tbl, $pretty_print = false, $relative_dir = "") {
    $file_name = date("m-d-y") . "-" . $tbl . ".json";

    $obj["GxDBConnect"] = self::$version;
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
    $q = $this->conn->prepare("SELECT * FROM $tbl;");
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
   * @param string $stmt                     Your SQL query statement.
   * @param integer $paginate_at=0           Paginate after N rows of data. Works with the $pg_query_name param.
   * @param string $pg_query_name="pg"       The paginate HTML query string name.
   * @param boolean $use_default_styles=true Assigns default inline styles.
   * @throws GxDBException
   */
  public function run_tbl_to_html($stmt, $paginate_at = 0, $pg_query_name = "pg", $use_default_styles = true) {
    $this->blacklist_check($stmt);

    $bg_color = "#CCC";
    $col_color_odd = "#F9F9F9";
    $col_color_even = "#F0F0F0";
    $header_color = "#9C9C9C";
    $alt_row = true;
    $row_num = 1;

    //col names
    $qNames = $this->conn->prepare($stmt . ";");
    $qNames->closeCursor();
    $qNames->execute();

    $names = $qNames->fetchAll(PDO::FETCH_ASSOC);
    $col_names = array_keys($names[0]);

    //main query
    if ($paginate_at) {
      if (stristr($stmt, "LIMIT") || stristr($stmt, "OFFSET")) {
        throw new GxDBException("Your 'run_tbl_to_html' statement cannot have a LIMIT or OFFSET clause.", 6);
      }

      $totalPgs = ceil($qNames->rowCount() / $paginate_at);
      $pgQuery  = isset($_GET[$pg_query_name]) ? $_GET[$pg_query_name] : 1;

      if ($pgQuery > $totalPgs) {
        $pgQuery = $totalPgs;
      }

      $stmt .= " LIMIT $paginate_at OFFSET " . $paginate_at * ($pgQuery - 1);
    }

    $q = $this->conn->prepare($stmt . ";");
    $q->closeCursor();
    $q->execute();

    $len = $q->columnCount();

    if ($use_default_styles) {
      $styles  = " style=\"width:100%; background-color:$bg_color; text-align:center\"";
      $styles2 = " style=\"padding:3px 12px; background-color:$header_color\"";
    }

    $echo = "<table class=\"GxDBConnectTable\"$styles>\n<tr class=\"headerRow\"$styles2>\n";

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

        if ($use_default_styles) {
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

    if ($paginate_at) {
      if ($totalPgs > 1) {
        $this->paginate($pgQuery, $pg_query_name, $totalPgs);
      }
    }
  }

  private function paginate($pg, $pg_query_name, $totalPgs) {
    $echo = "\n<p class=\"GxDBConnectPagination\">\n" . ($pg > 1 ? "<a href=\"" . $this->updateQueryStr($pg_query_name, $pg - 1) . "\">&lt;</a> " : "&lt; ") . "\n";

    for ($i = 1; $i <= $totalPgs; $i++) {
      $echo .= ($pg == $i ? $i . " " : "<a href=\"" . $this->updateQueryStr($pg_query_name, $i) . "\">$i</a>") . "\n";
    }

    $echo .= ($pg < $totalPgs ? "<a href=\"" . $this->updateQueryStr($pg_query_name, $pg + 1) . "\">&gt;</a> " : "&gt;") . "\n</p>\n";

    echo $echo;
  }

  private function updateQueryStr($pg_query_name, $pg) {
    if (!strstr($_SERVER['QUERY_STRING'], $pg_query_name . "=")) {
      if (strpos($_SERVER['REQUEST_URI'], "?")) {
        $div = "&";
      } else {
        $div = "?";
      }

      return $_SERVER['REQUEST_URI'] . $div . "$pg_query_name=$pg";
    } else {
      return preg_replace("/" . preg_quote($pg_query_name) . "=\d*/", $pg_query_name . "=" . $pg, $_SERVER['REQUEST_URI']);
    }
  }

}



/**
 * Extends the PHP Exception class to add a custom message format.
 */
class GxDBException extends Exception {
  /**
   * Constructor.
   * @param string $message The error message.
   * @param integer $code   The error code.
   */
  public function __construct($message, $code) {
    parent::__construct($message, $code);
  }

  /**
   * Override to exclude the output of potentially sensitive data.
   * @return string
   */
  public function __toString() {
    return __CLASS__ . ": [{$this->code}]: {$this->message}";
  }

}

?>
