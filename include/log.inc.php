<?php
$logArray = array('poam_action_owner'=>'UPDATE: responsible system',
                  'poam_type'=>'UPDATE: remediation type',
                  'poam_status'=>'UPDATE: remediation status',
                  'poam_blscr'=>'UPDATE: BLSCR number',
                  'poam_action_date_est'=>'UPDATE: course of action estimated completion date',
                  'poam_action_status'=>'UPDATE: course of action evaluation',
                  'poam_cmeasure_effectiveness'=>'UPDATE: countermeasure effectiveness',
                  'poam_action_suggested'=>'UPDATE: recommended course of action',
                  'poam_action_planned'=>'UPDATE: course of action',
                  'poam_action_resources'=>'UPDATE: course of action resources',
                  'poam_cmeasure'=>'UPDATE: countermeasure',
                  'poam_cmeasure_justification'=>'UPDATE: countermeasure justification',
                  'poam_threat_source'=>'UPDATE: threat source',
                  'poam_threat_justification'=>'UPDATE: threat justification',
                  'poam_previous_audits'=>'UPDATE: previous audits',
                  'poam_threat_level'=>'UPDATE: threat level',
                  'ev_sso_evaluation'=>'UPDATE: SSO evidence evaluation',
                  'ev_fsa_evaluation'=>'UPDATE: FSA evidence evaluation',
                  'ev_ivv_evaluation'=>'UPDATE: IV&V evidence evaluation'
                  );

function openfisma_log($db, $userid, $finding_id, $field, $old_val, $new_val, $time){
    $event = getEventName($field);
    $SQL = "INSERT INTO AUDIT_LOG ( ".
            	  "  finding_id, ".
            	  "  user_id, ".
            	  "  date, ".
            	  "  event, ".
            	  "  description ".
            	  ") VALUES (".
            	  $finding_id.",".
            	  $userid.",'".
            	  $time."','".
            	  $event."','".
            	  "Original: \"$old_val\"\n New: \"$new_val\"')";
    return $db->sql_query($SQL);
}

function add_poam_comment($db, $user_id, $poam_id, $ev_id, $parent_id, $topic, $body, $log, $time, $type){
    $sql = "INSERT INTO `POAM_COMMENTS` 
                (
                    `poam_id`,
                    `user_id`,
                    `ev_id`,
                    `comment_parent`,
                    `comment_date`,
                    `comment_topic`,
                    `comment_body`,
                    `comment_log`,
                    `comment_type`
                )
            VALUES
                (
                    $poam_id,
                    $user_id,
                    $ev_id,
                    $parent_id,
                    '$time',
                    '$topic',
                    '$body',
                    '$log',
                    '$type'
                )";
//    die($sql);
    return $db->sql_query($sql);
}

function getEventName($field){
    global $logArray;
    return $logArray[$field];
}
?>