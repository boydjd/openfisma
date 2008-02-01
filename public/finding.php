<?PHP
header("Cache-Control: no-cache, must-revalidate");

// include files
require_once("config.php");
require_once("smarty.inc.php");
require_once("dblink.php");
require_once("finding.class.php");
require_once("findingDBManager.php");
require_once("pubfunc.php");
require_once("user.class.php");
require_once("page_utils.php");

// set the screen name used for security functions
$screen_name = "finding";

// set the page name
$smarty->assign('pageName', 'Finding Summary');

// creates a session
session_start();


$user = new User($db);

// uses the verify login function in page_utils.php to verify username and password
verify_login($user, $smarty);

// get user right for this screen
$view_right	= $user->checkRightByFunction($screen_name, "view");
$edit_right = $user->checkRightByFunction($screen_name, "edit");
$add_right  = $user->checkRightByFunction($screen_name, "add");
$del_right  = $user->checkRightByFunction($screen_name, "delete");

// let's template know how to display the page
$smarty->assign('view_right', $view_right);
$smarty->assign('edit_right', $edit_right);
$smarty->assign('add_right', $add_right);
$smarty->assign('del_right', $del_right);
/**************User Rigth*****************/



/**************Main Area*****************/
if($view_right || $del_right || $edit_right) {
	$dbObj = new FindingDBManager($db);

	$summary_data = $dbObj->getSummaryList();
	$source_list  = $dbObj->getSourceList();
	$system_list  = $dbObj->getSystemList();
	$network_list = $dbObj->getNetworkList();

	//print_r($summary_data);
	$smarty->assign('summary_data', $summary_data);
	$smarty->assign('source_list', $source_list);
	$smarty->assign('system_list', $system_list);
	$smarty->assign('network_list', $network_list);

	$asc = 1;
	$fn = "date";
	$pageno = 1;
	$submit = "listall";
	$method = "get";
	// submit methos
	if(isset($_POST['sbt'])) {
		$submit = strtolower($_POST['sbt']);
		$pageno = intval($_POST['pageno']);
		$asc = intval($_POST['asc']);
		$fn = $_POST['fn'];
	}

	$nasc = ($asc == 1 ? 0 : 1);
	$pageno = ($pageno > 1 ? $pageno: 1);

	//echo $submit;
	if($submit == 'delete') {
		// delete the selected findings
		if($del_right) {
			// delete the entry need the "delete" right
			$dbObj->deleteFindings($_POST);
			$smarty->assign('filter_data', null);
		}
		// continue get the left search results
		$submit = "search";
	}

	if($submit == 'search') {
		// do search for finding
		$filter_data = $dbObj->searchFinding($_POST, $asc, $pageno);
		//print_r($filter_data);

		$smarty->assign('filter_data', $filter_data);

		// user's search options
		$smarty->assign('status', $_POST['status']);
		$smarty->assign('system', $_POST['system']);
		$smarty->assign('source', $_POST['source']);
		$smarty->assign('network', $_POST['network']);

		$smarty->assign('ip', $_POST['ip']);
		$smarty->assign('port', $_POST['port']);
		$smarty->assign('product', $_POST['product']);
		$smarty->assign('vulner', $_POST['vulner']);

		$smarty->assign('startdate', $_POST['startdate']);
		$smarty->assign('enddate', $_POST['enddate']);
	}
	else {
		$filter_data = $dbObj->searchFinding($_GET, $asc, $pageno, isset($_POST['fn'])?$_POST['fn']:'');
		//print_r($filter_data);

		$smarty->assign('filter_data', $filter_data);

		// open this sumary page
		$startdate	= strftime("%m/%d/%Y", (mktime(0, 0, 0, date("m")  , date("d") - 365, date("Y"))));
		$enddate	= strftime("%m/%d/%Y", (mktime(0, 0, 0, date("m")  , date("d"), date("Y"))));
		$smarty->assign('startdate', $startdate);
		$smarty->assign('enddate', $enddate);
	}

	if(count($filter_data) < 20)
		$nextpage = 0; // last page
	else
		$nextpage = 1;

	$totalpage = $dbObj->getSearchPages();
	if($totalpage == 0)
		$pageno = 0;

	$smarty->assign('submit', $submit);
	$smarty->assign('totalpage', $totalpage);
	$smarty->assign('pageno', $pageno);
	$smarty->assign('nextpage', $nextpage);

	$smarty->assign('fn', $fn);
	$smarty->assign('asc', $asc);
}

$smarty->assign('now', get_page_datetime());

$smarty->display('finding.tpl');
?>
