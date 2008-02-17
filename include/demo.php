<?PHP

require_once("config.php");
require_once("smarty.inc.php");

require_once("dblink.php");

/**************User Rigth*****************/
require_once("user.class.php");

$screen_name = "demo";

session_start();

$user = new User($db);
$loginstatus = $user->login();
if($loginstatus != 1) {
	// redirect to the login page
	$user->loginFailed($smarty);
	exit;
}

// check the user's right
$view_right	= $user->checkRightByFunction($screen_name, "view");
$edit_right = $user->checkRightByFunction($screen_name, "edit");
$add_right  = $user->checkRightByFunction($screen_name, "add");
$del_right  = $user->checkRightByFunction($screen_name, "delete");

// let's template know how to display the page
$smarty->assign('view_right', $view_right);
$smarty->assign('edit_right', $edit_right);
$smarty->assign('add_right', $add_right);
$smarty->assign('del_right', $del_right);
/**************User Rigth*****************/



/**************Main Area*****************/
if($view_right) {

}

if($del_right) {

}

if($edit_right) {
	
}

if($add_right) {
	
}


// if user have the right for his request, then set the "noright" message
$smarty->assign('noright', $noright);

$smarty->assign('now', gmdate ("M d Y H:i:s", time()));

$smarty->display('demo.tpl');
?>
