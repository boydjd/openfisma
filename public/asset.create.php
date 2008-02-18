<?PHP
$query_string = @$_REQUEST;

require_once("config.php");
require_once("smarty.inc.php");
require_once("dblink.php");
require_once("asset.class.php");
require_once("assetDBManager.php");
// User class which is required by all pages which need to validate authentication and interact with variables of a user (Functions: login, getloginstatus, getusername, getuserid, getpassword, checkactive, etc)
require_once("user.class.php");
// Functions required by all front-end pages gathered in one place for ease of maintenance. (verify_login, sets global page title, insufficient priveleges error, and get_page_datetime)
require_once("page_utils.php");

// This function will turn output buffering on. While output buffering is active no output is sent from the script (other than headers), instead the output is stored in an internal buffer.
ob_start();

// set the page name
$smarty->assign('pageName', 'Create an Asset');

// session_start() creates a session or resumes the current one based on the current session id that's being passed via a request, such as GET, POST, or a cookie.
// If you want to use a named session, you must call session_name() before calling session_start().
session_start();

// creates a new user object from the user class
$user = new User($db);

// validates that the user is logged in properly, if not redirects to the login page.
verify_login($user, $smarty);

$view_right	= $user->checkRightByFunction("asset", "view");
$edit_right = $user->checkRightByFunction("asset", "edit");
$add_right  = $user->checkRightByFunction("asset", "add");
$del_right  = $user->checkRightByFunction("asset", "delete");

$smarty->assign('view_right', $view_right);
$smarty->assign('edit_right', $edit_right);
$smarty->assign('add_right', $add_right);
$smarty->assign('del_right', $del_right);

if($edit_right) {
	//$query_string = $_REQUEST;
	extract($query_string);
	
	$dbObj = new AssetDBManager($db);
	
	if (!isset($listall) || $listall=="" || $listall==0)
	{	
		$limitnum=10;
		$listall=1;
	}
	else 
		$limitnum=0;
	$pageno = isset($pageno)?intval($pageno):0; 
	if ($pageno<1) $pageno = 1;	
	$prod_search_data  = $dbObj->searchProduct($query_string,0);
	
	$system_list  = $dbObj->getSystemList();
	$network_list = $dbObj->getNetworkList();
	
	if (isset($add) && ($add == 'add') && $edit_right)
	{
		if ($dbObj->createAsset($query_string))
		{
			ob_end_clean();
			header("Location: asset.php");
			exit();
		}
	}
	$smarty->assign('listall',$listall);
	$smarty->assign('pageno',$pageno);
	$smarty->assign('maxpageno',$maxpageno);
	$smarty->assign('assetname',isset($assetname)?$assetname:'');
	$smarty->assign('system',isset($system)?$system:'');
	$smarty->assign('network',isset($network)?$network:'');
	$smarty->assign('ip',isset($ip)?$ip:'');
	$smarty->assign('port',isset($port)?$port:'');
	if (!isset($addrtype)) $addrtype=1;
	$smarty->assign('chked'.$addrtype,'Checked');
	$smarty->assign('prod_id',isset($prod_id)?$prod_id:null);
	$smarty->assign('product_search',isset($product_search)?$product_search:'');
	$smarty->assign('prod_search_data', isset($prod_search_data)?$prod_search_data:'');
	$smarty->assign('system_list', $system_list);
	$smarty->assign('network_list', $network_list);
	$smarty->assign('action','create');
	$smarty->assign('formaction','asset.create.php');
}	
	$smarty->display('assetsCreate.tpl');

?>
