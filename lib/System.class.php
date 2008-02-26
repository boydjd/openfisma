<?PHP

// 
// INCLUDES
// 
require_once('Database.class.php');


//
// CLASS DEFINITION
// 

class System {

	// -----------------------------------------------------------------------
	//
	// VARIABLES
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
    private $system_tier;
    private $system_criticality_justification;
    private $system_sensitivity_justification;


	// -----------------------------------------------------------------------
	// 
	// CLASS METHODS
	// 
	// -----------------------------------------------------------------------

	public function __construct($db, $system_id = NULL) {

		// utilize an existing database connection
		$this->db = $db;

		// get System information or create a new one if none specified
		if ($system_id) {
		  $this->getSystem($system_id); 
		}

	} // __construct()
	

	public function __destruct() {

		// clear out the system_id to prevent any updates
		$this->system_id = 0;

	} // __destruct()


 	public function __ToString() {
 		
 		// return a string of information
 		return	$this->db->__ToString().
 			'<pre>'.
			'<br>SYSTEMS'.
			'<br>------'.

            '<br>system_id                                         : '.$this->system_id.
            '<br>system_name                                       : '.$this->system_name.
            '<br>system_nickname                                   : '.$this->system_nickname.
            '<br>system_desc                                       : '.$this->system_desc.
            '<br>system_type                                       : '.$this->system_type.
            '<br>system_primary_office                             : '.$this->system_primary_office.
            '<br>system_availability                               : '.$this->system_availability.
            '<br>system_integrity                                  : '.$this->system_integrity.
            '<br>system_confidentiality                            : '.$this->system_confidentiality.
            '<br>system_tier                                       : '.$this->system_tier.
            '<br>system_criticality_justification                  : '.$this->system_criticality_justification.
            '<br>system_sensitivity_justification                  : '.$this->system_sensitivity_justification.
			'<br></pre>';
 		
 	} // __ToString()
 	

	// -----------------------------------------------------------------------
	// 
	// CLASS MANIPULATION METHODS
	// 
	// -----------------------------------------------------------------------
	
	public function systemExists($system_id = NULL) {
		
		// make sure we have a positive, non-zero system_id
		if ($system_id) {
		
			// build our query
			$query = "SELECT `system_id` FROM " . TN_SYSTEMS . "  WHERE (`system_id` = '$system_id')";
			
			// execute the query
			$this->db->query($query);
			
			// check for results
			if ( $this->db->queryOK() && $this->db->num_rows() ) {
			     return 1; 
			} 
			else {
			     return 0; 
			}
		}
		
		// otherwise don't even bother checking it
		else { 
		     return 0; 
		}
		
	} // systemExists()
	

	public function getSystem($system_id = NULL) {
		
		// make sure we have a positive, non-zero system_id
		if ($system_id && $this->systemExists($system_id)) {
		
			// designate our retrieval query
			$query = "SELECT * FROM " . TN_SYSTEMS . "  WHERE (`system_id` = '$system_id')";
		
			// execute the query
			$this->db->query($query);
		
			// if we get a hit, store the information
			if ($this->db->num_rows() > 0) {
			
				// retrieve the results query
				$results = $this->db->fetch_assoc();
			
				// store the results locally

                $this->system_id                                          = $results['system_id'];
                $this->system_name                                        = $results['system_name'];
                $this->system_nickname                                    = $results['system_nickname'];
                $this->system_desc                                        = $results['system_desc'];
                $this->system_type                                        = $results['system_type'];
                $this->system_primary_office                              = $results['system_primary_office'];
                $this->system_availability                                = $results['system_availability'];
                $this->system_integrity                                   = $results['system_integrity'];
                $this->system_confidentiality                             = $results['system_confidentiality'];
                $this->system_tier                                        = $results['system_tier'];
                $this->system_criticality_justification                   = $results['system_criticality_justification'];
                $this->system_sensitivity_justification                   = $results['system_sensitivity_justification'];
			
			} // this->db->fetch_assoc()
			
			// system not retrieved, clear out any potential values
			else {
			     $this->clearSystem(); 
			}
		} // if $system_id

	} // getSystem()


		
	public function saveSystem(){
	
	    if ($this->system_id && $this->systemExists($this->system_id)){
    	    $query = "UPDATE " . TN_SYSTEMS . " SET ";    
            	    $query .= " `system_name`                                        = '$this->system_name', ";
            	    $query .= " `system_nickname`                                    = '$this->system_nickname', ";
            	    $query .= " `system_desc`                                        = '$this->system_desc', ";
            	    $query .= " `system_type`                                        = '$this->system_type', ";
            	    $query .= " `system_primary_office`                              = '$this->system_primary_office', ";
            	    $query .= " `system_availability`                                = '$this->system_availability', ";
            	    $query .= " `system_integrity`                                   = '$this->system_integrity', ";
            	    $query .= " `system_confidentiality`                             = '$this->system_confidentiality', ";
            	    $query .= " `system_tier`                                        = '$this->system_tier', ";
            	    $query .= " `system_criticality_justification`                   = '$this->system_criticality_justification', ";
            	    $query .= " `system_sensitivity_justification`                   = '$this->system_sensitivity_justification' ";	    
                    $query .= " WHERE `system_id`                                    = '$this->system_id' ";
	    }
	    else {
	       $query = "INSERT INTO " . TN_SYSTEMS . " (
                            `system_name`, 
                            `system_nickname`, 
                            `system_desc`, 
                            `system_type`, 
                            `system_primary_office`, 
                            `system_availability`, 
                            `system_integrity`, 
                            `system_confidentiality`, 
                            `system_tier`, 
                            `system_criticality_justification`, 
                            `system_sensitivity_justification`
                            ) VALUES (
                            '$this->system_name', 
                            '$this->system_nickname', 
                            '$this->system_desc', 
                            '$this->system_type', 
                            '$this->system_primary_office', 
                            '$this->system_availability', 
                            '$this->system_integrity', 
                            '$this->system_confidentiality', 
                            '$this->system_tier', 
                            '$this->system_criticality_justification', 
                            '$this->system_sensitivity_justification'
                            )";
	    }
	    
    	// execute our query
    	$this->db->query($query);
    
    	if ($this->db->queryOK()) { 
    	   if (!$system_id || !$this->systemExists($system_id)){
    	       $this->system_id = $this->db->insert_id();
    	   }
    	   return 1; 
    	} 
    	else {
    	   return 0; 
    	}
	} //saveSystem()
	
	public function clearSystem() {
		
		// clear out (non-db) user values

        unset($this->system_id);
        unset($this->system_name);
        unset($this->system_nickname);
        unset($this->system_desc);
        unset($this->system_type);
        unset($this->system_primary_office);
        unset($this->system_availability);
        unset($this->system_integrity);
        unset($this->system_confidentiality);
        unset($this->system_tier);
        unset($this->system_criticality_justification);
        unset($this->system_sensitivity_justification);
	} // clearSystem()


	public function deleteSystem() {

		// 
		// REMOVES SYSTEMS FROM " . TN_DATABASE!
		// 

		// ensure that we have an open database connection
		if ($this->db) {

			// define our query
			$query = "DELETE FROM " . TN_SYSTEMS . " WHERE (`system_id` = '$this->system_id')";

			// execute our query
			$this->db->query($query);

			// clear out the current object
			$this->clearSystem();
		
		} // $this->db

	} // deleteSystem()
	
	

	// -----------------------------------------------------------------------
	// 
	// VARIABLE ACCESS METHODS
	// 
	// -----------------------------------------------------------------------


    public function getSystemId()                                          { return $this->system_id; }
    public function getSystemName()                                        { return $this->system_name; }
    public function getSystemNickname()                                    { return $this->system_nickname; }
    public function getSystemDesc()                                        { return $this->system_desc; }
    public function getSystemType()                                        { return $this->system_type; }
    public function getSystemPrimaryOffice()                               { return $this->system_primary_office; }
    public function getSystemAvailability()                                { return $this->system_availability; }
    public function getSystemIntegrity()                                   { return $this->system_integrity; }
    public function getSystemConfidentiality()                             { return $this->system_confidentiality; }
    public function getSystemTier()                                        { return $this->system_tier; }
    public function getSystemCriticalityJustification()                    { return $this->system_criticality_justification; }
    public function getSystemSensitivityJustification()                    { return $this->system_sensitivity_justification; }

	public function getValidSystemIds($offset = 0, $limit = NULL) {
		
		// array to store system_ids
		$id_array = array();

		// create our query
		$query = "SELECT `system_id` FROM " . TN_SYSTEMS;

		// add in our offset and limit if a limit is provided
		if ($limit) { $query .= " LIMIT $offset, $limit";  }

		// execute the query
		$this->db->query($query);
	
		// evaluate the results
		if ($this->db->queryOK()) {
			
			// push the values onto the array
			while ($id = $this->db->fetch_array()) { array_push($id_array, $id[0]); }
			
		}
		
		// return the array of system_ids
		return $id_array;
		
	} // getValidSystemIds
	
	// -----------------------------------------------------------------------
	// 
	// VARIABLE MODIFY METHODS
	// 
	// -----------------------------------------------------------------------


    public function setSystemName($system_name  =  NULL){ 
		// error check input (by schema)
		if (strlen($system_name) <= 128){
            $this->system_name = $system_name;
            return true;
		} // input error check
		else {
		    return false;
		}
	} // setSystemName()
    
    
    public function setSystemNickname($system_nickname  =  NULL){ 
		// error check input (by schema)
		if (strlen($system_nickname) <= 8){
            $this->system_nickname = $system_nickname;
            return true;
		} // input error check
		else {
		    return false;
		}
	} // setSystemNickname()
    
    
    public function setSystemDesc($system_desc  =  NULL){ 
		// error check input (by schema)
		if (strlen($system_desc) >= 0){
            $this->system_desc = $system_desc;
            return true;
		} // input error check
		else {
		    return false;
		}
	} // setSystemDesc()
    
    
    public function setSystemType($system_type  =  NULL){ 
		// error check input (by schema)
		if (in_array($system_type, array('GENERAL SUPPORT SYSTEM','MAJOR APPLICATION')) ){
            $this->system_type = $system_type;
            return true;
		} // input error check
		else {
		    return false;
		}
	} // setSystemType()
    
    
    public function setSystemPrimaryOffice($system_primary_office  =  NULL){ 
		// error check input (by schema)
		if (strlen($system_primary_office) <= 10){
            $this->system_primary_office = $system_primary_office;
            return true;
		} // input error check
		else {
		    return false;
		}
	} // setSystemPrimaryOffice()
    
    
    public function setSystemAvailability($system_availability  =  NULL){ 
		// error check input (by schema)
		if (in_array($system_availability, array('NONE','LOW','MODERATE','HIGH')) ){
            $this->system_availability = $system_availability;
            return true;
		} // input error check
		else {
		    return false;
		}
	} // setSystemAvailability()
    
    
    public function setSystemIntegrity($system_integrity  =  NULL){ 
		// error check input (by schema)
		if (in_array($system_integrity, array('NONE','LOW','MODERATE','HIGH')) ){
            $this->system_integrity = $system_integrity;
            return true;
		} // input error check
		else {
		    return false;
		}
	} // setSystemIntegrity()
    
    
    public function setSystemConfidentiality($system_confidentiality  =  NULL){ 
		// error check input (by schema)
		if (in_array($system_confidentiality, array('NONE','LOW','MODERATE','HIGH')) ){
            $this->system_confidentiality = $system_confidentiality;
            return true;
		} // input error check
		else {
		    return false;
		}
	} // setSystemConfidentiality()
    
    
    public function setSystemTier($system_tier  =  NULL){ 
		// error check input (by schema)
		if (strlen($system_tier) <= 10){
            $this->system_tier = $system_tier;
            return true;
		} // input error check
		else {
		    return false;
		}
	} // setSystemTier()
    
    
    public function setSystemCriticalityJustification($system_criticality_justification  =  NULL){ 
		// error check input (by schema)
		if (strlen($system_criticality_justification) >= 0){
            $this->system_criticality_justification = $system_criticality_justification;
            return true;
		} // input error check
		else {
		    return false;
		}
	} // setSystemCriticalityJustification()
    
    
    public function setSystemSensitivityJustification($system_sensitivity_justification  =  NULL){ 
		// error check input (by schema)
		if (strlen($system_sensitivity_justification) >= 0){
            $this->system_sensitivity_justification = $system_sensitivity_justification;
            return true;
		} // input error check
		else {
		    return false;
		}
	} // setSystemSensitivityJustification()
    
    

} // class System
?>
