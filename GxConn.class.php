<?php
/*	Copyright (c) 2012 Leandro Silva | grafluxe.com/license */

/*
TODO

- add export to JSON option?
- update create_html_table()
	- allow custom queries
	- add pagination
	
*/

class GxConn {
	/** [author     ] Leandro Silva | Grafluxe.com */
	/** [description] Execute commands on a database using PHP Data Objects. Many security features added. Supports multiple database drivers. For security, semi-colons are not allowed in
					  in your statements and are dynamically added pre-execution. Note that methods prepended with "run_" execute sepecifc statements; use the query method for custom queries. */
	/** [example	] 
		$gxConn = new GXConn("mysql:host=localhost;dbname=tester;charset=utf8");
			
		$gxConn->col_whitelist = array('first', 'last');
		$gxConn->tbl_whitelist = array('test_table');
		
		$col1 = $_GET['first'];
		$col2 = $_GET['last'];
		
		$r = $gxConn->query("
			SELECT {$gxConn->col_check($col1)}
			FROM {$gxConn->tbl_check(test_table)}
			WHERE {$gxConn->col_check($col2)} = :ln
		", array(
			$gxConn->bind_value(':ln', 'Doe')
		), PDO::FETCH_BOTH
		);
	
		print_r($r);	
		
		$r2 = $gxConn->query("
			SELECT first
			FROM test_table
		");
		
		print_r($r2);
	*/
					  
	public $conn;										/** @conn Returns the PDO object. */
	public $col_whitelist;								/** @col_whitelist A whitelist of columns that can be queried. Expects a array. */
	public $tbl_whitelist;								/** @tbl_whitelist A whitelist of tables that can be queried. Expects a array. */
	public $get_last_stmt;								/** @get_last_stmt Returns the statement you last queried. */
	public $blacklist = array("DROP", "DELETE", "--");	/** @blacklist An array of forbidden clause words. Case does not matter. Defaults to array("DROP", "DELETE", "--"); */
	
	private $c;
	
	/** @__construct Constructor. You can use the GxConnHelper class to help setup the dsn param. By default, ATTR_EMULATE_PREPARES is set to false and ATTR_ERRMODE is set to ERRMODE_EXCEPTION. */
	public function __construct($dsn, $usr = "root", $pw = "root", array $opts = NULL) {
		set_exception_handler(array($this, "error_handler"));
		
		try {
			$this->c = new PDO($dsn, $usr, $pw, $opts);
		}catch(PDOException $e) {
			throw new GxConnException("There was problem connecting to your database.", 1);
		}
		
		$this->c->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		$this->c->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);	
		
		$this->conn = $this->c;
	}
	
	public static function error_handler($e) {
		echo "[Uncaught GxConn Error]: " . $e->getMessage();
	}
	
	private function blacklist_check($s) {
		if(isset($this->blacklist) && count($this->blacklist) > 0) {
			for($i = 0; $i < count($this->blacklist); $i++) {
				if($i > 0) { $regex .= "|"; }			
				$regex .= $this->blacklist[$i];
			}
						
			if(preg_match("/$regex/i", $s)) {
				throw new GXConnException('Your query statement contains a blacklisted clause.', 3);
			}
		}
	}
	
	private function semicolon_check($s) {	
		if(preg_match("/;/i", $s)) {
			throw new GXConnException('Your query statement cannot contain a semi-colon.', 4);
		}
	}
	
	/** @col_check Checks if a column in allowed to be used (via the column whitelist). */
	public function col_check($col) {
		if(isset($this->col_whitelist)) {
			if(! (array_search($col, $this->col_whitelist, true) !== false)) {
				throw new GXConnException('You do not have permission to query one of the columns in your statement.', 1);
			}
		}
		
		return $col;
	}
	
	/** @tbl_check Checks if a table in allowed to be used (via the table whitelist). */
	public function tbl_check($tbl) {
		if(isset($this->tbl_whitelist)) {
			if(! (array_search($tbl, $this->tbl_whitelist, true) !== false)) {
				throw new GXConnException('You do not have permission to query one of the tables in your statement.', 2);
			}
		}
		
		return $tbl;
	}
	
	/** @bind_value Use as the bind argument in the query method. Works like PDO's bindValue method.  */
	public function bind_value($parameter, $value, $data_type) {
		return array($parameter, $value, $data_type);
	}
	
	/** @query The main method used to run queries. The stmt param expects a string with your query statement (use col_check and tbl_check with the whitelists for
			   add security against SQL injecion). The bind param expects an array filled with the bind_value methods. The fetch_how param expects directions on how
			   to return the results and is defaulted to PDO::FETCH_ASSOC. */
	public function query($stmt, array $bind = NULL, $fetch_how = PDO::FETCH_ASSOC) {	
		$this->semicolon_check($stmt);
		$this->blacklist_check($stmt);	
				
		$q = $this->c->prepare($stmt . ';');
		$this->get_last_stmt = $q->queryString;
		
		if(isset($bind)) {
			for($i = 0; $i < count($bind); $i++) {
				if($bind[$i][2]) {
					$q->bindValue($bind[$i][0], $bind[$i][1], $bind[$i][2]);
				}else {
					$q->bindValue($bind[$i][0], $bind[$i][1]);
				}
			}
		}
		
		$q->closeCursor();
		$q->execute();
		
		try {
			return $q->fetchAll($fetch_how);
		}catch(PDOException $e) { 
			return NULL;
		}
	}
		 
	 /** @run_tbl_exists Returns a boolean determining whether a table exists. */
	public function run_tbl_exists($tbl) {
		$q = $this->c->prepare("SELECT 1 FROM {$this->tbl_check($tbl)};");
		$this->get_last_stmt = $q->queryString;
		
		try {
			$q->closeCursor();
			$q->execute();
			
			return true;
		}catch(PDOException $e) {
			return false;	
		}
	}
	
	/** @run_col_count Returns the total column count. */
	public function run_col_count($tbl) {
		$q = $this->c->prepare("SELECT * FROM {$this->tbl_check($tbl)};");
		$this->get_last_stmt = $q->queryString;

		$q->closeCursor();
		$q->execute();
		
		return $q->columnCount();
	}
	
	/** @run_col_info Returns an array of associative arrays with column info. */
	public function run_col_info($tbl) {		
		$q = $this->c->prepare("SHOW COLUMNS FROM {$this->tbl_check($tbl)};");
		$this->get_last_stmt = $q->queryString;
		
		$q->closeCursor();
		$q->execute();
		
		return $q->fetchAll(PDO::FETCH_ASSOC);
	}
	
	/** @run_col_data Returns all of a column's data. */
	public function run_col_data($col, $tbl) {	
		$q = $this->c->prepare("SELECT {$this->col_check($col)} FROM {$this->tbl_check($tbl)};");
		$this->get_last_stmt = $q->queryString;

		$q->closeCursor();
		$q->execute();
			
		$fin = array();
		foreach ($q as $r) { array_push($fin, $r[0]); }
		
		return $fin;
	}
	
	/** @run_col_exists Returns a boolean determining whether a column exists. */
	public function run_col_exists($col, $tbl) {
		$q = $this->c->prepare("SELECT {$this->col_check($col)} FROM {$this->tbl_check($tbl)};");
		$this->get_last_stmt = $q->queryString;
		
		try {
			$q->closeCursor();
			$q->execute();
			
			return true;
		}catch(PDOException $e) {
			return false;	
		}
	}
	
	/** @run_row_total Returns the total row count. */
	public function run_row_total($tbl) {		
		$q = $this->c->prepare("SELECT * FROM {$this->tbl_check($tbl)};");
		$this->get_last_stmt = $q->queryString;
		
		$q->closeCursor();
		$q->execute();
		
		return $q->rowCount();
	}
	
	/** @run_row_data Returns an array of the data in the specified row. Returns null if the specified row is greater than the total number of rows. */
	public function run_row_data($row, $tbl) {
		$row = (int)$row;
				
		$q = $this->c->prepare("SELECT * FROM {$this->tbl_check($tbl)};");
		$this->get_last_stmt = $q->queryString;
		
		$q->closeCursor();
		$q->execute();
		
		if($row > $this->run_row_total($tbl)) {
			return NULL;	
		}else {
			$arr = $q->fetchAll(PDO::FETCH_NUM);
			
			return $arr[$row];
		}
	}
	
	/** @run_tbl_to_html Echos a table with your database data. */
	public function run_tbl_to_html($tbl, $start_at_col = 0) {		
		$bg_color = '#CCC';
		$col_color_odd = '#F9F9F9';
		$col_color_even = '#F0F0F0';
		$header_color = '#9C9C9C';
		$alt_row = true;
		$len = $this->run_col_count($tbl);
		$arr = $this->run_col_info($tbl);
		$col_num = 1;
		$row_num = 1;
	
		for($i = 0; $i < count($arr); $i++) {
			$col_names[ ] = $arr[$i]['Field'];
		}
		
		$q = $this->query("SELECT * FROM {$this->tbl_check($tbl)}", NULL, PDO::FETCH_NUM);
		
		if($start_at_col > $len) { $start_at_col = ($len - 1); }
		if($start_at_col < 0) { $start_at_col = 0; }
		
		echo "<table id='GxConnTable' style='width:100%; background-color:$bg_color; text-align:center'>";
		
		//heads
		for($i = $start_at_col; $i < $len; $i++) {
			$col_num = $i + 1;
			
			echo "<td class='cols col$col_num colHeaders colHeader$col_num' style='padding:3px 12px; background-color:$header_color;'>$col_names[$i]</td>";
		}

		//data
		foreach($q as $row) {
			for($i = $start_at_col; $i < $len; $i++) {
				if($i == $start_at_col) {					
					if($alt_row) {
						$alt_row = false;
						$colColor = $col_color_odd;
						$alt_row_class = 'oddRow';
					}else {
						$alt_row = true;
						$colColor = $col_color_even;
						$alt_row_class = 'evenRow';
					}
				}
				
				if(($i % $len) == $start_at_col) { echo '<tr>'; }
				
				$col_num = $i + 1;
				
				echo "<td class='cols col$col_num $alt_row_class row$row_num' style='padding:3px 12px; background-color:$colColor;'>$row[$i]</td>";
				
				if($i == ($len - 1)) { 
					echo '</tr>';  
					$row_num++;
				}				
			}
		}
		
		echo '</table>';
	}
	
	
}

//-----------------------------------------------------------------------

class GxConnException extends Exception {
	/** [author] Leandro Silva | Grafluxe.com */

	/** @__construct Constructor. Custom exception.*/
    public function __construct($message, $code) {
		parent::__construct($message, $code);
    }

    public function __toString() {
		return __CLASS__ . ": [error #{$this->code}] {$this->message}";
    }
	
}

//-----------------------------------------------------------------------

class GXConnDSNHelper {
	/** [author     ] Leandro Silva | Grafluxe.com */
	/** [description] This class is filled with static methods that return DSN strings to use when connecting to a database. */
	
	public static function dsn_mysql($db, $host = 'localhost', $port = '') { return "mysql:host=$host;port=$port;dbname=$db"; }
	public static function dsn_mysqlSocket($db, $socket) { return "mysql:unix_socket=$socket;dbname=$db"; }
	public static function dsn_sqlite($db) { return "sqlite:$db"; }
	public static function dsn_sqliteMemory() { return "sqlite::memory:"; }
	public static function dsn_sqlite2($db) { return "sqlite2:$db"; }
	public static function dsn_sqlite2Memory() { return "sqlite2::memory:"; }
	public static function dsn_mssql($db, $host = 'localhost') { return "mssql:host=$host;dbname=$db"; }
	public static function dsn_sybase($db, $host = 'localhost') { return "sybase:host=$host;dbname=$db"; }
	public static function dsn_dblib($db, $host = 'localhost') { return "dblib:host=$host;dbname=$db"; }
	public static function dsn_pgsql($db, $host = 'localhost', $port = '') { return "pgsql:host=$host;port=$port;dbname=$db"; }
	public static function dsn_firebird($db, $host = 'localhost') { return "firebird:host=$host;dbname=$db"; }
	public static function dsn_oci($db) { return "oci:dbname=$db"; }
	public static function dsn_informix_ini($ini) { return "informix:DSN=$ini"; }
	public static function dsn_informix($db, $host, $service, $server, $protocol, $enable_scrollable_cursors) { return "informix:host=$host;service=$service;database=$db;server=$server;protocol=$protocol;EnableScrollableCursors=$enable_scrollable_cursors"; }
	public static function dsn_ibm_ini($ini) { return "ibm:DSN=$ini"; }
	public static function dsn_ibm($db) { return "ibm:DRIVER=$driver;DATABASE=$db;HOSTNAME=$host;PORT=$port;PROTOCOL=$protocol"; }
	public static function dsn_odbc($db, $driver, $protocol, $host, $port) { return "odbc:$db";	}
	public static function dsn_odbc_db2($db, $driver, $protocol, $uid, $pw, $host, $port) { return "odbc:DRIVER=$driver;HOSTNAME=$host;PORT=$port;DATABASE=$db;PROTOCOL=$protocol;UID=$uid;PWD=$pw"; }
	public static function dsn_odbc_access($db, $driver, $uid) { return "odbc:Driver=$driver;Dbq=$db;Uid=$uid"; }
	
}

?>