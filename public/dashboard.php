<?PHP
header("Cache-Control: no-cache, must-revalidate"); 

require_once("config.php");
require_once("dblink.php");
require_once("smarty.inc.php");
require_once("finding.class.php");
require_once("findingDBManager.php");
require_once("user.class.php");
require_once("page_utils.php");

$screen_name = "dashboard";
$smarty->assign('pageName', 'Dashboard');

session_start();

$user = new User($db);


$loginstatus = $user->login();

$Role_ID = $user->getRoleId() ;

/*
if($loginstatus != 1) {
	// redirect to the login page
	$user->loginFailed($smarty);
	exit;
}

displayLoginInfor($smarty, $user);
*/
verify_login($user, $smarty);


// get user right for this screen
// $user->checkRightByFunction($screen_name, "function_name");

$view_right	= $user->checkRightByFunction($screen_name, "view");
$edit_right = $user->checkRightByFunction($screen_name, "edit");
$add_right  = $user->checkRightByFunction($screen_name, "add");
$del_right  = $user->checkRightByFunction($screen_name, "delete");

// let's template know how to display the page

/**************User Rigth*****************/
//echo "view_right : ". $view_right . " edit_right : ". $edit_right. " add_right : ". $add_right . " del_right : ". $del_right ;
/**************Main Area*****************/
if($view_right || $del_right || $edit_right) 
{

  // conditionally require dashboard items
  require_once("dashboard_chart/Dashboard_Chart.php");
  require_once("dashboard_chart/create_xml.php");
  require_once("PoamSummary.class.php");

  // Get chart xml data and create charts
  $summary = generate_summary($user, new PoamSummary($db));
  create_xml_1($summary['total_open'], $summary['total_en'], $summary['total_eo'], $summary['total_ep'], $summary['total_es'], $summary['total_closed']);
  create_xml_2($summary['total_open'], $summary['total_en'], $summary['total_eo'], $summary['total_ep'], $summary['total_es'], $summary['total_closed']);
//  create_xml_3($summary['total_none'], $summary['total_cap'], $summary['total_fp'], $summary['total_ar']);
  create_xml_3(43, 31, 41, 55);

  // create a new chart and insert charts
  $dbc1 = new Dashboard_Chart;	
  $smarty->assign('dashboard1', $dbc1->InsertChart("temp/dashboard1.xml", "380" , "220"));
  $smarty->assign('dashboard2', $dbc1->InsertChart("temp/dashboard2.xml" , "200" , "220"));
  $smarty->assign('dashboard3', $dbc1->InsertChart("temp/dashboard3.xml" , "380" , "220"));
	

  // 
  // ALERTS
  // 

  $smarty->assign('need_type',  '<li>There are <b>' . $summary['total_none'] . '</b> items awaiting mitigation type (and approval).');
  $smarty->assign('need_mit',   '<li>There are <b>' . $summary['total_open'] . '</b> items awaiting mitigation approval.');
  $smarty->assign('need_ev_ot', '<li>There are <b>' . $summary['total_en'] .   '</b> items awaiting evidence (on time).');
  $smarty->assign('need_ev_od', '<li>There are <b>' . $summary['total_eo'] .   '</b> items awaiting evidence (overdue).');

  // -------------------------------------------------------------------

  $smarty->assign('now', get_page_datetime());
	
  $smarty->assign('view_right', $view_right);
  $smarty->assign('edit_right', $edit_right);
  $smarty->assign('add_right', $add_right);
  $smarty->assign('del_right', $del_right);
	
  $smarty->assign("firstname", $user->user_name_first);
  $smarty->assign("lastname", $user->user_name_last);
  $smarty->assign("customer_url", $customer_url);
  $smarty->assign("customer_logo", $customer_logo);	

}	
	
$smarty->display('dashboard.tpl');
?>
