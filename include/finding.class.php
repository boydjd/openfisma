<?PHP

require_once("pubfunc.php");

class Finding {
	private $dbConn;
	private $errno;
	public $finding_id;
	public $source_id;
	public $source_name;
	public $finding_status;
	public $finding_date_created;
	public $finding_date_discovered;
	public $finding_date_closed;
	public $finding_data;
	
	public $asset_obj;
	
	private $vulner_flag;
	public $vulner_brief;
	public $vulner_arr;
	public $vulnerability_arr;

	function __construct($fid, $dblink, $vflag = false) {
		$this->errno = 0; 
		$this->finding_id = $fid;
		$this->vulner_flag = $vflag;
		$this->dbConn = $dblink;
		if($fid > 0) {
			$this->init($fid);
		}
	}

	function __destruct() {
	}

	function getErrno() {
		return $this->errno;
	}

	function init($fid) {
		$this->errno = 1;

		if($fid > 0) {
			$sql = "SELECT f.finding_id,f.source_id,fs.source_name,f.asset_id,f.finding_status,f.finding_date_created,
							DATE_FORMAT(f.finding_date_discovered,'%Y-%m-%d') AS finding_date_discovered,f.finding_date_closed,f.finding_data 
						from ".TN_FINDINGS." AS f, ".TN_FINDING_SOURCES." AS fs 
						WHERE finding_id='$fid' AND f.source_id=fs.source_id";
			
			$result  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
			
			if($result && $row = $this->dbConn->sql_fetchrow($result)) {
				$aid = 0;

				$this->source_id	= $row['source_id'];
				$this->source_name	= $row['source_name'];
				$this->asset_id		= $aid = $row['asset_id'];
				$this->finding_status = $row['finding_status'];
				$this->finding_date_created = $row['finding_date_created'];
				$this->finding_date_discovered = $row['finding_date_discovered'];
				$this->finding_date_closed = $row['finding_date_closed'];
				$this->finding_data = $row['finding_data'];

				$this->dbConn->sql_freeresult($result);

				if($aid > 0) {
					$this->asset_obj = new Asset($aid, $this->dbConn);
					
				}

				$sql = "SELECT v.vuln_seq,v.vuln_type,v.vuln_desc_primary,v.vuln_desc_secondary 
								FROM ".TN_FINDING_VULNS." AS fv, ".TN_VULNERABILITIES." AS v 
								WHERE fv.finding_id='$fid' AND 
									fv.vuln_seq=v.vuln_seq AND 
									fv.vuln_type=v.vuln_type";
				$result  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
				$seq_arr = array();
				$type_arr = array();
				if($result) {
					$arr = array();
					$flag = true;
					while($row = $this->dbConn->sql_fetchrow($result)) {
						if($this->vulner_flag) {
							// vlunerability list
							$seq_arr[] = $row['vuln_seq'];
							$type_arr[] = $row['vuln_type'];
						}
						else {
							// vulnerability breif information
							if($flag) {
								$this->vulner_brief = substring($row['vuln_desc_primary'], 35);
								$flag = false;
							}

							$arr[] = $row['vuln_desc_primary'];// ."|". $row['vuln_desc_secondary'];
						}
					}
					$this->vulner_arr = $arr;

					$this->dbConn->sql_freeresult($result);
				}
				if($this->vulner_flag) {
					for($i = 0; $i < count($seq_arr); $i++) {
						$this->vulnerability_arr[] = new Vulnerability($seq_arr[$i], $type_arr[$i], $this->dbConn);
					}
				}

				$this->errno = 0;
			}
		}	
	}
}


class Asset {
	private $dbConn;
	public $asset_id;
	public $asset_name;
	public $system_arr;
	public $network_arr;
	public $ip_arr;
	public $port_arr;
	public $ipaddr_arr;
	public $prod_name;
	public $prod_vendor;
	public $prod_version;

	function __construct($aid, $dblink) {
		$this->dbConn = $dblink;
		$this->init($aid);
	}

	function __destruct() {
	}

	function init($aid) {
//		$sql = "SELECT a.asset_name,p.prod_name,p.prod_vendor,p.prod_version FROM ".TN_ASSETS AS a, PRODUCTS AS p WHERE a.asset_id='$aid' AND a.prod_id=p.prod_id";
                $sql = "SELECT a.asset_name,p.prod_name,p.prod_vendor,p.prod_version FROM ".TN_PRODUCTS." p RIGHT OUTER JOIN " . TN_ASSETS." a ON p.prod_id = a.prod_id WHERE a.asset_id='$aid'";

		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
		if($result && $row = $this->dbConn->sql_fetchrow($result)) {
			$this->asset_id = $aid;
			$this->asset_name = $row['asset_name'];
			$this->prod_name = $row['prod_name'];
			$this->prod_vendor = $row['prod_vendor'];
			$this->prod_version = $row['prod_version'];

			$this->dbConn->sql_freeresult($result);

			$sql = "SELECT s.system_name FROM ".TN_SYSTEM_ASSETS." AS sa, ".TN_SYSTEMS." AS s WHERE sa.asset_id='$aid' AND sa.system_id=s.system_id";
			$result  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
			if($result) {
				$arr = array();
				while($row = $this->dbConn->sql_fetchrow($result)) {
					$arr[] = $row['system_name'];
				}
				$this->system_arr = $arr;

				$this->dbConn->sql_freeresult($result);
			}
		}
			$sql = "SELECT aa.address_ip,aa.address_port,n.network_name 
						FROM ".TN_ASSET_ADDRESSES." AS aa,".TN_NETWORKS." AS n 
						WHERE aa.asset_id='$aid' AND
							aa.network_id=n.network_id";
			$result  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
			if($result) {
				$i_arr = array();
				$p_arr = array();
				$ip_arr = array();
				$n_arr = array();
				while($row = $this->dbConn->sql_fetchrow($result)) {
					$ip = $row['address_ip'];
					$port = $row['address_port'];
					$network = $row['network_name'];

					$i_arr[] = $ip;
					$p_arr[] = $port;
					$ip_arr[] = $ip . ":" . $port;
					$n_arr[] = $network;
				}
				$this->ip_arr = $i_arr;
				$this->port_arr = $p_arr;
				$this->ipaddr_arr = $ip_arr;
				$this->network_arr = $n_arr;

				$this->dbConn->sql_freeresult($result);
			}
		
	}
}


class Vulnerability {
	private $dbConn;
	public $vuln_seq;
	public $vuln_type;

	public $vuln_desc_primary;
	public $vuln_desc_secondary;

	public $vuln_date_discovered;
	public $vuln_date_modified;
	public $vuln_date_published;

	public $vuln_severity;

	public $vuln_loss_availability;
	public $vuln_loss_confidentiality;
	public $vuln_loss_integrity;
	public $vuln_loss_security_admin;
	public $vuln_loss_security_user;
	public $vuln_loss_security_other;

	public $vuln_type_access;
	public $vuln_type_input;
	public $vuln_type_input_bound;
	public $vuln_type_input_buffer;
	public $vuln_type_design;
	public $vuln_type_exception;
	public $vuln_type_environment;
	public $vuln_type_config;
	public $vuln_type_race;
	public $vuln_type_other;

	public $vuln_range_local;
	public $vuln_range_remote;
	public $vuln_range_user;

	function __construct($seq, $type, $dblink = 0) {
		$this->vuln_seq = $seq;
		$this->vuln_type = $type;
		$this->dbConn = $dblink;
		if($dblink) {
			$this->_init($seq, $type);
		}
	}

	function __destruct() {
	}

	private function _init($vseq, $vtype) {
		$sql = "SELECT * FROM ".TN_VULNERABILITIES." WHERE vuln_seq='$vseq' AND vuln_type='$vtype'";
		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
		if($result && $row = $this->dbConn->sql_fetchrow($result)) {
			$this->vuln_desc_primary		= $row["vuln_desc_primary"];
			$this->vuln_desc_secondary		= $row["vuln_desc_secondary"];

			$this->vuln_date_discovered		= $row["vuln_date_discovered"];
			$this->vuln_date_modified		= $row["vuln_date_modified"];
			$this->vuln_date_published		= $row["vuln_date_published"];

			$this->vuln_severity			= $row["vuln_severity"];

			$this->vuln_loss_availability	= $row["vuln_loss_availability"];
			$this->vuln_loss_confidentiality = $row["vuln_loss_confidentiality"];
			$this->vuln_loss_integrity		= $row["vuln_loss_integrity"];
			$this->vuln_loss_security_admin = $row["vuln_loss_security_admin"];
			$this->vuln_loss_security_user	= $row["vuln_loss_security_user"];
			$this->vuln_loss_security_other = $row["vuln_loss_security_other"];

			$this->vuln_type_access			= $row["vuln_type_access"];
			$this->vuln_type_input			= $row["vuln_type_input"];
			$this->vuln_type_input_bound	= $row["vuln_type_input_bound"];
			$this->vuln_type_input_buffer	= $row["vuln_type_input_buffer"];
			$this->vuln_type_design			= $row["vuln_type_design"];
			$this->vuln_type_exception		= $row["vuln_type_exception"];
			$this->vuln_type_environment	= $row["vuln_type_environment"];
			$this->vuln_type_config			= $row["vuln_type_config"];
			$this->vuln_type_race			= $row["vuln_type_race"];
			$this->vuln_type_other			= $row["vuln_type_other"];

			$this->vuln_range_local			= $row["vuln_range_local"];
			$this->vuln_range_remote		= $row["vuln_range_remote"];
			$this->vuln_range_user			= $row["vuln_range_user"];
			
			$this->dbConn->sql_freeresult($result);
		}
	}
}
?>
