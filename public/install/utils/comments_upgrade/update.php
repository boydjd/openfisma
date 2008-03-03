<?php
require_once("../../../ovms.ini.php");
require_once("dblink.php");
//die(json_encode($_POST));
foreach ($_POST as $k=>$v) {
    $k = str_replace('_', ' ', $k);
    $sql = "UPDATE ".TN_POAM_COMMENTS." SET `comment_type`='$v' WHERE ";
    if (is_numeric($k)){
        $sql .= " `comment_id`=$k";
    }
    else {
        $sql .= " `comment_topic`='$k'";
    }
//    die($sql);
    $r = $db->sql_query($sql);
    if($db->sql_errno()) die('FAIL');
}
die('OK');
?>