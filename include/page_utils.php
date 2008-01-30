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

function get_page_datetime() {
    date_default_timezone_set('UTC') ;
  return strftime("%b %d %Y %I:%M:%S %p");
  }




?>
