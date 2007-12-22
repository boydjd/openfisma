<?PHP
class Asset {
	private $dbConn;
	private $asset;
	private $product;
	private $address;
	private $num_addresses;

	public $asset_id;
	public $asset_name;
	public $prod_name;
	public $system_arr;
	public $network_arr;
	public $ip_arr;
	public $port_arr;
	public $ipaddr_arr;

	function __construct($asset_id, $dblink) {
		$this->dbConn = $dblink;
		$this->init($asset_id);
	}

	function __destruct() {
	}

	function init($asset_id) {

	  // retrieve the asset information
	  $asset  = Array();
	  $query  = "SELECT a.* FROM " . TN_ASSETS . " AS a WHERE (a.asset_id='$asset_id')";
	  $result = $this->dbConn->sql_query($query) or die("Query failed: " . $this->dbConn->sql_error());
	  $this->asset = $this->dbConn->sql_fetchrow($result);

	  // check for product information
	  if ($this->asset['prod_id'] != 0) {

		// retrieve the product information
		$product = Array();
		$query   = "SELECT p.* FROM " . PRODUCTS . " AS p WHERE (p.prod_id = '".$this->asset['prod_id']."')";
		$result  = $this->dbConn->sql_query($query) or die("Query failed: ".$this->dbConn->sql_error());
		$this->product = $this->dbConn->sql_fetchrow($result);

	  }

	  // retrieve the address information for the asset
	  $address = Array();
	  $query   = 
		"SELECT ".
		"  aa.*, ".
		"  n.* ".
		"FROM " . TN_ASSET_ADDRESSES .
		"  AS aa, ".TN_NETWORKS.
		"  AS n ".
		"WHERE ( ".
		"  aa.asset_id  = '".$this->asset['asset_id']."' AND ".
		"  n.network_id = aa.network_id ".
		")";
	  $result  = $this->dbConn->sql_query($query) or die("Query failed:".$this->dbConn->sql_error());
	  $this->address       = $this->dbConn->sql_fetchrow($result);
	  $this->num_addresses = $this->dbConn->sql_numrows($result);

	  /******************************************************************/
	  
	  if($result && $row = $this->dbConn->sql_fetchrow($result)) {
			$this->asset_id = $asset_id;
			$this->asset_name = $row['asset_name'];
			$this->prod_name = $row['prod_name'];

			$this->dbConn->sql_freeresult($result);

			$sql = "select s.system_name from ".TN_SYSTEM_ASSETS." as sa, SYSTEMS as s where sa.asset_id='$aid' and sa.system_id=s.system_id";
			$result  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
			if($result) {
				$arr = array();
				while($row = $this->dbConn->sql_fetchrow($result)) {
					$arr[] = $row['system_name'];
				}
				$this->system_arr = $arr;

				$this->dbConn->sql_freeresult($result);
			}

			$sql = "select aa.address_ip,aa.address_port,n.network_name 
						from ".TN_ASSET_ADDRESSES." as aa,NETWORKS as n 
						where aa.asset_id='$asset_id' and
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


	// ASSET ACCESSOR FUNCTIONS
	function getAssetId()           { return $this->asset['asset_id']; }
	function getAssetName()         { return $this->asset['asset_name']; }
	function getAssetSource()       { return $this->asset['asset_source']; }
	function getAssetDateCreated()  { return $this->asset['asset_date_created']; }


	// ADDRESS ACCESSOR FUNCTIONS
	function getNumAddresses() { return $this->num_addresses; }
	function getAddress($address_num = 0) {

	  // if $address_num is 0, return all
	  if ($address_num == 0) { return $this->address; }

	  // otherwise return the single address line
	  else { return $this->address[$address_num]; }

	}


	// PRODUCT ACCESSOR FUNCTIONS
	function getProductId()         { return $this->product['prod_id']; }
	function getProductVendor()     { return $this->product['prod_vendor']; }
	function getProductName()       { return $this->product['prod_name']; }
	function getProductVersion()    { return $this->product['prod_version']; }
	function getProductDesc()       { return $this->product['prod_desc']; }


}

?>
