<?PHP

require_once("sql_db.php");
require_once(OVMS_INCLUDE_PATH . DS . "tablenames_def.php");

$dbhost = (isset($DB_HOST)) ? $DB_HOST : "localhost";
$dbuser = (isset($DB_USER)) ? $DB_USER :  "openfisma";
$dbpass = (isset($DB_PASS)) ? $DB_PASS :  "0p3nfism@";
$dbname = (isset($DB)) ? $DB :  "openfisma";

$db = new sql_db($dbhost, $dbuser, $dbpass, $dbname, false);
if(!$db->db_connect_id)
	exit("Could not connect to the database");

if(!get_magic_quotes_gpc()) {
    if(is_array($_GET)) {
        array_walk_recursive($_GET,'addslashes_array','GET');
    }
    if(is_array($_POST)) {
        array_walk_recursive($_POST,'addslashes_array','POST');
    }
    if(is_array($_REQUEST)) {
        array_walk_recursive($_REQUEST,'addslashes_array','REQUEST');
    }
}

function addslashes_array($value,$key,$array_name) {
     switch($array_name) {
         case "GET":
             $_GET[$key] = addslashes($value);
             break;
         case "POST":
             $_POST[$key] = addslashes($value);
             break;
         case "REQUEST":
             $_REQUEST[$key] = addslashes($value);
             break;
      }
}
?>
