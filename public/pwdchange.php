<?PHP

// third party packages
require_once("config.php");
require_once("smarty.inc.php");
require_once("dblink.php");
require_once("user.class.php");
require_once("page_utils.php");

session_start();

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
