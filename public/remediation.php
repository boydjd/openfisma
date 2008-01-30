<?PHP
/*******************************************************************************
* File    : remediation.php
* Purpose : performs application requests for the remediation summary page
* Author  : Brian Gant
* Date    : 
*******************************************************************************/



header("Cache-Control: no-cache, must-revalidate"); 

require_once("config.php");
require_once("smarty.inc.php");
require_once("dblink.php");
require_once("finding.class.php");
require_once("findingDBManager.php");
require_once("user.class.php");
require_once("pubfunc.php");
require_once("page_utils.php");

$screen_name = "remediation";

// grab today's date
$today = gmdate("Ymd", time());
$sql_today = gmdate("Y-m-d", time());

session_start();

$user = new User($db);


$loginstatus = $user->login();
if($loginstatus != 1) {
	// redirect to the login page
	$user->loginFailed($smarty);
	exit;
}
displayLoginInfor($smarty, $user);

verify_login($user, $smarty);


// get user right for this screen
// $user->checkRightByFunction($screen_name, "function_name");

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


$total_pages = 0 ;

/**************Main Area*****************/
if($view_right || $del_right || $edit_right) 
{






	//added by chang  row_no
	if (!isset($_POST['row_no'])) 
		$row_no = 100 ;
	else 
		$row_no = $_POST['row_no'] ;
	$smarty->assign('row_no', $row_no ); 


	//added by chang  row_no
	$from_record = 0 ;
	if (isset($_POST['remediation_page'])) 
	{ 
		$smarty->assign('remediation_page', $_POST['remediation_page']); 
		$from_record = ( $_POST['remediation_page'] - 1 ) * $row_no;
		if ( $from_record < 0 )
			$from_record = 0 ;
	} 
	else 
	{
		$smarty->assign('remediation_page', '1'); 
	}







	
	/*******************************************************************************
	* FORM ACTIONS
	*******************************************************************************/
	
	// initialize or propagate filter values
	if (isset($_POST['filter_source'])) { $smarty->assign('filter_source', $_POST['filter_source']); } else { $smarty->assign('filter_source', 'any'); }
	if (isset($_POST['filter_system'])) { $smarty->assign('filter_system', $_POST['filter_system']); } else { $smarty->assign('filter_system', 'any'); }
	if (isset($_POST['filter_status'])) { $smarty->assign('filter_status', $_POST['filter_status']); } else { $smarty->assign('filter_status', 'any'); }
	if (isset($_POST['filter_type']))   { $smarty->assign('filter_type',   $_POST['filter_type']);   } else { $smarty->assign('filter_type',   'any'); }

	// initialize or propagate filter date values - updated by chang
	if (isset($_POST['filter_startdate']))       { $smarty->assign('filter_startdate',       $_POST['filter_startdate']);       } else { $smarty->assign('filter_startdate',       ''); }
	if (isset($_POST['filter_enddate']))         { $smarty->assign('filter_enddate',         $_POST['filter_enddate']);         } else { $smarty->assign('filter_enddate',         ''); }
	if (isset($_POST['filter_startcreatedate'])) { $smarty->assign('filter_startcreatedate', $_POST['filter_startcreatedate']); } else { $smarty->assign('filter_startcreatedate', ''); }
	if (isset($_POST['filter_endcreatedate']))   { $smarty->assign('filter_endcreatedate',   $_POST['filter_endcreatedate']);   } else { $smarty->assign('filter_endcreatedate',   ''); }

	// initialize or propagate filter date values - updated by chang 03162006
	if (isset($_POST['filter_asset_owners']))  { $smarty->assign('filter_asset_owners',  $_POST['filter_asset_owners']);  } else { $smarty->assign('filter_asset_owners',  'any'); }
	if (isset($_POST['filter_action_owners'])) { $smarty->assign('filter_action_owners', $_POST['filter_action_owners']); } else { $smarty->assign('filter_action_owners', 'any'); }

	// initialize or propagate sort values
	if (isset($_POST['sort_order']))    { $smarty->assign('sort_order', $_POST['sort_order']); } else { $smarty->assign('sort_order', 'any'); }
	if (isset($_POST['sort_by']))       { $smarty->assign('sort_by',    $_POST['sort_by']);    } else { $smarty->assign('sort_by',    'any'); }
	
	
	/*******************************************************************************
	* QUERY ACTIONS
	*******************************************************************************/

	// grab eligible system_ids for the current user
	$system_ids = $user->getSystemIdsByRole();

	// create part of our filter from " . TN_the results
	$system_list = '';

	if ($system_ids != 0) {

	  foreach ($system_ids as $system_id) { $system_list .= (strlen($system_list) > 0) ? ", $system_id" : "$system_id"; }

	}

	else { $system_list = '0'; }

	
	// 
	// FINDING_SOURCES FILTER QUERY
	// 
	$query = "SELECT DISTINCT ".
			 "  fs.source_id, ".
			 "  fs.source_nickname, ".
			 "  fs.source_name ".
			 "FROM ".
			 "  FINDINGS AS f, ".
			 "  FINDING_SOURCES AS fs, ".
			 "  POAMS AS p ".
			 "WHERE ( ".
			 "  p.finding_id = f.finding_id AND ".
			 "  fs.source_id = f.source_id ".
			 ") ".
			 "ORDER BY ".
			 "  fs.source_nickname ".
			 "ASC";
	
	// execute our built query
	$results         = $db->sql_query($query);
	$finding_sources = $db->sql_fetchrowset($results);
	$smarty->assign('finding_sources', $finding_sources);

	
	// 
	// SYSTEMS FILTER QUERY
	// 
	$query = "SELECT DISTINCT ".
			 "  s.system_id, ".
			 "  s.system_nickname, ".
			 "  s.system_name ".
			 "FROM ".
			 "  SYSTEMS AS s, ".
			 "  POAMS AS p ".
			 "WHERE ( ".
	         "  s.system_id IN (".$system_list.") ".
			 ") ".
			 "ORDER BY ".
			 "  s.system_nickname ".
			 "ASC";

	// execute our built query
	$results = $db->sql_query($query);
	$systems = $db->sql_fetchrowset($results);
	$smarty->assign('systems', $systems);
	






	// updated by chang - 03162006
	// Asset Owners FILTER QUERY
	// 
	$query = "SELECT DISTINCT ".
			 "  system_id, ".
			 "  system_nickname, ".
			 "  system_name ".
			 "FROM ".
			 "  SYSTEMS ORDER BY system_nickname ASC" ;

	// execute our built query
	$results = $db->sql_query($query);
	$asset_owners = $db->sql_fetchrowset($results);
	$smarty->assign('asset_owners', $asset_owners);

	// updated by chang - 03162006
	// Action Owners FILTER QUERY
	// 
	$query = "SELECT DISTINCT ".
			 "  system_id, ".
			 "  system_nickname, ".
			 "  system_name ".
			 "FROM ".
			 "  SYSTEMS ORDER BY system_nickname ASC" ;

	// execute our built query
	$results = $db->sql_query($query);
	$action_owners = $db->sql_fetchrowset($results);
	$smarty->assign('action_owners', $action_owners);


















	
	//
	// REMEDIATION LIST INFORMATION QUERY
	//
	$query   = "SELECT ".
			   "  p.poam_id, ".
			   "  p.legacy_poam_id, ".
			   "  fs.source_nickname, ".
			   "  fs.source_name, ".
			   "  s1.system_id       AS asset_owner_id, ".
			   "  s1.system_nickname AS asset_owner_nickname, ".
			   "  s1.system_name     AS asset_owner_name, ".
			   "  s2.system_id       AS action_owner_id, ".
			   "  s2.system_nickname AS action_owner_nickname, ".
			   "  s2.system_name     AS action_owner_name, ".
			   "  p.poam_status, ".
			   "  p.poam_type, ".
			   "  p.poam_date_created, ".
			   "  p.poam_action_date_est ".
			   "FROM ".
			   "  FINDINGS AS f, ".
			   "  FINDING_SOURCES AS fs, ".
			   "  POAMS AS p, ".	
			   "  SYSTEMS AS s1, ".
			   "  SYSTEMS AS s2, ".
	           "  SYSTEM_ASSETS AS sa ".
			   "WHERE ( ";
	
	// only show relevant systems
	if ($user->getUsername() != 'root') {
	   $query .= "  p.poam_action_owner IN (".$system_list.") AND ";
	}

	// do some filtering if necessary
	if (isset($_POST['filter_source']) && ($_POST['filter_source'] != 'any')) { $query .= "  fs.source_id = '".$_POST['filter_source']."' AND "; }
	if (isset($_POST['filter_system']) && ($_POST['filter_system'] != 'any')) { $query .= "  p.poam_action_owner = '".$_POST['filter_system']."' AND "; }


	// date filter - updated by chang
	if (isset($_POST['filter_startdate']) && ($_POST['filter_enddate'] )) 
	{
		$query .= "  p.poam_action_date_est >= '". convert_date_format( $_POST['filter_startdate'] ) ."' AND   p.poam_action_date_est <= '". convert_date_format( $_POST['filter_enddate'] ) ."' AND   "; 
	}
	
	// date filter - updated by chang	
	if (isset($_POST['filter_startcreatedate']) && ($_POST['filter_endcreatedate'] )) 
	{
		$query .= "  p.poam_date_created >= '". convert_date_format( $_POST['filter_startcreatedate'] ) ."' AND   p.poam_date_created <= '". convert_date_format( $_POST['filter_endcreatedate'] ) ."' AND   "; 
	}

	// asset owners filter - updated by chang	 03162006
	if (isset($_POST['filter_asset_owners'])   && ($_POST['filter_asset_owners']   != 'any')) 
	{ 
		$query .= "  s1.system_id  = '".$_POST['filter_asset_owners']."' AND "; 
		
	}

	// Action owners filter - updated by chang	 03162006
	if (isset($_POST['filter_action_owners'])   && ($_POST['filter_action_owners']   != 'any')) 
	{ 
		$query .= "  s2.system_id  = '".$_POST['filter_action_owners']."' AND "; 
	}




	if (isset($_POST['filter_type'])   && ($_POST['filter_type']   != 'any')) { $query .= "  p.poam_type = '".$_POST['filter_type']."' AND "; }
	if (isset($_POST['filter_status']) && ($_POST['filter_status'] != 'any')) {


		// handle the individual requests
		switch ($_POST['filter_status']) {

		case "NEW":

			$query .= " p.poam_status = 'OPEN' AND poam_type = 'NONE' AND ";
			break;

		case "OPEN":

			$query .= " p.poam_status = 'OPEN' AND poam_type != 'NONE' AND ";
			break;

		case "EN":

			$query .= " p.poam_status = 'EN' AND p.poam_action_date_est >= CURDATE() AND ";
			break;

		case "EO":

			$query .= " p.poam_status = 'EN' AND (p.poam_action_date_est < CURDATE() or p.poam_action_date_est is NULL) AND ";
			break;

		case "REJ-SSO":

			$query .= " p.poam_status = 'EN' AND p.poam_id in (select pe.poam_id from " . TN_POAM_EVIDENCE . " as pe where (pe.ev_sso_evaluation = 'DENIED')) AND ";
			break;

		case "REJ-SNP":

			$query .= " p.poam_status = 'EN' AND p.poam_id in (select pe.poam_id from " . TN_POAM_EVIDENCE  . " as pe where (pe.ev_fsa_evaluation = 'DENIED')) AND ";
			break;

		case "REJ-IVV":

			$query .= " p.poam_status = 'EN' AND p.poam_id in (select pe.poam_id from " . TN_POAM_EVIDENCE . " as pe where (pe.ev_ivv_evaluation = 'DENIED')) AND ";
			break;

		case "EP-SSO":

			$query .= " p.poam_status = 'EP' AND p.poam_id in (select distinct pe.poam_id from " . TN_POAM_EVIDENCE . " as pe where (pe.ev_sso_evaluation = 'NONE' and pe.ev_fsa_evaluation = 'NONE' and pe.ev_ivv_evaluation = 'NONE')) AND ";
			break;

		case "EP-SNP":

			$query .= " p.poam_status = 'EP' AND p.poam_id in (select distinct pe.poam_id from " . TN_POAM_EVIDENCE . " as pe where (pe.ev_sso_evaluation = 'APPROVED' and pe.ev_fsa_evaluation = 'NONE' and pe.ev_ivv_evaluation = 'NONE') order by ev_id desc) AND ";
			break;

		case "ES":

			$query .= " p.poam_status = 'ES' AND ";
			break;

		case "CLOSED":

			$query .= " p.poam_status = 'CLOSED' AND ";
			break;

		case "NOT-CLOSED":

			$query .= " p.poam_status NOT LIKE 'CLOSED' AND ";
			break;

		case "NOUP-30":

			$query .= " p.poam_status NOT LIKE 'CLOSED' AND p.poam_date_modified < SUBDATE(NOW(), 30) AND ";
			break;


		case "NOUP-60":

			$query .= " p.poam_status NOT LIKE 'CLOSED' AND p.poam_date_modified < SUBDATE(NOW(), 60) AND ";
			break;


		case "NOUP-90":

			$query .= " p.poam_status NOT LIKE 'CLOSED' AND p.poam_date_modified < SUBDATE(NOW(), 90) AND ";
			break;

		default:

			$query .= " p.poam_status = '" . $_POST['filter_status'] . "' AND ";
			break;

		}
	
	
	}
	
	// continue building our query
	$query .=  "  p.finding_id  = f.finding_id AND ".
			   "  f.source_id   = fs.source_id AND ".
	           "  f.asset_id    = sa.asset_id AND ".
	           "  sa.system_is_owner = 1 AND ".
			   "  s1.system_id  = sa.system_id AND ".
			   "  s2.system_id  = p.poam_action_owner ".
			   ") ".
			   "  ORDER BY ";
	
	// what are we ordering by?
	$sort_by = isset($_POST['sort_by'])?$_POST['sort_by']:'';
	switch ($sort_by) {
	 case 'remediation_id':
	   $query .= 'p.poam_id ';
	   break;
	
	 case 'finding_source':
	   $query .= 'fs.source_nickname ';
	   break;

	//updated by chang 03162006
	 case 'asset_owner':
	   $query .= 'asset_owner_name ';
	   break;
	
	 case 'action_owner':
	   $query .= 'action_owner_nickname ';
	   break;
	
	 case 'remediation_type':
	   $query .= 'p.poam_type ';
	   break;
	
	 case 'remediation_status':
	   $query .= 'p.poam_status, p.poam_action_date_est ';
	   break;
	
	 case 'remediation_date_created':
	   $query .= 'p.poam_date_created ';
	   break;
	
	 case 'action_date_est':
	   $query .= 'p.poam_action_date_est ';
	   break;
	
	 default:
	   $query .= 'action_owner_name, p.poam_date_created, p.poam_type, p.poam_status ';
	   break;
	
	}
	
	// what direction are we listing?
    if (isset($_POST['sort_order'])){
    	$query .= ($_POST['sort_order'] == 'any')?"ASC ":$_POST['sort_order'];
    }
	// execute our built query
//		echo $query;
	
	$results = $db->sql_query($query);
	
	$list    = $db->sql_fetchrowset($results);

	//updated by chang 03022006
	$current_page_query = $query . " limit $from_record,$row_no" ;
	//echo $current_page_query = $query; // . " limit $from_record, 10 " ;
	$results_current_page  = $db->sql_query($current_page_query );
	$current_page_list = $db->sql_fetchrowset($results_current_page);

	// update the current page list EO items to say EO
	for ($row=0; $row < count($current_page_list); $row++) {

		// grab the estimated completion dates
		$est = implode(split('-', $current_page_list[$row]['poam_action_date_est']));

		if ($current_page_list[$row]['poam_status'] == 'EN') {

			if ($est < $today) { $current_page_list[$row]['poam_status'] = 'EO'; }

		}


		// add in the legacy poam id if available
		if ($current_page_list[$row]['legacy_poam_id'] != '') {

			$current_page_list[$row]['nice_poam_id'] = $current_page_list[$row]['poam_id'] . " <i>(" . $current_page_list[$row]['legacy_poam_id'] . ")</i>";

		}

		else {

			$current_page_list[$row]['nice_poam_id'] = $current_page_list[$row]['poam_id'];

		}


	}

	
	/*******************************************************************************
	* SUMMARY INFORMATION CREATION
	*******************************************************************************/

	// initialize summary variables
	$summary = Array();
	$array_template  = array('NEW'=>'', 'OPEN'=>'', 'EN'=>'', 'ED'=>'', 'EO'=>'', 'EP'=>'', 'ES'=>'', 'EP_SNP'=>'',
	                                 'EP_SSO'=>'', 'CLOSED'=>'', 'TOTAL'=>'');
	$totals = $array_template;
	
	// go through the retrieved list
	for ($row=0; $row < count($list); $row++) {
	
	  // capture the system_id and name
	  $this_system = $list[$row]['action_owner_id'];
	
	  if(!isset($summary[$this_system])){
	      // init the summary item with an array template
	       $summary[$this_system] = $array_template;
	  }
	  // capture the system name and nickname
	  $summary[$this_system]['action_owner_nickname'] = $list[$row]['action_owner_nickname'];
	  $summary[$this_system]['action_owner_name']     = $list[$row]['action_owner_name'];
	
	  // count the NEW items
	  if (($list[$row]['poam_status'] == 'OPEN') && ($list[$row]['poam_type'] == 'NONE')) { 
		  $summary[$this_system]['NEW'] += 1; 
		  $totals['NEW'] += 1;
	  }
	
	  // count the OPEN ITEMS
	  if (($list[$row]['poam_status'] == 'OPEN') && ($list[$row]['poam_type'] != 'NONE')) { 
		  $summary[$this_system]['OPEN'] += 1; 
		  $totals['OPEN'] += 1;
	  }
	
	  // count the EN and EO items
	  if ($list[$row]['poam_status'] == 'EN') { 
	
		// grab the estimated completion date from " . TN_the remediation
		$est = implode(split('-', $list[$row]['poam_action_date_est']));
	
		// compare to the current date
		if ($est < $today ) {
	
		  // update that the date has passed
		  $list[$row]['poam_status'] = 'EO';
	
		  // count the remediation as overdue
		  $summary[$this_system]['EO'] += 1;
		  $totals['EO'] += 1;

		  // update the display to show it as EN in the list
		  $list[$row]['poam_status'] = 'EO';

		}
	
		// still on time, just count it
		else {
	
		  $summary[$this_system]['EN'] += 1;
		  $totals['EN'] += 1;
	
		}
	
	  }
	
	  // count the EP items
	  if ($list[$row]['poam_status'] == 'EP') { 

		$summary[$this_system]['EP'] += 1; 
		$totals['EP'] += 1;

		// grab the SSO approvals to differentiate the EPs
		$query = 
			"SELECT ".
			"ev_sso_evaluation ".
			"FROM POAM_EVIDENCE ".
			"WHERE ( ".
			"poam_id = '".$list[$row]['poam_id']."'".
			") ".
			"ORDER BY ev_id DESC ".
			"LIMIT 1";

//		print $query."<br>";

		$result   = $db->sql_query($query);
		$approval = $db->sql_fetchrow($result);

//		print_r($approval);

		// if the SSO has approved it, then tag it as S&P
		if ($approval['ev_sso_evaluation'] == 'APPROVED') {

			$summary[$this_system]['EP_SNP'] += 1;
			$totals['EP_SNP'] += 1;

		}

		// else tag it SSO 
		else {

			$summary[$this_system]['EP_SSO'] += 1;
			$totals['EP_SSO'] += 1;

		}


	  }

	
	  // count the ES items
	  if ($list[$row]['poam_status'] == 'ES') { 
		$summary[$this_system]['ES'] += 1;
		$totals['ES'] += 1;
	  }

	  // count the CLOSED items
	  if ($list[$row]['poam_status'] == 'CLOSED') { 
		$summary[$this_system]['CLOSED'] += 1;
		$totals['CLOSED'] += 1;
	  }
	  
	  
	  // count the total number for this system
	  $summary[$this_system]['TOTAL'] += 1;
	  $totals['TOTAL'] += 1;
	  
	  //total pages
	  $total_pages = ceil( $totals['TOTAL'] /$row_no);
	
	}
	
	// finally assign both the list and the summary
//	$smarty->assign('list', $list);
	$smarty->assign('list', $current_page_list);
	$smarty->assign('summary', $summary);
	$smarty->assign('total_pages',  $total_pages);
	$smarty->assign('totals',  $totals);
}
/*******************************************************************************
* PAGE DISPLAY
*******************************************************************************/

$smarty->assign('now', get_page_datetime());
$smarty->display('remediation.tpl');
?>
