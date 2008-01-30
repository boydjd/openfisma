<?PHP
/**********************************************************************
* FILE    : lib/Database.class.php
* PURPOSE : provides a database agnostic wrapper API for PHP functions
* 
* NOTES: 
* 
* Aside from the database link itself, this class maintains two other
* variables to simplify use downstream.   
* 
**********************************************************************/


// 
// CLASS DEFINITION
// 

class Database {

  // -----------------------------------------------------------------------
  // 
  // VARIABLES
  // 
  // -----------------------------------------------------------------------
  
  // the actual database link
  private $db;
  
  // database connection values
  private $DB_TYPE;
  private $DB_HOST;
  private $DB_PORT;
  private $DB_USER;
  private $DB_NAME;
  private $DB_PASS;
  
  // internal tracking stuff
  private $last_cursor;
  private $last_query;
  private $last_result;
  private $last_statement;
  private $last_error;
  
  
  // -----------------------------------------------------------------------
  // 
  // CLASS METHODS
  // 
  // -----------------------------------------------------------------------
  
  public function __construct($DB_TYPE = NULL, 
							  $DB_HOST = NULL, 
							  $DB_PORT = NULL, 
							  $DB_NAME = NULL, 
							  $DB_USER = NULL, 
							  $DB_PASS = NULL
							  ) {
	
	// store the database values
	$this->DB_TYPE = $DB_TYPE;
	$this->DB_HOST = $DB_HOST;
	$this->DB_PORT = $DB_PORT;
	$this->DB_NAME = $DB_NAME;
	$this->DB_USER = $DB_USER;
	$this->DB_PASS = $DB_PASS;


	// internal tracking stuff
  	$this->last_cursor = NULL;
  	$this->last_query = NULL;
  	$this->last_result = NULL;
  	$this->last_statement = NULL;
  	$this->last_error = NULL;
	
	
	// connect and switch to working database
	$this->connect();
	$this->select_db($this->DB_NAME);
	
  } // __construct()
  
  
  public function __destruct() {

	// close the database connection
	$this->close();

	// clear out our state variables
	unset($this->db);
	
	// clear out our operational variables
	unset($this->last_cursor);
	unset($this->last_query);
	unset($this->last_result);
	unset($this->last_statement);
	unset($this->last_error);
	
  	} // __destruct()
  
  
  public function __ToString() {
	
	return
	  "\n<pre>".
	  "\nDATABASE".
	  "\n--------".
	  //	  "\ndb             : ".$this->db.
	  //	  "\nlast_cursor    : ".$this->last_cursor.
	  "\nlast_query     : ".$this->last_query.
	  //	  "\nlast_result    : ".$this->last_result.
	  //	  "\nlast_statement : ".$this->last_statement.
	  //	  "\nlast_error     : ".$this->last_error.
	  "\n</pre>";
	
  } // __ToString()
  
  
  // -----------------------------------------------------------------------
  // 
  // CONNECTION METHODS
  // 
  // -----------------------------------------------------------------------
  
  
  public function connect() { 
	
	// create our database connection based on database type
	switch ($this->DB_TYPE) {
	case 'MYSQL' : $this->db = mysql_connect($this->DB_HOST, $this->DB_USER, $this->DB_PASS); break;
	case 'MYSQLI': 
	
		$this->db = mysqli_connect($this->DB_HOST, $this->DB_USER, $this->DB_PASS);
		//		mysqli_autocommit($this->db, FALSE);
		break;

	case 'ORACLE': $this->db =    oci_connect($this->DB_USER, $this->DB_PASS, $this->DB_HOST); break; # DB_HOST is tnsname
	default      : $this->db = NULL;
	} // switch db_type
	
	// test results
	if (!$this->db) { die('[ERROR] : Database.class.php : could not establish database connection'); }

  } // connect()
  

  public function connected() {
	
	// keep the db handler encapsulated and return a boolean
	if ($this->db) { return 1; }
	else { return 0; } 
	
  } // connected()
  
  
  public function select_db($db_name) {
	
	// verify link exists first
	if ($this->db) {
	  
	  // create our database connection based on database type
	  switch ($this->DB_TYPE) {
	  case 'MYSQL' :  mysql_select_db($this->DB_NAME, $this->db); break;
	  case 'MYSQLI': mysqli_select_db($this->db, $this->DB_NAME); break;
	  case 'ORACLE': break;
	  default      : ;
	  } // switch db_type
	  
	} // $this->db
	
  } // select_db()
  
  
  public function close() { 
	
	// verify link exists first
	if ($this->db) {
	  
	  // close the database connection based on database type
	  switch ($this->DB_TYPE) {
	  case 'MYSQL' :  mysql_close($this->db); $this->db = NULL; break;
	  case 'MYSQLI': mysqli_close($this->db); $this->db = NULL; break;
	  case 'ORACLE':    oci_close($this->db); $this->db = NULL; break;
	  default      : break;
	  } // switch db_type
	  
	} // $this->db
	
  } // close()

  
  // -----------------------------------------------------------------------
  // 
  // PHP DB WRAPPER METHODS
  // 
  // -----------------------------------------------------------------------  
  
  public function sanitize($query = NULL) {
	
	// use database-dependent cleaners
	switch ($this->DB_TYPE) {
	case 'MYSQL' : $query =  mysql_real_escape_string($query, $this->db); break;
	case 'MYSQLI': $query = mysqli_real_escape_string($this->db, $query); break;
	case 'ORACLE': break;
	default      : $query = $query;
	} // switch db_type		
	
	// return the cleaned results
	return $query;
	
  } // sanitize()
  
  
  // -----------------------------------------------------------------------
  // 
  // PHP DB TRANSACTION METHODS
  // 
  // -----------------------------------------------------------------------

  public function commit() {
  	
  	// verify link exists first
	if ($this->db) {
	  
	  // close the database connection based on database type
	  switch ($this->DB_TYPE) {
	  case 'MYSQL' : return true;  break;
	  case 'MYSQLI': return mysqli_commit($this->db); break;
	  case 'ORACLE': return    oci_commit($this->db); break;
	  default      : return false; break;
	  } // switch db_type
	  
	} // $this->db	
  	
  } // commit()
	

  public function rollback() {
  		
  	// verify link exists first
	if ($this->db) {
	  
	  // close the database connection based on database type
	  switch ($this->DB_TYPE) {
	  case 'MYSQL' : return true;  break;
	  case 'MYSQLI': return mysqli_rollback($this->db); break;
	  case 'ORACLE': return    oci_rollback($this->db); break;
	  default      : return false; break;
	  } // switch db_type
	  
	} // $this->db	
  	
  } // rollback()


  // -----------------------------------------------------------------------
  // 
  // PHP DB STATEMENT METHODS
  // 
  // -----------------------------------------------------------------------
  
  
  public function prepare($query) {

	// verify link exists first
	if ($this->db) {
	  
	  // retrieve the number of rows based on database type
	  switch ($this->DB_TYPE) {
	  case 'MYSQL' : die('[ERROR] : Database.class.php : stmt_prepare() not supported for MYSQL connection, use MYSQLI'); break;
	  
	  case 'MYSQLI':
	  
	  		// close the previous statement and open a new one
	  		mysqli_stmt_close($this->last_statement); 
	  		mysqli_stmt_init($this->db);
	  		
			// store the query and statement	  		
	  		$this->last_query = $query; 
	  		$this->last_statement = mysqli_stmt_prepare($this->db, $this->last_query);
	  		break;
	  		
	  case 'ORACLE': 
	  
	  		// close the previous statement
	  		oci_free_statement($this->last_statement);
	  
	  		// store the query and statement
	  		$this->last_query = $query;
	  		$this->last_statement = oci_parse($this->db, $this->last_query); 
	  		break;
	  		
	  default      : break;
	  } // switch db_type
	  
	} // $this->db  	

  } // stmt_prepare()


  public function bind_params() {
  	
  	// verify link exists first
	if ($this->db) {
	  
	  // close the database connection based on database type
	  switch ($this->DB_TYPE) {
	  case 'MYSQL' : die('[ERROR] : Database.class.php : bind_params() not supported for MYSQL connection, use MYSQLI'); break;
	  case 'MYSQLI':
	  
	  		// create the argument array
	  		$args = Array();
	  		
	  		// create the argument list
	  		array_push($args, $this->last_statement); 
			array_push($args, func_get_arg(0));
	  		for ($i = 1; $i < func_num_args(); $i++) { array_push($args, func_get_arg($i)); }
	  		
	  		// make the call
	  		call_user_func_array('mysqli_stmt_bind_param', $args);	  
	  		break;
	  		
	  		
	  case 'ORACLE': break;
	  default      : break;
	  } // switch db_type
	  
	} // $this->db
	  	
  } // stmt_bind_params()
  

  public function execute() {
  	
  	// verify link exists first
	if ($this->db) {
	  
	  // close the database connection based on database type
	  switch ($this->DB_TYPE) {
	  case 'MYSQL' : die('[ERROR] : Database.class.php : execute() not supported for MYSQL connection, use MYSQLI'); break;
	  case 'MYSQLI': return mysqli_stmt_execute($this->last_statement); break;
	  case 'ORACLE': return         oci_execute($this->last_statement); break;	  		
	  default      : break;
	  } // switch db_type
	  
	} // $this->db
	
  } // stmt_execute()
  
  
  public function bind_result() {
  	
  	// verify link exists first
	if ($this->db) {
	  
	  // close the database connection based on database type
	  switch ($this->DB_TYPE) {
	  case 'MYSQL' : die('[ERROR] : Database.class.php : bind_result() not supported for MYSQL connection, use MYSQLI'); break;
	  case 'MYSQLI': 
	  
	  	  	// create the argument array
	  		$args = Array();
	  		
	  		// create the argument list
	  		array_push($args, $this->last_statement); 
	  		for ($i = 1; $i < func_num_args(); $i++) { array_push($args, func_get_arg($i)); }
	  		
	  		// make the call
	  		call_user_func_array('mysqli_stmt_bind_result', $args);	  
	  		break;
	  
	  
	  case 'ORACLE': break;
	  default      : break;
	  } // switch db_type
	  
	} // $this->db
	  	
  } // stmt_bind_result()
  
  
  public function fetch() {
  	
  	// verify link exists first
	if ($this->db) {
	  
	  // close the database connection based on database type
	  switch ($this->DB_TYPE) {
	  case 'MYSQL' : die('[ERROR] : Database.class.php : fetch() not supported for MYSQL connection, use MYSQLI'); break;
	  case 'MYSQLI': return mysqli_stmt_fetch($this->last_statement); break;
	  case 'ORACLE': break;
	  default      : break;
	  } // switch db_type
	  
	} // $this->db
	  	
  } // stmt_fetch()
  
  public function free_statement() {
  	
  	// verify link exists first
	if ($this->db) {
		
	  switch ($this->DB_TYPE) {
	  case 'MYSQL' : die('[ERROR] : Database.class.php : free_statement() not supported for MYSQL connection, use MYSQLI'); break;
	  case 'MYSQLI':  mysqli_stmt_close($this->last_statement); break;
	  case 'ORACLE': oci_free_statement($this->last_statement); break;
	  default      : break;
	  } // switch db_type
	  
	} // $this->db
	  	
  } // stmt_fetch()


  // -----------------------------------------------------------------------
  // 
  // PHP DB QUERY METHODS
  // 
  // -----------------------------------------------------------------------
    
  public function error() {
	
	// verify link exists first
	if ($this->db) {
	  
	  // retrieve the last query error message based on database type
	  switch ($this->DB_TYPE) {
	  case 'MYSQL' : $this->last_error =  mysql_error($this->db); return $this->last_error; break;
	  case 'MYSQLI': $this->last_error = mysqli_error($this->db); return $this->last_error; break;
	  case 'ORACLE': break;
	  default      : return NULL;
	  } // switch db_type
	  
	} // $this->db
	
  } // error()


  public function fetch_array() {
	
	// verify link exists first
	if ($this->db) {
	  
	  // retrieve an array of results from last_result based on database type
	  switch ($this->DB_TYPE) {
	  case 'MYSQL' : return  mysql_fetch_array($this->last_result); break;
	  case 'MYSQLI': return mysqli_fetch_array($this->last_result); break;
	  case 'ORACLE': break;
	  default      : return NULL;
	  } // switch db_type
	  
	} // $this->db
	
  } // fetch_array()


  public function fetch_assoc() {
	
	// verify link exists first
	if ($this->db) {
	  
	  // retrieve an associative array of results from last_result based on database type
	  switch ($this->DB_TYPE) {
	  case 'MYSQL' : return  mysql_fetch_assoc($this->last_result); break;
	  case 'MYSQLI': return mysqli_fetch_assoc($this->last_result); break;
	  case 'ORACLE': break;
	  default      : return NULL;
	  } // switch db_type
	  
	} // $this->db
	
  } // fetch_assoc()
  
  
  public function insert_id() {
	
	// verify link exists first
	if ($this->db) {
	  
	  // retrieve the LAST_INSERT_ID() based on database type
	  switch ($this->DB_TYPE) {
	  case 'MYSQL' : return  mysql_insert_id($this->db); break;
	  case 'MYSQLI': return mysqli_insert_id($this->db); break;
	  case 'ORACLE': break;
	  default      : return NULL;
	  } // switch db_type
	  
	} // $this->db
	
  } // insert_id()


  public function num_rows() {
	
	// verify link exists first
	if ($this->db) {
	  
	  // retrieve the number of rows based on database type
	  switch ($this->DB_TYPE) {
	  case 'MYSQL' : return  mysql_num_rows($this->last_result); break;
	  case 'MYSQLI': return mysqli_num_rows($this->last_result); break;
	  case 'ORACLE': break;
	  default      : return NULL;
	  } // switch db_type
	  
	} // $this->db		
	
  } // num_rows()

  
  public function query($query = NULL) {
	
	// verify link exists first
	if ($this->db) {
	  
	  // store the query locally
	  $this->last_query = $query;
	  
	  // execute query based on database type
	  switch ($this->DB_TYPE) {
	  case 'MYSQL' : $this->last_result =  mysql_query($query, $this->db); break;
	  case 'MYSQLI': $this->last_result = mysqli_query($this->db, $query); break;
	  case 'ORACLE': break;
	  default      : $this->last_result = NULL;
	  } // switch db_type
	  
	  // retrieve the last error if necessary
	  if(!$this->last_result) { $this->error(); }
	  
	} // $this->db
	
  } // query()


  public function queryOK() { 
	
	// keep the result resource encapsulated and return a boolean
	if ($this->last_result) { return 1; } else { return 0; } 
		
  } // queryOK()
 
  
} // Database


// -----------------------------------------------------------------------------
// 
// MAIN
// 
// -----------------------------------------------------------------------------


// include the configuration class
require_once('Config.class.php');

// check for necessary definitions
$errors = 0;

if (!$_CONFIG->DB_TYPE()) { echo("[ERROR] Database.class.php: DB_TYPE not defined!<br>\n"); $errors++; }
if (!$_CONFIG->DB_HOST()) { echo("[ERROR] Database.class.php: DB_HOST not defined!<br>\n"); $errors++; }
if (!$_CONFIG->DB_PORT()) { echo("[ERROR] Database.class.php: DB_PORT not defined!<br>\n"); $errors++; }
if (!$_CONFIG->DB_NAME()) { echo("[ERROR] Database.class.php: DB_NAME not defined!<br>\n"); $errors++; }
if (!$_CONFIG->DB_USER()) { echo("[ERROR] Database.class.php: DB_USER not defined!<br>\n"); $errors++; }
if (!$_CONFIG->DB_PASS()) { echo("[ERROR] Database.class.php: DB_PASS not defined!<br>\n"); $errors++; }

// exit on errors
if ($errors) { die('exiting from previous errors'); }

// create our instance variable
$_DB = new Database($_CONFIG->DB_TYPE(), 
					$_CONFIG->DB_HOST(), 
					$_CONFIG->DB_PORT(), 
					$_CONFIG->DB_NAME(), 
					$_CONFIG->DB_USER(), 
					$_CONFIG->DB_PASS()
					);

?>