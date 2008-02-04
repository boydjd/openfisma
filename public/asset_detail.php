<?PHP
header("Cache-Control: no-cache, must-revalidate");

require_once("config.php");
require_once("smarty.inc.php");
require_once("dblink.php");
require_once("page_utils.php");
require_once("asset.class.php");


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

if (isset($_POST['asset_id'])) { $asset_id = $_POST['asset_id']; }
else { header('Location: asset.php'); }


if($view_right && $asset_id>0) {

  $asset = new Asset($asset_id, $db);

  $smarty->assign('asset_name',         $asset->getAssetName());
  $smarty->assign('asset_date_created', $asset->getAssetDateCreated());
  $smarty->assign('asset_source',       $asset->getAssetSource());

  $smarty->assign('product_vendor',     $asset->getProductVendor());
  $smarty->assign('product_name',       $asset->getProductName());
  $smarty->assign('product_version',    $asset->getProductVersion());

  $smarty->assign('address',            $asset->getAddress());


}


$smarty->assign('pageTitle', 'OpenFISMA');
$smarty->assign('pageName', 'View an Asset');
$smarty->assign('now', get_page_datetime());


// display our template
$smarty->display('asset_detail.tpl');

?>
