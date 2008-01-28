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

$smarty->assign("username",      $user->getUsername());
$smarty->assign("customer_url",  $customer_url);
$smarty->assign("customer_logo", $customer_logo);

if (isset($_POST['remediation_id'])) {
	$poam_id = intval($_POST['remediation_id']);
}

if ($_FILES && ($poam_id > 0)) {
	
    // make our directories if they do not exist (they should)
    if (!file_exists('evidence')) { mkdir('evidence', 0755); }
    if (!file_exists('evidence/'.$poam_id)) { mkdir('evidence/'.$poam_id , 0755); }
    
    // move the file and make sure it is readable
    $dest = 'evidence/'.$poam_id.'/'.gmdate('Ymd-His-', time()).$_FILES['evidence']['name'];
    $result_move = move_uploaded_file($_FILES['evidence']['tmp_name'], $dest);
    chmod($dest, 0755);
      
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

	if(isset($_POST['comment_topic'])){
	    $unix_timestamp = time();
        $now = gmdate('Y-m-d H:i:s', $unix_timestamp);
        $type = 'EV_'.strtoupper(substr($_POST['action'],0,3));
//        var_dump($_POST);die();
        add_poam_comment($db,$user->getUserId(),$poam_id, $_POST['ev_id'], 0,
                        $_POST['comment_topic'], $_POST['comment_body'], $_POST['comment_log'], $now, $type);
    }
    die($reload_page);
}

function getEvType($ev){
    switch ($ev){
        case 'sso_evaluation':
            return 'sso_evaluation';
        case 'fsa_evaluate':
            return 'fsa_evaluation';
        case 'ivv_evaluate':
            return 'ivv_evaluation';
    }
}
?>