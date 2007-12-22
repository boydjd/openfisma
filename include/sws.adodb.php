<?php

if( !defined('OVMS_ROOT_PATH') ) {
    exit();
}

define('DB_TYPE','ADODB_LITE');

if('ADODB_LITE' == constant('DB_TYPE')){

define('ADODB_LITE_DIR',OVMS_ROOT_PATH . '/vendor/adodblite/');
require_once(ADODB_LITE_DIR.'adodb-exceptions.inc.php'); //this is a must
//include(ADODB_LITE_DIR."adodb-errorhandler.inc.php"); 
require_once(ADODB_LITE_DIR."adodb.inc.php");
if( defined('DEBUG') ){
include (ADODB_LITE_DIR."query_debug_console/query_debug_console.php");
}

}

if('ADODB' == constant('DB_TYPE')) {
define('ADODB_DIR',OVMS_DIR.'database/');
require_once(ADODB_DIR.'adodb-exceptions.inc.php'); 
require_once(ADODB_DIR."adodb.inc.php");
}


?>
