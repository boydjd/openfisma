<?PHP
header("Cache-Control: no-cache, must-revalidate"); 

// include files
require_once("config.php");
require_once("dblink.php");
require_once("smarty.inc.php");
require_once("finding.class.php");
require_once("findingDBManager.php");
require_once("user.class.php");
require_once("page_utils.php");

// set the screen name used for security functions
$screen_name = "dashboard";

// set the page name
$smarty->assign('pageName', 'Dashboard');

// creates a session
session_start();

//
$user = new User($db);

//
$loginstatus = $user->login();

// get user role
$Role_ID = $user->getRoleId() ;

// uses the verify login function in page_utils.php to verify username and password
verify_login($user, $smarty);

// get user right for this screen
$view_right	= $user->checkRightByFunction($screen_name, "view");

// let's template know how to display the page
$smarty->assign('view_right', $view_right);

// check the user rights for viewing dashboard
if($view_right) 
{

  // conditionally require dashboard items
  require_once("dashboard_chart/Dashboard_Chart.php");
  require_once("dashboard_chart/create_xml.php");
  require_once("PoamSummary.class.php");

  // Get chart xml data and create charts
  $summary = generate_summary($user, new PoamSummary($db));
  create_xml_1($summary['total_open'], $summary['total_en'], $summary['total_eo'], $summary['total_ep'], $summary['total_es'], $summary['total_closed']);
  create_xml_2($summary['total_open'], $summary['total_en'], $summary['total_eo'], $summary['total_ep'], $summary['total_es'], $summary['total_closed']);
  create_xml_3($summary['total_none'], $summary['total_cap'], $summary['total_fp'], $summary['total_ar']);
  //create_xml_3(43, 31, 41, 55);

  // create a new chart and insert charts
  $dbc1 = new Dashboard_Chart;	
  $smarty->assign('dashboard1', $dbc1->InsertChart("temp/dashboard1.xml", "380" , "220"));
  $smarty->assign('dashboard2', $dbc1->InsertChart("temp/dashboard2.xml" , "200" , "220"));
  $smarty->assign('dashboard3', $dbc1->InsertChart("temp/dashboard3.xml" , "380" , "220"));
	

  // 
  // ALERTS
  // 

  //$smarty->assign('need_type',  '<li>There are <b>' . $summary['total_none'] . '</b> finding(s) awaiting clasification.');
  $smarty->assign('need_mit',   '<li>There are <b>' . $summary['total_open'] . '</b> finding(s) awaiting a mitigation strategy and approval.');
  $smarty->assign('need_ev_ot', '<li>There are <b>' . $summary['total_en'] .   '</b> finding(s) awaiting evidence.');
  $smarty->assign('need_ev_od', '<li>There are <b>' . $summary['total_eo'] .   '</b> overdue finding(s) awaiting evidence.');

}	

// display the following template	
$smarty->display('dashboard.tpl');
?>
