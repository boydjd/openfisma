<?PHP

// TODO: work with system_primary_office

class System {

  // -----------------------------------------------------------------------
  //
  // PRIVATE VARIABLES
  //
  // -----------------------------------------------------------------------

  private $db;

  private $system_id;
  private $system_name;
  private $system_nickname;

  private $system_desc;
  private $system_type;

  private $system_primary_office;

  private $system_availability;
  private $system_integrity;
  private $system_confidentiality;

  private $system_criticality_justification;
  private $system_sensitivity_justification;


  // -----------------------------------------------------------------------
  // 
  // CONSTRUCTOR
  // 
  // -----------------------------------------------------------------------

  public function __construct($db, $system_id = 0) {

	//
	// store the db link locally
	//
	$this->db = $db;

	//
	// create a new object if no system_id is given
	//
	if ($system_id == 0) {

	  // define our query
	  $query = "INSERT INTO " . TN_SYSTEMS . " (system_id) VALUES (NULL)";

	  // execute our query
	  $handler = $this->db->sql_query($query);	  


	  // retrieve our last insert id
	  $query = "SELECT LAST_INSERT_ID() AS system_id";

	  // execute our query
	  $handler = $this->db->sql_query($query);
	  $results = $this->db->sql_fetchrow($handler);

	  // store the system_id
	  $this->system_id = $results['system_id'];


	}

	//
	// retrieve the data if system_id is defined
	//
	else {

	  // define our query
	  $query = "SELECT * FROM " . TN_SYSTEMS . " WHERE (system_id = $system_id)";

	  // execute the query and retrieve the results
	  $handler = $this->db->sql_query($query);
	  $results = $this->db->sql_fetchrow($handler);

	  // save the results
	  $this->system_id       = $results['system_id'];
	  $this->system_name     = $results['system_name'];
	  $this->system_nickname = $results['system_nickname'];

	  $this->system_desc     = $results['system_desc'];
	  $this->system_type     = $results['system_type'];

	  $this->system_primary_office  = $results['system_primary_office'];

	  $this->system_availability    = $results['system_availability'];
	  $this->system_integrity       = $results['system_integrity'];
	  $this->system_confidentiality = $results['system_confidentiality'];
	  
	  $this->system_criticality_justification = $results['system_criticality_justification'];
	  $this->system_sensitivity_justification = $results['system_sensitivity_justification'];

	}

  } // __construct()


  // -----------------------------------------------------------------------
  // 
  // DESTRUCTOR
  // 
  // -----------------------------------------------------------------------

  public function __destruct() {

	// clear out the system_id to prevent any updates
	$this->system_id = 0;

  }


  // -----------------------------------------------------------------------
  // 
  // CLASS MANIPULATION METHODS
  // 
  // -----------------------------------------------------------------------

  public function deleteSystem() {

	// define our query
	$query = "DELETE FROM " . TN_SYSTEMS . " WHERE (system_id = $this->system_id)";

	// execute our query
	$handler = $this->db->sql_query($query);

	// destroy the object;
	$this->__destruct();

  }


  // -----------------------------------------------------------------------
  // 
  // VARIABLE ACCESS METHODS
  // 
  // -----------------------------------------------------------------------

  public function getSystemId()                       { return $this->system_id; }
  public function getSystemName()                     { return $this->system_name; }
  public function getSystemNickname()                 { return $this->system_nickname; }

  public function getSystemDesc()                     { return $this->system_desc; }
  public function getSystemType()                     { return $this->system_type; }

  public function getSystemPrimaryOffice()            { return $this->system_primary_office; }

  public function getSystemAvailability()             { return $this->system_availability; }
  public function getSystemIntegrity()                { return $this->system_integrity; }
  public function getSystemConfidentiality()          { return $this->system_confidentiality; }

  public function getSystemCriticalityJustification() { return $this->system_criticality_justification; }
  public function getSystemSensitivityJustification() { return $this->system_sensitivity_justification; }


  // -----------------------------------------------------------------------
  // 
  // VARIABLE MODIFY METHODS
  // 
  // -----------------------------------------------------------------------

  public function setSystemName($system_name = '') {

	// do not operate on a dead system
	if ($system_id != 0) {

	  // error check input
	  if (strlen($system_name) <= 128) {
		
		// set our local copy
		$this->system_name = $system_name;
		
		// define our query
		$query = 
		  "UPDATE " . TN_SYSTEMS . " ".
		  "SET   (system_name = $this->system_name) ".
		  "WHERE (system_id   = $this->system_id)";
		
		// execute our query
		$handler = $this->db->sql_query($query);
		
	  }

	}

  } // setSystemName()


  public function setSystemNickname($system_nickname = '') {

	// do not operate on a dead system
	if ($system_id != 0) {

	  // error check input
	  if (strlen($system_nickname) <= 8) {
	  
		// set our local copy
		$this->system_nickname = $system_nickname;
		
		// define our query
		$query = 
		  "UPDATE " . TN_SYSTEMS . " ".
		  "SET   (system_nickname = $this->system_nickname) ".
		  "WHERE (system_id       = $this->system_id)";
		
		// execute our query
		$handler = $this->db->sql_query($query);

	  }
		
	}

  } // setSystemNickname()


  public function setSystemDesc($system_desc = '') {

	// do not operate on a dead system
	if ($system_id != 0) {

	  // error check input
	  if (strlen($system_desc) >= 0) {

		// set our local copy
		$this->system_desc = $system_desc;
	   
		// define our query
		$query = 
		  "UPDATE " . TN_SYSTEMS . " ".
		  "SET   (system_desc = $this->system_desc) ".
		  "WHERE (system_id   = $this->system_id)";
		
		// execute our query
		$handler = $this->db->sql_query($query);

	  }
		
	}

  } // setSystemDesc()


  public function setSystemType($system_type = '') {

	// do not operate on a dead system
	if ($system_id != 0) {

	  // only accept 'GSS' / 'GENERAL SUPPORT SYSTEM' or 'MA' / 'MAJOR APPLICATION'
	  if (
		// ($system_type == 'GSS') || 
		($system_type == 'GENERAL SUPPORT SYSTEM') ||
		//  ($system_type == 'MA')  || 
		($system_type == 'MINOR APPLICATION' ||
		($system_type == 'MAJOR APPLICATION') )) {

		// set our local copy
		$this->system_desc = $system_type;
		
		// define our query
		$query = 
		  "UPDATE " . TN_SYSTEMS . " ".
		  "SET   (system_type = $this->system_type) ".
		  "WHERE (system_id   = $this->system_id)";
		
		// execute our query
		$handler = $this->db->sql_query($query);

	  }

	}

  }


  public function setSystemPrimaryOffice() {

	// do not operate on a dead system
	if ($system_id != 0) {

	}

  }


  public function setSystemAvailability($system_availability = '') {

	// do not operate on a dead system
	if ($system_id != 0) {

	  // error check input
	  if (($system_availability == 'NONE') || 
		  ($system_availability == 'LOW') || 
		  ($system_availability == 'MODERATE') || 
		  ($system_availability == 'HIGH')) {

		// set our local copy
		$this->system_availability = $system_availability;
		
		// define our query
		$query = 
		  "UPDATE " . TN_SYSTEMS . " ".
		  "SET   (system_availability = $this->system_availability) ".
		  "WHERE (system_id           = $this->system_id)";
		
		// execute our query
		$handler = $this->db->sql_query($query);
		
	  }

	}

  }


  public function setSystemIntegrity($system_integrity = '') {

	// do not operate on a dead system
	if ($system_id != 0) {

	  // error check input
	  if (($system_integrity == 'NONE') || 
		  ($system_integrity == 'LOW') || 
		  ($system_integrity == 'MODERATE') || 
		  ($system_integrity == 'HIGH')) {

		// set our local copy
		$this->system_integrity = $system_integrity;
		
		// define our query
		$query = 
		  "UPDATE " . TN_SYSTEMS . " ".
		  "SET   (system_integrity = $this->system_integrity) ".
		  "WHERE (system_id        = $this->system_id)";
		
		// execute our query
		$handler = $this->db->sql_query($query);
		
	  }

	}

  }

  
  public function setSystemConfidentiality($system_confidentiality = '') {

	// do not operate on a dead system
	if ($system_id != 0) {

	  // error check input
	  if (($system_confidentiality == 'NONE') || 
		  ($system_confidentiality == 'LOW') || 
		  ($system_confidentiality == 'MODERATE') || 
		  ($system_confidentiality == 'HIGH')) {

		// set our local copy
		$this->system_confidentiality = $system_confidentiality;
		
		// define our query
		$query = 
		  "UPDATE " . TN_SYSTEMS . " ".
		  "SET   (system_confidentiality = $this->system_confidentiality) ".
		  "WHERE (system_id              = $this->system_id)";
		
		// execute our query
		$handler = $this->db->sql_query($query);
		
	  }

	}

  }


  public function setSystemCriticalityJustification($system_criticality_justification = '') {

	// do not operate on a dead system
	if ($system_id != 0) {

	  // error check input
	  if (strlen($system_criticality_justification) >= 0) {

		// set our local copy
		$this->system_criticality_justification = $system_criticality_justification;
	   
		// define our query
		$query = 
		  "UPDATE " . TN_SYSTEMS . " ".
		  "SET   (system_criticality_justification = $this->system_criticality_justification) ".
		  "WHERE (system_id                        = $this->system_id)";
		
		// execute our query
		$handler = $this->db->sql_query($query);

	  }

	}

  }


  public function setSystemSensitivityJustification($sytem_sensitivity_justification = '') {

	// do not operate on a dead system
	if ($system_id != 0) {

	  // error check input
	  if (strlen($system_sensitivity_justification) >= 0) {

		// set our local copy
		$this->system_sensitivity_justification = $system_sensitivity_justification;
	   
		// define our query
		$query = 
		  "UPDATE " . TN_SYSTEMS . " ".
		  "SET   (system_sensitivity_justification = $this->system_sensitivity_justification) ".
		  "WHERE (system_id                        = $this->system_id)";
		
		// execute our query
		$handler = $this->db->sql_query($query);

	  }

	}

  }


} // class System

?>
