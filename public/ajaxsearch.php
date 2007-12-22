<?PHP
require_once("config.php");
require_once("dblink.php");
require_once("findingDBManager.php");
require_once("finding.class.php");


/*
if(isset($_REQUEST['asset_needle'])) {
	$needle = $_REQUEST['asset_needle'];
	if($needle == "yes") {
		$system_id = $_REQUEST['system_id'];
		$network_id = $_REQUEST['network_id'];
		$ip = $_REQUEST['ip'];
		$port = $_REQUEST['port'];

		$dbObj = new FindingDBManager($db);
		$asset_arr = $dbObj->getAssetListBySearch($system_id, $network_id, $ip, $port);
		//print_r($asset_arr);
		xmlFormatOfAssetList($asset_arr);
	}
}
*/

if(isset($_REQUEST['asset_needle'])) {
	$needle = $_REQUEST['asset_needle'];
	$system_id = $_REQUEST['system_id'];

	$dbObj = new FindingDBManager($db);
	$asset_arr = $dbObj->getAssetList($needle, $system_id);
	//print_r($asset_arr);
	xmlFormatOfAssetList($asset_arr);
}


if(isset($_REQUEST['vulner_needle'])) {
	$needle   = $_REQUEST['vulner_needle'];
	$offset   = $_POST['vuln_offset'];
	$num_rows = $_POST['NUM_VULN_ROWS'];
	$dbObj = new FindingDBManager($db);
	$vulner_arr = $dbObj->getVulnerList($needle, $offset, $num_rows);
	xmlFormatOfVulner($vulner_arr);
}

if(isset($_REQUEST['assetid_needle'])) {
	$needle = intval($_REQUEST['assetid_needle']);
	if($needle > 0) {
		$assetObj = new Asset($needle, $db);
		xmlFormatOfAsset($assetObj);
	}
}


function xmlFormatOfVulner($arr) {
	header('Content-type: text/xml;charset=ISO-8859-1');

	echo "<vulners>\r\n";
	foreach($arr as $vkey=>$vdesc) {
		list($seq, $type) = explode(":", $vkey);
		echo "\t<vulner vuln_seq='$seq' vuln_type='$type'>\r\n";
		echo "\t\t<vuln_desc>$vdesc</vuln_desc>\r\n";
		echo "\t</vulner>\r\n";
	}
	echo "</vulners>\r\n";
}

function xmlFormatOfAssetList($arr) {
	header('Content-type: text/xml;charset=ISO-8859-1');

	echo "<assets>\r\n";
	foreach($arr as $akey=>$aname) {
		echo "\t<asset asset_id='$akey' asset_name='$aname' />\r\n";
	}
	echo "</assets>\r\n";
}

function xmlFormatOfAsset($obj) {
?>
<table border="0" width="100%" cellpadding="3" cellspacing="1">
<tr>
	<td>
	<table border="0"cellpadding="3" cellspacing="1">
	<tr>
		<td>&nbsp;</td>
		<td align="right"><b>System:</b></td>
		<td colspan="7">
<?PHP
	for($i = 0; $i < count($obj->system_arr); $i++) {
	    if($i > 0) { echo " |&nbsp;"; }
		echo $obj->system_arr[$i];
	}
?>
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td align="right"><b>IP Address:</b></td>
		<td colspan="7">
<?PHP
	for($i = 0; $i < count($obj->ipaddr_arr); $i++) {
	    if($i > 0) { echo " |&nbsp;"; }
		echo $obj->ipaddr_arr[$i];
	}
?>
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td align="right"><b>Product:</b></td>
		<td><?=$obj->prod_name?></td>
		<td>&nbsp;</td>
		<td align="right"><b>Vendor:</b></td>
		<td><?=$obj->prod_vendor?></td>
		<td>&nbsp;</td>
		<td align="right"><b>Version:</b></td>
		<td><?=$obj->prod_version?></td>
	</tr>
	</table>
	</td>
</tr>
</table>
<?PHP
}
?>