<?PHP
header("Cache-Control: no-cache, must-revalidate");

error_reporting(0);
session_start();
ob_start();

$query_string = @$_REQUEST;
require_once("config.php");
require_once("smarty.inc.php");
require_once("dblink.php");

require_once("asset.class.php");
require_once("assetDBManager.php");

/* BEGIN **** User Right ***************/
require_once("user.class.php");
require_once("page_utils.php");


$screen_name = "asset";

$user = new User($db);

/*
$loginstatus = $user->login();
if($loginstatus != 1) {
	// redirect to the login page
	$user->loginFailed($smarty);
	exit;
}
displayLoginInfor($smarty, $user);
*/
verify_login($user, $smarty);

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



// redirect if there was no information posted
if (isset($_POST['asset_id'])) { $asset_id = $_POST['asset_id']; }
else { header('Location: asset.php'); }



if($edit_right && $asset_id>0)
{
	/*
	echo "<br> ******** query_string ************ <br>";
	print_r($query_string);
	echo "<br> ********  ************ <br>";
	*/
	extract($query_string);

	//echo(__LINE__.$search.__LINE__.$add.__LINE__.$edit);
	$dbObj = new AssetDBManager($db);
	if (isset($edit))
	{
		//echo "11111111edit" ;
		if ($dbObj->updateAsset($query_string,$asset_id))
		{
			//ob_end_flush();
			//header("Location: asset.php");
			//exit();
			//unset($_GET) ;
		}
	}

	if (!isset($search) && !isset($edit))
	{
		//echo "11111111 no edit no search" ;

		$filter_data = $dbObj->searchAssets($query_string,$asset_id,1);
		$assetname = $filter_data[0]['asset_name'];
		$system = $filter_data[0]['system_id'];
		$network = $filter_data[0]['network_id'];
		$ip = $filter_data[0]['address_ip'];
		$port = $filter_data[0]['address_port'];
		$prod_id = $filter_data[0]['prod_id'];
		$current_prod_id = $prod_id;

		if (strlen($ip)>7 && sizeof(explode('.',$ip))>4) $addrtype=2;
		if (strlen($ip)>7 && sizeof(explode('.',$ip))>4) $addrtype=1;
	}
	else
		$current_prod_id = $_POST['current_prod_id'];


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

	///////////added by chang 03232006
	$current_prod_info  = $dbObj->getProductByID($current_prod_id);
	/*
	echo " *************** <br>";
	echo $current_prod_id ;
	echo " ******current_prod_info******* <br>";
	print_r($current_prod_info) ;
	echo " <br> *************** <br>";
	*/

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
	$smarty->assign('product_search', @$product_search);
	$smarty->assign('prod_search_data', $prod_search_data);
	$smarty->assign('system_list', $system_list);
	$smarty->assign('network_list', $network_list);
	$smarty->assign('action','edit');
	$smarty->assign('asset_id',$asset_id);

	//updated by chang
	$smarty->assign('current_prod_id',$current_prod_id);
	$smarty->assign('current_prod_info',$current_prod_info);

	//$smarty->assign('formaction','asset_modify.php?asset_id='.$asset_id);
	$smarty->assign('formaction','asset_modify.php');
}
	$smarty->assign('pageTitle', 'OVMS');
	$smarty->assign('pageName', 'Update an Asset');
	$smarty->assign('now', get_page_datetime());

	$smarty->display('asset_modify.tpl');
?>
