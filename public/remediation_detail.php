<?PHP
/*******************************************************************************
* File    : remediation_detail.php
* Purpose : performs application requests for the remediation detail page
* Author  : Brian Gant
* Date    :
*******************************************************************************/


/*******************************************************************************
* INITIALIZE PAGE
*******************************************************************************/

header("Cache-Control: no-cache, must-revalidate");

require_once("config.php");
require_once("smarty.inc.php");
require_once("dblink.php");
require_once("user.class.php");
require_once("page_utils.php");

$screen_name = "remediation_detail";

session_start();

// grab today's date
$today = gmdate("Ymd", time());


/*******************************************************************************
* USER RIGHTS
*******************************************************************************/


$user = new User($db);

$loginstatus = $user->login();
if($loginstatus != 1) {
	// redirect to the login page
	$user->loginFailed($smarty);
	exit;
}
displayLoginInfor($smarty, $user);


verify_login($user, $smarty);

// assign header information
$smarty->assign("username", $user->getUsername());
$smarty->assign("customer_url", $customer_url);
$smarty->assign("customer_logo", $customer_logo);

// retrieve the user's rights
$smarty->assign('view_asset_addresses',               $user->checkRightByFunction($screen_name, 'remediation_view_asset_addresses'));
$smarty->assign('view_asset_name',                    $user->checkRightByFunction($screen_name, 'remediation_view_asset_name'));

$smarty->assign('view_finding_instance_data',         $user->checkRightByFunction($screen_name, 'remediation_view_finding_instance_data'));

$smarty->assign('modify_type',                        $user->checkRightByFunction($screen_name, 'remediation_modify_type'));
$smarty->assign('modify_action_owner',                $user->checkRightByFunction($screen_name, 'remediation_modify_action_owner'));

$smarty->assign('generate_raf',                       $user->checkRightByFunction($screen_name, 'remediation_generate_raf'));

$smarty->assign('modify_previous_audits',             $user->checkRightByFunction($screen_name, 'remediation_modify_previous_audits'));

$smarty->assign('view_blscr',                         $user->checkRightByFunction($screen_name, 'remediation_view_blscr'));
$smarty->assign('modify_blscr',                       $user->checkRightByFunction($screen_name, 'remediation_modify_blscr'));

$smarty->assign('view_cmeasure',                      $user->checkRightByFunction($screen_name, 'remediation_view_cmeasure'));
$smarty->assign('modify_cmeasure',                    $user->checkRightByFunction($screen_name, 'remediation_modify_cmeasure'));
$smarty->assign('modify_cmeasure_effectiveness',      $user->checkRightByFunction($screen_name, 'remediation_modify_cmeasure_effectiveness'));
$smarty->assign('modify_cmeasure_justification',      $user->checkRightByFunction($screen_name, 'remediation_modify_cmeasure_justification'));

$smarty->assign('view_threat',                        $user->checkRightByFunction($screen_name, 'remediation_view_threat'));
$smarty->assign('modify_threat_level'    ,            $user->checkRightByFunction($screen_name, 'remediation_modify_threat_level'));
$smarty->assign('modify_threat_source',               $user->checkRightByFunction($screen_name, 'remediation_modify_threat_source'));
$smarty->assign('modify_threat_justification',        $user->checkRightByFunction($screen_name, 'remediation_modify_threat_justification'));

$smarty->assign('view_mitigation',                    $user->checkRightByFunction($screen_name, 'remediation_view_mitigation'));
$smarty->assign('modify_mitigation_recommendation',   $user->checkRightByFunction($screen_name, 'remediation_modify_mitigation_recommendation'));
$smarty->assign('modify_mitigation_course_of_action', $user->checkRightByFunction($screen_name, 'remediation_modify_mitigation_course_of_action'));
$smarty->assign('modify_mitigation_resources',        $user->checkRightByFunction($screen_name, 'remediation_modify_mitigation_resources'));
$smarty->assign('modify_mitigation_completion_date',  $user->checkRightByFunction($screen_name, 'remediation_modify_mitigation_completion_date'));
$smarty->assign('modify_mitigation_sso_approval',     $user->checkRightByFunction($screen_name, 'remediation_modify_mitigation_sso_approval'));

$smarty->assign('view_evidence',                      $user->checkRightByFunction($screen_name, 'remediation_view_evidence'));
$smarty->assign('modify_evidence_upload',             $user->checkRightByFunction($screen_name, 'remediation_modify_evidence_upload'));
$smarty->assign('modify_evidence_sso_approval',       $user->checkRightByFunction($screen_name, 'remediation_modify_evidence_sso_approval'));
$smarty->assign('modify_evidence_fsa_approval',       $user->checkRightByFunction($screen_name, 'remediation_modify_evidence_fsa_approval'));
$smarty->assign('modify_evidence_ivv_approval',       $user->checkRightByFunction($screen_name, 'remediation_modify_evidence_ivv_approval'));

$smarty->assign('view_comments',                      $user->checkRightByFunction($screen_name, 'remediation_view_comments'));
$smarty->assign('modify_comments',                    $user->checkRightByFunction($screen_name, 'remediation_modify_comments'));



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



//
// NEW REMEDIATION
//
if (isset($_POST['finding_id'])) {

  // grab the finding_id for easy use
  $finding_id = $_POST['finding_id'];

  // update the finding
  $query =
	"UPDATE ".
	"  FINDINGS AS f ".
	"SET ".
	"  f.finding_status = 'REMEDIATION' ".
	"WHERE ( ".
	"  f.finding_id = '".$finding_id."' ".
	")";
  $results = $db->sql_query($query);

  // grab the responsible system from the finding
  $query =
	"SELECT ".
	"  sa.system_id ".
	"FROM  " . TN_SYSTEM_ASSETS .
	"  AS sa, ".TN_FINDINGS.
	"  AS f ".
	"WHERE (".
	"  f.finding_id = '".$finding_id."' AND ".
	"  sa.asset_id  = f.asset_id AND ".
	"  sa.system_is_owner = 1 ".
	")";
  $results = $db->sql_query($query);
  $rows    = $db->sql_fetchrow($results);
  $system  = $rows['system_id'];

  // create the new POAM
  $query =
	"INSERT INTO POAMS ( ".
	"  finding_id, ".
	"  poam_created_by, ".
	"  poam_modified_by, ".
	"  poam_date_created, ".
	"  poam_date_modified, ".
	"  poam_action_date_est, ".
	"  poam_action_owner ".
	") ".
	"VALUES ( ".
	" '".$finding_id."', ".
	" '".$user->getUserId()."', ".
	" '".$user->getUserId()."', ".
	" NOW(), ".
	" NOW(), ".
	" '0000-00-00', ".
	" '".$system."' ".
	")";
  $results = $db->sql_query($query);

  //echo $query;

  // get the insert id and fake the remediation post with the new POAM id
  $query   = "SELECT LAST_INSERT_ID() AS poam_id FROM  " . TN_POAMS;
  $results = $db->sql_query($query);
  $rows    = $db->sql_fetchrow($results);
  $remediation_id = $rows['poam_id'];

  // create the root comment for the poam
  $query =
	"INSERT INTO POAM_COMMENTS ".
	"(poam_id, user_id, comment_parent, comment_date, comment_topic, comment_body) ".
	"VALUES ( ".
	"  '".$remediation_id."', ".
	"  '".$user->getUserId()."', ".
	"  NULL, ".
	"  NOW(), ".
	"  'SYSTEM: NEW REMEDIATION CREATED', ".
	"  'A new remediation was created from finding ".$_POST['finding_id']."' ".
	")";
  $results = $db->sql_query($query);

}

//
// EXISTING REMEDIATION
//
else {

  //
  // REDIRECT ON A NON-FORM SUBMITTED INVOCATION OR GRAB THE REMEDIATION ID
  //
  if (! isset($_POST['remediation_id'])) { header('Location: remediation.php'); }
  else { $remediation_id = $_POST['remediation_id']; }

}

$smarty->assign('remediation_id', $remediation_id);



/*******************************************************************************
* QUERY ACTIONS
*******************************************************************************/

//
// FINDING INFORMATION QUERY
//
$query = "SELECT ".
         "  a.asset_id, ".
         "  a.asset_name, ".
         "  fs.source_nickname, ".
         "  fs.source_name, ".
         "f.finding_id, ".
         "  f.finding_status, ".
         "  f.finding_date_discovered, ".
         "  f.finding_date_created, ".
         "  f.finding_data, ".
         "  s.system_nickname, ".
         "  s.system_name ".
         "FROM " . TN_ASSETS .
         "  AS a, " . TN_FINDINGS.
         "  AS f, " . TN_FINDING_SOURCES.
         "  AS fs, " . TN_POAMS.
         "  AS p, " . TN_SYSTEMS.
         "  AS s, " . TN_SYSTEM_ASSETS.
         "  AS sa ".
         "WHERE ( ".
         "  p.poam_id = ".$remediation_id." AND ".
         "  p.finding_id = f.finding_id AND ".
         "  fs.source_id = f.source_id AND ".
         "  f.asset_id = a.asset_id AND ".
         "  sa.system_id = s.system_id AND ".
         "  sa.system_is_owner = 1 AND ".
         "  sa.asset_id = a.asset_id ".
         ") ";

// execute our built query
$results = $db->sql_query($query);
$finding = $db->sql_fetchrow($results);
$smarty->assign('finding', $finding);


//
// ASSET NETWORK AND ADDRESSES QUERY
//
$query = "SELECT DISTINCT ".
         "  n.network_nickname, ".
         "  aa.address_ip, ".
         "  aa.address_port ".
         "FROM " . TN_NETWORKS .
         " AS n, " . TN_ASSETS .
         " AS a, " . TN_ASSET_ADDRESSES .
         " AS aa ".
         "WHERE ( ".
         "  aa.asset_id = ".$finding['asset_id']." AND ".
         "  n.network_id = aa.network_id ".
         ")";

// execute our built query
$results         = $db->sql_query($query);
$asset_addresses = $db->sql_fetchrowset($results);
$smarty->assign('asset_addresses', $asset_addresses);


//
// FINDING VULNERABILITIES QUERY
//
$query = "SELECT DISTINCT ".
         "  v.vuln_type, ".
         "  v.vuln_seq, ".
         "  v.vuln_desc_primary, ".
         "  v.vuln_desc_secondary ".
         "FROM " . TN_FINDINGS.
         " AS f, " . TN_FINDING_VULNS.
         " AS fv, " . TN_POAMS.
         " AS p, " . TN_VULNERABILITIES.
         " AS v ".
         "WHERE ( ".
         "  p.poam_id = ".$remediation_id." AND ".
         "  p.finding_id = f.finding_id AND ".
         "  fv.finding_id = f.finding_id AND ".
         "  fv.vuln_type = v.vuln_type AND ".
         "  fv.vuln_seq = v.vuln_seq ".
         ") ";

// execute our built query
$results         = $db->sql_query($query);
$vulnerabilities = $db->sql_fetchrowset($results);
$smarty->assign('vulnerabilities', $vulnerabilities);


//
// REMEDIATION QUERY
//
$query = "SELECT ".
         "  p.*, ".
         "  s.system_nickname, ".
         "  s.system_name, ".
         "  u1.user_name AS created_by, ".
         "  u2.user_name AS modified_by ".
         "FROM " . TN_POAMS.
         " AS p, " . TN_SYSTEMS.
         " AS s, " . TN_USERS.
         " AS u1, " . TN_USERS.
         " AS u2 ".
         "WHERE ( ".
         "  p.poam_id = ".$remediation_id." AND ".
         "  s.system_id = p.poam_action_owner AND ".
         "  u1.user_id = p.poam_created_by AND ".
         "  u2.user_id = p.poam_modified_by ".
         ") ";

// execute our built query
$results     = $db->sql_query($query);
$remediation = $db->sql_fetchrow($results);


// check the date for our status
$est = implode(split('-', $remediation['poam_action_date_est']));

// update the status if necessary
if (($est < $today) && ($remediation['poam_status'] == 'EN')) { $remediation['poam_status'] = 'EO'; }

// assign our remediation information to Smarty
$smarty->assign('remediation', $remediation);

// grab a few variables for quick testing
$smarty->assign('remediation_status',     $remediation['poam_status']);
$smarty->assign('remediation_type',       $remediation['poam_type']);
$smarty->assign('threat_level',           $remediation['poam_threat_level']);
$smarty->assign('cmeasure_effectiveness', $remediation['poam_cmeasure_effectiveness']);


//
// PRODUCT QUERY
//
$query = "SELECT ".
         "  p.prod_id, ".
         "  p.prod_vendor, ".
         "  p.prod_name, ".
         "  p.prod_version ".
         "FROM " . TN_ASSETS.
         " AS a, " . TN_FINDINGS.
         " AS f, " . TN_PRODUCTS.
         " AS p ".
         "WHERE ( ".
         "  f.finding_id = ".$remediation{'finding_id'}." AND ".
         "  a.asset_id = f.asset_id AND ".
         "  p.prod_id = a.prod_id ".
         ") ";

// execute our built query
$results = $db->sql_query($query);
$product = $db->sql_fetchrow($results);
$smarty->assign('product', $product);


//
// BLSCR QUERY
//
$query = "SELECT ".
         "  b.* ".
         "FROM " . TN_BLSCR.
         "  AS b, " . TN_POAMS.
         "  AS p ".
         "WHERE ( ".
         "  p.poam_id = ".$remediation_id." AND ".
         "  b.blscr_number = p.poam_blscr ".
         ") ";

// execute our built query
$results = $db->sql_query($query);
$blscr   = $db->sql_fetchrow($results);
$smarty->assign('blscr', $blscr);


//
// COMMENTS QUERY
//
$query = "SELECT ".
         "  u.user_name, ".
         "  pc.* ".
         "FROM " . TN_POAM_COMMENTS.
         " AS pc, " . TN_USERS.
         " AS u ".
         "WHERE ( ".
         "  pc.poam_id = ".$remediation_id." AND ".
         "  pc.user_id = u.user_id AND ".
         "  1 ".
//         "  pc.comment_type IN ('EST','SSO') ".
         ") ".
         "ORDER BY ".
//         "  pc.comment_parent, ".
         "  pc.comment_date ".
         "DESC ";

$results      = $db->sql_query($query);
$comments     = $db->sql_fetchrowset($results);
$comments_est = $comments_sso = $comments_ev = array();
if (count($comments) > 0){
    foreach ($comments as &$comment) {
    	$comment['comment_topic'] = stripslashes($comment['comment_topic']);
    	$comment['comment_body']  = nl2br($comment['comment_body']);
    	$comment['comment_log']  = nl2br($comment['comment_log']);
    	if ($comment['comment_type'] == 'EST'){
    	    $comments_est[] = $comment;
    	}elseif ($comment['comment_type'] == 'SSO'){
    	    $comments_sso[] = $comment;
    	}
    	elseif (isset($comment['ev_id']) && ($comment['ev_id'] > 0)) {
    		$comments_ev[$comment['ev_id']][$comment['comment_type']] = $comment;
    	}
    }
}
$smarty->assign('comments_ev', $comments_ev);
$smarty->assign('comments_est', $comments_est);
$smarty->assign('comments_sso', $comments_sso);
$smarty->assign('num_comments_est', count($comments_est));
$smarty->assign('num_comments_sso', count($comments_sso));


//
// EVIDENCE QUERY
//
$query = "SELECT ".
         "  u.user_name AS submitted_by, ".
         "  pe.* ".
         "FROM " . TN_POAM_EVIDENCE.
         " AS pe, " . TN_USERS.
         " AS u ".
         "WHERE ( ".
         "  pe.poam_id = '".$remediation_id."' AND ".
         "  u.user_id  = pe.ev_submitted_by ".
         ") ".
         "ORDER BY ".
         "  pe.ev_date_submitted ".
         "ASC";

// execute our built query
$results      = $db->sql_query($query);
$all_evidence = $db->sql_fetchrowset($results);
foreach ($all_evidence as &$evidence) {
	$evidence['comments'] = $comments_ev[$evidence['ev_id']];
}
$num_evidence = $db->sql_numrows($results);
$smarty->assign('all_evidence', $all_evidence);
$smarty->assign('num_evidence', $num_evidence);


//
// Audit log
//
$query = "SELECT ".
         "  u.user_name, ".
         "  al.*, FROM_UNIXTIME(al.date) AS time ".
         "FROM `AUDIT_LOG`" .
         " AS al, " . TN_USERS.
         " AS u, ". TN_POAMS.
         " AS p ".
         "WHERE ( ".
         "  al.finding_id = p.finding_id AND ".
         "  p.poam_id = ".$remediation_id." AND ".
         "  al.user_id = u.user_id ".
         ") ".
         "ORDER BY ".
         "  al.date ".
         "DESC ";

$results      = $db->sql_query($query);
$logs     = $db->sql_fetchrowset($results);
$smarty->assign('logs', $logs);
$smarty->assign('num_logs', count($logs));

//
// ROOT COMMENT
//
$query = "SELECT ".
         "  pc.comment_id ".
         "FROM " . TN_POAM_COMMENTS.
         " AS pc ".
         "WHERE ( ".
         "  pc.poam_id = ".$remediation_id." AND ".
         "  pc.comment_parent IS NULL ".
         ") ";
$results      = $db->sql_query($query);
$root_comment = $db->sql_fetchrow($results);
$smarty->assign('root_comment', $root_comment['comment_id']);

//
// ALL FIELDS OK?
//
$r = $remediation;
$r_fields_null = array($r['poam_threat_source'], $r['poam_threat_justification'], 
				  $r['poam_cmeasure'], $r['poam_cmeasure_justification'], $r['poam_action_suggested'], 
				  $r['poam_action_planned'], $r['poam_action_resources'], $r['poam_blscr']);
$r_fields_zero = array($r['poam_action_date_est']);
$r_fields_none = array($r['poam_cmeasure_effectiveness'], $r['poam_threat_level']);
$is_completed = (in_array(null, $r_fields_null) || in_array('NONE', $r_fields_none) || in_array('0000-00-00', $r_fields_zero))?'no':'yes';
$smarty->assign('is_completed', $is_completed);

/*******************************************************************************
* PAGE DISPLAY
*******************************************************************************/


$smarty->assign('now', gmdate ("M d Y H:i:s", time()));
$smarty->display('remediation_detail.tpl');
?>
