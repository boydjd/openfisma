<?PHP
// no-cache — forces caches to submit the request to the origin server for validation before releasing a cached copy, every time. This is useful to assure that authentication is respected.
// must-revalidate — tells caches that they must obey any freshness information you give them about a representation. By specifying this header, you’re telling the cache that you want it to strictly follow your rules.
header("Cache-Control: no-cache, must-revalidate");

// required for all pages, after user login is verified function displayloginfor checks all user security functions, gets the users first/last name and customer log as well as loads ovms.ini.php
require_once("config.php");
require_once("dblink.php");
require_once("asset.class.php");

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

$view_right	= $user->checkRightByFunction("asset", "view");
$edit_right = $user->checkRightByFunction("asset", "edit");
$add_right  = $user->checkRightByFunction("asset", "add");
$del_right  = $user->checkRightByFunction("asset", "delete");

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

// display our template
$smarty->display('asset_detail.tpl');

?>
