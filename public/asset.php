<?PHP
header("Cache-Control: no-cache, must-revalidate");

error_reporting(0);
$query_string = @$_REQUEST;
require_once("config.php");
require_once("smarty.inc.php");
require_once("dblink.php");
//require_once("asset.class.php");
require_once("assetDBManager.php");

/* BEGIN **** User Right ***************/
require_once("user.class.php");
$screen_name = "asset";

session_start();
$user = new User($db);

$loginstatus = $user->login();
if($loginstatus != 1) {
	// redirect to the login page
	$user->loginFailed($smarty);
	exit;
}



displayLoginInfor($smarty, $user);
/*
$smarty->assign("username", $user->getUsername());
$smarty->assign("customer_url", $customer_url);
$smarty->assign("customer_logo", $customer_logo);
*/


// get user right for this screen
// $user->checkRightByFunction($screen_name, "function_name");

$view_right	= $user->checkRightByFunction($screen_name, "view");
$edit_right = $user->checkRightByFunction($screen_name, "edit");
$add_right  = $user->checkRightByFunction($screen_name, "add");
$del_right  = $user->checkRightByFunction($screen_name, "delete");


// let's template know how to display the page
$smarty->assign('view_right', $view_right);
$smarty->assign('edit_right', $edit_right);
$smarty->assign('add_right', $add_right);
$smarty->assign('del_right', $del_right);
/* END **** User Right ***************/

/**************Main Area*****************/
if($view_right || $del_right || $edit_right) {
	//$query_string = $_REQUEST;
	extract($query_string);
	$dbObj = new AssetDBManager($db);
	if (isset($action) && $action == "Delete" && $del_right)
	{

		$dbObj->deleteAssets($query_string);
	}
	if (!isset($listall) || $listall=="" || $listall==0)
	{
		$limitnum=10;
		$listall=1;
	}
	else
		$limitnum=0;
	$pageno = isset($pageno)?(int)$pageno:0;
	if ($pageno<1) $pageno = 1;
 	$filter_data = $dbObj->searchAssets($query_string,0);
	
	$report_filter_data = array();
    if(is_array($filter_data)){
    	foreach ($filter_data as $key => $valarr) {
    		if (is_array($valarr))
    		{
    			$report_filter_data[] = array('Asset Name' => $valarr['asset_name'],
    										  'System' => $valarr['system_name'],
    										  'IP Address' => $valarr['address_ip'],
    										  'Port' => $valarr['address_port'],
    										  'Product' => $valarr['prod_name'],
    										  'Vendor' => $valarr['prod_vendor']);
    		}
    	}
    }
	$_SESSION['asset_report_data'] = $report_filter_data;
	$_SESSION['asset_report_querystring'] = $query_string;
	$smarty->assign('filter_data', $filter_data);
	$smarty->assign('filter_data_rownum', sizeof($filter_data));

	$summary_data = $dbObj->getSummaryList();

	$system_list  = $dbObj->getSystemList();
	$network_list = $dbObj->getNetworkList();

	$smarty->assign('listall',$listall);
	$smarty->assign('pageno',isset($pageno)?$pageno:0);
	$smarty->assign('order',isset($order)?$order:0);
	$smarty->assign('orderbyfield', isset($orderbyfield)?$orderbyfield:'');
	$smarty->assign('maxpageno',$maxpageno);
	$smarty->assign('vendor', isset($vendor)?$vendor:'');
	$smarty->assign('product', isset($product)?$product:'');
	$smarty->assign('ip', isset($ip)?$ip:'');
	$smarty->assign('port', isset($port)?$port:'');
	$smarty->assign('version', isset($version)?$version:'');
	$smarty->assign('system', isset($system)?$system:'');
	$smarty->assign('summary_data', $summary_data);
	$smarty->assign('system_list', $system_list);
	$smarty->assign('network_list', $network_list);
}

	$smarty->assign('now', gmdate ("M d Y H:i:s", time()));
	$smarty->display('assets.tpl');


?>
