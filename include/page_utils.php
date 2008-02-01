<?PHP
/*
** Functions required by all front-end pages, 
** gathered in one place for ease of maintenance.
*/

function verify_login($user, $smarty) {
  $loginstatus = $user->login();
  if($loginstatus != 1) {
    // redirect to the login page
    $user->loginFailed($smarty);
    exit;
    }
 
  displayLoginInfor($smarty, $user);
  }

/*
** date_default_timezone_set() sets the default timezone used by all date/time functions.
*/  
  
function get_page_datetime() {
  date_default_timezone_set('America/New_York');
  return strftime("%b %d %Y %I:%M:%S %p");
  }

// Sets the title of each page
$smarty->assign('pageTitle', 'OpenFISMA');

// Sets the timezone of all dates
$smarty->assign('now', get_page_datetime());

// set the users first name
$smarty->assign("firstname", $user->user_name_first);

// set the users last name
$smarty->assign("lastname", $user->user_name_last);

?>
