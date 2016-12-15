<?php
/**
 * @author Leandro Silva | Grafluxe, 2012-16
 * @license MIT
 */

/**
  * DSN helper. This class is filled with static methods that return DSN strings. Use it to simplify the database connection process.
  */
class GxConnDSNHelper {
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
