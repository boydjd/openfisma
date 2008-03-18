<?PHP

// required for all pages, after user login is verified function displayloginfor checks all user security functions, gets the users first/last name and customer log as well as loads ovms.ini.php
require_once("config.php");
require_once("dblink.php");

header('Cache-control: no-cache, no-store');
header('Pragma: no-cache');
header('Expires: -1');
$lifeTime = 60*30;
session_set_cookie_params($lifeTime);
session_start();

$user = new User($db);

$smarty->assign("warning", $login_warning);
$login = 0;

// Check to see if the user is logged out, if so set status to 1 and display error message.
if(isset($_GET["logout"])) {
    $user->logout();
    $smarty->assign("login", 1);
    $smarty->assign("errmsg", "You are currently logged out.");
    $smarty->display("login.tpl");
    exit;
}

// Check to see if the user is trying to login and grab the status from _POST
if(isset($_POST["login"]))
    $login = intval($_POST["login"]);

// if the login status is set to 1 do the following
if($login == 1){
    $smarty->assign("login", $login);

    // check to see if username exists in _POST, if so set it to $username
    if(isset($_POST["username"]))
        $username = $_POST["username"];

    // check to see if password exists in _POST, if so set it to $userpass
    if(isset($_POST["userpass"]))
        $password = $_POST["userpass"];

    // Check to see if the username field was left blank and display error if it was
    if(empty($username)) {
        $smarty->assign("errmsg", "No Username was given, Please enter a Username to continue.");
        $smarty->display("login.tpl");
        exit;
    }

    // Check to see if the password field was left blank and display error if it was
    $smarty->assign("username", $username);
    if(empty($password)) {
        $smarty->assign("errmsg", "No Password was given, Please enter a Password to continue.");
        $smarty->display("login.tpl");
        exit;
    }

    // calls the login function in user.class.php
    $logined = $user->login($username, $password);

    if($logined == 1) {
        session_unregister('ovms_session_error_password');
        
        $ret = $user->checkExpired();
        $user->login(null,null);

        if($ret == 0) {
            // login ok, redirect to first page the user is allowed to view
            $header_screen = 'header';
            if($user->checkRightByFunction("dashboard", "view")) {
              header("Location: dashboard.php");
              }
            elseif($user->checkRightByFunction("finding", "view")) {
              header("Location: finding.php");
              }
            elseif($user->checkRightByFunction("asset", "view")) {
              header("Location: asset.php");
              }
            elseif($user->checkRightByFunction("remediation", "view")) {
              header("Location: remediation.php");
              }
            elseif($user->checkRightByFunction("report", "view")) {
              header("Location: report.php");
              }
            elseif($user->checkRightByFunction($header_screen, "admin_menu")) {
              header("Location: tbadm.php");
              }
            elseif($user->checkRightByFunction($header_screen, "vulner_menu")) {
              header("Location: vulnerabilities.php");
              }
            // default back to dashboard - which will just be rejected with an 
            // error message.
            else {
                header("Location: dashboard.php");
              }
            exit;
        }

        else {
            switch($ret) {
            case 1:
                $msg = "This is the first time you have logged in, Please change your password.";
                $url = "pwdchange.php";
                break;
            case 2:
                $msg = "Your password is currently expired and you have been locked out, please contact the administrator for assistance.";
                $url = "login.php";
                break;
            default:
                $msg = "Your password will expire after $ret days, Please change your password.";
                $url = "pwdchange.php";
                break;
            }

            echo "<script language=\"javascript\">\r\n";
            echo "alert(\"$msg\");\r\n";
            echo "window.location=\"$url\";\r\n";
            echo "</script>\r\n";
            exit;
        }
    }
    else {

        $ec = 0;
        if($user->getLoginStatus() == 3) {
            // count error times
            if(isset($_SESSION['ovms_session_error_password']))
                $ec = $_SESSION['ovms_session_error_password'] + 1;
            else
                $ec = 1;

            $ec = ($ec > 2 ? 4 : $ec);

            // error password count
            session_register('ovms_session_error_password');
            $_SESSION['ovms_session_error_password'] = $ec;
        }

        $user->loginFailed($smarty, $ec);
        exit;
    }
}
else {
    $smarty->assign("login", 1);
    $smarty->display("login.tpl");
}
?>
