<?PHP

// required for all pages, after user login is verified function displayloginfor checks all user security functions, gets the users first/last name and customer log as well as loads ovms.ini.php
require_once("config.php");
require_once("dblink.php");

// set the page name
$smarty->assign('pageName', 'Change Password');

// session_start() creates a session or resumes the current one based on the current session id that's being passed via a request, such as GET, POST, or a cookie.
// If you want to use a named session, you must call session_name() before calling session_start().
session_start();

// creates a new user object from the user class
$user = new User($db);

// validates that the user is logged in properly, if not redirects to the login page.
verify_login($user, $smarty);


$ret = $user->checkExpired();
$firstlogin = ($ret == 1 ? true : false);

if(isset($_POST['oldpass']) && isset($_POST['newpass']) && isset($_POST['cfmpass'])) {
	$oldpass = $_POST['oldpass'];
	$newpass = $_POST['newpass'];
	$cfmpass = $_POST['cfmpass'];

	$ret = $user->changePassword($oldpass, $newpass, $cfmpass);
	if($ret == 0) {
		// change password ok
		$firstlogin = false;
	}

	$smarty->assign("errmsg", $ret);
	$smarty->assign('chgflag', true);
}
else {
	$smarty->assign('chgflag', false);   
}

//
$smarty->assign('firstlogin', $firstlogin); 
// pass variables to smarty template
$smarty->assign('now', get_page_datetime());   

$smarty->display('pwdchange.tpl');
?>
