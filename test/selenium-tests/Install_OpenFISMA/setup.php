<?
/**
 * Setup for Install_OpenFISMA Selenium Test Case
 *
 * Lookup the target database credentials. Drop the schema and recreate
 * it. Return the database credentials to the browser in a form that
 * the Selenium script will recognize.
 *
 * @package Test_Selenium_InstallOpenFISMA
 * @author     Mark Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
 */
 
require_once('../../../paths.php');
require_once( APPS . DS . 'basic.php');
require_once( CONFIGS . DS . 'debug.php');
import(LIBS, VENDORS, VENDORS.DS.'Pear');
require_once 'Zend/Registry.php';
require_once 'Zend/Config.php';
require_once 'Zend/Db.php';
require_once 'Zend/Db/Table.php';
require_once ( CONFIGS . DS . 'database.php');

$dbuser = 'ci';
$datasource = Zend_Registry::get('datasource')->default;
$db = Zend_DB::factory($datasource);
$dbuser = $datasource->params->username;
$dbpass = $datasource->params->password;
$dbname = mysqli_real_escape_string($db->getConnection(),$datasource->params->dbname);



$sql = "DROP DATABASE $dbname";
$db->getConnection()->query($sql);
$sql = "CREATE DATABASE $dbname";
$db->getConnection()->query($sql);

?>

<html><head><title></title></head><body>
<div id='dbuser'><?=$dbuser?></div>
<div id='dbpass'><?=$dbpass?></div>
<div id='dbname'><?=$dbname?></div>
</body></html>
