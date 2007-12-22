<form action="test_dao.php" method="POST">
<textarea name="q" id="q" cols="80" rows="5"><?=$_REQUEST['q']?></textarea><br />
<input type="submit" id="do_query" name="do_query">
</form>
<?php
if (!isset($_REQUEST['q'])) {
	die();
}

$sql = $_REQUEST['q'];

require_once("..".DIRECTORY_SEPARATOR."ovms.ini.php");
require_once(VENDER_TOOL_PATH._S.'adodblite'._S.'adodb.inc.php');

$db = ADONewConnection(OVMS_DB_TYPE);
//$db->debug = true ;
$db->SetFetchMode(ADODB_FETCH_ASSOC);
$db->Connect("$DB_HOST", "$DB_USER", "$DB_PASS", "$DB");

if ($db->IsConnected()) {
	echo ' CONN_OK ';
}

if ($result = $db->Execute($sql)){
    echo ' QUERY_OK ';
    $allResult = $result->GetAll();
    echo "<table border='1'>";
    echo "<tr name='fields'>";
    foreach ($allResult[0] as $k=>$v) {
    	echo "<th>$k</td>";
    }
    echo "</tr>";
    foreach ($allResult as $n=>$result) {
        echo "<tr name='$n'>";
        foreach ($result as $key=>$value) {
        	echo "<td name='$key'>$value</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}
else {
	echo ' QUERY_FAIL ';
}

$db->Close();
?>