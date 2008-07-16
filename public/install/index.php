<?php
/**
 * /public/install/index.php
 *
 * This is the controller for the OpenFISMA installer application.
 *
 * @author      Unknown
 * @todo        Cleanup and assign author
 * @copyright   (c) 2008 Endeavor Systems, Inc. (http://www.endeavorsystems.com)
 * @license     http://www.openfisma.org/mw/index.php?title=License
 */

define("DS", DIRECTORY_SEPARATOR);

define('OVMS_INSTALL', 1);

define('OVMS_INSTALL_PATH',dirname(__FILE__));
define('OVMS_ROOT_PATH',realpath(dirname(dirname(OVMS_INSTALL_PATH))));
define('OVMS_WEB_PATH',OVMS_ROOT_PATH.DS.'public');
define("OVMS_VENDOR_PATH", OVMS_ROOT_PATH. DS ."vendor");
define("PDF_FONT_FOLDER", OVMS_VENDOR_PATH. DS ."pdf". DS ."fonts");
define("OVMS_INJECT_PATH", OVMS_ROOT_PATH. DS ."inject");
define("OVMS_INCLUDE_PATH", OVMS_ROOT_PATH. DS ."include");
define('OVMS_LOCAL_PEAR', OVMS_VENDOR_PATH .  DS  . 'Pear');

define('OVMS_CONF_PATH',OVMS_ROOT_PATH.DS.'conf');
define('OVMS_SMARTY_PATH',OVMS_ROOT_PATH.DS.'smarty');

define('OVMS_DATABASE','schema.sql');
define('_OKIMG',"<img src='img/yes.gif' width='6' height='12' border='0' alt='OK' /> ");
define('_NGIMG',"<img src='img/no.gif' width='6' height='12' border='0' alt='NO' /> ");
define('REQUEST_SMARTY_VERSION','2.6.14');
define('REQUEST_PHP_VERSION','5');

ini_set('include_path',ini_get('include_path').PATH_SEPARATOR.OVMS_LOCAL_PEAR);

include_once(OVMS_INCLUDE_PATH.'/classload.php');
require_once(OVMS_INSTALL_PATH.'/fun.lib.php');

$title='';
$content='';
$b_next="";
$b_reload="";
$b_back="";
$content = '';
$install_times = 'first';

$myts =& TextSanitizer::getInstance();
if(isset($_POST)) {
    foreach ($_POST as $k=>$v) {
        if (!is_array($v)) {
            $$k = $myts->stripSlashesGPC($v);
        }
    }
}

/* This language stuff needs to be cleaned up */
$language = 'english';
setcookie("install_lang", $language);

if(!empty($_POST['op']))
    $op = $_POST['op'];
elseif(!empty($_GET['op']))
    $op = $_GET['op'];
else
    $op = '';

switch ($op){
    case "":
        $title = 'OpenFISMA Introduction';
        $content = '<table width=\'80%\' style=\'word-break:break-all;\'><tr><td align=\'left\'>
                    OpenFISMA is an open source application designed to reduce the complexity and
                    automate the regulatory requirements of the Federal Information Security
                    Management Act (FISMA) and the National Institute of Science and Technology
                    (NIST) Risk Management Framework (RMF). While many security managers are eager
                    to demonstrate their best practices for incident response, patch management,
                    and configuration management, they are overwhelmed with the reporting and
                    documentation requirements of FISMA. OpenFISMA automates the following:
                    <UL>
                    <LI> FISMA Reporting
                    <LI> Plan of Action and Milestones (POA&M)
                    </UL>

                    <b>SOFTWARE REQUIREMENTS</b><br>
                    <br>
                    OpenFISMA requires the following software. The installation program will check
                    to ensure you have the correct versions of the software required installed on
                    your system.
                    <UL>
                    <LI> Apache version 2+
                    <LI> PHP version 5+
                    <LI> MySQL version 5+
                    <LI> Perl 5+
                    </UL>

                    <b>ADVANCED INSTALLATION INSTRUCTIONS</b><br>
                    <br>
                    Refer to INSTALL file in the application directory.
                    </td></tr></table>';
        $b_next = array('settingcheck');
        break;

    case "settingcheck":
        $title = 'Checking software versions&hellip;';
        $signal = true;
        $content = "<div style=\"text-align:left;padding-left:130px;\">";
        if(version_compare(phpversion(),REQUEST_PHP_VERSION)){
            $content .= '<p>'._OKIMG.'PHP version is '.phpversion().'</p>';
        }else {
            $content .= '<p>'._NGIMG.'PHP version is '.phpversion().'</p>';
            $content .= '<p>Installation of PHP 5 or higher is outside the scope of this installation tutorial.<br>
                            Please refer to <a href="http://www.php.net">PHP site </a>for more information </p>';
            $signal = false;
        }
        if($signal){
            $b_next = array('modcheck');
        }else {
            $b_reload = true;
        }
        break;

    case "modcheck":
        $writeok = array( 0=>OVMS_WEB_PATH."/temp",
        OVMS_ROOT_PATH."/log",
        OVMS_WEB_PATH."/evidence",
        OVMS_WEB_PATH."/ovms.ini.php");
        $title = "Checking file and directory permissions&hellip;";
        $content = "<table align='center'><tr><td align='left'>\n";
        $error = false;
        foreach ($writeok as $wok) {
            if (! is_dir($wok)) {
                if ( file_exists($wok) ) {
                    @chmod("../".$wok, 0666);
                    if (! is_writeable($wok)) {
                        $content .= _NGIMG.'File '.realpath($wok).' is NOT writable.<br />';
                        $error = true;
                    }else{
                        $content .= _OKIMG.'File '.realpath($wok).' is writable.<br />';
                    }
                }
            } else {
                @chmod($wok, 0777);
                if (! is_writeable($wok)) {
                    $content .= _NGIMG.'Directory '.realpath($wok).' is NOT writable.<br />';
                    $error = true;
                }else{
                    $content .= _OKIMG.'Directory '.realpath($wok).' is writable.<br />';
                }
            }
        }
        $content .= "</td></tr></table>\n";
        if(!$error) {
            $content .= '<p>No errors detected.</p>';
            $content .= "<div style=\"text-align:right;\"><input type='checkbox' name='is_second_inst' value='on' />Use existing Database</div>";
            $b_next = array('dbform');
        }else{
            $content .= "<p>In order for the modules included in the package to
                            work correctly, the following files must be writeable
                            by the server. Please change the permission setting
                            for these files. (i.e. 'chmod 666 file_name' and
                            'chmod 777 dir_name' on a UNIX/LINUX server, or
                            check the properties of the file and make sure the
                            read-only flag is not set on a Windows server)</p>";
            $b_reload = true;
        }
        break;

    case "dbform":
        $title = "Configure Database Settings";
        $b_next = array('dbconfirm');
        $config = file_get_contents(OVMS_WEB_PATH . DS.'ovms.ini.php');
        $isTheFirstTime = preg_match('/OVMS_INSTALL/', $config);
        unset($config);
        $sm = new setting_manager('false');
        if(!$isTheFirstTime) {
            @include(OVMS_WEB_PATH . DS . 'ovms.ini.php');
            $sm->readConstant();
        }
        if(isset($_POST['is_second_inst'])&& $_POST['is_second_inst']==true) {
            $content .= "<input type='hidden' name='is_second' value='true' />";
            $install_times = 'second';
            $b_next = array('dbconfirm2');
        }
        $content .= $sm->editform($install_times);
        break;

    case "dbconfirm2":
        $install_times = 'second';

    case "dbconfirm":
        if($install_times == 'second'){
            require_once(OVMS_INCLUDE_PATH . '/sws.adodb.php');
        }
        $title = 'Verify database connection';
        $sm = new setting_manager('post');
        $contentoferr = '';
        $contentoferr = $sm->checkData($install_times);
        if (!empty($contentoferr)) {
            $content .= $contentoferr;
        }
        else{
            $content .= $sm->confirmForm('');
            $b_next = array('dbsave');
        }
        if($install_times == 'second'){
            $content .= try_db_connect($sm);
        }
        if(empty($contentoferr)){
            if( $install_times == 'first') {
                $b_next = array('dbsave');
            }else{
                $b_next = array('dbsave2');
            }
        }
        $b_back = array('');
        break;
    case "dbsave2":
        $install_times = 'second';
    case "dbsave":
        $title = 'Saving configuration data&hellip;';
        $mm =&new mainfile_manager(OVMS_WEB_PATH.'/ovms.ini.php');
        $ret = $mm->copyDistFile(OVMS_CONF_PATH.'/mainfile.dist.php');
        if($ret){
            $sm = &new setting_manager('post');
            $ret = $sm->saveToFile($mm);
            if(!$ret){
                break;
            }
        }
        $outputarray = explode('<br />',$mm->report());
        $typeofrewrite = array('type','','port');
        foreach($outputarray as $key =>$element){
            $element = ereg_replace('Constant[[:space:]]<b>OVMS_DB_(TYPE|HOST|PORT)</b>[[:space:]]written[[:space:]]to','Database connection '.current($typeofrewrite).'successfully configured to',$element);
            next($typeofrewrite);
            $content .= $element.'<br />';
        }
        if($ret){
            $content .= '<p>Configuration data has been saved successfully to ovms.ini.php.</p>';
            if($install_times == 'second') {
                $b_next = array('complete');
            }else {
                $b_next = array('initial');
            }
        }else{
            $content .= 'Please check the file permission and try again.';
            $b_reload = true;
        }
        break;

    case "initial":
        require_once(OVMS_WEB_PATH.'/ovms.ini.php');
        $content = "<table align=\"center\">\n";
        $content .= "<tr><td align='center'>";
        $content .= "<table align=\"center\">\n";
        $confirm_idx = array('host','uname','name_c','dbname');
        $sm = &new setting_manager('const');
        foreach($confirm_idx as $tuple) {
            $content .= "<tr><td>".$sm->getConfigName($tuple)
            .":&nbsp;&nbsp;</td><td><b>"
            .$sm->getConfigVal($tuple)."</b></td></tr>\n";
        }
        $content .= "</table><br />\n";
        $content .= "</td></tr><tr><td align=\"center\">";
        $content .= "If the above setting is correct, press the button below to continue.<br /><br />\n";
        $content .= "</td></tr></table>\n";
        $b_next = array('checkDB');
        $b_back = array('dbform');
        $b_reload = true;
        $title = 'Confirm Database Settings';
        break;

    case "checkDB":
        $title = 'Initialize Database';
        include_once(OVMS_WEB_PATH. DS. 'ovms.ini.php');
        include_once(OVMS_INCLUDE_PATH. DS. 'sws.adodb.php');
        $content = "<div style=\"text-align:left;padding-left:130px;\">";
        $dbname ='';
        $name_c ='';
        $stage = '';
        try{
            $sm = & new setting_manager('const');
            $db = & NewADOConnection($sm->getConfigVal('type'));
            $db->setFetchMode(ADODB_FETCH_NUM);
            $db->port = $sm->getConfigVal('port');
            $db->createdatabase = true ; //create db if not exist
            $dbname = $sm->getConfigVal('dbname');
            $name_c = $sm->getConfigVal('name_c');
            $passwd_c = $sm->getConfigVal('pass_c');
            $stage =  "Create database $dbname<br />";
            $uname=$sm->getConfigVal('uname');
            $upass=$sm->getConfigVal('upass');
            $db->Connect($sm->getConfigVal('host'),$uname,$upass,$dbname);
            $db->SelectDB($dbname);
            $content .= _OKIMG . $stage;
            $stage = "Create account $name_c and grant it all privilege of $dbname<br />";
            if($uname!=$name_c){
                $db->query("GRANT ALL PRIVILEGES ON `$dbname` . * TO '$name_c'@'localhost' IDENTIFIED BY '$passwd_c' WITH GRANT OPTION");
                $content .= _OKIMG . $stage;
            }
            $stage = "Create tables.<br />"  ;
            $schema_file = OVMS_INSTALL_PATH . DS. "db". DS. OVMS_DATABASE;
            if(file_exists($schema_file)){
                $sqlArray = explode(';', file_get_contents($schema_file) );
                foreach ($sqlArray as $sql) {
                    $sql = trim($sql);
                    if(!empty($sql)) {
                        $db->query($sql);
                    }
                }
                $content .= _OKIMG . $stage;
            }
            $dataFile = array(OVMS_INSTALL_PATH.DS.'db'.DS.'init_data.sql');
            if(!import_data($db,$dataFile)){
                throw new Exception('Initializing database failed');
            }
            $content .= "</div>";
        }catch(Exception $e) {
            $content .= _NGIMG .$stage;
            $content .= "<font color='red'> ERROR: </font>".$e->getMessage();
            $b_back=array('dbform');
            break 1;
        }
        $sm = & new setting_manager('const');
        $mm = &new mainfile_manager( OVMS_WEB_PATH.DS.'ovms.ini.php');
        $need_clean = $sm->getConfigVal('name_c');
        if(!empty( $need_clean ) ) {
            if(!$sm->clearRootAccount($mm)) {
                $content .= _INST_OK_CLEAR_ROOT . $mm->report() .'<br/>';
            }
        }
        $b_next=array('complete');
        break;

    case "complete": //OK
    $title =  'Installation is complete';
    $content = "<table width='60%' align='center'><tr><td align='left'>
                <u><b>Final steps</b></u>
                <p>Make sure to remove this installation directory (/public/install) from the root of
                your application.</p>
                <u><b>Application site</b></u>
                <p>Click <a href='/index.php'>HERE</a> to see the home page of your site.</p>
                <u><b>Support</b></u>
                <p>Visit <a href='http://www.openfisma.org/' target='_blank'>OpenFISMA.org</a></p>
                </td></tr></table>\n";
    break;

    default:
        die ('unknown installer action');
        break;
}
include(OVMS_INSTALL_PATH .'/install_frm.php');
?>
