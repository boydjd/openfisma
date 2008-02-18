<?PHP
// no-cache — forces caches to submit the request to the origin server for validation before releasing a cached copy, every time. This is useful to assure that authentication is respected.
// must-revalidate — tells caches that they must obey any freshness information you give them about a representation. By specifying this header, you’re telling the cache that you want it to strictly follow your rules.
header("Cache-Control: no-cache, must-revalidate");

require_once("config.php");
require_once("smarty.inc.php");
require_once("dblink.php");
require_once("finding.class.php");
require_once("findingDBManager.php");
// User class which is required by all pages which need to validate authentication and interact with variables of a user (Functions: login, getloginstatus, getusername, getuserid, getpassword, checkactive, etc)
require_once("user.class.php");
// Functions required by all front-end pages gathered in one place for ease of maintenance. (verify_login, sets global page title, insufficient priveleges error, and get_page_datetime)
require_once("page_utils.php");

// set the page name
$smarty->assign('pageName', 'Create a Finding');

// session_start() creates a session or resumes the current one based on the current session id that's being passed via a request, such as GET, POST, or a cookie.
// If you want to use a named session, you must call session_name() before calling session_start().
session_start();

// creates a new user object from the user class
$user = new User($db);

// validates that the user is logged in properly, if not redirects to the login page.
verify_login($user, $smarty);

// get user right for this screen
// $user->checkRightByFunction($screen_name, "function_name");
$view_right	= $user->checkRightByFunction("finding", "view");
$edit_right = $user->checkRightByFunction("finding", "edit");
$add_right  = $user->checkRightByFunction("finding", "add");
//$del_right  = $user->checkRightByFunction("finding", "delete");

// let's template know how to display the page
$smarty->assign('view_right', $view_right);
$smarty->assign('edit_right', $edit_right);
$smarty->assign('add_right', $add_right);
//$smarty->assign('del_right', $del_right);
/**************User Rigth*****************/

$fid = 0;
$act = "new";
$do = "none";

if(isset($_POST['fid']))
	$fid = intval($_POST['fid']);
if(isset($_POST['act']))
	$act = strtolower(trim($_POST['act']));
//var_dump($_POST);
/***************finding page search informations******************************************/
if(!empty($_POST)){
    $smarty->assign('pageno', $_POST['pageno']);
    $smarty->assign('asc', $_POST['asc']);
    $smarty->assign('fn', $_POST['fn']);
    $smarty->assign('submit', strtolower($_POST['sbt']));
    $smarty->assign('startdate', $_POST['startdate']);
    $smarty->assign('enddate', $_POST['enddate']);
    
    $smarty->assign('status', $_POST['status']);
    $smarty->assign('source', $_POST['source']);
    $smarty->assign('system', $_POST['system']);
    $smarty->assign('vulner', $_POST['vulner']);
    
    $smarty->assign('product', $_POST['product']);
    $smarty->assign('network', $_POST['network']);
    $smarty->assign('ip', $_POST['ip']);
    $smarty->assign('port', $_POST['port']);
}
/******************************************************************/

if(isset($_POST['do']))
	$do = strtolower(trim($_POST['do']));

$smarty->assign('act', $act); // operate action
$smarty->assign('fid', $fid);		// finding id

$dbObj = new FindingDBManager($db);

// edit right
if($act == "edit" && $edit_right) {
	if($do == "update") {
		$res = $dbObj->updateFinding($fid, $_POST['status']);
		if($res)
			$smarty->assign("msg", "Finding updated successfully");
		else
			$smarty->assign("msg", "Finding update failed");
	}

	$findingObj = $dbObj->getFindingByID($fid, true);
	if($findingObj->getErrno() == 0) {
		// the finding is exist
		//print_r($findingObj);
		$smarty->assign('finding', $findingObj);
	}

	$smarty->display('findingdetail.tpl');
}
else if($act == "view" && $view_right) {
	$findingObj = $dbObj->getFindingByID($fid, true);
	if($findingObj->getErrno() == 0) {
		// the finding is exist
		//print_r($findingObj);
		$smarty->assign('finding', $findingObj);
	}

	$smarty->display('findingdetail.tpl');
}
else if($add_right) {
	if($do == "create") {
		//print_r($_POST);
		$res = $dbObj->createFinding($_POST);
		if($res)
			$smarty->assign("msg", "Finding created successfully");
		else
			$smarty->assign("msg", "Finding creation failed");
	}
	$discovered_date = strftime("%m/%d/%Y", (mktime(0, 0, 0, date("m")  , date("d"), date("Y"))));

	$source_list = $dbObj->getSourceList();
	$asset_list = $dbObj->getAssetList();
	$system_list  = $dbObj->getSystemList();
	$network_list = $dbObj->getNetworkList();

	$smarty->assign('discovered_date', $discovered_date);
	$smarty->assign('system_list', $system_list);
	$smarty->assign('network_list', $network_list);
	$smarty->assign('source_list', $source_list);
	$smarty->assign('asset_list', $asset_list);

	// add a new finding
	list($asset_id, $svalue) = each ($asset_list);
	$assetObj = new Asset($asset_id, $db);
	//$smarty->assign('asset_id', $asset_id);
	$smarty->assign('assetObj', $assetObj);

	$act = "new";
	$smarty->display('findingedit.tpl');
}
else {
	$smarty->display('findingdetail.tpl');
}

?>
