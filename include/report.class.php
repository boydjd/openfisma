<?PHP
require_once("user.class.php");

class Report {
	private $dbConn;
	private $user;
	private $errno;
	private $startdate;
	private $enddate;
	
	private $system  = NULL;
	private $source  = NULL;
	private $sy      = NULL;
    private $poam_type    = NULL;
	private $status = NULL;
	private $poam_id = NULL;
    private $overdue = NULL;
	
	
	function __construct($conn, $user) {
		$this->dbConn = $conn;
		$this->user   = $user;
	}

	function __destruct() {
	}
	function getErrno() {
		return $this->errno;
	}
	//////////////
	function setStartdate($startdate) {
		$this->startdate=$startdate;
	}
	function setEnddate($enddate) {
		$this->enddate=$enddate;
	}
	function setSystem($system){
		$this->system=$system;
	}
	function setSource($source){
		$this->source=$source;
	}
	function setSy($sy){
		$this->sy=$sy;
	}
    function setType($poam_type){
        $this->poam_type=$poam_type;
    }
	function setStatus($status){
		$this->status=$status;
	}
	function setPoamID($poam_id){
		$this->poam_id=$poam_id;
	}
    function setOverdue($overdue){
        $this->overdue=$overdue;
    }
///////////
	function getSysIDSQL($system){//construct get system_id sql,we can use it future
		return "SELECT system_id AS id
		FROM " . TN_SYSTEMS ."
		WHERE system_nickname = '$system'
		LIMIT 1 ";
	}
	function getSysGIDSQL($system){//construct get sysgroup_id sql,we can use it future
		return "SELECT sysgroup_id AS id
		FROM " . TN_SYSTEM_GROUPS . "
		WHERE sysgroup_nickname = '$system'
		LIMIT 1 ";
	}
	////



	/*
	** Retrieve the FSA system ID.
	** Call this once at the beginning of FISMA reporting.
	**
	** Input:
	**  None
	**
	** Return:
	**  $fsa_system_id - systems.system_id corresponding to FSA
	*/
	function getFSASysID($system) {
	  // Fetch AND call FSA system id query
	  $sql = $this->getSysIDSQL($system);

	  $result  = $this->dbConn->sql_query($sql) or die("Query failed: " .$sql."<br>". $this->dbConn->sql_error());

	  // throw exception if no FSA entry found
          if(!$this->dbConn->sql_numrows($result)) {
            die("getFSASysID - no entry found in SYSTEMS for FSA");
            }

	  // retrieve the one row
  	  $result= $this->dbConn->sql_fetchrow($result);
	  // return the one column in that row
	  return $result['id'];
	  }


	/*
	** Retrieve the FSA system group ID.
	** Call this once at the beginning of FISMA reporting.
	**
	** Input:
	**  None
	**
	** Return:
	**  $fsa_sysgroup_id - system_groups.sysgroup_id 
	**  corresponding to FSA
	*/
	function getFSASysGroupID($system) {
	  // Fetch and call FSA system id query
	  $sql = $this->getSysGIDSQL($system);
	
	  $result  = $this->dbConn->sql_query($sql) or die("Query failed: " .$sql."<br>". $this->dbConn->sql_error());

          // throw exception if no FSA entry found
	  if(!$this->dbConn->sql_numrows($result)) {
	    die("getFSASysGroupID - no entry found in SYSTEM_GROUPS for FSA");
	    }

	  // retrieve the one row
  	  $result= $this->dbConn->sql_fetchrow($result);
	  // return the one column in that row
	  return $result['id'];
	  }

	/*
	** Get unique system nicknames from the SYSTEMS table.
	** 
	** Input: None
	**
	** Return:
	**  result set containing system nicknames
	**   one column, aliased to 'name'
	*/
        function getSystems() {
          $system_filter = $this->getSystemFilter();

          $sql = "SELECT DISTINCT system_nickname AS name FROM " . TN_SYSTEMS . "WHERE $system_filter";
//echo "sql: $sql<br>";
          $result  = $this->dbConn->sql_query($sql) or die("Query failed: " .$sql."<br>". $this->dbConn->sql_error());                                                    return $this->dbConn->sql_fetchrowset($result);
          }

//	function getSystems() {
//	  $sql = "SELECT DISTINCT system_nickname AS name FROM SYSTEMS";
//	  $result  = $this->dbConn->sql_query($sql) or die("Query failed: " .$sql."<br>". $this->dbConn->sql_error());
//	  return $this->dbConn->sql_fetchrowset($result);
//	  }

	/*
	** Get unique finding source nicknames from the FINDING_SOURCES table.
	** 
	** Input: None
	**
	** Return:
	**  result set containing source nicknames
	**   one column, aliased to 'name'
	*/
	function getSources() {
	  $sql = "SELECT DISTINCT source_nickname AS name FROM ".TN_FINDING_SOURCES."";
	  $result  = $this->dbConn->sql_query($sql) or die("Query failed: " .$sql."<br>". $this->dbConn->sql_error());
	  return $this->dbConn->sql_fetchrowset($result);
	  }

	/*
	/*
	** Return list of POAMs filtered by creation date and optional
	** system, source, status, type.
	*/
	function getPOAMReport(){//for report 2
		$system_filter = NULL;
		$source_filter = NULL;
		$FY_filter     = NULL;
		$type_filter   = NULL;
		$status_filter = NULL;

		/*
		** Use system nickname filter if that's passed in, 
		** otherwise filter by the range of systems visible to the
		** current user
		*/
		if (!is_null($this->system) && strlen($this->system) > 0) {
		  $system_filter = " AND sys_owner.system_nickname = '$this->system'";
		  }
		else {
		  $system_filter = ' AND sys_owner.' . $this->getSystemFilter();
		  }

		if (!is_null($this->source) && strlen($this->source) > 0) {
		  $source_filter = " AND fins.source_nickname = '$this->source'";
		  }

		if (!is_null($this->sy) && strlen($this->sy) > 0) {
		  $begin_date = $this->sy . "-01-01";
		  $end_date   = $this->sy . "-12-31";
		  $FY_filter =  " AND p.poam_date_created >= '$begin_date'";
		  $FY_filter .= " AND p.poam_date_created <= '$end_date'";
		  }
        if(!is_null($this->poam_type) && strlen($this->poam_type) > 0) {
            $type_filter = " AND p.poam_type = '$this->poam_type'";
		}

		//
		// $this->status is an array like this:
		//  [0] -> 'Open'
		//  [1] -> 'Closed'
		// with items only existing if they've been checked on
		// the HTML form.
		// Convert this to an IN clause like ('OPEN', 'CLOSED')
		//

        if($this->status) {
            switch ($this->status) {
                case "":
                    break;
                case "closed":
                    $status_filter = " AND p.poam_status = 'closed'";
                    break;
                case "open":
                    $status_filter = " AND p.poam_status != 'closed'";
                    break;
              }              
         }
          
         if($this->status == "open" && $this->overdue) {
              switch ($this->overdue) {
                 case "":
                      break;
                 case "30":
                     $overdue_filter = " AND p.poam_date_created > SUBDATE(NOW(), 30) AND p.poam_date_created < NOW()";
                     break;
                 case "60":
                     $overdue_filter = " AND p.poam_date_created < SUBDATE(NOW(),30) AND p.poam_date_created > SUBDATE(NOW(),60)";
                     break;
                 case "90":
                     $overdue_filter = " AND p.poam_date_created < SUBDATE(NOW(),60) AND p.poam_date_created > SUBDATE(NOW(),90)";
                     break;
                 case "120":
                     $overdue_filter = " AND p.poam_date_created < SUBDATE(NOW(),90) AND p.poam_date_created > SUBDATE(NOW(),120)";
                     break;
                 case "greater":
                     $overdue_filter = " AND p.poam_date_created < SUBDATE(NOW(),120)";
                     break;
		     }
         }
         
         
         if($this->status == "en" && $this->overdue) {
              switch ($this->overdue) {
                 case "":
                      break;
                 case "30":
                     $overdue_filter = " AND p.poam_action_date_est > SUBDATE(NOW(), 30) AND p.poam_action_date_est < NOW()";
                     break;
                 case "60":
                     $overdue_filter = " AND p.poam_action_date_est < SUBDATE(NOW(),30) AND p.poam_action_date_est > SUBDATE(NOW(),60)";
                     break;
                 case "90":
                     $overdue_filter = " AND p.poam_action_date_est < SUBDATE(NOW(),60) AND p.poam_action_date_est > SUBDATE(NOW(),90)";
                     break;
                 case "120":
                     $overdue_filter = " AND p.poam_action_date_est < SUBDATE(NOW(),90) AND p.poam_action_date_est > SUBDATE(NOW(),120)";
                     break;
                 case "greater":
                     $overdue_filter = " AND p.poam_action_date_est < SUBDATE(NOW(),120)";
                     break;
		     }
         }
		/*
		** Existence of a particular poam_id request overrides
		** any other filters.
		*/
		if($this->poam_id) {
		  $query_filter = " AND p.poam_id = " . $this->poam_id;
		  }
		else {
 		  // if not a specific poam, concatenate the other criteria
		  $query_filter = "$system_filter
				   $source_filter
				   $FY_filter
                   $type_filter
                   $status_filter
                   $overdue_filter";
		  }

		/*
		** Until the primary office is associated with an explicit
		** table, set it to 'FSA'
		*/
		$sql="SELECT
		'FSA' po,
		sys_owner.system_nickname system,
		sys.system_tier tier,
		p.poam_id findingnum,
		fin.finding_data finding,
		p.poam_type ptype,
		p.poam_status pstatus,
		fins.source_nickname source,
		aadd.address_ip SD,
		net.network_nickname location,
		sys.system_availability availability,
		sys.system_integrity integrity,
		sys.system_confidentiality confidentiality,
		p.poam_action_suggested recommendation ,
		p.poam_action_planned correctiveaction,
		p.poam_cmeasure_effectiveness effectiveness,
		p.poam_threat_level threatlevel,
		p.poam_action_date_est EstimatedCompletionDate
		FROM " . TN_POAMS . " p,
		".TN_SYSTEMS." sys,
		".TN_SYSTEMS." sys_owner,
		".TN_FINDINGS." fin,
		".TN_ASSETS." a 
        LEFT JOIN ".TN_ASSET_ADDRESSES." aadd 
            ON (aadd.asset_id = a.asset_id) 
        LEFT JOIN ".TN_NETWORKS." AS net ON 
            (net.network_id = aadd.network_id),
		".TN_FINDING_SOURCES." fins,
		".TN_SYSTEM_ASSETS." sa
		WHERE fin.finding_id = p.finding_id AND
		a.asset_id = fin.asset_id AND
		sa.asset_id = a.asset_id AND
 		sys.system_id = sa.system_id AND
		sa.system_is_owner = 1 AND
		fins.source_id = fin.source_id AND
		sys_owner.system_id = p.poam_action_owner
		$query_filter
		";
		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " .$sql."<br>". $this->dbConn->sql_error());
		return $this->dbConn->sql_fetchrowset($result);
				
	}
	////

	/*
	** SQL to generate unqualified list of all POAMs 
	** agency wide.
	** The intent is to attach filter clauses to the end
	** of this string to generate full queries to address
	** the agency wide items below.
	**
	** Inputs:
	**  fsa_system_id - systems.system_id corresponding to FSA
	** Return:
	**  SQL query string returning all POAMs for fsa_system
	**   Column of interest is aliased as 'num_poams'
	*/
	function getAgencyWidePOAMs($fsa_system_id) {
	  return ("SELECT 
	    COUNT(DISTINCT p.poam_id) AS num_poams
            FROM " . TN_POAMS ."
             p, 
            ".TN_FINDINGS." f,
            ".TN_ASSETS." a,
            ".TN_SYSTEM_ASSETS." sa
            WHERE
            f.finding_id = p.finding_id AND
            a.asset_id = f.asset_id AND
            sa.asset_id = a.asset_id AND
            sa.system_id = $fsa_system_id");
          }

	/*
	** SQL to generate unqualified list of all POAMs 
	** for FSA system group not including the FSA system itself.
	** The intent is to attach filter clauses to the end
	** of this string to generate full queries to address
	** the FSA system items below.
	**
	** Inputs:
	**  fsa_system_id - systems.system_id corresponding to FSA
	**  fsa_group_id - system_groups.sysgroup_id corresponding to FSA
	** Return:
	**  SQL query string returning all POAMs for systems in the FSA
	**   system group other than FSA itself.
	**   Column of interest is aliased as 'num_poams'
	*/
	function getSystemPOAMs($fsa_system_id, $fsa_group_id) {
	  return ("SELECT 
	    COUNT(DISTINCT p.poam_id) AS num_poams
            FROM " . TN_POAMS . "
             p, 
            ".TN_FINDINGS." f,
            ".TN_ASSETS." a,
            ".TN_SYSTEM_ASSETS." sa
            WHERE
            f.finding_id = p.finding_id AND
            a.asset_id = f.asset_id AND
            sa.asset_id = a.asset_id AND
            sa.system_id IN (SELECT system_id 
              FROM " . TN_SYSTEM_GROUP_SYSTEMS . "
              WHERE (sysgroup_id = $fsa_group_id 
                AND system_id != $fsa_system_id)
            )");
	  }


	/*
	** FISMA 'A' report fields
	** getAFilter()
	** getAAgencyWide()
	** getASystem()
	**
	** Provide total number of POAMs that match the criteria
	** "Total number of weaknesses identified at the start of 
	**  the [report period]"
	** for the FSA system (getAAgencyWide)  and all FSA group systems 
	** other than FSA itself (getASystem)
	*/

	function getAFilter () {
	  // Filter list to those that
	  //  a) were created before the report start date
	  //  b) were closed after the start date or remain unclosed
	  return("AND p.poam_date_created < '$this->startdate'
                  AND (p.poam_date_closed IS NULL
                       OR p.poam_date_closed >= '$this->startdate')");
	  }

	function getAAgencyWide($fsa_system_id){
		// Retrieve unqualified FSA agency POAM count SQL
		$all_FSA_POAMs = $this->getAgencyWidePOAMs($fsa_system_id);
		$filter = $this->getAFilter();

		// Combine full POAM count with filter to create query
		$sql = "$all_FSA_POAMs $filter";
//echo "$sql<br/>";

		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " .$sql."<br>". $this->dbConn->sql_error());
		$result= $this->dbConn->sql_fetchrow($result);
		return $result['num_poams'];

	}
	function getASystem($fsa_system_id, $fsa_group_id){
		// Retrieve unqualified FSA agency POAM count SQL
		$all_FSA_POAMs = $this->getSystemPOAMs($fsa_system_id, $fsa_group_id);
		$filter = $this->getAFilter();

		// Combine full POAM count with filter to create query
		$sql = "$all_FSA_POAMs $filter";
//echo "$sql<br/>";

		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " .$sql."<br>". $this->dbConn->sql_error());

		$result= $this->dbConn->sql_fetchrow($result);
		return $result['num_poams'];
	}

	/*
	** FISMA 'B' report fields
	** getBFilter()
	** getBAgencyWide()
	** getBSystem()
	**
	** Provide total number of POAMs that match the criteria
	** "Number of weaknesses for which corrective action was completed
	**  on time (including testing) by the end of the [report period]"
	** for the FSA system (getBAgencyWide)  and all FSA group systems 
	** other than FSA itself (getBSystem)
	*/

	function getBFilter() {
	  // Filter POAM results to those that
	  //  a) were created before the report end date
	  //  b) were expected to be complete by the end date
	  //  c) were acted upon after the report start date
	  //  d) and acted upon before the report end date
	  return("AND p.poam_date_created <= '$this->enddate'
	          AND p.poam_action_date_est <= '$this->enddate'
	          AND p.poam_action_date_actual >= '$this->startdate'
	          AND p.poam_action_date_actual <= '$this->enddate'");
	  }

	function getBAgencyWide($fsa_system_id){
		// Retrieve unqualified FSA agency POAM count SQL
		$all_FSA_POAMs = $this->getAgencyWidePOAMs($fsa_system_id);

		$filter = $this->getBFilter();

		// Combine full POAM count with filter to create query
		$sql = "$all_FSA_POAMs $filter";
//echo "$sql<br/>";
		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " .$sql."<br>". $this->dbConn->sql_error());
		$result= $this->dbConn->sql_fetchrow($result);
		return $result['num_poams'];

	}
	function getBSystem($fsa_system_id, $fsa_group_id){
		// Retrieve unqualified FSA agency POAM count SQL
		$all_FSA_POAMs = $this->getSystemPOAMs($fsa_system_id, $fsa_group_id);

		$filter = $this->getBFilter();

		// Combine full POAM count with filter to create query
		$sql = "$all_FSA_POAMs $filter";
//echo "$sql<br/>";

		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " .$sql."<br>". $this->dbConn->sql_error());

		$result= $this->dbConn->sql_fetchrow($result);
		return $result['num_poams'];
	}

	/*
	** FISMA 'C' report fields
	** getCFilter()
	** getCAgencyWide()
	** getCSystem()
	**
	** Provide total number of POAMs that match the criteria
	** "Number of weaknesses for which corrective action is ongoing
	**  and is on track to complete as originally scheduled"
	** for the FSA system (getCAgencyWide)  and all FSA group systems 
	** other than FSA itself (getCSystem)
	*/

	function getCFilter() {
	  // Filter POAM results to those that
	  //  a) have creation date prior to report end date
	  //  a) have estimated completion dates beyond the report end date
	  //  b) have not been completed
	  return("AND p.poam_date_created <= '$this->enddate'
		  AND p.poam_action_date_est > '$this->enddate'
		  AND p.poam_action_date_actual IS NULL");
	  }

	function getCAgencyWide($fsa_system_id){
		// Retrieve unqualified FSA agency POAM count SQL
		$all_FSA_POAMs = $this->getAgencyWidePOAMs($fsa_system_id);

		$filter = $this->getCFilter();

		// Combine full POAM count with filter to create query
		$sql = "$all_FSA_POAMs $filter";
//echo "$sql<br/>";
		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " .$sql."<br>". $this->dbConn->sql_error());
		$result= $this->dbConn->sql_fetchrow($result);
		return $result['num_poams'];

	}
	function getCSystem($fsa_system_id, $fsa_group_id){
		// Retrieve unqualified FSA agency POAM count SQL
		$all_FSA_POAMs = $this->getSystemPOAMs($fsa_system_id, $fsa_group_id);

		$filter = $this->getCFilter();

		// Combine full POAM count with filter to create query
		$sql = "$all_FSA_POAMs $filter";
//echo "$sql<br/>";

		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " .$sql."<br>". $this->dbConn->sql_error());

		$result= $this->dbConn->sql_fetchrow($result);
		return $result['num_poams'];
	}
	
	/*
	** FISMA 'D' report fields
	** getDFilter()
	** getDAgencyWide()
	** getDSystem()
	**
	** Provide total number of POAMs that match the criteria
	** "Number of weaknesses for which corrective action has been delayed
	**  including a brief explanation for the delay"
	** for the FSA system (getDAgencyWide)  and all FSA group systems 
	** other than FSA itself (getDSystem)
	*/

	function getDFilter() {
	  // Filter POAM results to those that
	  //  a) have an estimated action date prior to the report end date
	  //  b) have not had action taken
	  //  c) or have had action taken after the report end date
	  return("AND p.poam_action_date_est <= '$this->enddate'
		  AND (p.poam_action_date_actual IS NULL
		       OR p.poam_action_date_actual > '$this->enddate')");
	  }

	function getDAgencyWide($fsa_system_id){
		// Retrieve unqualified FSA agency POAM count SQL
		$all_FSA_POAMs = $this->getAgencyWidePOAMs($fsa_system_id);

		$filter = $this->getDFilter();

		// Combine full POAM count with filter to create query
		$sql = "$all_FSA_POAMs $filter";
//echo "$sql<br/>";
		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " .$sql."<br>". $this->dbConn->sql_error());
		$result= $this->dbConn->sql_fetchrow($result);
		return $result['num_poams'];

	}
	function getDSystem($fsa_system_id, $fsa_group_id){
		// Retrieve unqualified FSA agency POAM count SQL
		$all_FSA_POAMs = $this->getSystemPOAMs($fsa_system_id, $fsa_group_id);

		$filter = $this->getDFilter();

		// Combine full POAM count with filter to create query
		$sql = "$all_FSA_POAMs $filter";
//echo "$sql<br/>";

		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " .$sql."<br>". $this->dbConn->sql_error());

		$result= $this->dbConn->sql_fetchrow($result);
		return $result['num_poams'];
	}
		
	/*
	** FISMA 'E' report fields
	** getEFilter()
	** getEAgencyWide()
	** getESystem()
	**
	** Provide total number of POAMs that match the criteria
	** "Number of weaknesses discovered following the last POAM update
	**  and a brief explanation of how they were identified"
	** for the FSA system (getEAgencyWide)  and all FSA group systems 
	** other than FSA itself (getESystem)
	*/

	function getEFilter() {
	  // Filter POAM results to those that
	  //  a) were created since the report start date
	  //  b) and were created before the report end date
	  return("AND p.poam_date_created >= '$this->startdate'
		  AND p.poam_date_created <= '$this->enddate'");
	  }

	function getEAgencyWide($fsa_system_id){
		// Retrieve unqualified FSA agency POAM count SQL
		$all_FSA_POAMs = $this->getAgencyWidePOAMs($fsa_system_id);

		$filter = $this->getEFilter();

		// Combine full POAM count with filter to create query
		$sql = "$all_FSA_POAMs $filter";
//echo "$sql<br/>";
		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " .$sql."<br>". $this->dbConn->sql_error());
		$result= $this->dbConn->sql_fetchrow($result);
		return $result['num_poams'];

	}
	function getESystem($fsa_system_id, $fsa_group_id){
		// Retrieve unqualified FSA agency POAM count SQL
		$all_FSA_POAMs = $this->getSystemPOAMs($fsa_system_id, $fsa_group_id);

		$filter = $this->getEFilter();

		// Combine full POAM count with filter to create query
		$sql = "$all_FSA_POAMs $filter";
//echo "$sql<br/>";

		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " .$sql."<br>". $this->dbConn->sql_error());

		$result= $this->dbConn->sql_fetchrow($result);
		return $result['num_poams'];
	}

	/*
	** FISMA 'F' report fields
	** getFFilter()
	** getFAgencyWide()
	** getFSystem()
	**
	** Provide total number of POAMs that match the criteria
	**
	** for the FSA system (getFAgencyWide)  and all FSA group systems 
	** other than FSA itself (getFSystem)
	*/

	function getFFilter() {
	  // Filter POAM results to those that
	  //  a) have creation date before report end date
	  //  b) have never been closed
	  //  c)  or were closed after the report end date
	  return("AND p.poam_date_created <= '$this->enddate'
		  AND (p.poam_date_closed IS NULL
		  OR p.poam_date_closed > '$this->enddate')");
	  }

	function getFAgencyWide($fsa_system_id){
		// Retrieve unqualified FSA agency POAM count SQL
		$all_FSA_POAMs = $this->getAgencyWidePOAMs($fsa_system_id);

		$filter = $this->getFFilter();

		// Combine full POAM count with filter to create query
		$sql = "$all_FSA_POAMs $filter";
//echo "$sql<br/>";
		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " .$sql."<br>". $this->dbConn->sql_error());
		$result= $this->dbConn->sql_fetchrow($result);
		return $result['num_poams'];

	}
	function getFSystem($fsa_system_id, $fsa_group_id){
		// Retrieve unqualified FSA agency POAM count SQL
		$all_FSA_POAMs = $this->getSystemPOAMs($fsa_system_id, $fsa_group_id);

		$filter = $this->getFFilter();

		// Combine full POAM count with filter to create query
		$sql = "$all_FSA_POAMs $filter";
//echo "$sql<br/>";

		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " .$sql."<br>". $this->dbConn->sql_error());

		$result= $this->dbConn->sql_fetchrow($result);
		return $result['num_poams'];
	}
	
	/*
	** NIST Baseline Security Control Report
	**
	** Retrieves counts of POAMs by BLSCR id number. Count listings
	** are broken out by BLSCR class (MANAGEMENT, OPERATIONAL, TECHNICAL).
	** 
	** Input:
	**  None
	**
	** Return:
	**  Array containing three result sets in the order:
	**   MANAGEMENT, OPERATIONAL, TECHNICAL
	**  Each row in each result set contains two fields:
	**   't' - BLSCR type code
	**   'n' - number of results found for that code
	**         may be zero
	*/
	function getReport31(){
		$arr_tmp= array();
	
		// Retrieve MANAGEMENT results.
		// Use outer join to get zero-count entries.	
		$sql = "SELECT b.blscr_number AS t, count(p.poam_id) AS n
		  FROM " . TN_POAMS . " p 
		  RIGHT OUTER JOIN " . TN_BLSCR . " b ON p.poam_blscr = b.blscr_number
		  WHERE b.blscr_class = 'MANAGEMENT'
		  GROUP BY b.blscr_number";
		
		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " .$sql."<br>". $this->dbConn->sql_error());
		
		// Add rowset to return array
		array_push ($arr_tmp, $this->dbConn->sql_fetchrowset($result));

		// Retrieve OPERATIONAL results.
		// Use outer join to get zero-count entries.	
		$sql = "SELECT b.blscr_number AS t, count(p.poam_id) AS n
		  FROM " . TN_POAMS ." p 
		  RIGHT OUTER JOIN " . TN_BLSCR . " b ON p.poam_blscr = b.blscr_number
		  WHERE b.blscr_class = 'OPERATIONAL'
		  GROUP BY b.blscr_number";

		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " .$sql."<br>". $this->dbConn->sql_error());
		
		// Add rowset to return array
		array_push ($arr_tmp, $this->dbConn->sql_fetchrowset($result));

		// Retrieve TECHNICAL results.
		// Use outer join to get zero-count entries.	
		$sql = "SELECT b.blscr_number AS t, count(p.poam_id) AS n
		  FROM " . TN_POAMS . "p 
		  RIGHT OUTER JOIN " . TN_BLSCR . " b ON p.poam_blscr = b.blscr_number
		  WHERE b.blscr_class = 'TECHNICAL'
		  GROUP BY b.blscr_number";

		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " .$sql."<br>". $this->dbConn->sql_error());
		
		// Add rowset to return array
		array_push ($arr_tmp, $this->dbConn->sql_fetchrowset($result));
		
		// Return array of rowsets
		return $arr_tmp;
	}

	/*
	** FIPS 199 Category Breakdown
	** 
	** Returns a simple list of records from SYSTEMS table.
	**
	** Input:
	**  None
	** 
	** Return:
	**  sql rowset containing result rows
	*/
	function getReport32(){
		// Get a simple list of system name, type, CIA stats.
		// FIPS 199 category will be derived from CIA data.
		$sql = "SELECT s.system_name AS name,
			s.system_type AS type, 
			s.system_confidentiality AS conf, 
			s.system_integrity AS integ, 
			s.system_availability AS avail,
			'n/a' AS last_upd
			FROM " . TN_SYSTEMS . "s";

		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " .$sql."<br>". $this->dbConn->sql_error());
		
		$rowset = $this->dbConn->sql_fetchrowset($result);
		
		return $rowset;
	}
//	function getReport32(){
//		$arr_tmp= array();
//		$arr_tmp2= array();
//		//Low Moderate High 
//		$sql="select FIPS199Catagory as t,count(*) as n FROM xxx where xxx='yyy' group by FIPS199Catagory";
//		$sql="select 'Low' as t,123 as n union 
//					select 'Moderate' as t,234 as n union 
//					select 'High' as t,345 as n 
//					";
//		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " .$sql."<br>". $this->dbConn->sql_error());
//		$arr_tmp2=$this->dbConn->sql_fetchrowset($result);
//		
//		//put 3 value to return array. 0:Low 1:Moderate 2:High 
//		array_push ($arr_tmp, array($arr_tmp2[0]['n'],$arr_tmp2[1]['n'],$arr_tmp2[2]['n']));
//		
//		$sql="select System Name System Type Mission Criticalty FIPS 199 Catagory Confidentiality 
//		Integrity Availability Last Invertory Update  from xxx ";
//		$sql="SELECT 1 as SysNam,2 as SystTyp,3 as MisCri,4 as FIP199Cat,5 as Con,6 as Integrity,
//		7 as Ava,8 as LasInvUpd";//comment this line if the upon sql can run.
//		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " .$sql."<br>". $this->dbConn->sql_error());
//		array_push ($arr_tmp, $this->dbConn->sql_fetchrowset($result));
//		
//		return $arr_tmp;
//	}
	/*
	** Products with open vulnerabilities query
	**
	** Links non-closed POAMs to PRODUCTS via FINDINGS and ASSETS.
	**
	** Input:
	**  None
	** 
	** Return:
	**  sql rowset containing result rows
	*/
	function getReport33(){
		$sql = "SELECT prod.prod_vendor AS Vendor, 
			prod.prod_name AS Product, 
			prod.prod_version AS Version, 
			count(prod.prod_id) AS NumoOV
			FROM " . TN_POAMS . "p, " . TN_FINDINGS . " f, " . TN_ASSETS . " a, " . TN_PRODUCTS . " prod
			WHERE p.poam_status IN ('OPEN', 'EN', 'EP', 'ES')
			AND f.finding_id = p.finding_id
			AND a.asset_id = f.asset_id 
			AND prod.prod_id = a.prod_id 
			GROUP BY prod.prod_vendor, prod.prod_name, prod.prod_version";
		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " .$sql."<br>". $this->dbConn->sql_error());
		return $this->dbConn->sql_fetchrowset($result);
	}
	/*
	** Software discovered through vulnerability assessment query
	**
	** Lists distinct PRODUCTS for ASSETS of source SCAN
	**
	** Input:
	**  None
	** 
	** Return:
	**  sql rowset containing result rows
	*/
	function getReport34(){
		$sql = "SELECT DISTINCT 
			p.prod_vendor AS Vendor, 
			p.prod_name AS Product,
			p.prod_version AS Version
			FROM " . TN_PRODUCTS . "p, " . TN_ASSETS . " a
			WHERE a.asset_source = 'SCAN'
			AND p.prod_id = a.prod_id";
		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " .$sql."<br>". $this->dbConn->sql_error());
		return $this->dbConn->sql_fetchrowset($result);
	}
	/*
	** Total number of systems with open vulnerabilities query
	**
	** Links open POAMS to SYSTEMS via FINDINGS, ASSETS, SYSTEM_ASSETS.
	**
	** Input:
	**  None
	** 
	** Return:
	**  array of 2 sql rowsets
	**   1: total count of systems with vulnerabilities
	**   2: list of vulnerability count by system
	*/
	function getReport35(){
		$arr_tmp= array();

		//
		// Retrieve the number of open vulnerabilities for each
		// system_nickname
		//
		$sql="SELECT sys.system_nickname AS sysnick, 
		      COUNT(sys.system_id) AS vulncount
		      FROM " . TN_POAMS . "p, " . TN_FINDINGS . " f, 
		      " . TN_ASSETS . " a, " . TN_SYSTEM_ASSETS . " sa, " . TN_SYSTEMS . " sys
		      WHERE p.poam_type IN ('CAP', 'AR', 'FP') 
		      AND p.poam_status IN ('OPEN', 'EN', 'EP', 'ES')
		      AND f.finding_id = p.finding_id
		      AND a.asset_id = f.asset_id
		      AND sa.asset_id = a.asset_id
		      AND sys.system_id = sa.system_id
		      GROUP BY (sa.system_id)";
		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " .$sql."<br>". $this->dbConn->sql_error());
		$sys_vulncounts = $this->dbConn->sql_fetchrowset($result);
		//

		//
		// Get a list of unique system nicknames from SYSTEMS table
		// - this is used to initialize the range of systems to 0
		//		
		$sql = "SELECT DISTINCT system_nickname 
			FROM " . TN_SYSTEMS;
		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " .$sql."<br>". $this->dbConn->sql_error());
		$systems = $this->dbConn->sql_fetchrowset($result);

		//
		// Initialize a hash to all zero for each nickname
		//
		$system_totals = array();
		foreach($systems as $system_row) {
		  $system_nick = $system_row['system_nickname'];
		  $system_totals[$system_nick] = 0;
		  }

		//
		// Set hash values to total vulnerabilities for known open
		// keys.
		// Track overall total of open vulnerabilities.
		//	
		$total_open = 0;
		foreach((array)$sys_vulncounts as $sv_row) {
		  $system_nick = $sv_row['sysnick'];
		  $system_totals[$system_nick] = $sv_row['vulncount'];
//		echo "$system_nick ";
		  $total_open++;
		  }

		//
		// Convert totals hash to array of hashes to imitate a
		// query return - Smarty will be happier with this.
		//
		$system_total_array = array();
		foreach(array_keys($system_totals) as $key) {
		  $val = $system_totals[$key];
		  $this_row = array();
		  $this_row['nick'] = $key;
		  $this_row['num']  = $val;
		  array_push($system_total_array, $this_row);
		  }

		//echo $sql;
		array_push($arr_tmp, $total_open);
		array_push($arr_tmp, $system_total_array);
		return $arr_tmp;
		}	

	function getSystemFilter() {
          $system_array = $this->user->getSystemIdsByRole();

	  $system_list = '';
	  if(is_array($system_array)) {
   	    foreach($system_array as $sys_id) {
	      $system_list .= (strlen($system_list) > 0) ? ",$sys_id" : "$sys_id";
	      }
	    }

	  // Keep sql from failing if there are no systems returned
	  $system_list = (strlen($system_list) > 0) ? $system_list : '-1';

          $system_filter = "system_id in ($system_list)";

	  return $system_filter;
	  }

}


?>



