<?php

require_once("log.inc.php");
// required for all pages, after user login is verified function displayloginfor checks all user security functions, gets the users first/last name and customer log as well as loads ovms.ini.php
require_once("config.php");
// required for all pages, sets smarty directory locations for cache, templates, etc.
require_once("smarty.inc.php");
require_once("dblink.php");
// User class which is required by all pages which need to validate authentication and interact with variables of a user (Functions: login, getloginstatus, getusername, getuserid, getpassword, checkactive, etc)
require_once("user.class.php");
// Functions required by all front-end pages gathered in one place for ease of maintenance. (verify_login, sets global page title, insufficient priveleges error, and get_page_datetime)
require_once("page_utils.php");

// session_start() creates a session or resumes the current one based on the current session id that's being passed via a request, such as GET, POST, or a cookie.
// If you want to use a named session, you must call session_name() before calling session_start().
session_start();

// creates a new user object from the user class
$user = new User($db);

// validates that the user is logged in properly, if not redirects to the login page.
verify_login($user, $smarty);

//
// turn off magic quotes
//
if (get_magic_quotes_gpc()) { set_magic_quotes_runtime(0); } // echo "quotes off"; }

if (!isset($_POST['poam_id'])) {
	die('alert("No remediation id.");');
}
else {
	$poam_id = $_POST['poam_id'];
}

//    $reload_page = "alert('Operation succeed.');";
	$reload_page = '$(\'<form id="frmJump" action="remediation_detail.php" method="POST">';
    $reload_page .= '<input type="hidden" name="remediation_id" value="'.$poam_id.'">';
    $reload_page .= '</form>\').appendTo("body").submit();';

$sql_update = "UPDATE ".TN_POAMS." SET ";
$sql_get_old_val = "SELECT ";
$i = 0;
//$comment = array();
$logArr = array();

$unix_timestamp = time();
$now = gmdate('Y-m-d H:i:s', $unix_timestamp);

$userid = $user->getUserId();
$username = $user->getUsername();

foreach ($_POST as $k => $v) {
    if (!in_array($k, array('poam_id')) && (substr($k, 0, 7) != 'comment')){
	   $sql_update .= " `$k`= '".$v."', ";
	   $sql_get_old_val .= "`$k`,";
	   $i++;
	   $logArr[$k] = $v;
    }
}

$sql_update .= " `poam_date_modified`='".$now."', `poam_modified_by`= ".$userid;

if (isset($_POST['poam_action_date_est']) && isset($_POST['poam_action_suggested']) && isset($_POST['poam_action_planned'])) {
	$sql_update .= ", `poam_action_status`='NONE' ";
}

if (isset($_POST['poam_action_status'])){		// if poam_action_status changed
	if ($_POST['poam_action_status']=='APPROVED') {
		// update poam_status
		$sql_update .= ", `poam_status`='EN' ";
	}
	elseif ($_POST['poam_action_status']!='APPROVED'){
		$sql_update .= ", `poam_status`='OPEN' ";
	}
}
$sql_update .= " WHERE `poam_id`=".intval($poam_id);
$sql_get_old_val .= TN_POAMS.".finding_id FROM ".TN_POAMS.",".TN_POAM_EVIDENCE." WHERE ".TN_POAMS.".`poam_id`=".intval($poam_id);
//die($sql_update);
$db->sql_query($sql_get_old_val);
$row = $db->sql_fetchrow();

// write log
if ($i?$db->sql_query($sql_update):1) {
    foreach ($logArr as $field=>$value) {
        openfisma_log($db, $userid, $row['finding_id'], $field, $row[$field], $value, $unix_timestamp);
	}
	if(isset($_POST['comment_topic'])){
        add_poam_comment($db,$userid,$poam_id, 0, isset($_POST['comment_parent'])?intval($_POST['comment_parent']):0,
                        $_POST['comment_topic'], $_POST['comment_body'], $_POST['comment_log'], $now, $_POST['comment_type']);
    }
    die($reload_page);
}
else {
	die('alert("Save data fail!");');
}

?>