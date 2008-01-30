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
	$query = "select count(*) as count from " . TN_POAMS . " where (poam_action_owner = '" . $this->system_id . "'";

	// add in our types and statuses
	if ($type or $status) {

	  // add in type
	  if ($type)   { $query .= " and poam_type = '$type'";  }

	  // add in statys
	  if ($status) { 

		switch ($status) {
		case "EN" :
		  $query .= " and poam_status = '$status' and poam_action_date_est > NOW()";
		  break;

		case "EO" :
		  $query .= " and poam_status = 'EN' and (poam_action_date_est <= NOW() or poam_action_date_est IS NULL)";
		  break;

		default   : 
		  $query .= " and poam_status = '$status'"; 
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
