<?php
/* vim: set tabstop=4 shiftwidth=4 expandtab: */

define("DS", DIRECTORY_SEPARATOR);

define('OVMS_INSTALL', 1);

define('OVMS_INSTALL_PATH',dirname(__FILE__));
define('OVMS_ROOT_PATH',realpath(OVMS_INSTALL_PATH.'/../..'));
define('OVMS_WEB_PATH',OVMS_ROOT_PATH.'/public');
define('OVMS_CONF_PATH',OVMS_ROOT_PATH.'/conf');
define('OVMS_LIB_PATH',OVMS_ROOT_PATH.'/lib');
define('OVMS_INCL_PATH',OVMS_ROOT_PATH.'/include');
define('OVMS_SMARTY_PATH',OVMS_ROOT_PATH.'/smarty');
define("OVMS_VENDOR_PATH", OVMS_ROOT_PATH."/vendor");
define("LOCAL_PEAR_PATH", OVMS_VENDOR_PATH."/Pear");

define('OVMS_DATABASE','schema.sql');
define('_OKIMG',"<img src='img/yes.gif' width='6' height='12' border='0' alt='OK' /> ");
define('_NGIMG',"<img src='img/no.gif' width='6' height='12' border='0' alt='NO' /> ");
define('REQUEST_SMARTY_VERSION','2.6.14');
define('REQUEST_PHP_VERSION','5');

$ins = (DIRECTORY_SEPARATOR == '/')?':':';';
ini_set('include_path',ini_get('include_path').$ins.OVMS_PEAR);

include_once(OVMS_INCL_PATH.'/classload.php');
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

$language = 'english';
if ( !empty($_POST['lang']) ) {
    $language = $_POST['lang'];
} else {
    if (isset($_COOKIE['install_lang'])) {
        $language = $_COOKIE['install_lang'];
    } else {
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $accept_langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            $language_array = array('en' => 'english', 'ja' => 'japanese', 'fr' => 'french', 'de' => 'german', 'nl' => 'dutch', 'es' => 'spanish', 'tw' => 'tchinese', 'cn' => 'schinese', 'ro' => 'romanian');
            foreach ($accept_langs as $al) {
                $al = strtolower($al);
                $al_len = strlen($al);
                if ($al_len > 2) {
                    if (preg_match("/([a-z]{2});q=[0-9.]+$/", $al, $al_match)) {
                        $al = $al_match[1];
                    } else {
                        continue;
                    }
                }
                if (isset($language_array[$al])) {
                    $language = $language_array[$al];
                    break;
                }
            }
        }
    }
}

if ( file_exists(OVMS_INSTALL_PATH.'/language/'.$language.'/install.php') ) {
    include_once OVMS_INSTALL_PATH."/language/".$language."/install.php";
} elseif ( file_exists(OVMS_INSTALL_PATH.'/language/english/install.php') ) {
    include_once OVMS_INSTALL_PATH.'/language/english/install.php';
    $language = 'english';
} else {
    echo 'no language file.';
    exit();
}

setcookie("install_lang", $language);
if(!empty($_POST['op']))
$op = $_POST['op'];
elseif(!empty($_GET['op']))
$op = $_GET['op'];
else
$op = '';

$install_tpl = OVMS_INSTALL_PATH .'/install_frm.php';

switch ($op){
    case "":
        $title = _INST_INIT_L0;
        if (!defined('_INST_LS_L1')) {
            define('_INST_LS_L1', 'Choose language to be used for the installation process');
        }
        $content = "<p>"._INST_LS_L1."</p>"
        ."<select name='lang'>"
        ."<option value='english'>english</option> ";
        $content .= "</select>";
        $b_next = array('start', _INST_INIT_L1);
        break;

    case "start":
        $title = _INST_INIT_L1;
        $content = "<table width='80%' style=\"word-break:break-all;\"><tr><td align='left'>\n";
        include(OVMS_INSTALL_PATH.'/language/'.$language.'/welcome.php');
        $content .= "</td></tr></table>\n";
        $b_next = array('settingcheck', _INST_DW_L9);
        break;

    case "settingcheck":
        $title = _INST_SET_L1;
        $signal = true;
        $content = "<div style=\"text-align:left;padding-left:130px;\">";
        if(version_compare(phpversion(),REQUEST_PHP_VERSION)){
            $content .= sprintf('<p>'._OKIMG._INST_SET_L2,phpversion());
        }else {
            $content .= sprintf('<p>'._NGIMG._INST_SET_L2,phpversion());
            $content .= _INST_SET_L3;
            $signal = false;
        }
        $version = '';
        try {
            if(!@include_once('Smarty.class.php')){
                @include_once(OVMS_ROOT_PATH.'/vendor/smarty/Smarty.class.php');
            }
            $mysmarty = new Smarty();
            $signalofsmarty = true;
            $version = $mysmarty->_version;
            if(version_compare($version,REQUEST_SMARTY_VERSION,"<")){
                $signalofsmarty = false;
                throw new Exception($mysmarty->_version);
            }
            if($signalofsmarty){
                $content .= sprintf(_OKIMG._INST_SET_L4.SMARTY_DIR ,$version);
            }
        }catch(Exception $e){
            $content .= sprintf(_NGIMG._INST_SET_L6 , $version);
            $content .= _INST_SET_L8.$e->getMessage();
            $signal = false;
        }
        ini_set('include_path', LOCAL_PEAR_PATH.PATH_SEPARATOR.ini_get('include_path'));
        try {
            @include_once('Spreadsheet/Excel/Writer.php');
            $myspread = new Spreadsheet_Excel_Writer();
            $content .= "<p>"._OKIMG._INST_SET_L12."</p>";
        }catch(Exception $e){
            $signal = false;
            $content .= "<p>"._NGIMG._INST_SET_L13.$e->getMessage()."</p>";
        }
        try {
            @include_once('OLE.php');
            $myole = new OLE();
            $content .= "<p>"._OKIMG._INST_SET_L11."</p>";
        }catch(Exception $e){
            $signal = false;
            $content .= "<p>"._NGIMG._INST_SET_L14.$e->getMessage()."</p>";
        }
        $content .= "</div>";
        if($signal){
            $b_next = array('modcheck', _INST_DW_L9);
        }else {
            $b_reload = true;
        }
        break;

    case "modcheck":
        $writeok = array(0=>OVMS_SMARTY_PATH."/templates_c",
        1=>OVMS_WEB_PATH."/temp",
        2=>OVMS_ROOT_PATH."/log",
        3=>OVMS_WEB_PATH."/ovms.ini.php");
        $title = _INST_DW_L1;
        $content = "<table align='center'><tr><td align='left'>\n";
        $error = false;
        foreach ($writeok as $wok) {
            if (! is_dir($wok)) {
                if ( file_exists($wok) ) {
                    @chmod("../".$wok, 0666);
                    if (! is_writeable($wok)) {
                        $content .= _NGIMG.sprintf(_INST_DW_L4,realpath($wok))."<br />";
                        $error = true;
                    }else{
                        $content .= _OKIMG.sprintf(_INST_DW_L3,realpath($wok))."<br />";
                    }
                }
            } else {
                @chmod($wok, 0777);
                if (! is_writeable($wok)) {
                    $content .= _NGIMG.sprintf(_INST_DW_L5,realpath($wok))."<br />";
                    $error = true;
                }else{
                    $content .= _OKIMG.sprintf(_INST_DW_L6, realpath($wok))."<br />";
                }
            }
        }
        $content .= "</td></tr></table>\n";
        if(!$error) {
            $content .= "<p>"._INST_DW_L7."</p>";
            $content .= "<div style=\"text-align:right;\"><input type='checkbox' name='is_second_inst' value='on' />"._INST_DW_L10."</div>";
            $b_next = array('dbform', _INST_CI_L1 );
        }else{
            $content .= "<p>"._INST_DW_L8."</p>";
            $b_reload = true;
        }
        break;

    case "dbform":
        $title = _INST_CI_L1;
        $b_next = array('dbconfirm',_INST_CD_L1);
        $config = file_get_contents(OVMS_WEB_PATH . '/ovms.ini.php');
        $isTheFirstTime = preg_match('/OVMS_INSTALL/', $config);
        unset($config);
        $sm = new setting_manager('false');
        if(!$isTheFirstTime){
            include(OVMS_WEB_PATH . '/ovms.ini.php');
            $sm->readConstant();
        }
        if(isset($_POST['is_second_inst'])&& $_POST['is_second_inst']==true ){
            $content .= "<input type='hidden' name='is_second' value='true' />";
            $install_times = 'second';
            $b_next = array('dbconfirm2',_INST_CD_L1);
        }
        $content .= $sm->editform($install_times);
        break;

    case "dbconfirm2":
        $install_times = 'second';

    case "dbconfirm":
        if($install_times == 'second'){
            require_once(OVMS_INCL_PATH . '/sws.adodb.php');
            $title = _INST_CI_L2;
        }else{
            $title = _INST_CI_L1;
        }
        $sm = new setting_manager('post');
        $contentoferr = '';
        $contentoferr = $sm->checkData($install_times);
        if (!empty($contentoferr)) {
            $content .= $contentoferr;
        }
        else{
            $content .= $sm->confirmForm('');
            $b_next = array('dbsave',_INST_WC_L1 );
        }
        if($install_times == 'second'){
            $content .= try_db_connect($sm);
        }
        if(empty($contentoferr)){
            if( $install_times == 'first') {
                $b_next = array('dbsave',_INST_WC_L1 );
            }else{
                $b_next = array('dbsave2',_INST_WC_L1 );
            }
        }
        $b_back = array('', _INST_CI_L1);
        break;
    case "dbsave2":
        $install_times = 'second';
    case "dbsave":
        $title = _INST_WC_L2;
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
            $content .= "<p>"._INST_WC_L4."</p>\n";
            if($install_times == 'second') {
                $b_next = array('complete', _INST_WC_L5);
            }else {
                $b_next = array('initial', _INST_WC_L5);
            }
        }else{
            var_dump($sm->settingNames);
            $content .=_INST_WC_L8;
            $b_reload = true;
        }
        break;

    case "initial":
        include_once(OVMS_WEB_PATH.'/ovms.ini.php');
        $content = "<table align=\"center\">\n";
        $content .= "<tr><td align='center'>";
        $content .= "<table align=\"center\">\n";
        $confirm_idx = array('host','uname','name_c','dbname');
        $sm = &new setting_manager('const','');
        foreach($confirm_idx as $tuple) {
            $content .= "<tr><td>".$sm->getConfigName($tuple)
            .":&nbsp;&nbsp;</td><td><b>"
            .$sm->getConfigVal($tuple)."</b></td></tr>\n";
        }
        $content .= "</table><br />\n";
        $content .= "</td></tr><tr><td align=\"center\">";
        $content .= _INST_CMD_L2."<br /><br />\n";
        $content .= "</td></tr></table>\n";
        $b_next = array('checkDB', _INST_CMD_L4);
        $b_back = array('dbform', _INST_CMD_L3);
        $b_reload = true;
        $title = _INST_CMD_L1;
        break;

    case "checkDB":
        $title = _INST_ID_L1;
        include_once(OVMS_WEB_PATH. '/ovms.ini.php');
        include_once(OVMS_INCL_PATH. '/sws.adodb.php');
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
            $stage =  sprintf(_INST_ID_L2 . "<br />", $dbname );
            $db->Connect($sm->getConfigVal('host'),$sm->getConfigVal('uname'),$sm->getConfigVal('upass'),$dbname);
            $db->SelectDB($sm->getConfigVal('dbname'));
            $content .= _OKIMG . $stage;
            $stage = sprintf(_INST_ID_L4 . "<br />", $name_c, $dbname) ;
            $db->query("GRANT ALL PRIVILEGES ON `$dbname` . * TO '$name_c'@'localhost'
                    IDENTIFIED BY '$passwd_c' WITH GRANT OPTION");
            $content .= _OKIMG . $stage;
            $stage = _INST_ID_L5 . "<br />"  ;
            $schema_file = OVMS_INSTALL_PATH . "/db/".OVMS_DATABASE;
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
            $dataFile = array(OVMS_INSTALL_PATH.'/db/init_data.sql');
            if(!import_data($db,$dataFile)){
                throw new Exception('Initializing databaase failed');
            }
            $content .= "</div>";
        }catch(Exception $e) {
            $content .= _NGIMG .$stage;
            $content .= "<font color='red'> ERROR: </font>".$e->getMessage();
            $b_back=array('dbform',_INST_CI_L1);
            break 1;
        }
        $sm = & new setting_manager('const');
        $mm = &new mainfile_manager( OVMS_WEB_PATH.'/ovms.ini.php');
        $need_clean = $sm->getConfigVal('name_c');
        if(!empty( $need_clean ) ) {
            if(!$sm->clearRootAccount($mm)) {
                $content .= _INST_OK_CLEAR_ROOT . $mm->report() .'<br/>';
            }
        }
        $b_next=array('complete',_INST_OK_L2);
        break;

    case "complete": //OK
    $title =  _INST_OK_L1;
    $content = "<table width='60%' align='center'><tr><td align='left'>\n";
    include OVMS_INSTALL_PATH.'/language/'.$language.'/finish.php';
    $content .= "</td></tr></table>\n";
    break;

    default:
        break;
}
include $install_tpl;
?>
