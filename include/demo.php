<?PHP

require_once("config.php");
require_once("smarty.inc.php");
require_once("dblink.php");
require_once("user.class.php");
require_once("page_utils.php");

// set the screen name used for security functions
$screen_name = "demo";

// set the page name
$smarty->assign('pageName', 'Demo Page');

// session_start() creates a session or resumes the current one based on the current session id that's being passed via a request, such as GET, POST, or a cookie.
// If you want to use a named session, you must call session_name() before calling session_start().
session_start();

// creates a new user object from the user class
$user = new User($db);

// validates that the user is logged in properly, if not redirects to the login page.
verify_login($user, $smarty);

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

$smarty->assign('now', gmdate ("M d Y H:i:s", time()));
$smarty->display('demo.tpl');
?>
