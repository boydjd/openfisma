<?PHP
// no-cache ? forces caches to submit the request to the origin server for validation before releasing a cached copy, every time. This is useful to assure that authentication is respected.
// must-revalidate ? tells caches that they must obey any freshness information you give them about a representation. By specifying this header, you’re telling the cache that you want it to strictly follow your rules.
header("Cache-Control: no-cache, must-revalidate");

// required for all pages, after user login is verified function displayloginfor checks all user security functions, gets the users first/last name and customer log as well as loads ovms.ini.php
require_once("config.php");
require_once("dblink.php");
require_once("finding.class.php");
require_once("findingDBManager.php");

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
$view_right = $user->checkRightByFunction("finding", "read");
$edit_right = $user->checkRightByFunction("finding", "update");
$add_right  = $user->checkRightByFunction("finding", "create");
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

if(!empty($_POST)){
    if(isset($_POST['pageno']))		$smarty->assign('pageno', $_POST['pageno']);
    if(isset($_POST['asc']))		$smarty->assign('asc', $_POST['asc']);
    if(isset($_POST['fn']))			$smarty->assign('fn', $_POST['fn']);
    if(isset($_POST['sbt']))		$smarty->assign('submit', strtolower($_POST['sbt']));
    if(isset($_POST['startdate']))	$smarty->assign('startdate', $_POST['startdate']);
    if(isset($_POST['enddate']))	$smarty->assign('enddate', $_POST['enddate']);

    if(isset($_POST['status']))		$smarty->assign('status', $_POST['status']);
    if(isset($_POST['source']))		$smarty->assign('source', $_POST['source']);
    if(isset($_POST['system']))		$smarty->assign('system', $_POST['system']);
    if(isset($_POST['vulner']))		$smarty->assign('vulner', $_POST['vulner']);

    if(isset($_POST['product']))	$smarty->assign('product', $_POST['product']);
    if(isset($_POST['network']))	$smarty->assign('network', $_POST['network']);
    if(isset($_POST['ip']))			$smarty->assign('ip', $_POST['ip']);
    if(isset($_POST['port']))		$smarty->assign('port', $_POST['port']);
}

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
