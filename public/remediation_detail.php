<?PHP
// no-cache ? forces caches to submit the request to the origin server for validation before releasing a cached copy, every time. This is useful to assure that authentication is respected.
// must-revalidate ? tells caches that they must obey any freshness information you give them about a representation. By specifying this header, you�re telling the cache that you want it to strictly follow your rules.
header("Cache-Control: no-cache, must-revalidate");

// required for all pages, after user login is verified function displayloginfor checks all user security functions, gets the users first/last name and customer log as well as loads ovms.ini.php
require_once("config.php");
require_once("dblink.php");

// set the page name
$smarty->assign('pageName', 'Remediation Summary');

// session_start() creates a session or resumes the current one based on the current session id that's being passed via a request, such as GET, POST, or a cookie.
// If you want to use a named session, you must call session_name() before calling session_start().
session_start();

// grab today's date
$today = date("Ymd", time());

// creates a new user object from the user class
$user = new User($db);

// validates that the user is logged in properly, if not redirects to the login page.
verify_login($user, $smarty);

// let's template know how to display the page
$smarty->assign('view_right',                             $user->checkRightByFunction("remediation", "read"));
$smarty->assign('modify_type',                            $user->checkRightByFunction("remediation", 'update'));
$smarty->assign('modify_action_owner',                    $user->checkRightByFunction("remediation", 'update_finding_assignment'));
$smarty->assign('generate_raf',                           $user->checkRightByFunction("remediation", 'generate_raf'));
$smarty->assign('modify_blscr',                           $user->checkRightByFunction("remediation", 'update_control_assignment'));
$smarty->assign('modify_cmeasure',                        $user->checkRightByFunction("remediation", 'update_cmeasures'));
$smarty->assign('modify_cmeasure_effectiveness',          $user->checkRightByFunction("remediation", 'update_cmeasures'));
$smarty->assign('modify_cmeasure_justification',          $user->checkRightByFunction("remediation", 'update_cmeasures'));
$smarty->assign('modify_threat_level',                    $user->checkRightByFunction("remediation", 'update_threat'));
$smarty->assign('modify_threat_source',                   $user->checkRightByFunction("remediation", 'update_threat'));
$smarty->assign('modify_threat_justification',            $user->checkRightByFunction("remediation", 'update_threat'));
$smarty->assign('modify_mitigation_recommendation',       $user->checkRightByFunction("remediation", 'update_finding_recommendation'));
$smarty->assign('modify_mitigation_course_of_action',     $user->checkRightByFunction("remediation", 'update_finding_course_of_action'));
$smarty->assign('modify_mitigation_resources',            $user->checkRightByFunction("remediation", 'update_finding_resources'));
$smarty->assign('modify_mitigation_completion_date',      $user->checkRightByFunction("remediation", 'update_est_completion_date'));
$smarty->assign('modify_mitigation_sso_approval',         $user->checkRightByFunction("remediation", 'update_mitigation_strategy_approval'));
$smarty->assign('view_evidence',                          $user->checkRightByFunction("remediation", 'read_evidence'));
$smarty->assign('modify_evidence_upload',                 $user->checkRightByFunction("remediation", 'update_evidence'));
$smarty->assign('modify_evidence_sso_approval',           $user->checkRightByFunction("remediation", 'update_evidence_approval_first'));
$smarty->assign('modify_evidence_fsa_approval',           $user->checkRightByFunction("remediation", 'update_evidence_approval_second'));
$smarty->assign('modify_evidence_ivv_approval',           $user->checkRightByFunction("remediation", 'update_evidence_approval_third'));
/*$smarty->assign('view_comments',                        $user->checkRightByFunction("remediation", 'view_comments'));
$smarty->assign('modify_comments',                        $user->checkRightByFunction("remediation", 'modify_comments'));*/

/*******************************************************************************
* FORM ACTIONS
*******************************************************************************/

// initialize or propagate filter values
if (isset($_POST['remediation_ids'])) { $smarty->assign('remediation_ids', $_POST['remediation_ids']); } else { $smarty->assign('remediation_ids', 'any'); }
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

  $current_time_int = time();
  // grab the finding_id for easy use
  $finding_id = $_POST['finding_id'];

  // update the finding
  $query =
	"UPDATE ".
	"  " . TN_FINDINGS . " AS f ".
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
	"INSERT INTO " . TN_POAMS . " ( ".
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
	" '$current_time_string', ".
	" '$current_time_string', ".
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
    "INSERT INTO " . TN_AUDIT_LOG . " ".
    "(finding_id,user_id,date,event,description)".
	"VALUES ( ".
    "  '".$_POST['finding_id']."', ".
	"  '".$user->getUserId()."', ".
    "  '$current_time_int', ".
    "  'CREATE:NEW REMEDIATION CREATED', ".
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
if(!$db->sql_numrows($results)) {
    header('Location: remediation.php?unlucky='.$remediation_id);
}
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
$num_evidence = $db->sql_numrows($results);
if($num_evidence){
    foreach ($all_evidence as &$evidence) {
        if($comments_ev != null){
    	    $evidence['comments'] = $comments_ev[$evidence['ev_id']];
        }
        $evidence['fileName'] = basename($evidence['ev_submission']);
        if (file_exists($evidence['ev_submission'])) {
            $evidence['fileExists'] = 1;
        }
        else {
        	$evidence['fileExists'] = 0;
        }
    }
}
$smarty->assign('all_evidence', $all_evidence);
$smarty->assign('num_evidence', $num_evidence);


//
// Audit log
//
$query = "SELECT ".
         "  u.user_name, ".
         "  al.*, al.date AS time ".
         "FROM " . TN_AUDIT_LOG . "" .
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
foreach ((array)$logs as $k=>$v) {
    date_default_timezone_set('America/New_York');
	$logs[$k]['time'] = date('Y-m-d H:i:s', $logs[$k]['time']);
}
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
$smarty->display('remediation_detail.tpl');
?>
