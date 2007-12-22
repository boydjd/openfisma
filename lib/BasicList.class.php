<?PHP
/**
 * @file BasicList.class.php
 * 
 * @defgroup Libraries
 * 
 */


/**
 * @class BasicList
 * @ingroup Libraries
 * @brief BasicList is a class to provide a generic list interface for 
 * database tables
 * 
 * The BasicList abstract class provides an interface from which to build 
 * row listing classes for database tables within the OVMS application by
 * implementing common listing functionality. All list classes should be
 * an extension of this class.
 *
 */
abstract class BasicList {


  // -----------------------------------------------------------------------
  // 
  // VARIABLES
  // 
  // -----------------------------------------------------------------------  

  /// db database connection
  protected $db;

  /// var table table to query
  protected $table;

  /// parameter and filter arrays
  protected $params;
  protected $filters;

  /// sorting options
  protected $order;

  /// key list key
  protected $key;

  // -----------------------------------------------------------------------
  // 
  // CLASS METHODS
  // 
  // -----------------------------------------------------------------------  
	
  /** 
   * @fn __construct()
   * @brief BasicList class constructor
   * @param db database connection (instance of class Database)
   * @param table database table we are querying
   */
  public function __construct($db = NULL, $table = NULL) {

	// grab our database connector and table
	$this->db    = $db;
	$this->table = $table;

	// use reset to initialize
	$this->reset();

  } // __construct()

  /**
   * @fn __destruct()
   * @brief BasicList class destructor
   */
  public function __destruct() {}

  /**
   * @fn __ToString()
   * @brief returns BasicList as a string (not yet implemented)
   * @return string representation of BasicList
   */
  public function __ToString() {}

  /**
   * @fn reset()
   * @brief method to reset the internal variables
   */
  public function reset() {

	// initialize our parameter and filter arrays
	$this->params  = array();
	$this->filters = array();
	
	// sorting options
	$this->order = NULL;

	// reset the key
	$this->resetKey();

  } // reset()

  /**
   * @fn resetKey()
   * @brief method used to reset the @var key for keylists
   */
  public function resetKey() {

	// reset the key
	$this->key = NULL;

  } // resetKey()


  // -----------------------------------------------------------------------
  // 
  // QUERY 
  // 
  // -----------------------------------------------------------------------  

  private function array_list($array = NULL) {

	// initialize our list
	$list = "";

	// loop through the array
	while ($item = array_pop($array)) { $list .= "'".$item."',"; }

	// remove the last comma
	if (strlen($list) > 0) { $list = substr($list, 0, strlen($list) - 1); }

	// return the list
	return $list;

  } // array_list()

  private function buildQuery($counter = TRUE, $offset = 0, $limit = NULL) {
	
	// work with local copies
	$P = $this->params;
	$F = $this->filters;
	
	// open query
	$query  = "SELECT ";
	
	// parameter string
	$params = "";
	
	// loop through parameter array
	while ($param = array_pop($P)) { $params .= "$param, "; }

	// trim the last comma and space (if necessary)
	if (strlen($params) > 0) { $params = substr($params, 0, strlen($params) - 2); }
	
	// apply paramters
	if ($counter) { $query .= "count(*) as count "; } else { $query  .= $params; }
	
	// apply table
	$query  .= " FROM ".$this->table." ";
	
	// filter string
	$filter  = "";
	$columns = array_keys($F);
	
	// for readability
	$value    = 0;
	$polarity = 1;
	
	// loop through the keys and add the filters
	while ($column = array_pop($columns)) { 

	  // we were given a list, is IN ()
	  if (is_array($F[$column][$value])) {

		// polarity is true
		if ($F[$column][$polarity] == TRUE)  { $filter .= $column." IN (".$this->array_list($F[$column][$value]).")"; }
		if ($F[$column][$polarity] == FALSE) { $filter .= $column." NOT IN (".$this->array_list($F[$column][$value]).")"; }		
		
	  }

	  // we were given a single item
	  else {

		// polarity is true
		if ($F[$column][$polarity] == TRUE)  { $filter .= $column."  = '".$F[$column][$value]."'"; }
		if ($F[$column][$polarity] == FALSE) { $filter .= $column." != '".$F[$column][$value]."'"; }

	  }
	  
	  // AND the filters
	  $filter .= ' AND ';
	  
	} // filters
	
	// remove final AND and put it in parentheses
	if (strlen($filter) > 0) { 
	  
	  $filter = substr($filter, 0, strlen($filter) - 5);
	  $filter = 'WHERE ('.$filter.')';
	  
	}

	// apply filters
	$query  .= $filter;
	
	// apply ordering
	if ($this->order) { $query .= " ORDER BY ".$this->order; }

	// apply limits
	if (!$count && $limit) { $query  .= " LIMIT $offset, $limit"; }
	
	// return the results
	return $query;
	
  } // buildQuery()
  
  
  public function getUniques() {
	
	// work with a local param copy
	$params = $this->params;
	
	// create our associative array of unique items
	$results = array();
	
	// loop through the parameters
	while ($param = array_pop($params)) { 

	  // create the query
	  $query = "SELECT DISTINCT $param FROM ".$this->table;

	  // execute the query
	  $this->db->query($query);
		
		// set up our array
		$results[$param] = array();

		// add the results to our array
		while ($row = $this->db->fetch_array()) { array_push($results[$param], $row[0]); }
		
	} // while $params

	// return associative array of arrays
	return $results;

  } // getUniques()


  public function getList($offset = 0, $limit = NULL) {

	// create an array to catch the list
	$list = array();
	  
	// execute the query
	$this->db->query($this->buildQuery(FALSE, $offset, $limit));

	// grab all of the results
	while ($row = $this->db->fetch_assoc()) { array_push($list, $row); }
	  
	// return the results
	return $list;

  } // getList()

  
  public function getListSize() { 

	// execute the count query
	$this->db->query($this->buildQuery());

	// retrieve the result
	$result = $this->db->fetch_assoc();

	// return the resuls
	return $result['count'];

  } // getListSize()


  public function getKeyList() {

	// initialize the key list
	$key_list = Array();

	// retrieve the row list
	$row_list = $this->getList(FALSE, 0, NULL);

	// loop through rows and build the key list
	while ($row = array_shift($row_list)) {

	  // initialize a new array for the retrieved key value
	  $cols = Array();

	  // loop through the columns and add it to the $key row
	  while ( list($row_key, $row_val) = each($row) ) {

		if ($row_key != $this->key) { $cols[$row_key] = $row_val; }

	  }

	$key_list[$row[$this->key]] = $cols;

	} // while $row

	// return the list
	return $key_list;

  } // getKeyList()


  // -----------------------------------------------------------------------
  // 
  // ORDER
  // 
  // -----------------------------------------------------------------------

  public function setOrder($order = NULL) { $this->order = $order; }


} // class BasicList

?>
