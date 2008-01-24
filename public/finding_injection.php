<?PHP
header("Cache-Control: no-cache, must-revalidate");

require_once("config.php");
require_once("smarty.inc.php");
require_once("ovms.ini.php");
require_once("dblink.php");
require_once("finding.class.php");
require_once("findingDBManager.php");

require_once("upload_utils.php");

/**************User Rights*****************/
require_once("user.class.php");


$screen_name = "finding"; // sync with the finding page upload permissions

session_start();

$user = new User($db);
$loginstatus = $user->login();
if($loginstatus != 1) {
        // redirect to the login page
        $user->loginFailed($smarty);
        exit;
}

displayLoginInfor($smarty, $user);

// retrieve the user's persmissions

$upload_right = $user->checkRightByFunction($screen_name, "upload");

if (!$upload_right) {

	/*
	** Bail out here if the user has insufficient privileges
	*/
	$smarty->assign('err_msg', 'Insufficient privilege to access this function.');
	$smarty->display('finding_upload_status.tpl');
	return;

}
$smarty->display('finding_injection.tpl');
?>
