<?PHP
class Raf {
	private $dbConn;
	private $errno;
	private $poam_id;
	private $enddate;
	
	
	
	function __construct($conn) {
		$this->dbConn = $conn;
	}

	function __destruct() {
	}
	function getErrno() {
		return $this->errno;
	}
	//////////////
	function setPoam_id($poam_id) {
		$this->poam_id=mysql_real_escape_string($poam_id);
	}
	function getPoam_id() {
		return $this->poam_id;
	}	
	////
	function getWeaknessVulnerabilityTrackingNO() {
		//////fake code
		return $this->poam_id;
	}

	/*
	** The main POAM query.
	** Get POAM attributes, finding source, system attributes.
	**
	** Input:
	**  none 
	**
	** Return:
	**  single query return row with POAM stats.
	*/
	function getPOAMFields() {
	  $poam_id = $this->poam_id;

	  $sql =  "SELECT p.poam_date_created AS dt_created,
              		  p.poam_date_modified AS dt_mod,
              		  p.poam_date_closed AS dt_closed,
              		  p.poam_is_repeat AS is_repeat,
              		  p.poam_previous_audits AS prev,
              		  CASE p.poam_type 
                      WHEN 'NONE' THEN 'None'
                      WHEN 'AR' THEN 'Accepted Risk'
                      WHEN 'CAP' THEN 'Corrective Action Plan'
                      WHEN 'FP' THEN 'False Positive'
                      END AS poam_type,
                    p.poam_type poam_type_code,
                    CASE p.poam_status
                      WHEN 'OPEN' THEN 'Open'
                      WHEN 'EN' THEN 'Evidence Needed'
                      WHEN 'EP' THEN 'Evidence Provided'
                      WHEN 'ES' THEN 'Evidence Submitted'
                      WHEN 'CLOSED' THEN 'Closed'
                      END AS poam_status,
              		  p.poam_cmeasure AS cm,
              		  p.poam_cmeasure_effectiveness AS cm_eff,
              		  p.poam_cmeasure_justification AS cm_just,
              		  p.poam_threat_source AS t_source,
              		  p.poam_threat_level AS t_level,
              		  p.poam_threat_justification AS t_just,
              		  p.poam_action_suggested AS act_sug,
              		  p.poam_action_planned AS act_plan,
              		  p.poam_action_date_est,
              		  f.finding_data,
              		  f.finding_date_discovered AS dt_discv,
              		  fs.source_name,
              		  a.asset_name,
              		  s.system_availability AS s_a,
              		  s.system_integrity AS s_i,
              		  s.system_confidentiality s_c,
              		  s.system_criticality,
              		  s.system_criticality_justification AS s_c_just,
              		  s.system_sensitivity_justification AS s_s_just,
              		  s.system_primary_office AS s_po,
              		  s.system_nickname AS s_nick
               FROM POAMS p, 
                    FINDINGS f, 
                    SYSTEMS s, 
                    FINDING_SOURCES fs, 
                    ASSETS a		 
              WHERE p.finding_id = f.finding_id 
                AND fs.source_id = f.source_id 
                AND s.system_id = p.poam_action_owner 
                AND a.asset_id = f.asset_id
                AND p.poam_id={$this->poam_id}";


		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " .$sql."<br>". $this->dbConn->sql_error());
		// throw exception if no FSA entry found
		if(!$this->dbConn->sql_numrows($result)) {
		  die("getPOAMFields - unable to retrieve POAM results for POAM id $poam_id");
		}
		$row = $this->dbConn->sql_fetchrow($result);

		return($row);
		}

	/*
	** Get vulnerability description(s) associated with this POAM.
	**
	** Input:
	**  none 
	**
	** Return:
	**  rowset containing vulnerability descriptions.
	*/
	function getVulnDescriptions() {
          $poam_id = $this->poam_id;

	  $sql = "SELECT v.vuln_desc_primary AS vuln
		  FROM " . TN_POAMS . " p, " . TN_FINDINGS . " f, " . TN_FINDING_VULNS . " fv, " . TN_VULNERABILITIES . " v
		  WHERE p.poam_id = '$poam_id'
		  AND f.finding_id = p.finding_id
		  AND fv.finding_id = f.finding_id
		  AND v.vuln_seq = fv.vuln_seq
		  AND v.vuln_type = fv.vuln_type";

	  $result  = $this->dbConn->sql_query($sql) or die("Query failed: " .$sql."<br>". $this->dbConn->sql_error());
	  return $this->dbConn->sql_fetchrowset();
	  }

	/*
	** Get asset name(s) associated with this POAM.
	**
	** Input:
	**  none 
	**
	** Return:
	**  rowset containing asset names.
	*/
	function getAssetNames() {
          $poam_id = $this->poam_id;

	  $sql = "SELECT prod.prod_name AS pname
		  FROM " . TN_POAMS . " p, " . TN_FINDINGS . " f, " . TN_ASSETS . " a, " . TN_PRODUCTS . " prod
		  WHERE p.poam_id = '$poam_id'
		  AND f.finding_id = p.finding_id
		  AND a.asset_id = f.asset_id
		  AND prod.prod_id = a.prod_id";

	  $result  = $this->dbConn->sql_query($sql) or die("Query failed: " .$sql."<br>". $this->dbConn->sql_error());
	  return $this->dbConn->sql_fetchrowset($result);
  	  }
}


?>
