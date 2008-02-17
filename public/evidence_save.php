<?php
// Smarty specific includes
require_once("config.php");
require_once("smarty.inc.php");
// db includes
require_once("dblink.php");

session_start();
require_once("user.class.php");
require_once("log.inc.php");

$user = new User($db);
$loginstatus = $user->login();
if($loginstatus != 1) {
	// redirect to the login page
	$user->loginFailed($smarty);
	exit;
}

displayLoginInfor($smarty, $user);

if (isset($_POST['remediation_id'])) {
	$poam_id = intval($_POST['remediation_id']);
}

if ($_FILES && ($poam_id > 0)) {
	
    // make our directories if they do not exist (they should)
    if (!file_exists('evidence')) { mkdir('evidence', 0755); }
    if (!file_exists('evidence/'.$poam_id)) { mkdir('evidence/'.$poam_id , 0755); }
    
    // move the file and make sure it is readable
    $dest = 'evidence/'.$poam_id.'/'.gmdate('Ymd-His-', time()).$_FILES['evidence']['name'];
    $result_move = move_uploaded_file($_FILES['evidence']['tmp_name'], dirname(__FILE__).'/'.$dest);
    if($result_move){
        chmod(dirname(__FILE__).'/'.$dest, 0755);
    }
    else {
    	die('Move upload file fail. ' . dirname(__FILE__).'/'.$dest);
    }
      
    // generate our query
    $query =
    "INSERT INTO POAM_EVIDENCE ( ".
    "  poam_id, ".
    "  ev_submission, ".
    "  ev_submitted_by, ".
    "  ev_date_submitted ".
    ") VALUES ( ".
    "  '".$poam_id."', ".
    "  '".$dest."', ".
    "  '".$user->getUserId()."', ".
    "  CURDATE() ".
    ")";
    
    // execute it
    $result_insert = $db->sql_query($query);
    
    // we now have provided evidence, update the POAM status and completion date
    $query = "UPDATE POAMS SET poam_status = 'EP', poam_action_date_actual = NOW() WHERE (poam_id = '".$poam_id."')";
    $result_update = $db->sql_query($query);
    
//    $reload_page = '<script LANGUAGE="JavaScript" type="text/javascript" src="javascripts/jquery/jquery.js"></script>';
//    $reload_page .= '<script>';
//    $reload_page .= '$(\'<form id="frmJump" action="remediation_detail.php" method="POST">';
//    $reload_page .= '<input type="hidden" name="remediation_id" value="'.$_POST['remediation_id'].'">';
//    $reload_page .= '</form>\').appendTo("body").submit();';
//    $reload_page .= '</script>';

    $reload_page = <<< end
        <html>
        <head>
        <title>...</title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
        <script LANGUAGE="JavaScript" type="text/javascript" src="javascripts/jquery/jquery.js"></script>
        <script>
        $(document).ready(function(){
            $('<form id="frmJump" action="remediation_detail.php" method="POST"><input type="hidden" name="remediation_id" value="$poam_id"></form>').appendTo("body").submit();
        });
        </script>
        </head>
        <body>
            loading ...
        </body>
        </html>
end;
    
    if($result_insert && $result_update && $result_move){       
        die($reload_page);
    }
    else {
    	die('<script>alert("Save data fail!");</script>'.$reload_page);
    }
}
elseif (($_POST['action'] == 'sso_evaluate') || ($_POST['action'] == 'fsa_evaluate') || ($_POST['action'] == 'ivv_evaluate')) {

    $field = 'ev_'.getEvType($_POST['action']);
    $sql_get_finding_id = "SELECT ".TN_POAMS.".finding_id, ".TN_POAM_EVIDENCE.'.'.$field." FROM ".
                            TN_POAMS." LEFT JOIN ".TN_POAM_EVIDENCE." ON ".TN_POAMS.".`poam_id`=".TN_POAM_EVIDENCE.".`poam_id`"
                            ." WHERE ".TN_POAMS.".`poam_id`=".intval($poam_id)." AND ev_id=".$_POST['ev_id'];
    $db->sql_query($sql_get_finding_id);
    $old_val = $db->sql_fetchrow();
    
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
    
	$reload_page = '$(\'<form id="frmJump" action="remediation_detail.php" method="POST">';
    $reload_page .= '<input type="hidden" name="remediation_id" value="'.$poam_id.'">';
    $reload_page .= '</form>\').appendTo("body").submit();';
    
//    die($reload_page);
    $unix_timestamp = time();
    $now = gmdate('Y-m-d H:i:s', $unix_timestamp);
    $userid = $user->getUserId();
    openfisma_log($db, $userid, $old_val['finding_id'], $field, $old_val[$field], $_POST['new_value'], $unix_timestamp);

	if(isset($_POST['comment_topic'])){
        $type = 'EV_'.strtoupper(substr($_POST['action'],0,3));
        add_poam_comment($db,$user->getUserId(),$poam_id, $_POST['ev_id'], 0,
                        $_POST['comment_topic'], $_POST['comment_body'], $_POST['comment_log'], $now, $type);
    }
    die($reload_page);
}

function getEvType($ev){
    switch ($ev){
        case 'sso_evaluate':
            return 'sso_evaluation';
        case 'fsa_evaluate':
            return 'fsa_evaluation';
        case 'ivv_evaluate':
            return 'ivv_evaluation';
    }
}
?>