<?PHP

class PoamSummary {

  // ---------------------------------------------------------------------------
  // 
  // INSTANCE VARIABLES
  // 
  // ---------------------------------------------------------------------------

  // db handler
  private $db;

  // user_id and system_id
  private $system_id;


  // ---------------------------------------------------------------------------
  // 
  // CLASS METHODS
  // 
  // ---------------------------------------------------------------------------

  function __construct($db = NULL) {

	// store the DB handler
	if ($db) { $this->db = $db; } else { $this->__destruct(); }

  } // __construct()


  function __destruct() {  } // __destruct()


  // ---------------------------------------------------------------------------
  // 
  // SET METHODS
  // 
  // ---------------------------------------------------------------------------

  function setSystemId($system_id = NULL) { $this->system_id = $system_id; }


  // ---------------------------------------------------------------------------
  // 
  // GET METHODS
  // 
  // ---------------------------------------------------------------------------

  function poamCount($type = NULL, $status = NULL) {

	// start building our query
	$query = "SELECT COUNT(*) AS count FROM " . TN_POAMS . " WHERE (poam_action_owner = '" . $this->system_id . "'";

	// add in our types AND statuses
	if ($type or $status) {

	  // add in type
	  if ($type)   { $query .= " AND poam_type = '$type'";  }

	  // add in statys
	  if ($status) { 
        $now = date("Y-m-d H:i:s");
		switch ($status) {
		case "EN" :
		  $query .= " AND poam_status = '$status' AND poam_action_date_est > '$now'";
		  break;

		case "EO" :
		  $query .= " AND poam_status = 'EN' AND (poam_action_date_est <= '$now' OR poam_action_date_est IS NULL)";
		  break;

		default   : 
		  $query .= " AND poam_status = '$status'"; 
		  break;

		} // switch

	  } // if status

	} // if type or status

	// close the query
	$query .= ")";

	// execute the query
	$result = $this->db->sql_query($query);

	// handle the results
	if ($result) { 

	  $row = $this->db->sql_fetchrow($result);
	  return $row['count'];

	}

	else { return NULL; }


  } // poamCount()
  

  } // PoamSummary


?>
