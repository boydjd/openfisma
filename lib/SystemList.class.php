<?PHP

// 
// INCLUDES
// 
require_once('Database.class.php');
require_once('BasicList.class.php');


//
// CLASS DEFINITION
// 

class SystemList extends BasicList {

  // -----------------------------------------------------------------------
  // 
  // CLASS METHODS
  // 
  // -----------------------------------------------------------------------
  
  public function __construct($db = NULL) { 

	// call the parent constructor with the db connection and table name
	parent::__construct($db, 'SYSTEMS'); 

  } // __construct()
  

  public function __destruct() {

	// call the parent destructor
	parent::__destruct();

  } // __destruct()
  
  public function __ToString() {} // __ToString()
 	

  // -----------------------------------------------------------------------
  // 
  // PARAMETERS
  // 
  // -----------------------------------------------------------------------
  
  public function getSystemId($isKey = FALSE)                                       { array_push($this->params, 'system_id');                                         if ($isKey) { $this->key = 'system_id'; } }
  public function getSystemName($isKey = FALSE)                                     { array_push($this->params, 'system_name');                                       if ($isKey) { $this->key = 'system_name'; } }
  public function getSystemNickname($isKey = FALSE)                                 { array_push($this->params, 'system_nickname');                                   if ($isKey) { $this->key = 'system_nickname'; } }
  public function getSystemDesc($isKey = FALSE)                                     { array_push($this->params, 'system_desc');                                       if ($isKey) { $this->key = 'system_desc'; } }
  public function getSystemType($isKey = FALSE)                                     { array_push($this->params, 'system_type');                                       if ($isKey) { $this->key = 'system_type'; } }
  public function getSystemPrimaryOffice($isKey = FALSE)                            { array_push($this->params, 'system_primary_office');                             if ($isKey) { $this->key = 'system_primary_office'; } }
  public function getSystemAvailability($isKey = FALSE)                             { array_push($this->params, 'system_availability');                               if ($isKey) { $this->key = 'system_availability'; } }
  public function getSystemIntegrity($isKey = FALSE)                                { array_push($this->params, 'system_integrity');                                  if ($isKey) { $this->key = 'system_integrity'; } }
  public function getSystemConfidentiality($isKey = FALSE)                          { array_push($this->params, 'system_confidentiality');                            if ($isKey) { $this->key = 'system_confidentiality'; } }
  public function getSystemTier($isKey = FALSE)                                     { array_push($this->params, 'system_tier');                                       if ($isKey) { $this->key = 'system_tier'; } }
  public function getSystemCriticalityJustification($isKey = FALSE)                 { array_push($this->params, 'system_criticality_justification');                  if ($isKey) { $this->key = 'system_criticality_justification'; } }
  public function getSystemSensitivityJustification($isKey = FALSE)                 { array_push($this->params, 'system_sensitivity_justification');                  if ($isKey) { $this->key = 'system_sensitivity_justification'; } }  

  // -----------------------------------------------------------------------
  // 
  // FILTERS
  // 
  // -----------------------------------------------------------------------
  
  public function filterSystemId($value = NULL, $bool = TRUE)                                       { $this->filters['system_id']                                         = array($value, $bool); }
  public function filterSystemName($value = NULL, $bool = TRUE)                                     { $this->filters['system_name']                                       = array($value, $bool); }
  public function filterSystemNickname($value = NULL, $bool = TRUE)                                 { $this->filters['system_nickname']                                   = array($value, $bool); }
  public function filterSystemDesc($value = NULL, $bool = TRUE)                                     { $this->filters['system_desc']                                       = array($value, $bool); }
  public function filterSystemType($value = NULL, $bool = TRUE)                                     { $this->filters['system_type']                                       = array($value, $bool); }
  public function filterSystemPrimaryOffice($value = NULL, $bool = TRUE)                            { $this->filters['system_primary_office']                             = array($value, $bool); }
  public function filterSystemAvailability($value = NULL, $bool = TRUE)                             { $this->filters['system_availability']                               = array($value, $bool); }
  public function filterSystemIntegrity($value = NULL, $bool = TRUE)                                { $this->filters['system_integrity']                                  = array($value, $bool); }
  public function filterSystemConfidentiality($value = NULL, $bool = TRUE)                          { $this->filters['system_confidentiality']                            = array($value, $bool); }
  public function filterSystemTier($value = NULL, $bool = TRUE)                                     { $this->filters['system_tier']                                       = array($value, $bool); }
  public function filterSystemCriticalityJustification($value = NULL, $bool = TRUE)                 { $this->filters['system_criticality_justification']                  = array($value, $bool); }
  public function filterSystemSensitivityJustification($value = NULL, $bool = TRUE)                 { $this->filters['system_sensitivity_justification']                  = array($value, $bool); }  

} // class SystemList

?>