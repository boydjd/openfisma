<?PHP
// no-cache — forces caches to submit the request to the origin server for validation before releasing a cached copy, every time. This is useful to assure that authentication is respected.
// must-revalidate — tells caches that they must obey any freshness information you give them about a representation. By specifying this header, you’re telling the cache that you want it to strictly follow your rules.
header("Cache-Control: no-cache, must-revalidate"); 

error_reporting(0);
ob_start();
$query_string = @$_REQUEST;
require_once("config.php");
require_once("smarty.inc.php");
require_once("dblink.php");
require_once("page_utils.php");
require_once("asset.class.php");
require_once("assetDBManager.php");
require_once("user.class.php");

// set the screen name used for security functions
$screen_name = "asset";

// set the page name
$smarty->assign('pageName', 'View an Asset');

// session_start() creates a session or resumes the current one based on the current session id that's being passed via a request, such as GET, POST, or a cookie.
// If you want to use a named session, you must call session_name() before calling session_start().
session_start();

// creates a new user object from the user class
$user = new User($db);

// validates that the user is logged in properly, if not redirects to the login page.
verify_login($user, $smarty);

// get user right for this screen
// $user->checkRightByFunction($screen_name, "function_name");

$view_right	= $user->checkRightByFunction($screen_name, "view");
$edit_right = $user->checkRightByFunction($screen_name, "edit");
$add_right  = $user->checkRightByFunction($screen_name, "add");
$del_right  = $user->checkRightByFunction($screen_name, "delete");
//$view_right=0;
//$edit_right=0;
//$add_right=0;
//$del_right=0;
// let's template know how to display the page
$smarty->assign('view_right', $view_right);
$smarty->assign('edit_right', $edit_right);
$smarty->assign('add_right', $add_right);
$smarty->assign('del_right', $del_right);
/* END **** User Right ***************/

if (isset($_REQUEST['aid']))
	$aid = $_REQUEST['aid'];


if($view_right && $aid>0) {


	extract($query_string);


	//echo(__LINE__.$search.__LINE__.$add.__LINE__.$edit);
	$dbObj = new AssetDBManager($db);
	if ($edit)
	{



		if ($dbObj->updateAsset($query_string,$aid))
		{
			ob_end_flush();
			header("Location: asset.php");
			exit();
		}

	}
	if (!$search && !$edit)
	{
		$filter_data = $dbObj->searchAssets($query_string,$aid,1);
		$assetname = $filter_data[0]['asset_name'];
		$system = $filter_data[0]['system_id'];
		$network = $filter_data[0]['network_id'];
		$ip = $filter_data[0]['address_ip'];
		$port = $filter_data[0]['address_port'];
		$prod_id = $filter_data[0]['prod_id'];
		if (strlen($ip)>7 && sizeof(explode('.',$ip))>4) $addrtype=2;
		if (strlen($ip)>7 && sizeof(explode('.',$ip))>4) $addrtype=1;


	}
	if (!isset($prod_id)) $prod_id=0;

	if (!isset($listall) || $listall=="" || $listall==0)
	{
		$limitnum = 10;
		$listall = 1;
		$prod_id_t = $prod_id;
	}
	else
	{
		$limitnum=0;
		$prod_id_t = 0;
	}
	$pageno = (int) $pageno;
	if ($pageno<1) $pageno = 1;
	$prod_search_data  = $dbObj->searchProduct($query_string,$prod_id_t);

	$system_list  = $dbObj->getSystemList();
	$network_list = $dbObj->getNetworkList();


	$smarty->assign('listall',$listall);
	$smarty->assign('pageno',$pageno);
	$smarty->assign('maxpageno',$maxpageno);
	$smarty->assign('assetname',$assetname);
	$smarty->assign('system',$system);
	$smarty->assign('network',$network);
	$smarty->assign('ip',$ip);
	$smarty->assign('port',$port);
	if (!isset($addrtype)) $addrtype=1;
	$smarty->assign('chked'.$addrtype,'Checked');
	$smarty->assign('prod_id',$prod_id);
	$smarty->assign('product_search',$product_search);
	$smarty->assign('prod_search_data', $prod_search_data);
	$smarty->assign('system_list', $system_list);
	$smarty->assign('network_list', $network_list);
	$smarty->assign('aid',$aid);

}

	$smarty->display('assetsview.tpl');

?>
