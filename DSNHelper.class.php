<?php
/**
 * @author Leandro Silva | Grafluxe, 2012-16
 * @license MIT
 */

/**
  * Data Source Name (DSN) helper. This class is filled with static methods that
  * return DSN strings. Use it to simplify the database connection process. Note
  * that the 'db' method parameters can be set to an empty string if you plan to
  * assign the database later.
  */
class DSNHelper {
  /**
   * DSN for CUBRID.
   * @param  string $db
   * @param  string $host="localhost"
   * @param  string $port=""
   * @return string
   */
  public static function cubrid($db, $host = "localhost", $port = "") {
    $dsn = "cubrid:";

    if ($db) { $dsn .= "dbname=$db;"; }
    if ($host) { $dsn .= "host=$host;"; }
    if ($port) { $dsn .= "port=$port;"; }

    return $dsn;
  }

  /**
   * DSN for Microsoft SQL Server and Sybase.
   * @param  string $db
   * @param  string $host="localhost"
   * @param  string $charset=""
   * @param  string $appname=""
   * @return string
   */
  public static function sybase($db, $host = "localhost", $charset = "", $appname = "") {
    $dsn = "sybase:";

    if ($db) { $dsn .= "dbname=$db;"; }
    if ($host) { $dsn .= "host=$host;"; }
    if ($charset) { $dsn .= "charset=$charset;"; }
    if ($appname) { $dsn .= "appname=$appname;"; }

    return $dsn;
  }

  /**
   * DSN for Firebird.
   * @param  string $db
   * @param  string $charset=""
   * @param  string $role=""
   * @return string
   */
  public static function firebird($db, $charset = "", $role = "") {
    $dsn = "firebird:";

    if ($db) { $dsn .= "dbname=$db;"; }
    if ($charset) { $dsn .= "charset=$charset;"; }
    if ($role) { $dsn .= "role=$role;"; }

    return $dsn;
  }

  /**
   * DSN for IBM via config file.
   * @param  string $ini
   * @return string
   */
  public static function ibm_ini($ini) {
    return "ibm:dsn=$ini";
  }

  /**
   * DSN for IBM.
   * @param  String $db
   * @param  String $host="localhost"
   * @param  String $port=""
   * @param  String $usr=""
   * @param  String $pwd=""
   * @return string
   */
  public static function ibm($db, $host = "localhost", $port = "", $usr = "", $pwd = "") {
    $dsn = "ibm:driver={ibm db2 odbc driver};protocol=tcpip;";

    if ($db) { $dsn .= "database=$db;"; }
    if ($host) { $dsn .= "hostname=$host;"; }
    if ($port) { $dsn .= "port=$port;"; }
    if ($usr) { $dsn .= "uid=$usr;"; }
    if ($pwd) { $dsn .= "pwd=$pwd;"; }

    return $dsn;
  }

  /**
   * DSN for Informix via config file.
   * @param  string $ini
   * @return string
   */
  public static function informix_ini($ini) {
    return "informix:dsn=$ini";
  }

  /**
   * DSN for Informix.
   * @param  string $db
   * @param  string $server
   * @param  string $app
   * @param  boolean $connection_pooling
   * @param  boolean $encrypt
   * @param  string $failover_partner
   * @param  integer $login_timeout
   * @param  string $multiple_active_result_sets
   * @param  boolean $quoted_id
   * @param  string $trace_file
   * @param  boolean $trace_on
   * @param  string $transaction_isolation
   * @param  boolean $trust_server_certificate
   * @param  string $wsid
   * @return string
   */
  public static function informix($db, $server, $app, $connection_pooling, $encrypt, $failover_partner, $login_timeout, $multiple_active_result_sets, $quoted_id, $trace_file, $trace_on, $transaction_isolation, $trust_server_certificate, $wsid) {
    $dsn = "sqlsrv:";

    if ($db) { $dsn .= "database=$db;"; }
    if ($server) { $dsn .= "server=$server;"; }
    if ($app) { $dsn .= "app=$app;"; }
    if ($connection_pooling) { $dsn .= "connectionpooling=$connection_pooling;"; }
    if ($encrypt) { $dsn .= "encrypt=$encrypt;"; }
    if ($failover_partner) { $dsn .= "failover_partner=$failover_partner;"; }
    if ($login_timeout) { $dsn .= "logintimeout=$login_timeout;"; }
    if ($multiple_active_result_sets) { $dsn .= "multipleactiveresultsets=$multiple_active_result_sets;"; }
    if ($quoted_id) { $dsn .= "quotedid=$quoted_id;"; }
    if ($trace_file) { $dsn .= "tracefile=$trace_file;"; }
    if ($trace_on) { $dsn .= "traceon=$trace_on;"; }
    if ($transaction_isolation) { $dsn .= "transactionisolation=$transaction_isolation;"; }
    if ($trust_server_certificate) { $dsn .= "trustservercertificate=$trust_server_certificate;"; }
    if ($wsid) { $dsn .= "wsid=$wsid;"; }

    return $dsn;
  }

  /**
   * DSN for MySQL (versions 3-5).
   * @param  string $db
   * @param  string $host="localhost"
   * @param  string $port=""
   * @param  string $charset=""
   * @return string
   */
  public static function mysql($db, $host = "localhost", $port = "", $charset = "") {
    $dsn = "mysql:";

    if ($db) { $dsn .= "dbname=$db;"; }
    if ($host) { $dsn .= "host=$host;"; }
    if ($port) { $dsn .= "port=$port;"; }
    if ($charset) { $dsn .= "charset=$charset;"; }

    return $dsn;
  }

  /**
   * DSN for MySQL Unix Socket (versions 3-5).
   * @param  string $db
   * @param  string $unix_socket
   * @param  string $charset=""
   * @return string
   */
  public static function mysql_socket($db, $unix_socket, $charset = "") {
    $dsn = "mysql:";

    if ($db) { $dsn .= "dbname=$db;"; }
    if ($unix_socket) { $dsn .= "unix_socket=$unix_socket;"; }
    if ($charset) { $dsn .= "charset=$charset;"; }

    return $dsn;
  }

  /**
   * DSN for OCI.
   * @param  string $db
   * @return string
   */
  public static function oci($db) {
    return "oci:dbname=$db";
  }

  /**
   * DSN for ODBC and DB2.
   * @param  string $db
   * @return string
   */
  public static function odbc($db) {
    return "odbc:$db";
  }

  /**
   * DSN for ODBC and DB2 with full syntax.
   * @param  string $db
   * @param  string $host="localhost"
   * @param  string $port=""
   * @param  string $protocol=""
   * @param  string $usr=""
   * @param  string $pwd=""
   * @return string
   */
  public static function odbc_full($db, $host = "localhost", $port = "", $protocol = "", $usr = "", $pwd = "") {
    $dsn = "odbc:driver={ibm db2 odbc driver};";

    if ($db) { $dsn .= "database=$db;"; }
    if ($host) { $dsn .= "hostname=$host;"; }
    if ($port) { $dsn .= "port=$port;"; }
    if ($protocol) { $dsn .= "protocol=$protocol;"; }
    if ($usr) { $dsn .= "uid=$usr;"; }
    if ($pwd) { $dsn .= "pwd=$pwd;"; }

    return $dsn;
  }

  /**
   * DSN for ODBC MS Access.
   * @param  string $mdb
   * @param  string $usr
   * @return string
   */
  public static function odbc_msaccess($mdb, $usr) {
    return "odbc:Driver={Microsoft Access Driver (*.mdb)};dbq=$mdb;uid=$usr";
  }

  /**
   * DSN for PostgreSQL.
   * @param  string $db
   * @param  string $host="localhost"
   * @param  string $port=""
   * @param  string $usr=""
   * @param  string $pwd=""
   * @return string
   */
  public static function pgsql($db, $host = "localhost", $port = "", $usr = "", $pwd = "") {
    return "pgsql:";

    if ($db) { $dsn .= "dbname=$db;"; }
    if ($host) { $dsn .= "host=$host;"; }
    if ($port) { $dsn .= "port=$port;"; }
    if ($usr) { $dsn .= "user=$usr;"; }
    if ($pwd) { $dsn .= "password=$pwd;"; }

    return $dsn;
  }

  /**
   * DSN for sqlite.
   * @param  string $db
   * @return string
   */
  public static function sqlite($db) {
    return "sqlite:$db";
  }

  /**
   * DSN for sqlite in memory.
   * @return string
   */
  public static function sqlite_in_memory() {
    return "sqlite::memory:";
  }

  /**
   * DSN for sqlite2.
   * @param  string $db
   * @return string
   */
  public static function sqlite2($db) {
    return "sqlite2:$db";
  }

  /**
   * DSN for sqlite2 in memory.
   * @return string
   */
  public static function sqlite2_in_memory() {
    return "sqlite2::memory:";
  }

}

?>
