<?PHP

class AssetDBManager {
	private $dbConn;
    private $limit = true;
    function setLimit($flag = true)
    {
    	if ($flag) $this->limit=true;
    	else $this->limit=false;
    }

	function __construct($conn) {
		$this->dbConn = $conn;
	}

	function __destruct() {
	}

	function setDBLink($conn) {
		$this->dbConn = $conn;
	}

	function getSystemList() {
		$sql = "select system_id as sid, system_name as sname from ".TN_SYSTEMS;
		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
		$data = null;
		if($result) {
			while($row = $this->dbConn->sql_fetchrow($result)) {
				$sid = $row['sid'];
				$sname = $row['sname'];
				$data[$sid] = $sname;
			}
			$this->dbConn->sql_freeresult($result);
		}

		return $data;
	}


	function getProductList() {
		$sql = "select prod_id as sid, prod_name as sname from ".TN_PRODUCTS;
		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
		$data = null;
		if($result) {
			while($row = $this->dbConn->sql_fetchrow($result)) {
				$sid = $row['sid'];
				$sname = $row['sname'];
				$data[$sid] = $sname;
			}
			$this->dbConn->sql_freeresult($result);
		}

		return $data;
	}

	/////////////////////////////added by chang 03232006
	  function getProductByID($PID)
	  {
		$sql = "select prod_id , prod_name, prod_vendor , prod_version  from ".TN_PRODUCTS." where prod_id = '$PID'";
		$result  = $this->dbConn->sql_query($sql) or die("Query 21 failed: " . $this->dbConn->sql_error());
		$data = null;
		if($result)
		  {
			while($row = $this->dbConn->sql_fetchrow($result))
			  {
				$data['prod_id'] = $row['prod_id'];
				$data['prod_name'] = $row['prod_name'];
				$data['prod_vendor'] = $row['prod_vendor'];
				$data['prod_version'] = $row['prod_version'];
			  }
			$this->dbConn->sql_freeresult($result);
		  }

		return $data;
	  }

	function getNetworkList() {
		$sql = "select network_id as sid, network_name as sname from ".TN_NETWORKS;
		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
		$data = null;
		if($result) {
			while($row = $this->dbConn->sql_fetchrow($result)) {
				$sid = $row['sid'];
				$sname = $row['sname'];
				$data[$sid] = $sname;
			}
			$this->dbConn->sql_freeresult($result);
		}

		return $data;
	}


	function getAssetList() {
		$sql = "select asset_id as sid, asset_name as sname from ".TN_ASSETS;
		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
		$data = null;
		if($result) {
			while($row = $this->dbConn->sql_fetchrow($result)) {
				$sid = $row['sid'];
				$sname = $row['sname'];
				$data[$sid] = $sname;
			}
			$this->dbConn->sql_freeresult($result);
		}

		return $data;
	}



	function getSourceList() {
		$sql = "select source_id as sid, source_name as sname from ".TN_FINDING_SOURCES;
		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
		$data = null;
		if($result) {
			while($row = $this->dbConn->sql_fetchrow($result)) {
				$sid = $row['sid'];
				$sname = $row['sname'];
				$data[$sid] = $sname;
			}
			$this->dbConn->sql_freeresult($result);
		}

		return $data;
	}
	function getSummaryList() {
		$data = null;
		$total = 0;

		/*
		$sql = "select s.system_name as name, count(a.asset_id) as num
				from ".TN_ASSETS as a, SYSTEM_ASSETS as sa, SYSTEMS as s ,PRODUCTS as p, (select DISTINCT asset_id from ASSET_ADDRESSES) as aa
				where a.asset_id = sa.asset_id and sa.system_id = s.system_id and p.prod_id = a.prod_id and aa.asset_id= a.asset_id and sa.system_is_owner = '1'
				group by s.system_id
				order by s.system_name";
		*/
		$sql = "select s.system_name as name, count(a.asset_id) as num
				from ".TN_ASSETS." as a, SYSTEM_ASSETS as sa, SYSTEMS as s
				where a.asset_id = sa.asset_id and sa.system_id = s.system_id and sa.system_is_owner = '1'
				group by s.system_id
				order by s.system_name";
	//echo "$sql<br/>";
		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
		if($result) {

			while($row = $this->dbConn->sql_fetchrow($result)) {
				$data[] = array('sname'=>$row['name'],'svalue' =>$row['num']);
				$total += $row['num'];
			}
			$data[] = array('sname'=>'Total','svalue' =>$total);
			$this->dbConn->sql_freeresult($result);
		}
		return $data;
	}


	function createAsset($post) {
		$fid = 0;

		$asset_name = trim($post['assetname']);
		$system_id = $post['system'];
		$network_id = $post['network'];
		$ip = trim($post['ip']);
		$port = trim($post['port']);
		$prod_id = $post['prod_id'];
		$addrtype = $post['addrtype'];
		$created_date_time = date("Y-m-d H:i:s");

		if (!get_magic_quotes_gpc()) {
			$asset_name = addslashes($asset_name);
			$ip 		= addslashes($ip);
			$port 		= addslashes($port);
			$system_id 	= addslashes($system_id);
			$network_id = addslashes($network_id);
			$prod_id 	= addslashes($prod_id);
		}



		$sql = "INSERT INTO ASSETS (`asset_id` , `prod_id` , `asset_name` , `asset_date_created` , `asset_source` )
				VALUES ('', '$prod_id' , '$asset_name', '$created_date_time', 'MANUAL')";
		//echo(__LINE__." ".$sql."<br>");
		$res  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());

		if($res) {
			$sql = "select max(asset_id) as asset_id from ".TN_ASSETS."
						WHERE prod_id='$prod_id' and
							asset_name='$asset_name' and
							asset_date_created='$created_date_time'";
			//echo(__LINE__." ".$sql."<br>");
			$result  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
			if($result) {
				if($row = $this->dbConn->sql_fetchrow($result)) {
					$aid = $row['asset_id'];
				}
				$this->dbConn->sql_freeresult($result);
			}
			//echo(__LINE__." ".$aid."<br>");
			if($aid > 0) {
				$sql = "INSERT INTO ASSET_ADDRESSES(`asset_id`,`network_id`,`address_date_created`,`address_ip`,`address_port`)
						VALUES ('$aid', '$network_id', '$created_date_time', '$ip' ,'$port')";
				//echo(__LINE__." ".$sql."<br>");
				$res  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
				$sql = "INSERT INTO SYSTEM_ASSETS(`system_id`,`asset_id`,`system_is_owner`)
						VALUES ('$system_id', '$aid', '1')";
				//echo(__LINE__." ".$sql."<br>");
				$res  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
			}
		}

		return $aid;
	}
	function searchProduct($post,$prod_id=0) {
		global $maxpageno,$pageno;
		$data = array();
		$data = null;
		$listnum = 15;
		$rownum = 0;
		$where = "";

		$product_search = trim(@$post['product_search']);
		$pageno = trim(@$post['pageno']);
		if (!get_magic_quotes_gpc()) {
			$product_search = addslashes($product_search);
		}

		if ($product_search != "") $where = "and instr(prod_name,'$product_search')>0";
		if ($prod_id>0) $where .= " and prod_id='$prod_id' ";

		$sql = "select count(prod_id) as num from ".TN_PRODUCTS." where 1=1 ".$where;
		$result = $this->dbConn->sql_query($sql);

		if ($result)
		{
			if ($row = $this->dbConn->sql_fetchrow($result))
				$rownum = $row['num'];
		}
		if ($rownum == 0)
		{
			return $data;
		}

		$pageno = (int) $pageno;
		$maxpageno = floor(($rownum - 1) / $listnum) + 1;
		if ($pageno > $maxpageno) $pageno = $maxpageno;
		if ($pageno<1) $pageno = 1;
		$limitclause = " limit ".($pageno-1)*$listnum.",".$listnum;
		$sql = "select prod_id as sid, prod_name as sname,prod_vendor as svendor,prod_version as sversion from ".TN_PRODUCTS." where 1=1 ".$where.$limitclause;
		//echo($sql);
		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());

		if($result) {
			while($row = $this->dbConn->sql_fetchrow($result)) {
				$sid      = $row['sid'];
				$sname    = $row['sname'];
				$svendor  = $row['svendor'];
				$sversion = $row['sversion'];
				$data[] = array("sid"=>$sid,"svendor"=>$svendor,"sname"=>$sname,"sversion"=>$sversion,);
			}
			$this->dbConn->sql_freeresult($result);
		}

		return $data;
	}

	function searchAssets($post,$aid) {
		global $maxpageno,$pageno,$limit;
		$asset_arr = array();
		$asset_arr = null;
		$listnum = 15;
		$rownum = 0;
		$where = "";

        extract($post);
/*        
		$system = $post['system'];
		$vendor = trim($post['vendor']);
		$product = $post['product'];
		$version = trim($post['version']);
		$ip = trim($post['ip']);
		$port = trim($post['port']);
		$order = trim($post['order']);
		$orderbyfield = trim($post['orderbyfield']);
*/
		if (!get_magic_quotes_gpc()) {
			$system = addslashes($system);
			$ip 		= addslashes($ip);
			$port 		= addslashes($port);
			$vendor 	= addslashes($vendor);
			$version = addslashes($version);
			$product 	= addslashes($product);

		}

	//	$asset_address_sql = "(select t.asset_id,t.address_date_created,t.address_ip,t.address_port,t.network_id from ".TN_(select * from ASSET_ADDRESSES order by asset_id asc,address_date_created desc) as t  group by t.asset_id asc) as aa";
		$asset_address_sql = "ASSET_ADDRESSES as aa";
		$system_asset_sql = "(select system_id,asset_id,system_is_owner from ".TN_SYSTEM_ASSETS." where system_is_owner=1 group by asset_id,system_id) as sa";

		$product_sql = "PRODUCTS as p";
		$system_sql = "SYSTEMS as s";
		$network_sql = "NETWORKS as n";
		$assets_sql = "ASSETS as a";


	/*
		$asset_sql = "select a.asset_id,a.asset_name,s.system_id,s.system_name,p.prod_id,p.prod_name,p.prod_vendor,aa.network_id,aa.address_ip,aa.address_port";
	*/
		$asset_sql = "select aaa.asset_id,aaa.asset_name,aaa.system_id,aaa.system_name,p.prod_id,p.prod_name,p.prod_vendor,aaa.network_id,aaa.address_ip,aaa.address_port ";

/*
		$asset_sql_from_where = " from ".TN_$assets_sql,$asset_address_sql,$product_sql,$system_sql,$system_asset_sql
				where aa.asset_id=a.asset_id and a.asset_id=sa.asset_id and a.prod_id=p.prod_id and sa.system_id=s.system_id ";
*/

/*
		if(!empty($ip) && strlen($ip) > 0) {
			$assethave = true;
			$asset_sql_from_where .= "and aa.address_ip='$ip' ";
		}
		if(intval($port) > 0) {
			$assethave = true;
			$asset_sql_from_where .= "and aa.address_port='$port' ";
		}


		if(!empty($vendor) && strlen($vendor) > 0) {
			$assethave = true;
			$asset_sql_from_where .= "and instr(p.prod_vendor,'$vendor') ";
		}
		if(!empty($version) && strlen($version) > 0) {
			$assethave = true;
			$asset_sql_from_where .= "and instr(p.prod_version,'$version') ";
		}
		if(!empty($product) && strlen($product) > 0) {
			$assethave = true;
			$asset_sql_from_where .= "and instr(p.prod_name ,'$product')>0 ";
		}


		if($system > 0) {
			$assethave = true;
			$asset_sql_from_where .= "and sa.system_id='$system' ";
		}

		if (intval($aid) >0 ) {
			$assethave = true;
			$asset_sql_from_where .= "and a.asset_id='$aid' ";
		}

*/

		/*
		** Set up asset addresses filter
		*/
		$aa_filter = "";

		if(isset($ip) && strlen($ip) > 0) {
			$assethave = true;
			$aa_filter .= "and aa.address_ip='$ip' ";
		}
		if(isset($port) && (intval($port) > 0)) {
			$assethave = true;
			$glue = (strlen($aa_filter) > 0) ? "and" : "where";
			$aa_filter .= "$glue aa.address_port='$port' ";
		}


		/*
		** Set up products filter
		*/
		$prod_filter = "";

		if(isset($vendor) && strlen($vendor) > 0) {
			$assethave = true;
			$glue = (strlen($prod_filter) > 0) ? "and" : "where";
			$prod_filter .= "$glue instr(p.prod_vendor,'$vendor') ";
		}
		if(isset($version) && strlen($version) > 0) {
			$assethave = true;
			$glue = (strlen($prod_filter) > 0) ? "and" : "where";
			$prod_filter .= "$glue instr(p.prod_version,'$version') ";
		}
		if(isset($product) && strlen($product) > 0) {
			$assethave = true;
			$glue = (strlen($prod_filter) > 0) ? "and" : "where";
			$prod_filter .= "$glue instr(p.prod_name ,'$product')>0 ";
		}

		/*
		** Set up system assets filter
		*/
		$sa_filter = "";
		if(isset($system) && ($system > 0)) {
			$assethave = true;
			$sa_filter .= "and sa.system_id='$system' ";
		}

		/*
		** Set up asset id filter
		*/
		$a_filter = "";
		if (isset($aid) && ($aid > 0)) {
			$assethave = true;
			$a_filter .= "and a.asset_id='$aid' ";
		}

		if (isset($limit) && ($limit > 0))
		{
			$limitclause = " limit 0,$limit";
		}
/*
		$Aorderby['asset_name'] = "a.asset_name";
		$Aorderby['system'] = "s.system_name";
		$Aorderby['ip'] = "aa.address_ip";
		$Aorderby['port'] = "aa.address_port";
		$Aorderby['product_name'] = "p.prod_name";
		$Aorderby['vendor'] = "p.prod_vendor";
*/
		$Aorderby['asset_name'] = "aaa.asset_name";
		$Aorderby['system'] = "aa.system_name";
		$Aorderby['ip'] = "aaa.address_ip";
		$Aorderby['port'] = "aaa.address_port";
		$Aorderby['product_name'] = "p.prod_name";
		$Aorderby['vendor'] = "p.prod_vendor";
		/*
		a.asset_id,a.asset_name,s.system_id,s.system_name,p.prod_id,p.prod_name,p.prod_vendor,aa.network_id,aa.address_ip,aa.address_port*/
		if (isset($order) && ($order == "ASC" || $order == "DESC") && (isset($Aorderby[$orderbyfield])))
		{
			$orderby_sql = " order by ".$Aorderby[$orderbyfield]." ".$order;

		}
		else {
			$orderby_sql = '';
		}

		/*
		** New OUTER JOIN query structure (Mar.24 2006)
		*/
		$asset_sql_from_where = "FROM $product_sql
					RIGHT OUTER JOIN
					(SELECT 
                        a.asset_id, 
                        a.prod_id,
                        a.asset_name, 
                        s.system_id, 
                        s.system_name, 
                        aa.network_id, 
                        aa.address_ip, 
                        aa.address_port
					FROM ASSETS AS a,
					$asset_address_sql,
					$system_sql,
					$system_asset_sql
					where aa.asset_id=a.asset_id
					and a.asset_id=sa.asset_id
					and sa.system_id=s.system_id
					$aa_filter
					$sa_filter) AS aaa
					ON aaa.prod_id = p.prod_id
					$prod_filter";

		//echo __LINE__.$order.$orderbyfield.$orderby_sql;
		//echo("<br>$asset_sql<br>");
		$sql = "select count(aaa.asset_id) as num ".$asset_sql_from_where;

		//echo("<br/>$sql<br/>");

		$result = $this->dbConn->sql_query($sql);
		if ($result)
		{
			if ($row = $this->dbConn->sql_fetchrow($result))
				$rownum = $row['num'];
		}
		if ($rownum == 0)
		{
			return $asset_arr;
		}
		$pageno = (int) $pageno;
		$maxpageno = floor(($rownum - 1) / $listnum) + 1;
		if ($pageno > $maxpageno) $pageno = $maxpageno;
		if ($pageno<1) $pageno = 1;
		if ($this->limit)
		$limitclause = " limit ".($pageno-1)*$listnum.",".$listnum;


		//echo("<br/>$asset_sql.$asset_sql_from_where.$orderby_sql.$limitclause<br/>");
		$result  = $this->dbConn->sql_query($asset_sql.$asset_sql_from_where.$orderby_sql.$limitclause) or die("Query failed: " . $this->dbConn->sql_error());
		$aid_arr = array();
		$asset_arr = array();
		$num = 0;
		if($result) {
			while($row = $this->dbConn->sql_fetchrow($result)) {
				$num++;
				$aid_arr[] = $row['asset_id'];
				//a.asset_id,a.asset_name,s.system_id,s.system_name,p.prod_id,p.prod_name,p.prod_vendor,aa.network_id,aa.address_ip,aa.address_port
				$asset_arr[] = array('asset_id' => $row['asset_id'],
													 'asset_name' => $row['asset_name'],
													 'system_id' => $row['system_id'],
													 'prod_id' => $row['prod_id'],
													 'prod_name' => $row['prod_name'],
													 'prod_vendor' => $row['prod_vendor'],
													 'network_id' => $row['network_id'],
													 'address_ip' => $row['address_ip'],
													 'address_port' => $row['address_port'],
													 'system_name' => $row['system_name']);
			}
			$this->dbConn->sql_freeresult($result);
		}


		//print_r($asset_arr);

		return $asset_arr;
	}





	function deleteAssets($post)
	{
		foreach($post as $skey=>$svalue) {
			if(substr($skey, 0, 4) == "aid_" && substr($svalue, 0, 4) == "aid.") {
				$aid = intval(substr($svalue, 4));
				$sql = "DELETE from ".TN_ASSETS." WHERE asset_id='$aid'";
				$res  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
				//echo "<br>".$sql."<br>";
				$sql = "DELETE from ".TN_ASSET_ADDRESSES." WHERE asset_id='$aid'";
				$res  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
				//echo $sql."<br>";
				$sql = "UPDATE SYSTEM_ASSETS SET system_is_owner='0' WHERE asset_id='$aid'";
				$res  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
				//echo $sql."<br><br>";
				//echo $sql;

			}
		}
	}



	function updateAsset($post,$aid) {
		$asset_name = trim($post['assetname']);
		$system_id = $post['system'];
		$network_id = $post['network'];
		$ip = trim($post['ip']);
		$port = trim($post['port']);
		$prod_id = @$post['prod_id'];
		$created_date_time = date("Y-m-d H:i:s");

		if (!get_magic_quotes_gpc()) {
			$asset_name = addslashes($asset_name);
			$ip 		= addslashes($ip);
			$port 		= addslashes($port);
			$system_id 	= addslashes($system_id);
			$network_id = addslashes($network_id);
			$prod_id 	= addslashes($prod_id);
		}

		$sql = "UPDATE ASSETS SET asset_name='$asset_name',prod_id='$prod_id' WHERE asset_id='$aid'";
		$res  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
		//echo("<br>".$sql."<br>");
		$sql = "SELECT max(address_date_created) as max_date_created from ".TN_ASSET_ADDRESSES." WHERE asset_id='$aid'";
		//echo("<br>".$sql."<br>");
		$res  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
		if($res) {
			if ($row = $this->dbConn->sql_fetchrow($res))
			{
				$address_date_created = $row['max_date_created'];
				$sql = "UPDATE ASSET_ADDRESSES SET network_id='$network_id',address_ip='$ip',address_port='$port'
				 	WHERE asset_id='$aid' and address_date_created='$address_date_created'";
			}
			else
			{
				$sql = "INSERT INTO ASSET_ADDRESSES(`asset_id`,`network_id`,`address_date_created`,`address_ip`,`address_port`)
					VALUES ('$aid', '$network_id', '$created_date_time', '$ip' ,'$port')";
			}
		}
		else
		{
			$sql = "INSERT INTO ASSET_ADDRESSES(`asset_id`,`network_id`,`address_date_created`,`address_ip`,`address_port`)
					VALUES ('$aid', '$network_id', '$created_date_time', '$ip' ,'$port')";
				//echo(__LINE__." ".$sql."<br>");
		}
		$this->dbConn->sql_freeresult($res);
		//echo("<br>".$sql."<br>");
		$res  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());


		$sql = "SELECT system_id from ".TN_SYSTEM_ASSETS." WHERE asset_id='$aid' and system_id='$system_id'";
		//echo("<br>".$sql."<br>");
		$res  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
		if($res) {
			if ($row = $this->dbConn->sql_fetchrow($res))
			{
				$sql_2 = "UPDATE SYSTEM_ASSETS SET system_is_owner=1
				 	WHERE system_id='$system_id' and asset_id='$aid'";
			}
			else
			{
				$sql_2 = "INSERT INTO SYSTEM_ASSETS(`system_id`,`asset_id`,`system_is_owner`)
						VALUES ('$system_id', '$aid', '1')";
			}
		}
		else
		{
			$sql_2 = "INSERT INTO SYSTEM_ASSETS(`system_id`,`asset_id`,`system_is_owner`)
						VALUES ('$system_id', '$aid', '1')";
				//echo(__LINE__." ".$sql."<br>");
		}
		$this->dbConn->sql_freeresult($res);
		$sql_1 = "UPDATE SYSTEM_ASSETS SET system_is_owner=0 WHERE asset_id='$aid'";
		$res  = $this->dbConn->sql_query($sql_1) or die("Query failed: " . $this->dbConn->sql_error());
		$res  = $this->dbConn->sql_query($sql_2) or die("Query failed: " . $this->dbConn->sql_error());
		//echo("<br>".$sql_1."<br>");
		//echo("<br>".$sql_2."<br>");

		return $res;
	}
}

?>
