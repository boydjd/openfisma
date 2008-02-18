<?PHP

// Functions required by all front-end pages, 
// gathered in one place for ease of maintenance.
function verify_login($user, $smarty) {
    $loginstatus = $user->login();
    if($loginstatus != 1) {
    // redirect to the login page
    $user->loginFailed($smarty);
    exit;
    }
 
  displayLoginInfor($smarty, $user);
}

// date_default_timezone_set() sets the default timezone used by all date/time functions.
function get_page_datetime() {
  date_default_timezone_set('America/New_York');
  return strftime("%b %d %Y %I:%M:%S %p");
}

// Sets the master time for smarty
$smarty->assign('now', get_page_datetime());

// Sets the master pagetitle for OpenFISMA
$smarty->assign('pageTitle', 'OpenFISMA');

// set the error message for insufficient privileges
$smarty->assign('noright', "Sorry, you currently do not have sufficient privileges to complete your request.");

?>
