<?PHP

class FindingDBManager {
    private $pagesize = 20;
    private $totalfindings = 0;
    private $dbConn;
    public  $user_id;

    function __construct($conn,$user_id=null) {
        $this->dbConn = $conn;
        $this->user_id = $user_id;
    }

    function __destruct() {
    }

    function setDBLink($conn) {
        $this->dbConn = $conn;
    }

	// function call to generate a list of system ids AND names AND sort the list alphabetically 
    // no variables are passed to the function
	function getSystemList() {
        $sql = "SELECT system_id AS sid, system_name AS sname FROM " . TN_SYSTEMS . " ORDER BY sname asc";
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

	// function call to generate a list of finding sources by id AND name AND sort alphabetically
	// no variables are passed to the function
    function getSourceList() {
        $sql = "SELECT source_id AS sid, source_name AS sname FROM " . TN_FINDING_SOURCES . " ORDER BY sname asc";
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

	// function call to generate a list of product ids AND names FROM the products table AND sort the list alphabetically
	// no variables are passed to the function
    function getProductList() {
        $sql = "SELECT prod_id AS sid, prod_name AS sname FROM " . TN_PRODUCTS . " ORDER BY sname asc";
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

	// function call to generate a list of network ids AND names FROM the networks table AND sort the list alphabetically
	// no variables are passed to the function
    function getNetworkList() {
        $sql = "SELECT network_id AS sid, network_name AS sname FROM " . TN_NETWORKS ." ORDER BY sname asc";
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

	// function call to generate a list of asset ids AND names FROM the assets table AND sort the list alphabetically
	// the $needle variable is used to identify an asset
    function getAssetList($needle = "", $sid = 0) {
        $sql = "SELECT a.asset_id AS sid, a.asset_name AS sname FROM " . TN_ASSETS . " AS a ";
        // if variable $needle is empty do the following
		if(empty($needle)) {
            // if variable $sid is not empty AND greater than zero SELECT the assets that map to the corresponding system_id
			if(!empty($sid) && $sid > 0)
                $sql .= " LEFT JOIN ".TN_SYSTEM_ASSETS." AS sa ON a.asset_id=sa.asset_id WHERE sa.system_id=$sid ";
        }
		// if variable $needle is not empty to the following
        else {
			// if variable $sid is empty or zero SELECT assets that correspond to systems that sound like $needle
            if(empty($sid) || $sid == 0)
                $sql .= " WHERE a.asset_name LIKE '%$needle%' ";
			// if variable $sid exists then SELECT assets that correspond to the system_id AND systems that sound like $needle
			else
                $sql .= " LEFT JOIN ".TN_SYSTEM_ASSETS." AS sa ON a.asset_id=sa.asset_id WHERE sa.system_id='$sid' AND a.asset_name LIKE '%$needle%' ";
        }
		// append to search query AND ensure the list is sorted alphabetically
        $sql .= " ORDER BY sname ASC";

//        die($sql);
        $result  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
        $data = array();
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

	// function call the generate the summary list used on the finding page
    function getSummaryList() {
        $data = array();
        $sql = "SELECT s.system_name AS sname, f.finding_status AS status, COUNT(f.finding_id) AS num 
                FROM " . TN_FINDINGS . " AS f, " . TN_SYSTEM_ASSETS . " AS a, " . TN_SYSTEMS . " AS s, ".TN_USER_SYSTEM_ROLES." AS u
                WHERE f.asset_id=a.asset_id 
                    AND s.system_id=a.system_id AND u.user_id=".$this->user_id." AND u.system_id=a.system_id AND a.asset_id=f.asset_id
                GROUP BY s.system_id, f.finding_status 
                ORDER BY s.system_name";
        $result  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
        if($result) {
            while($row = $this->dbConn->sql_fetchrow($result)) {
                $data[$row['sname']]['system'] = $row['sname'];
                if(!isset($data[$row['sname']]['total'])) $data[$row['sname']]['total']=0;
                
                if ('REMEDIATION'==$row['status']) {
                	$data[$row['sname']]['reme'] = $row['num'];
                	$data[$row['sname']]['total'] += $row['num'];
                }
                if ('CLOSED'==$row['status']) {
                	$data[$row['sname']]['closed'] = $row['num'];
                	$data[$row['sname']]['total'] += $row['num'];
                }
                if ('OPEN'==$row['status']) { // open count number should be split to 30,60,90 etc counts
//                	$data[$row['sname']]['open'] = $row['num'];
                	$data[$row['sname']]['total'] += $row['num'];
                }
                $data[$row['sname']]['thirty'] = '';
                $data[$row['sname']]['sixty'] = '';
                $data[$row['sname']]['ninety'] = '';
            }
            $this->dbConn->sql_freeresult($result);
        }
        
        $sql = "SELECT s.system_name AS sname, COUNT(f.finding_id) AS num, DATE_FORMAT(f.finding_date_created, '%Y%m%d') AS date_num 
                FROM " . TN_FINDINGS . " AS f, " . TN_SYSTEM_ASSETS . " AS a, " . TN_SYSTEMS . " AS s, ".TN_USER_SYSTEM_ROLES." AS u
                WHERE f.asset_id=a.asset_id 
                    AND s.system_id=a.system_id  AND f.finding_status='OPEN' AND u.user_id=".$this->user_id." AND u.system_id=a.system_id AND a.asset_id=f.asset_id
                GROUP BY s.system_id, date_num";
        $result = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
        $today = date('Ymd',mktime(0, 0, 0, date("m")  , date("d"), date("Y")));
        $day30 = date('Ymd',mktime(0, 0, 0, date("m")  , date("d")-30, date("Y")));
        $day60 = date('Ymd',mktime(0, 0, 0, date("m")  , date("d")-60, date("Y")));
        $day90 = date('Ymd',mktime(0, 0, 0, date("m")  , date("d")-90, date("Y")));
        if($result) {
            while($row = $this->dbConn->sql_fetchrow($result)) {
                $day = $row['date_num'];
                if ($today == $day) {
                	$data[$row['sname']]['open'] = $row['num'];
                }
                elseif (($day < $today) && ($day > $day30)) {
                	$data[$row['sname']]['thirty'] += $row['num'];
                }
                elseif (($day < $day30) && ($day > $day60)) {
                	$data[$row['sname']]['sixty'] += $row['num'];
                }
                else {
                	$data[$row['sname']]['ninety'] += $row['num'];
                }
            }
            $this->dbConn->sql_freeresult($result);
        }
        return array_values($data);
    }

	// function call to search findings only used on the findings page
    function searchFinding($post, $asc, $pgno, $fn = "") {
        $pagesize = $this->pagesize;
        $finding_arr = array();
        
        if(!isset($post['startdate'])) {
            $startdate    = strftime("%Y-%m-%d", (mktime(0, 0, 0, date("m")  , date("d") - 7, date("Y"))));
        }
        else {
            $datesource = convert_date_format($post['startdate']);
            $y = substr($datesource, 0, 4);
            $m = substr($datesource, 5, 2);
            $d = substr($datesource, 8, 2);
            $startdate    = strftime("%Y-%m-%d", (mktime(0, 0, 0, $m, $d, $y)));
        }
        
        if(!isset($post['enddate'])) {
            $enddate    = strftime("%Y-%m-%d", (mktime(0, 0, 0, date("m")  , date("d"), date("Y"))));
        }
        else {
            $datesource = convert_date_format($post['enddate']);
            $y = substr($datesource, 0, 4);
            $m = substr($datesource, 5, 2);
            $d = substr($datesource, 8, 2);
            $enddate    = strftime("%Y-%m-%d", (mktime(0, 0, 0, $m, $d, $y)));
        }

        $status        = isset($post['status'])?trim($post['status']):null;
        $source        = isset($post['source'])?intval($post['source']):null;
        $system        = isset($post['system'])?intval($post['system']):null;
        $network       = isset($post['network'])?intval($post['network']):null;

        $ip            = isset($post['ip'])?trim($post['ip']):null;
        $port          = isset($post['port'])?intval($post['port']):null;
        $product       = isset($post['product'])?trim($post['product']):null;
        $vulner        = isset($post['vulner'])?trim($post['vulner']):null;


        if(empty($fn))
            $fn = isset($post['fn'])?trim($post['fn']):null;
        if(empty($fn))
            $fn = "date";

        // relative tables
        $sql_table = " FROM ".TN_FINDINGS . " AS f,".TN_USER_SYSTEM_ROLES." AS u,".TN_SYSTEM_ASSETS." AS sa";
        // query condition
        $sql_con = " WHERE ";
        $sql_con .="u.user_id=".$this->user_id." AND u.system_id=sa.system_id AND sa.asset_id=f.asset_id AND";
        if(!empty($status) && $status != "DELETED") {
            $sql_con .= " f.finding_status='$status' ";
        }
        else {
            $sql_con .= " f.finding_status!='DELETED' ";
        }

        if(!empty($startdate) && strlen($startdate) == 10) {
            $sql_con .= " AND f.finding_date_discovered>='$startdate' ";
        }
        if(!empty($enddate) && strlen($enddate) == 10) {
            $sql_con .= " AND f.finding_date_discovered<='$enddate' ";
        }
        if(!empty($source) && intval($source) > 0) {
            $sql_con .= " AND f.source_id='$source' ";
        }

        /*
        ** Set up tables to query.
        ** Complexity of query is determined by passed-in search
        ** criteria and column sort selection.
        **
        ** Make sure tables are available for column sorts - avoid
        ** falling through to the default finding_id sot.
        */
        $aahave = ($fn == 'ip' || $fn == 'port') ? true : false;
        $assethave = ($aahave == true || $fn == 'asset') ? true : false;
        $prodhave = false;
        $syshave = false;
        $asset_table = "";
        $asset_con = "";
        if(!empty($network) && intval($network) > 0) {
            $assethave = true;
            $aahave = true;
            $asset_con .= " AND aa.network_id='$network' ";
        }
        if(!empty($ip) && strlen($ip) > 0) {
            $aahave = true;
            $assethave = true;
            $asset_con .= " AND aa.address_ip='$ip' ";
        }
        if(!empty($port) && intval($port) > 0) {
            $assethave = true;
            $aahave = true;
            $asset_con .= " AND aa.address_port='$port' ";
        }

        if(!empty($product) && strlen($product) > 0) {
            $assethave = true;
            $prodhave = true;
            $asset_table .= "," . TN_PRODUCTS . " AS p";
            $asset_con .= " AND a.prod_id=p.prod_id ";

            $asset_con .= " AND p.prod_name like '%$product%' ";
        }
        if($system > 0) {
            $assethave = true;
            $syshave = true;
            $asset_con .= " AND a.asset_id=sa.asset_id ";

            $asset_con .= " AND sa.system_id='$system' ";
        }

        if($assethave) {
            if($aahave) {
                $asset_table .= "," . TN_ASSET_ADDRESSES . " AS aa";
                $asset_con .= " AND a.asset_id=aa.asset_id ";
            }
            $sql_table .= "," . TN_ASSETS . " AS a".$asset_table;

            $sql_con .= " AND f.asset_id=a.asset_id " . $asset_con;
        }

        $vulnerhave = false;
        if(!empty($vulner) && strlen($vulner) > 0) {
            $vulnerhave = true;

            $sql_table .= "," . TN_FINDING_VULNS . " AS fv," . TN_VULNERABILITIES . " AS v";
            $sql_con .= " AND f.finding_id=fv.finding_id AND fv.vuln_seq=v.vuln_seq AND fv.vuln_type=v.vuln_type ";

            $sql_con .= " AND v.vuln_desc_primary like '%$vulner%' ";
        }


        $sql_order = "";
        if($fn == 'status') {
            $sql_order = " ORDER BY f.finding_status ";
        }
        else if($fn == 'source') {
            $sql_order = " ORDER BY f.source_id ";
        }
        else if($fn == 'date') {
            $sql_order = " ORDER BY f.finding_date_discovered ";
        }
        else if($fn == 'network') {
            if($aahave)
                $sql_order = " ORDER BY aa.network_id ";
            else {
                if($assethave)
                    $sql_order = " ORDER BY a.asset_id ";
                else
                    $sql_order = " ORDER BY f.finding_id ";
            }
        }
        else if($fn == 'ip') {
            if($aahave)
                $sql_order = " ORDER BY aa.address_ip ";
            else {
                if($assethave)
                    $sql_order = " ORDER BY a.asset_id ";
                else
                    $sql_order = " ORDER BY f.finding_id ";
            }
        }
        else if($fn == 'port') {
            if($aahave)
                $sql_order = " ORDER BY aa.address_port ";
            else {
                if($assethave)
                    $sql_order = " ORDER BY a.asset_id ";
                else
                    $sql_order = " ORDER BY f.finding_id ";
            }
        }
        else if($fn == 'product') {
            if($prodhave)
                $sql_order = " ORDER BY p.prod_name ";
            else {
                if($assethave)
                    $sql_order = " ORDER BY a.asset_id ";
                else
                    $sql_order = " ORDER BY f.finding_id ";
            }
        }
        else if($fn == 'system') {
            if($syshave)
                $sql_order = " ORDER BY sa.system_id ";
            else {
                if($assethave)
                    $sql_order = " ORDER BY a.asset_id ";
                else
                    $sql_order = " ORDER BY f.finding_id ";
            }
        }
        else if($fn == 'vulner') {
            if($vulnerhave)
                $sql_order = " ORDER BY fv.vuln_seq ";
            else
                $sql_order = " ORDER BY f.finding_id ";
        }
        else {
            $sql_order = " ORDER BY f.finding_id ";
        }

        if($asc > 0) {
            $sql_order .= " DESC ";
        }
        if($fn != 'date') {
            $sql_order .= ",f.finding_date_discovered DESC ";
        }

        $sql = "SELECT count(DISTINCT f.finding_id) AS total " . $sql_table . $sql_con;
        //echo $sql;
        $result  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
        if($result && $row = $this->dbConn->sql_fetchrow($result)) {
            $this->totalfindings = $row['total'];
            $this->dbConn->sql_freeresult($result);
        }


        if($pgno > 1)
            $pagepos = $pagesize * ($pgno - 1);
        else
            $pagepos = 0;

        $sql = "SELECT DISTINCT f.finding_id " . $sql_table . $sql_con . $sql_order . " limit $pagepos, $pagesize ";
        //echo $sql;
        $result  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
        $fid_arr = null;
        $num = 0;
        if($result) {
            while($row = $this->dbConn->sql_fetchrow($result)) {
                $num++;
                $fid_arr[] = $row['finding_id'];
            }
            $this->dbConn->sql_freeresult($result);
        }

        for($i=0; $i<count($fid_arr); $i++) {
            $fid = $fid_arr[$i];
            $finding = $this->getFindingByID($fid);
            $finding_arr[$fid] = $finding;
            // print_r($finding);
        }

        return $finding_arr;
    }


    function getSearchPages() {
        if($this->totalfindings > 0) {
            return ceil($this->totalfindings / $this->pagesize);
        }

        return 0;
    }


    function getFindingByID($fid, $flag = false) {
        return new Finding($fid, $this->dbConn, $flag);
    }

    function getVulnerList($needle, $offset, $row_count) {
        $sql = "SELECT vuln_seq, vuln_type, vuln_desc_primary FROM " . TN_VULNERABILITIES . " WHERE vuln_desc_primary like '%$needle%' LIMIT $offset, $row_count";
        $result  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
        $vuln_arr = array();
        if($result) {
            $num = 0;
            while($row = $this->dbConn->sql_fetchrow($result)) {
                $vid = $row['vuln_seq'] . ":" . $row['vuln_type'];
                $vuln_arr[$vid] = $row['vuln_desc_primary'];
            }
            $this->dbConn->sql_freeresult($result);
        }

        return $vuln_arr;
    }

    function createFinding($post) {
        $fid = 0;
        $source = $post['source'];
        $asset_id = $post['asset_list'];
        // don't let user set status - 04/03/2006cfd
        $status = 'OPEN'; //$post['status'];
        $discovereddate = $post['discovereddate'];
        $finding_data = $post['finding_data'];

        //$opendate = $post['opendate'];
        //$closedate = $post['closedate'];
        $now = date("Y-m-d H:m:s");
        $m = substr($discovereddate, 0, 2);
        $d = substr($discovereddate, 3, 2);
        $y = substr($discovereddate, 6, 4);
        $disdate = strftime("%Y-%m-%d", (mktime(0, 0, 0, $m, $d, $y)));

        $sql = "INSERT INTO ".TN_FINDINGS." (source_id, asset_id, finding_status, finding_date_created,finding_date_discovered,finding_data)
                    VALUES ('$source', '$asset_id', '$status', '$now', '$disdate', '$finding_data')";
        $res  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
        if($res) {
            /*
            $sql = "SELECT max(finding_id) FROM ".TN_FINDINGS
                        WHERE source_id='$source' AND
                            asset_id='$asset_id' AND
                            finding_status='$status' AND
                            finding_date_created='$opendate'";
            $result  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
            if($result) {
                if($row = $this->dbConn->sql_fetchrow($result)) {
                    $fid = $row['finding_id'];
                }
                $this->dbConn->sql_freeresult($result);
            }
            */
            $fid = $this->dbConn->sql_nextid();
            if($fid > 0) {
                foreach($post AS $skey=>$svalue) {
                    if(substr($skey, 0, 6) == "vuln_-") {
                        list($vuln_seq, $vuln_type) = explode(":", $svalue);

                        $sql = "INSERT INTO ".TN_FINDING_VULNS." (finding_id, vuln_seq, vuln_type)
                                    VALUES ('$fid', '$vuln_seq', '$vuln_type')";
                        //echo $sql;
                        $res  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
                    }
                }
            }
        }

        return $fid;
    }

    function deleteFindings($post) {
        // delete finding, only set status is 'discard', not really delete FROM ".TN_database
        foreach($post AS $skey=>$svalue) {
            if(substr($skey, 0, 4) == "fid_" && substr($svalue, 0, 4) == "fid.") {
                $fid = intval(substr($svalue, 4));
                $sql = "UPDATE ".TN_FINDINGS." SET finding_status='deleted' WHERE finding_id='$fid'";
                //echo $sql;
                $res  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
                
                $sql = "DELETE FROM ".TN_POAMS." WHERE `finding_id`='$fid'";
                $res  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
                //$sql = "delete FROM ".TN_FINDING_VULNS WHERE finding_id='$fid'";
                //$res  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
            }
        }
    }

    function updateFinding($fid, $status) {
        //$now = date("Y-m-d H:m:s");
        $sql = "UPDATE ".TN_FINDINGS." SET finding_status='$status' WHERE finding_id='$fid'";
        $res  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
        return $res;
    }
}

?>
