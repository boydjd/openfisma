<?PHP
header("Cache-Control: no-cache, must-revalidate");

require_once("config.php");
require_once("smarty.inc.php");
require_once("ovms.ini.php");
require_once("dblink.php");
require_once("finding.class.php");
require_once("findingDBManager.php");
require_once("page_utils.php");
require_once("upload_utils.php");

/**************User Rights*****************/
require_once("user.class.php");


$screen_name = "finding"; // sync with the finding page upload permissions
$smarty->assign('pageName', 'Spreadsheet Upload');

session_start();

$user = new User($db);
$loginstatus = $user->login();
if($loginstatus != 1) {
        // redirect to the login page
        $user->loginFailed($smarty);
        exit;
}

displayLoginInfor($smarty, $user);

// retrieve the user's persmissions
$upload_right = $user->checkRightByFunction($screen_name, "upload");

// assign user right to smarty template
$smarty->assign('upload_right', $upload_right);

// start with csv file operation
$csvFileArray = isset($_FILES['csv'])?$_FILES['csv']:array();
$isCsv = checkCsvFile($csvFileArray);
switch ($isCsv){
    case -1:
        $error_msg = 'Error: Binary file.';
        break;
    case -4:
        $error_msg = 'Error: Empty file.';
        break;
    case -2:
        $error_msg = 'Error: File is too big.';
        break;
    case 0:
        $error_msg = '';
        break;
    default:
        $error_msg = 'Error: Unknow error.';
        break;            
}
if (empty($csvFileArray) || $csvFileArray['error']) {
    $smarty->display('finding_injection.tpl');	
    return ;
}
elseif (!empty($error_msg)){
    $smarty->assign('error_msg', $error_msg);
    $smarty->display('finding_injection.tpl');	
    return ;
}

$fileName = $csvFileArray['name'];
$tempFile = $csvFileArray['tmp_name'];
$fileSize = $csvFileArray['size'];

$faildArray = array();
$succeedArray = array();

$row = -2;
$handle = fopen($tempFile,"r");
while ($data = fgetcsv($handle, 1000, ",", '"')) {
    if (implode('',$data)!=''){
        $row++;   
        if ($row>0){ // there are 2 lines for the table heaser, ignor it.
            $sql = csvQueryBuild($data, $db);
            if (!$sql) {
                $faildArray[] = $data;
            }
            else {
                foreach ($sql as $query) {
                	$db->sql_query($query) or die("Query failed: " .$query."<br>". $db->sql_error());
                }
                $succeedArray[] = $data;
            }
        }
    }
}
fclose($handle);

$summary_msg = "You have upload a CSV file which contains $row lines of data.<br />";
if(count($faildArray)>0){
//    unlink('temp/*.csv');
    $temp_file = 'temp/csv_'.date('YmdHis').'_'.rand(10,99).'.csv';
    $fp = fopen($temp_file, 'w');
    foreach ($faildArray as $fail) {
    	fputcsv($fp, $fail);
    }
    fclose($fp);
    $summary_msg .= count($faildArray)." line(s) cannot be parsed. Click <a href='$temp_file'>here</a> to download it, then fix it and try again.<br />";
}
if(count($succeedArray)>0){
    $summary_msg .= count($succeedArray)." line(s) prased successful.";
}
if(count($succeedArray)==$row){
    $summary_msg .= "Congratulations! All of the lines prased successful.";
}

$smarty->assign('error_msg', $summary_msg);
$smarty->display('finding_injection.tpl');	
return ;


function checkCsvFile($fileArray){
    if($fileArray['size']<1) return -4; // empty file
    
    if($fileArray['size']>1048576) return -2; // big file
    
    $bi = preg_match('/\x00|\xFF/', file_get_contents($fileArray['tmp_name']));
    if($bi) return -1; // binary file
    
    return 0;
}

function csvQueryBuild($row, &$db){
    if (!is_array($row) || (count($row)<7)) return false;
    if (strlen($row[3])>63 || (!is_numeric($row[4]) && !empty($row[4]))) return false;
    if (in_array('', array($row[0],$row[1],$row[2],$row[5],$row[6]))) return false;
    $row[2] = date('Y-m-d',strtotime($row[2]));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$row[2])) return false;
    $row[0] = getSnsIdByName('SYSTEM', $row[0], $db);
    $row[1] = getSnsIdByName('NETWORK', $row[1], $db);
    $row[5] = getSnsIdByName('SOURCE', $row[5], $db);
    if (!$row[0] || !$row[1] || !$row[5]) return false;
    $sql[] = "INSERT INTO ASSETS (asset_name, asset_date_created, asset_source) VALUES(':$row[3]:$row[4]', '$row[2]', 'SCAN')";
    $sql[] = "INSERT INTO SYSTEM_ASSETS (system_id, asset_id, system_is_owner) VALUES($row[0], LAST_INSERT_ID(), 1)";
    $sql[] = "INSERT INTO ASSET_ADDRESSES (asset_id,network_id,address_date_created,address_ip,address_port) VALUES(
              LAST_INSERT_ID(), $row[1], '$row[2]', '$row[3]', '$row[4]')";
    $sql[] = "INSERT INTO FINDINGS (source_id,asset_id,finding_status,finding_date_created,finding_date_discovered,finding_data) VALUES(
              $row[5], LAST_INSERT_ID(), 'OPEN', NOW(), '$row[2]', '$row[6]')";
    return $sql;
}

function getSnsIdByName($type, $name_str, &$db){
    if (!in_array($type, array('SYSTEM', 'NETWORK', 'SOURCE')) || ($name_str=='')) {
    	return false;
    }
    switch ($type) {
    	case 'SYSTEM':
    		$sql = "SELECT system_id FROM SYSTEMS WHERE system_nickname = '$name_str'";
    		break;
    	case 'NETWORK':
    		$sql = "SELECT network_id FROM NETWORKS WHERE network_nickname = '$name_str'";
    		break;
    	case 'SOURCE':
    		$sql = "SELECT source_id FROM FINDING_SOURCES WHERE source_nickname = '$name_str'";
    		break;
    
    	default:
    		break;
    }
    $result = $db->sql_query($sql) or die("Query failed: " .$sql."<br>". $db->sql_error());
    $id = $db->sql_fetchrow($result);
    return is_array($id)?array_pop($id):false;
}
?>
