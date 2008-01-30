<?PHP
/*******************************************************************************
* File    : remediation_modify.php
* Purpose : performs application requests for the remediation modification page
* Author  : Brian Gant
* Date    :
*******************************************************************************/
header("Cache-Control: no-cache, must-revalidate");


//
// REDIRECT ON A NON-FORM SUBMITTED INVOCATION
//
if (! isset($_POST['remediation_id'])) { header('Location: remediation.php'); }


//
// turn off magic quotes
//
if (get_magic_quotes_gpc()) { set_magic_quotes_runtime(0); }


/*******************************************************************************
* INITIALIZE PAGE
*******************************************************************************/

// Smarty specific includes
require_once("config.php");
require_once("smarty.inc.php");
// db includes
require_once("dblink.php");

$screen_name = "remediation_modify";

// grab today's date
$today = gmdate("Ymd", time());


/*******************************************************************************
* USER RIGHTS
*******************************************************************************/

session_start();
require_once("user.class.php");

$user = new User($db);
$loginstatus = $user->login();
if($loginstatus != 1) {
	// redirect to the login page
	$user->loginFailed($smarty);
	exit;
}

//////////////////////////////////////
if ($_POST['target'] == 'save_poam'){

  // need modify. -- Alix
    $smarty->assign('table_header_comment', 'Comment you changes and continue submit.');
    $smarty->assign('comment_topic', 'UPDATE : ');
    
    $smarty->assign('remediation_id', $_POST['remediation_id']);
    $smarty->assign('target',         $_POST['target']);
    $smarty->assign('action',         $_POST['action']);
    $smarty->assign('validated',      $_POST['validated']);
    $smarty->assign('approved',       $_POST['approved']);
    $smarty->assign('form_action',    'Submit');
    
    $smarty->assign('now', gmdate ("M d Y H:i:s", time()));
    
    // display the page
    $smarty->display('remediation_modify.tpl');
    die();
}
//////////////////////////////////////

displayLoginInfor($smarty, $user);

$smarty->assign("username",      $user->getUsername());
$smarty->assign("customer_url",  $customer_url);
$smarty->assign("customer_logo", $customer_logo);


//
// turn off magic quotes
//
if (get_magic_quotes_gpc()) { set_magic_quotes_runtime(0); } // echo "quotes off"; }

//print_r($_POST);

/*******************************************************************************
* FORM SUBMISSION SANITIZATION
*******************************************************************************/

// straighten up the descriptive submit values from remediation_detail
if ($_POST['form_action'] == 'New Comment')       { $_POST['form_action'] = 'Submit'; }
if ($_POST['form_action'] == 'Respond')           { $_POST['form_action'] = 'Submit'; }
if ($_POST['form_action'] == 'Update')            { $_POST['form_action'] = 'Submit'; }
if ($_POST['form_action'] == 'Evaluate')          { $_POST['form_action'] = 'Submit'; }
if ($_POST['form_action'] == 'Submit Evidence')   { $_POST['form_action'] = 'Submit'; }



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
* FORM SUBMISSION HANDLING
*******************************************************************************/

//
// CANCEL
//
if (isset($_POST['form_action']) && ($_POST['form_action'] == 'Cancel')) {

  // initialize the action cancelled specific values
  $smarty->assign('page_title',  'Action cancelled');

  // delete any uploaded evidence
  if (isset($_POST['file_loc'])) { unlink($_POST['file_loc']); }


} // if (form_action == cancel)

//
// SUBMIT
//
if (isset($_POST['form_action']) && ($_POST['form_action'] == 'Submit')) {


  // propagate the root comment
  $smarty->assign('root_comment', $_POST['root_comment']);


  //
  // VALIDATED = NO
  //
  if (isset($_POST['validated']) && ($_POST['validated'] == 'no')) {

	//
	// TARGET / ACTION
	//
	if (isset($_POST['target']) && isset($_POST['action'])) {


	  //
	  // TARGET?
	  //
	  switch ($_POST['target']) {
        
		//
		// TARGET = COMMENT
		//
	  case 'comment':

		// comment response actions
		if ($_POST['action'] == 'respond') {

		  // set up the title and header fields
		  $smarty->assign('table_header_modify',  'Original Comment');
		  $smarty->assign('table_header_comment', 'Respond to a comment');

		  // select and assign the parent comment the parent comment
		  $query =
			"SELECT ".
			"  pc.comment_id, ".
			"  pc.comment_topic, ".
			"  pc.comment_body ".
			"FROM " . TN_POAM_COMMENTS . 
			"  AS pc ".
			"WHERE ( ".
			"  pc.poam_id = ".$_POST['remediation_id']." AND ".
			"  pc.comment_id = ".$_POST['root_comment']." ".
			") ";
		  $results         = $db->sql_query($query);
		  $parent_comment  = $db->sql_fetchrow($results);
		  $smarty->assign('comment_topic', 'Re: '.$parent_comment['comment_topic']);
		  $parent_comment['comment_body'] = implode("\n> ", explode("\n", $parent_comment['comment_body']));
		  $smarty->assign('comment_body', '> '.$parent_comment['comment_body']."\n---------------------------------\n");
		} // if (target = comment && action = respond)

		// comment add initialization
		if ($_POST['action'] == 'add') {
		  $smarty->assign('table_header_comment', 'Create a new comment');
		}

		break; // target = comment


		//
		// TARGET = REMEDIATION
		//
	  case 'remediation':

		// propagate the POST VALUES
		if (isset($_POST['new_value'])) { $smarty->assign('new_value', $_POST['new_value']); }

		// assign the header fields
		$smarty->assign('page_title', 'Remediation Evaluation');
		$smarty->assign('table_header_modify',  'Evaluation');
		$smarty->assign('table_header_comment', 'Evaluation comments');
		$smarty->assign('comment_topic',        'UPDATE: IV&V evaluation');

		break;


		//
		// TARGET = PREVIOUS AUDITS
		//
	  case 'previous_audits':

		// propagate the POST VALUES
		if (isset($_POST['new_value'])) { $smarty->assign('new_value', $_POST['new_value']); }

		// assign the header fields
		$smarty->assign('page_title', 'Previous Audits');
		$smarty->assign('table_header_modify',  'Previous Audits');
		$smarty->assign('table_header_comment', 'Modification Comments');
		$smarty->assign('comment_topic',        'UPDATE: previous audits');

		// retrieve the current values
		$query =
		  "SELECT ".
		  "  p.poam_previous_audits AS value".
		  "FROM " . TN_POAMS .
		  "  AS p ".
		  "WHERE ( ".
		  "  p.poam_id = '".$_POST['remediation_id']."' ".
		  ")";
		$results       = $db->sql_query($query);
		$current_value = $db->sql_fetchrow($results);
		$smarty->assign('current_value', $current_value['value']);

		break;


		//
		// TARGET = EVIDENCE
		//
	  case 'evidence':

		// propagate the POST values
		if (isset($_POST['new_value'])) { $smarty->assign('new_value', $_POST['new_value']); }
		if (isset($_POST['ev_id']))     { $smarty->assign('ev_id',     $_POST['ev_id']); }

		// ADD EVIDENCE
		if ($_POST['action'] == 'add') {

		  // propagate the file upload status and any existing information
		  if (isset($_POST['uploaded']))  { $smarty->assign('uploaded',  $_POST['uploaded']); }
		  if (isset($_POST['file_name'])) { $smarty->assign('file_name', $_POST['file_name']); }
		  if (isset($_POST['file_loc']))  { $smarty->assign('file_loc',  $_POST['file_loc']); }

		  // assign the page header
		  $smarty->assign('page_title', 'Evidence upload');

		  // set up the title and header fields
		  $smarty->assign('table_header_modify',  'Evidence upload');
		  $smarty->assign('table_header_comment', 'Evidence comments');
		  $smarty->assign('comment_topic', 'UPDATE: evidence upload');

		}

		// EVALUATE EVIDENCE
		if (($_POST['action'] == 'sso_evaluate') || ($_POST['action'] == 'fsa_evaluate') || ($_POST['action'] == 'ivv_evaluate')) {

		  // assign the specific comment topic
		  if ($_POST['action'] == 'sso_evaluate') { $smarty->assign('comment_topic', 'UPDATE: SSO evidence evaluation'); }
		  if ($_POST['action'] == 'fsa_evaluate') { $smarty->assign('comment_topic', 'UPDATE: FSA evidence evaluation'); }
		  if ($_POST['action'] == 'ivv_evaluate') { $smarty->assign('comment_topic', 'UPDATE: IV&V evidence evaluation'); }

		  // assign the page header
		  $smarty->assign('page_title', 'Evidence evaluation');

		  // set up the title and header fields
		  $smarty->assign('table_header_modify',  'Evidence evaluation');
		  $smarty->assign('table_header_comment', 'Evaluation comments');

		  // retrieve the current piece of evidence to display
		  $query =
			"SELECT ".
			"  pe.ev_submission ".
			"FROM " . TN_POAM_EVIDENCE .
			" AS pe ".
			"WHERE ( ".
			"  pe.ev_id = '".$_POST['ev_id']."' ".
			")";
		  $results  = $db->sql_query($query);
		  $evidence = $db->sql_fetchrow($results);
		  $smarty->assign('evidence', $evidence);

		}

		// UPLOAD EVIDENCE
		if ($_POST['action'] == 'add') {

		  // set up the title and header fields
		  $smarty->assign('table_header_modify',  'Evidence upload');
		  $smarty->assign('table_header_comment', 'Evidence comments');
		  $smarty->assign('comment_topic', 'UPDATE: evidence upload');

		}

		break; // target = evidence


		//
		// TARGET = REMEDIATION OWNER
		//
	  case 'remediation_owner':


		// set up the title and header fields
		$smarty->assign('page_title', 'Responsible system');
		$smarty->assign('table_header_modify',  'Responsible system');
		$smarty->assign('table_header_comment', 'Reason for change');
		$smarty->assign('comment_topic', 'UPDATE: responsible system');


		// propagate the POST value if it exists
		if (isset($_POST['new_value'])) {

		  $smarty->assign('new_value', $_POST['new_value']);

		  // grab the system name for descriptive form display
		  $query =
			"SELECT ".
			"  s.system_id, ".
			"  s.system_nickname, ".
			"  s.system_name ".
			"FROM " . TN_SYSTEMS .
			"  AS s ".
			"WHERE ( ".
			"  s.system_id = '".$_POST['new_value']."' ".
			")";
		  $results    = $db->sql_query($query);
		  $new_system = $db->sql_fetchrow($results);
		  $smarty->assign('new_system', $new_system);

		}

		else {
		  // get the current system
		  $query =
			"SELECT ".
			"  s.system_id, ".
			"  s.system_nickname, ".
			"  s.system_name ".
			"FROM " . TN_SYSTEMS .
			"  AS s, ".
			"  POAMS AS p ".
			"WHERE ( ".
			"  s.system_id = p.poam_action_owner ".
			")";
		  $results       = $db->sql_query($query);
		  $current_value = $db->sql_fetchrow($results);
		  $smarty->assign('current_value', $current_value);

		  // get all of the systems
		  $query =
			"SELECT DISTINCT ".
			"  s.system_id, ".
			"  s.system_nickname, ".
			"  s.system_name ".
			"FROM " . TN_SYSTEMS .
			"  AS s, ".TN_POAMS.
			"  AS p ".
			"WHERE ( ".
			"  s.system_id != p.poam_action_owner AND ".
			"  p.poam_id = '".$_POST['remediation_id']."' ".
			")";
		  $results    = $db->sql_query($query);
		  $all_values = $db->sql_fetchrowset($results);
		  $smarty->assign('all_values', $all_values);
		}

		break; // target = remediation_owner


		//
		// TARGET = REMEDIATION TYPE
		//
	  case 'remediation_type':

		// propagate the POST value
		if (isset($_POST['new_value'])) { $smarty->assign('new_value', $_POST['new_value']); }

		// set up the title and header fields
		$smarty->assign('page_title', 'Change remediation type');
		$smarty->assign('table_header_modify',  'New remediation type');
		$smarty->assign('table_header_comment', 'Reason for change');

		// automatically generate the comment topic
		$smarty->assign('comment_topic', 'UPDATE: remediation type');

		// get the current type
		$query =
		  "SELECT ".
		  "  p.poam_type as value ".
		  "FROM " . TN_POAMS .
		  " AS p ".
		  "WHERE ( ".
		  "  p.poam_id = ".$_POST['remediation_id']." ".
		  ")";
		$results = $db->sql_query($query);
		$current_value = $db->sql_fetchrow($results);
		$smarty->assign('current_value', $current_value['value']);

		break; // switch (target) = remediation_type


		//
		// TARGET = REMEDIATION STATUS
		//
	  case 'remediation_status':

		// propagate the POST value
		if (isset($_POST['new_value'])) { $smarty->assign('new_value', $_POST['new_value']); }

		// set up the title and header fields
		$smarty->assign('page_title', 'Change remediation status ');
		$smarty->assign('table_header_modify',  'New remediation status');
		$smarty->assign('table_header_comment', 'Reason for change');

		// automatically generate the comment topic
		$smarty->assign('comment_topic', 'UPDATE: threat level');

		// get the current value
		$query =
		  "SELECT ".
		  "  p.poam_status AS value ".
		  "FROM " . TN_POAMS .
		  "  AS p ".
		  "WHERE ( ".
		  "  p.poam_id = ".$_POST['remediation_id']." ".
		  ")";
		$results = $db->sql_query($query);
		$current_value = $db->sql_fetchrow($results);
		$smarty->assign('current_value', $current_value['value']);

		break; // switch (target) = remediation_status


		//
		// TARGET = BLSCR
		//
	  case 'blscr_number':

		// propagate the POST value
		if (isset($_POST['new_value'])) { $smarty->assign('new_value', $_POST['new_value']); }

		// set up the title and header fields
		$smarty->assign('page_title', 'Change Baseline Security Report number');
		$smarty->assign('table_header_modify',  'New BLSCR number');
		$smarty->assign('table_header_comment', 'Reason for change');

 		// automatically generate the comment topic
		$smarty->assign('comment_topic', 'UPDATE: BLSCR number');

		// get the BLSCR number
		$query =
		  "SELECT ".
		  "  p.poam_blscr AS value ".
		  "FROM " . TN_POAMS .
		  "  AS p ".
		  "WHERE ( ".
		  "  p.poam_id = ".$_POST['remediation_id']." ".
		  ")";
		$results = $db->sql_query($query);
		$current_value = $db->sql_fetchrow($results);
		$smarty->assign('current_value', $current_value['value']);

		// get all BLSCR numbers
		$query =
		  "SELECT DISTINCT ".
		  "  b.blscr_number AS value ".
		  "FROM " . TN_BLSCR . 
		  "  AS b ".
		  "ORDER BY ".
		  "  b.blscr_number ".
		  "ASC";
		$results = $db->sql_query($query);
		$all_values = $db->sql_fetchrowset($results);
		$smarty->assign('all_values', $all_values);

		break; // switch (target) = blscr_number


		//
		// TARGET = ACTION_DATE_EST
		//
	  case 'action_date_est':

		// push the Smarty date into a single value
		if (isset($_POST['Date_Month']) && isset($_POST['Date_Day']) && isset($_POST['Date_Year'])) {
		  $_POST['new_value'] = $_POST['Date_Year'].'-'.$_POST['Date_Month'].'-'.$_POST['Date_Day'];
		}
		// propagate the POST value
		if (isset($_POST['new_value'])) { $smarty->assign('new_value', $_POST['new_value']); }

		// set up the title and header fields
		$smarty->assign('page_title', 'Change course of action estimated completion date');
		$smarty->assign('table_header_modify',  'New estimated completion date');
		$smarty->assign('table_header_comment', 'Reason for change');

 		// automatically generate the comment topic
		$smarty->assign('comment_topic', 'UPDATE: course of action estimated completion date');

		// get the BLSCR number
		$query =
		  "SELECT ".
		  "  p.poam_action_date_est AS value ".
		  "FROM " . TN_POAMS .
		  "  AS p ".
		  "WHERE ( ".
		  "  p.poam_id = ".$_POST['remediation_id']." ".
		  ")";
		$results = $db->sql_query($query);
		$current_value = $db->sql_fetchrow($results);
		$smarty->assign('current_value', $current_value['value']);

		break; // switch (target) = action_date_est


		//
		// TARGET = ACTION_SUGGESTED
		//
	  case 'action_suggested':

		// propagate the POST value
		if (isset($_POST['new_value'])) { $smarty->assign('new_value', $_POST['new_value']); }

		// set up the title and header fields
		$smarty->assign('page_title', 'Change recommended course of action ');
		$smarty->assign('table_header_modify',  'New recommended course of action');
		$smarty->assign('table_header_comment', 'Reason for change');

		// automatically generate the comment topic
		$smarty->assign('comment_topic', 'UPDATE: recommended course of action');

		// get the current value
		$query =
		  "SELECT ".
		  "  p.poam_action_suggested as value ".
		  "FROM " . TN_POAMS .
		  "  AS p ".
		  "WHERE ( ".
		  "  p.poam_id = ".$_POST['remediation_id']." ".
		  ")";
		$results = $db->sql_query($query);
		$current_value = $db->sql_fetchrow($results);
		$smarty->assign('current_value', $current_value['value']);

		break; // switch (target) = action_suggested


		//
		// TARGET = ACTION PLANNED
		//
	  case 'action_planned':

		// propagate the POST value
		if (isset($_POST['new_value'])) { $smarty->assign('new_value', $_POST['new_value']); }

		// set up the title and header fields
		$smarty->assign('page_title', 'Change course of action ');
		$smarty->assign('table_header_modify',  'New plan');
		$smarty->assign('table_header_comment', 'Reason for change');

		// automatically generate the comment topic
		$smarty->assign('comment_topic', 'UPDATE: course of action');

		// get the current value
		$query =
		  "SELECT ".
		  "  p.poam_action_planned AS value ".
		  "FROM " . TN_POAMS . 
		  "  AS p ".
		  "WHERE ( ".
		  "  p.poam_id = ".$_POST['remediation_id']." ".
		  ")";
		$results = $db->sql_query($query);
		$current_value = $db->sql_fetchrow($results);
		$smarty->assign('current_value', $current_value['value']);

		break; // switch (target) = action_planned


		//
		// TARGET = ACTION RESOURCES
		//
	  case 'action_resources':

		// propagate the POST value
		if (isset($_POST['new_value'])) { $smarty->assign('new_value', $_POST['new_value']); }

		// set up the title and header fields
		$smarty->assign('page_title', 'Change course of action resources ');
		$smarty->assign('table_header_modify',  'New resources');
		$smarty->assign('table_header_comment', 'Reason for change');

		// automatically generate the comment topic
		$smarty->assign('comment_topic', 'UPDATE: course of action resources');

		// get the current value
		$query =
		  "SELECT ".
		  "  p.poam_action_resources AS value ".
		  "FROM " . TN_POAMS .
		  "  AS p ".
		  "WHERE ( ".
		  "  p.poam_id = ".$_POST['remediation_id']." ".
		  ")";
		$results = $db->sql_query($query);
		$current_value = $db->sql_fetchrow($results);
		$smarty->assign('current_value', $current_value['value']);

		break; // switch (target) = action_resources


		//
		// TARGET = ACTION APPROVAL
		//
	  case 'action_approval':

		// propagate the POST value
		if (isset($_POST['new_value']))     { $smarty->assign('new_value', $_POST['new_value']); }

		// set up the title and header fields
		$smarty->assign('page_title', 'Evaluate proposed course of action');
		$smarty->assign('table_header_modify',  'Proposed course of action');
		$smarty->assign('table_header_comment', 'Reason for decision');

		// automatically generate the comment topic
		$smarty->assign('comment_topic', 'UPDATE: course of action evaluation');

		// propagate the current value or retrieve it
		if (isset($_POST['current_value'])) { $smarty->assign('current_value', $_POST['current_value']); }
		else {
		  // get the current value
		  $query =
			"SELECT ".
			"  p.poam_action_planned AS value ".
			"FROM " . TN_POAMS .
			"  AS p ".
			"WHERE ( ".
			"  p.poam_id = ".$_POST['remediation_id']." ".
			")";
		  $results = $db->sql_query($query);
		  $current_value = $db->sql_fetchrow($results);
		  $smarty->assign('current_value', $current_value['value']);
		}

		break; // switch (target) = action_approval


		//
		// TARGET = COUNTERMEASURE EFFECTIVENESS
		//
	  case 'cmeasure_effectiveness':

		// propagate the POST value
		if (isset($_POST['new_value'])) { $smarty->assign('new_value', $_POST['new_value']); }

		// set up the title and header fields
		$smarty->assign('page_title', 'Change countermeasure effectiveness ');
		$smarty->assign('table_header_modify',  'New effectiveness');
		$smarty->assign('table_header_comment', 'Reason for change');

		// automatically generate the comment topic
		$smarty->assign('comment_topic', 'UPDATE: countermeasure effectiveness');

		// get the current value
		$query =
		  "SELECT ".
		  "  p.poam_cmeasure_effectiveness AS value ".
		  "FROM " . TN_POAMS .
		  "  AS p ".
		  "WHERE ( ".
		  "  p.poam_id = ".$_POST['remediation_id']." ".
		  ")";
		$results = $db->sql_query($query);
		$current_value = $db->sql_fetchrow($results);
		$smarty->assign('current_value', $current_value['value']);

		break; // switch (target) = countermeasure_effectiveness


		//
		// TARGET = COUNTERMEASURE
		//
	  case 'cmeasure':

		// propagate the POST value
		if (isset($_POST['new_value'])) { $smarty->assign('new_value', $_POST['new_value']); }

		// set up the title and header fields
		$smarty->assign('page_title', 'Change countermeasure');
		$smarty->assign('table_header_modify',  'New countermeasure');
		$smarty->assign('table_header_comment', 'Reason for change');

		// automatically generate the comment topic
		$smarty->assign('comment_topic', 'UPDATE: countermeasure');

		// get the current value
		$query =
		  "SELECT ".
		  "  p.poam_cmeasure AS value ".
		  "FROM " . TN_POAMS .
		  "  AS p ".
		  "WHERE ( ".
		  "  p.poam_id = ".$_POST['remediation_id']." ".
		  ")";
		$results = $db->sql_query($query);
		$current_value = $db->sql_fetchrow($results);
		$smarty->assign('current_value', $current_value['value']);

		break; // switch (target) = countermeasure


		//
		// TARGET = COUNTERMEASURE JUSTIFICATION
		//
	  case 'cmeasure_justification':

		// propagate the POST value
		if (isset($_POST['new_value'])) { $smarty->assign('new_value', $_POST['new_value']); }

		// set up the title and header fields
		$smarty->assign('page_title', 'Change countermeasure justification');
		$smarty->assign('table_header_modify',  'New countermeasure justification');
		$smarty->assign('table_header_comment', 'Reason for change');

		// automatically generate the comment topic
		$smarty->assign('comment_topic', 'UPDATE: countermeasure justification');

		// get the current value
		$query =
		  "SELECT ".
		  "  p.poam_cmeasure_justification AS value ".
		  "FROM " . TN_POAMS .
		  " AS p ".
		  "WHERE ( ".
		  "  p.poam_id = ".$_POST['remediation_id']." ".
		  ")";
		$results = $db->sql_query($query);
		$current_value = $db->sql_fetchrow($results);
		$smarty->assign('current_value', $current_value['value']);

		break; // switch (target) = countermeasure_justification


		//
		// TARGET = THREAT LEVEL
		//
	  case 'threat_level':

		// propagate the POST value
		if (isset($_POST['new_value'])) { $smarty->assign('new_value', $_POST['new_value']); }

		// set up the title and header fields
		$smarty->assign('page_title', 'Change threat level ');
		$smarty->assign('table_header_modify',  'New threat level');
		$smarty->assign('table_header_comment', 'Reason for change');

		// automatically generate the comment topic
		$smarty->assign('comment_topic', 'UPDATE: threat level');

		// get the current value
		$query =
		  "SELECT ".
		  "  p.poam_threat_level AS value ".
		  "FROM " . TN_POAMS .
		  "  AS p ".
		  "WHERE ( ".
		  "  p.poam_id = ".$_POST['remediation_id']." ".
		  ")";
		$results = $db->sql_query($query);
		$current_value = $db->sql_fetchrow($results);
		$smarty->assign('current_value', $current_value['value']);

		break; // switch (target) = threat_level


		//
		// TARGET = THREAT SOURCE
		//
	  case 'threat_source':

		// propagate the POST value
		if (isset($_POST['new_value'])) { $smarty->assign('new_value', $_POST['new_value']); }

		// set up the title and header fields
		$smarty->assign('page_title', 'Change threat source');
		$smarty->assign('table_header_modify',  'New threat source');
		$smarty->assign('table_header_comment', 'Reason for change');

		// automatically generate the comment topic
		$smarty->assign('comment_topic', 'UPDATE: threat source');

		// get the current value
		$query =
		  "SELECT ".
		  "  p.poam_threat_source AS value ".
		  "FROM " . TN_POAMS .
		  "  AS p ".
		  "WHERE ( ".
		  "  p.poam_id = ".$_POST['remediation_id']." ".
		  ")";
		$results = $db->sql_query($query);
		$current_value = $db->sql_fetchrow($results);
		$smarty->assign('current_value', $current_value['value']);

		break; // switch (target) = threat_source


		//
		// TARGET = THREAT JUSTIFICATION
		//
	  case 'threat_justification':

		// propagate the POST value
		if (isset($_POST['new_value'])) { $smarty->assign('new_value', $_POST['new_value']); }

		// set up the title and header fields
		$smarty->assign('page_title', 'Change threat justification');
		$smarty->assign('table_header_modify',  'New threat justification');
		$smarty->assign('table_header_comment', 'Reason for change');

		// automatically generate the comment topic
		$smarty->assign('comment_topic', 'UPDATE: threat justification');

		// get the current value
		$query =
		  "SELECT ".

		  "  p.poam_threat_justification AS value ".
		  "FROM " . TN_POAMS .
		  " AS p ".
		  "WHERE ( ".
		  "  p.poam_id = ".$_POST['remediation_id']." ".
		  ")";
		$results = $db->sql_query($query);
		$current_value = $db->sql_fetchrow($results);
		$smarty->assign('current_value', $current_value['value']);

		break; // switch (target) = threat_justification


	  } // switch (target)


	  //
	  // ERROR CHECK input, posted comment_topic signifies input
	  //
	  if (isset($_POST['comment_topic']) && isset($_POST['comment_body'])) {

		// initialize our form_errors array
		$form_errors = Array();

		// replace single and double quotes with html escape code
		$_POST['comment_topic'] = str_replace('\\\'', '&#39;', $_POST['comment_topic']);
		$_POST['comment_topic'] = str_replace('\"',   '&#34;', $_POST['comment_topic']);
		$_POST['comment_body']  = str_replace('\\\'', '&#39;', $_POST['comment_body']);
		$_POST['comment_body']  = str_replace('\"',   '&#34;', $_POST['comment_body']);

		// propagate the comment post values
		$smarty->assign('comment_topic', $_POST['comment_topic']);
		$smarty->assign('comment_body',  $_POST['comment_body']);

		//
		// ACCOMPANYING COMMENT
		//
		if (strlen($_POST['comment_topic']) ==  0 ) { array_push($form_errors, 'Empty comment topic not allowed'); }
		if (strlen($_POST['comment_topic']) >= 64 ) { array_push($form_errors, 'Topic must be shorter than 64 characters'); }
		if (strlen($_POST['comment_body'])  ==  0 ) { array_push($form_errors, 'Empty comment body not allowed'); }


		/*

		Temporarily removed at time of JS calendar replacement.

		//
		// ESTIMATED DATE OF COMPLETION
		//
		if (($_POST['target'] == 'action_date_est') && (!ereg("([0-9]{2})/([0-9]{2})/([0-9]{4})", $_POST['new_value']))) {

		  array_push($form_errors, 'Date format incorrect, use form MM/DD/YYYY or select from calendar');

		}

        */

		//
		//EVIDENCE
		//
		if (($_POST['target'] == 'evidence') && ($_POST['action'] == 'add')) {

		  // no upload yet
		  if ($_POST['uploaded'] == 'yes') {

			// propagate POST values
			$smarty->assign('uploaded',  $_POST['uploaded']);
			$smarty->assign('file_name', $_POST['file_name']);
			$smarty->assign('file_loc',  $_POST['file_loc']);

		  }

		  // we have an upload
		  else {

			// check to see
			if ($_FILES['evidence']['error'] != UPLOAD_ERR_OK  ) {

			  // push the error and set uploaded to no
			  $smarty->assign('uploaded', 'no');
			  array_push($form_errors, 'Upload error, please resubmit file');

			}

			// we have an upload
			else {

			  // set the flag in smarty, save the file to temp storage and store file location
			  $smarty->assign('uploaded', 'yes');
			  $smarty->assign('file_name', $_FILES['evidence']['name']);

			  // make our directories if they do not exist (they should)
			  if (!file_exists('evidence')) { mkdir('evidence', 0755); }
			  if (!file_exists('evidence/'.$_POST['remediation_id'])) { mkdir('evidence/'.$_POST['remediation_id'] , 0755); }

			  // move the file and make sure it is readable
			  $dest = 'evidence/'.$_POST['remediation_id'].'/'.gmdate('Ymd-His-', time()).$_FILES['evidence']['name'];
			  move_uploaded_file($_FILES['evidence']['tmp_name'], $dest);
			  chmod($dest, 0755);

			  // store the file location
			  $smarty->assign('file_loc', $dest);

			}

		  }

		} // evidence


		//
		// TEXT BOX ENTRIES
		//
		if ($_POST['target'] == 'action_planned'  || $_POST['target'] == 'action_suggested' || $_POST['target'] == 'action_resources' ||
			$_POST['target'] == 'cmeasure'  || $_POST['target'] == 'cmeasure_justification' ||
			$_POST['target'] == 'threat_source'   || $_POST['target'] == 'threat_justification' ||
			$_POST['target'] == 'previous_audits' ) {

		  // perform error checking
		  if (strlen($_POST['new_value']) == 0) { array_push($form_errors, 'Empty modification values not allowed'); }

		  // clear the single and double quotes
		  $_POST['new_value']  = str_replace('\\\'', '&#39;', $_POST['new_value']);
		  $_POST['new_value']  = str_replace('\"',   '&#34;', $_POST['new_value']);

		} // text box entries


		//
		// HANDLE RESULTS
		//
		if (count($form_errors) == 0) { $_POST['validated'] = 'yes'; }
		else { $smarty->assign('form_errors', $form_errors); }


	  } // if (isset comment_topic and comment_body)

	} // if (target && action)

  } // if (validated == no)


  //
  // VALIDATED = YES
  //
  else {

	// submission of a validated entry is approval
	$_POST['approved'] = 'yes';

	//
	// TARGET / ACTION
	//
	if (isset($_POST['target']) && isset($_POST['action'])) {

	  // prepare to catch errors from mysql
	  $form_errors = Array();

	  // TARGET
	  switch ($_POST['target']) {


		//
		// COMMENT - no need, it will be inserted after this switch
		//

		//
		// PREVIOUS_AUDITS
		//
	  case 'previous_audits':

		// create our update query
		$query =
		  "UPDATE ".
		  "  POAMS AS p ".
		  "SET ".
		  "  p.poam_previous_audits = '".$_POST['new_value']."' ".
		  "WHERE ".
		  "  p.poam_id = '".$_POST['remediation_id']."' ";

		// execute it
		$results = $db->sql_query($query);

		break;


		//
		// EVIDENCE
		//
	  case 'evidence':

		// add in our new evidence
		if ($_POST['action'] == 'add') {

		  // generate our query
		  $query =
			"INSERT INTO POAM_EVIDENCE ( ".
			"  poam_id, ".
			"  ev_submission, ".
			"  ev_submitted_by, ".
			"  ev_date_submitted ".
			") VALUES ( ".
			"  '".$_POST['remediation_id']."', ".
			"  '".$_POST['file_loc']."', ".
			"  '".$user->getUserId()."', ".
			"  CURDATE() ".
			")";

		  // execute it
		  $results = $db->sql_query($query);

		  // we now have provided evidence, update the POAM status and completion date
		  $query = "UPDATE POAMS SET poam_status = 'EP', poam_action_date_actual = NOW() WHERE (poam_id = '".$_POST['remediation_id']."')";
		  $results = $db->sql_query($query);

		}

		// update our evaluation of submitted evidence
		if (($_POST['action'] == 'sso_evaluate') || ($_POST['action'] == 'fsa_evaluate') || ($_POST['action'] == 'ivv_evaluate')) {

		  // generate our query
		  $query = "UPDATE POAM_EVIDENCE AS pe SET ";

		  // handle the sso evaluation
		  if ($_POST['action'] == 'sso_evaluate') {

			// sso denies, so exclude the other two approvals
			if ($_POST['new_value'] == 'DENIED') {
			  $query .= "  pe.ev_fsa_evaluation = 'EXCLUDED', ";
			  $query .= "  pe.ev_ivv_evaluation = 'EXCLUDED', ";
			}

			// base update for sso approval
			$query .= "  pe.ev_sso_evaluation = '".$_POST['new_value']."', pe.ev_date_sso_evaluation = NOW() ";

		  }

		  // handle the fsa evaluation
		  if ($_POST['action'] == 'fsa_evaluate') {

			// fsa denies, so exclude the other approval
			if ($_POST['new_value'] == 'DENIED') { $query .= "  pe.ev_ivv_evaluation = 'EXCLUDED', "; }

			// base update for fsa approval
			$query .= "  pe.ev_fsa_evaluation = '".$_POST['new_value']."', pe.ev_date_fsa_evaluation = NOW() ";

		  }

		  // handle the ivv evaluation
		  if ($_POST['action'] == 'ivv_evaluate') {

			// base update for ivv approval
			$query .= "  pe.ev_ivv_evaluation = '".$_POST['new_value']."', pe.ev_date_ivv_evaluation = NOW() ";

		  }

		  // finish the query with the correct evidence id to update and execute it
		  $query  .= "WHERE pe.ev_id = '".$_POST['ev_id']."' ";
		  $results = $db->sql_query($query);

		  // FSA approval changes status to ES
		  if (($_POST['action'] == 'fsa_evaluate') && ($_POST['new_value'] == 'APPROVED')) {

			$query   = "UPDATE POAMS AS p SET p.poam_status = 'ES' WHERE p.poam_id = '".$_POST['remediation_id']."' ";
			$results = $db->sql_query($query);

		  }

		  // IVV approval changes status to CLOSED
		  if (($_POST['action'] == 'ivv_evaluate') && ($_POST['new_value'] == 'APPROVED')) {

			// change POAM status
			$query   = "UPDATE POAMS AS p SET p.poam_status = 'CLOSED', p.poam_date_closed = NOW() WHERE p.poam_id = '".$_POST['remediation_id']."' ";
			$results = $db->sql_query($query);

			// change FINDING status
			$query   =
			  "UPDATE ".
			  "  FINDINGS AS f,".
			  "  POAMS AS p ".
			  "SET ".
			  "  f.finding_status = 'CLOSED', ".
			  "  f.finding_date_closed = NOW() ".
			  "WHERE ".
			  "  f.finding_id = p.finding_id AND ".
			  "  p.poam_id = '".$_POST['remediation_id']."'";
			$results = $db->sql_query($query);

		  }

		  // any denial changes status back to EN, clears completion date
		  if ($_POST['new_value'] == 'DENIED') {

			// update the poam status and completion date
			$query = "UPDATE POAMS SET poam_status = 'EN', poam_action_date_actual = NULL WHERE (poam_id = '".$_POST['remediation_id']."')";
			$results = $db->sql_query($query);

		  }

		}

		break;


		//
		// REMEDIATION
		//
	  case 'remediation':

		// create our update query
		$query =
		  "UPDATE ".
		  "  POAMS AS p ".
		  "SET ".
		  "  p.poam_status = '".$_POST['new_value']."' ".
		  "WHERE ".
		  "  p.poam_id = '".$_POST['remediation_id']."' ";

		// execute it
		$results = $db->sql_query($query);

		break; // switch (target) = remediation_owner


		//
		// REMEDIATION_OWNER
		//
	  case 'remediation_owner':

		// create our update query
		$query =
		  "UPDATE ".
		  "  POAMS AS p ".
		  "SET ".
		  "  p.poam_action_owner = '".$_POST['new_value']."' ".
		  "WHERE ".
		  "  p.poam_id = '".$_POST['remediation_id']."' ";

		// execute it
		$results = $db->sql_query($query);

		break; // switch (target) = remediation_owner


		//
		// REMEDIATION_TYPE
		//
	  case 'remediation_type':

		// update our POAM to reflect the new type
		$query =
		  "UPDATE ".
		  "  POAMS AS p ".
		  "SET ".
		  "  p.poam_type               = '".$_POST['new_value']."', ".
		  "  p.poam_status             = 'OPEN', ".
		  "  p.poam_date_modified      = NOW(), ";
//		  "  p.poam_action_suggested   = NULL, ";
//
//		// update action planned based on type
//		if ($_POST['new_value'] == 'CAP') { $query .= "  p.poam_action_planned = NULL, "; }
//
//		// update False Positive course of action
//		if ($_POST['new_value'] == 'FP' ) {
//		  $query .= "  p.poam_action_planned = 'Document the non-existance of vulnerability or weakness and submit as evidence for review.', ";
//		}
//
//		// update Accepted Risk course of action
//		if ($_POST['new_value'] == 'AR' ) {
//		  $query .= "  p.poam_action_planned = 'Document the business justification for the acceptance of risk, ".
//			"the level of risk and the impact on the accepting environment. Include the factors used to determine ".
//			"the risk level and impact in your documentation and submit as evidence for review.', ";
//		}
//
		// continue building our query
		$query .=
		  "  p.poam_action_planned     = NULL, ".
		  "  p.poam_action_date_est    = NULL, ".
		  "  p.poam_action_date_actual = NULL, ".
		  "  p.poam_action_resources   = NULL, ".
		  "  p.poam_action_status      = 'NONE' ".
		  "WHERE ".
		  "  p.poam_id = '".$_POST['remediation_id']."' ";

		// execute it
		$results = $db->sql_query($query);

		// exclude all sso non-evaluations
		$query   =
		  "UPDATE POAM_EVIDENCE as pe ".
		  "SET    pe.ev_sso_approval = 'EXCLUDED' ".
		  "WHERE  pe.ev_sso_approval = 'NONE' ".
		  "AND    poam_id = '".$_POST['remediation_id']."'";
		$results = $db->sql_query($query);

		// exclude all fsa non-evaluations
		$query   =
		  "UPDATE POAM_EVIDENCE as pe ".
		  "SET    pe.ev_fsa_approval = 'EXCLUDED' ".
		  "WHERE  pe.ev_fsa_approval = 'NONE' ".
		  "AND    poam_id = '".$_POST['remediation_id']."'";
		$results = $db->sql_query($query);

		// exclude all iv&v non-evaluations
		$query   =
		  "UPDATE POAM_EVIDENCE as pe ".
		  "SET    pe.ev_ivv_approval = 'EXCLUDED' ".
		  "WHERE  pe.ev_ivv_approval = 'NONE' ".
		  "AND    poam_id = '".$_POST['remediation_id']."'";
		$results = $db->sql_query($query);

		break; // switch (target) = remediation_type


		//
		// BLSCR_NUMBER
		//
	  case 'blscr_number':

		// create our update query
		$query =
		  "UPDATE ".
		  "  POAMS AS p ".
		  "SET ".
		  "  p.poam_blscr = '".$_POST['new_value']."' ".
		  "WHERE ".
		  "  p.poam_id = '".$_POST['remediation_id']."' ";

		// execute it
		$results = $db->sql_query($query);

		break; // switch (target) = blscr_number


		//
		// ACTION_DATE_EST
		//
	  case 'action_date_est':


		/*
		// reformat the date string for MySQL
		$split_date = split('/', $_POST['new_value']);
		$full_date  = $split_date[2].'-'.$split_date[0].'-'.$split_date[1];
		*/

		// create our update query
		$query =
		  "UPDATE ".
		  "  POAMS AS p ".
		  "SET ".
		  "  p.poam_action_date_est   = '".$_POST['new_value']."', ".
		  "  p.poam_action_status     = 'NONE' ".
		  "WHERE ".
		  "  p.poam_id = '".$_POST['remediation_id']."' ";

		// execute it
		$results = $db->sql_query($query);

		break; // switch (target) = action_date_est


		//
		// ACTION_SUGGESTED
		//
	  case 'action_suggested':

		// create our update query
		$query =
		  "UPDATE ".
		  "  POAMS AS p ".
		  "SET ".
		  "  p.poam_action_suggested   = '".$_POST['new_value']."', ".
		  "  p.poam_action_status      = 'NONE' ".
		  "WHERE ".
		  "  p.poam_id = '".$_POST['remediation_id']."' ";

		// execute it
		$results = $db->sql_query($query);

		break; // switch (target) = action_suggested


		//
		// ACTION PLANNED
		//
	  case 'action_planned':

		// create our update query
		$query =
		  "UPDATE ".
		  "  POAMS AS p ".
		  "SET ".
		  "  p.poam_action_planned = '".$_POST['new_value']."', ".
		  "  p.poam_action_status  = 'NONE' ".
		  "WHERE ".
		  "  p.poam_id = '".$_POST['remediation_id']."' ";

		// execute it
		$results = $db->sql_query($query);

		break; // switch (target) = action_planned


		//
		// ACTION RESOURCES
		//
	  case 'action_resources':

		// create our update query
		$query =
		  "UPDATE ".
		  "  POAMS AS p ".
		  "SET ".
		  "  p.poam_action_resources = '".$_POST['new_value']."' ".
		  "WHERE ".
		  "  p.poam_id = '".$_POST['remediation_id']."' ";

		// execute it
		$results = $db->sql_query($query);

		break; // switch (target) = action_resources


		//
		// ACTION APPROVAL
		//
	  case 'action_approval':

		// create our update query
		$query =
		  "UPDATE ".
		  "  POAMS AS p ".
		  "SET ".
		  "  p.poam_action_status = '".$_POST['new_value']."' ".
		  "WHERE ".
		  "  p.poam_id = '".$_POST['remediation_id']."' ";

		// execute it
		$results = $db->sql_query($query);

		// if it was an approval, change status to EN
		if ($_POST['new_value'] == 'APPROVED') {

		  $query   = "UPDATE POAMS SET poam_status = 'EN' WHERE poam_id = '".$_POST['remediation_id']."' ";
		  $results = $db->sql_query($query);

		}

		// if it was a denial, change status to OPEN
		else {

		  $query   = "UPDATE POAMS SET poam_status = 'OPEN' WHERE poam_id = '".$_POST['remediation_id']."' ";
		  $results = $db->sql_query($query);

		}

		break; // switch (target) = action_approval


		//
		// COUNTERMEASURE EFFECTIVENESS
		//
	  case 'cmeasure_effectiveness':

		// create our update query
		$query =
		  "UPDATE ".
		  "  POAMS AS p ".
		  "SET ".
		  "  p.poam_cmeasure_effectiveness = '".$_POST['new_value']."', ".
		  "  p.poam_action_status          = 'NONE' ".
		  "WHERE ".
		  "  p.poam_id = '".$_POST['remediation_id']."' ";

		// execute it
		$results = $db->sql_query($query);

		break; // switch (target) = countermeasure_effectiveness


		//
		// COUNTERMEASURE
		//
	  case 'cmeasure':

		// create our update query
		$query =
		  "UPDATE ".
		  "  POAMS AS p ".
		  "SET ".
		  "  p.poam_cmeasure      = '".$_POST['new_value']."', ".
		  "  p.poam_action_status = 'NONE' ".
		  "WHERE ".
		  "  p.poam_id = '".$_POST['remediation_id']."' ";

		// execute it
		$results = $db->sql_query($query);

		break; // switch (target) = countermeasure


		//
		// COUNTERMEASURE JUSTIFICATION
		//
	  case 'cmeasure_justification':

		// create our update query
		$query =
		  "UPDATE ".
		  "  POAMS AS p ".
		  "SET ".
		  "  p.poam_cmeasure_justification = '".$_POST['new_value']."', ".
		  "  p.poam_action_status          = 'NONE' ".
		  "WHERE ".
		  "  p.poam_id = '".$_POST['remediation_id']."' ";

		// execute it
		$results = $db->sql_query($query);

		break; // switch (target) = countermeasure_justification


		//
		// THREAT LEVEL
		//
	  case 'threat_level':

		// create our update query
		$query =
		  "UPDATE ".
		  "  POAMS AS p ".
		  "SET ".
		  "  p.poam_threat_level  = '".$_POST['new_value']."', ".
		  "  p.poam_action_status = 'NONE' ".
		  "WHERE ".
		  "  p.poam_id = '".$_POST['remediation_id']."' ";

		// execute it
		$results = $db->sql_query($query);

		break; // switch (target) = threat_level


		//
		// THREAT SOURCE
		//
	  case 'threat_source':

		// create our update query
		$query =
		  "UPDATE ".
		  "  POAMS AS p ".
		  "SET ".
		  "  p.poam_threat_source = '".$_POST['new_value']."', ".
		  "  p.poam_action_status = 'NONE' ".
		  "WHERE ".
		  "  p.poam_id = '".$_POST['remediation_id']."' ";

		// execute it
		$results = $db->sql_query($query);

		break; // switch (target) = threat_source


		//
		// THREAT JUSTIFICATION
		//
	  case 'threat_justification':

		// create our update query
		$query =
		  "UPDATE ".
		  "  POAMS AS p ".
		  "SET ".
		  "  p.poam_threat_justification = '".$_POST['new_value']."', ".
		  "  p.poam_action_status        = 'NONE' ".
		  "WHERE ".
		  "  p.poam_id = '".$_POST['remediation_id']."' ";

		// execute it
		$results = $db->sql_query($query);

		break; // switch (target) = threat_justification

	  } // switch (target)


	} // if (target && action)


	//
	// UPDATE LAST MODIFIED INFORMATION
	//
	$query =
	  "UPDATE ".
	  "  POAMS AS p ".
	  "SET ".
	  "  p.poam_date_modified = NOW(), ".
	  "  p.poam_modified_by = '".$user->getUserId()."' ".
	  "WHERE ( ".
	  "  p.poam_id = '".$_POST['remediation_id']."' ".
	  ")";
	$results = $db->sql_query($query);


	//
	// ADD MODIFICATION COMMENT
	//

	$query =
      "INSERT INTO ".
	  "  POAM_COMMENTS ".
	  "( ".
	  "  poam_id, ".
	  "  user_id, ".
	  "  comment_parent, ".
	  "  comment_date, ".
	  "  comment_topic, ".
	  "  comment_body ".
	  ") ".
	  "VALUES ( ".
	  "  '".$_POST['remediation_id']."', ".
	  "  '".$user->getUserId()."', ".
	  "  '".$_POST['root_comment']."', ".
	  "  '".gmdate('Y-m-d H:i:s', time())."', ".
	  "  '".$_POST['comment_topic']."', ".
	  "  '".$_POST['comment_body']."' ".
	  ")";

	//echo $query."<br><br>";
	$results = $db->sql_query($query);

	//
	// report the results on the continuation page
	//
	if ($results == 0) {

	  // initialize the page title
	  $smarty->assign('page_title', 'Action failed');

	}

	else {

	  $smarty->assign('page_title', 'Action completed');

	}

  }


} // if (form_action == submit)


/*******************************************************************************
* DISPLAY THE PAGE
*******************************************************************************/

// pass form variables through
$smarty->assign('remediation_id', $_POST['remediation_id']);
$smarty->assign('target',         $_POST['target']);
$smarty->assign('action',         $_POST['action']);
$smarty->assign('validated',      $_POST['validated']);
$smarty->assign('approved',       $_POST['approved']);
$smarty->assign('form_action',    $_POST['form_action']);


$smarty->assign('now', gmdate ("M d Y H:i:s", time()));

// display the page
$smarty->display('remediation_modify.tpl');

?>
