<?PHP

require_once("ovms.ini.php");
require_once("sql_db.php");

$dbhost = (isset($DB_HOST)) ? $DB_HOST : "localhost";
$dbuser = (isset($DB_USER)) ? $DB_USER :  "ovms";
$dbpass = (isset($DB_PASS)) ? $DB_PASS :  "ovms";
$dbname = (isset($DB)) ? $DB :  "ovms";

require_once(OVMS_INCLUDE_PATH . DS . "tablenames_def.php");

$db = new sql_db($dbhost, $dbuser, $dbpass, $dbname, false);
if(!$db->db_connect_id)
	exit("Could not connect to the database");


if(!get_magic_quotes_gpc()) {
	if(is_array($_GET)) {
       foreach($_GET as $key=>$value) {
           $_GET[$key] = addslashes($value);
       }
	}
	if(is_array($_POST)) {
       foreach($_POST as $key=>$value) {
           $_POST[$key] = addslashes($value);
       }
	}
	if(is_array($_REQUEST)) {
       foreach($_REQUEST as $key=>$value) {
           $_REQUEST[$key] = addslashes($value);
       }
	}
}
?>
