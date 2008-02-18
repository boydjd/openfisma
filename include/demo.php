<?PHP

// required for all pages, after user login is verified function displayloginfor checks all user security functions, gets the users first/last name and customer log as well as loads ovms.ini.php
require_once("config.php");
// required for all pages, sets smarty directory locations for cache, templates, etc.
require_once("smarty.inc.php");
require_once("dblink.php");
// User class which is required by all pages which need to validate authentication and interact with variables of a user (Functions: login, getloginstatus, getusername, getuserid, getpassword, checkactive, etc)
require_once("user.class.php");
// Functions required by all front-end pages gathered in one place for ease of maintenance. (verify_login, sets global page title, insufficient priveleges error, and get_page_datetime)
require_once("page_utils.php");

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
$view_right	= $user->checkRightByFunction("demo", "view");
$edit_right = $user->checkRightByFunction("demo", "edit");
$add_right  = $user->checkRightByFunction("demo", "add");
$del_right  = $user->checkRightByFunction("demo", "delete");

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
