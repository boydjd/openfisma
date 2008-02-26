<? 

// required for all pages, after user login is verified function displayloginfor checks all user security functions, gets the users first/last name and customer log as well as loads ovms.ini.php
require_once('config.php');
require_once('dblink.php');

$xlsname = "OpenFISMA_Injection_Template"; 
header("Content-type:application/vnd.ms-excel"); 
header("Content-Disposition:filename=$xlsname.xls"); 

$sql =  "SELECT system_nickname FROM " . TN_SYSTEMS . " ORDER BY system_id DESC";
$System = getfeild($sql,"System",$db);
$sql = "SELECT network_nickname FROM " . TN_NETWORKS . " ORDER BY network_id DESC";
$Network = getfeild($sql,"Network",$db);
$sql = "SELECT source_nickname FROM " . TN_FINDING_SOURCES . " ORDER BY source_id DESC";
$AduitSource = getfeild($sql,"AuditSource",$db);

$smarty->assign('System',$System);
$smarty->assign('Network',$Network);
$smarty->assign('AduitSource',$AduitSource);
$smarty->display('data_injection.tpl');

function getfeild($sql,$feild,$db){
    $result = $db->sql_query($sql) or die("Query failed:".$sql."<br>".$db->sql_error());
    $Arraytype = $db->sql_fetchrowset($result);
    switch($feild){
        case "System":
           $feild = "system_nickname";
           break;
        case "Network":
           $feild = "network_nickname";
           break;
        case "AuditSource":
           $feild = "source_nickname";
           break;
    }
    foreach ($Arraytype as $value){
        $type[] = $value[''.$feild.''];
    }
    $type = implode(",",$type);
    return $type;
}

?>
