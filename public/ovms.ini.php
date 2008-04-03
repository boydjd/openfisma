<?php
/*
** This is used as a single point of configuration data for scripts
** - both PHP (dblink, finding_upload) and Perl (inject_utils).
*/

    define('DEPLOY_MODE', 'DBG'); //DBG, RLS
    // Database
    // Choose the database to be used
    define('OVMS_DB_TYPE', 'mysql');

    // Database Hostname
    // Hostname of the database server. If you are unsure, 'localhost' works in most cases.
    define('OVMS_DB_HOST', 'localhost');

   //this port is used while connect
    define('OVMS_DB_PORT', '3306');

    // Database Username
    // Your database user account on the host
    define('OVMS_DB_USER', 'sws_live');

    // Database Password
    // Password for your database user account
    define('OVMS_DB_PASS', '123456');

    // Database Name
    // The name of database on the host. The installer will attempt to create the database if not exist
    define('OVMS_DB_NAME', 'testfisma');

    //this pass_c was used to connect database by new user
    define('OVMS_DB_PASS_C', '');

    //this name_c was used to connect database by new user
    define('OVMS_DB_NAME_C', '');

    if(!defined('OVMS_ROOT_PATH')){
        define("DS", DIRECTORY_SEPARATOR);
        define('OVMS_ROOT_PATH', '/opt/reyo/openfisma0403');
        define("OVMS_WEB_PATH", OVMS_ROOT_PATH. DS ."public");
        define("OVMS_WEB_TEMP", OVMS_WEB_PATH. DS ."temp");
        define("OVMS_VENDOR_PATH", OVMS_ROOT_PATH. DS ."vendor");
        define("PDF_FONT_FOLDER", OVMS_VENDOR_PATH. DS ."pdf". DS ."fonts");
        define("OVMS_INJECT_PATH", OVMS_ROOT_PATH. DS ."inject");
        define("OVMS_INCLUDE_PATH", OVMS_ROOT_PATH. DS ."include");
        define('OVMS_LOCAL_PEAR', OVMS_VENDOR_PATH .  DS  . 'Pear');
        define("OVMS_TEMP", ini_get('upload_tmp_dir'));
    }


$OVMS_ROOT = OVMS_ROOT_PATH;
$DB_HOST = OVMS_DB_HOST;
$DB_USER= OVMS_DB_USER;
$DB_PASS= OVMS_DB_PASS;
$DB= OVMS_DB_NAME;

$CUSTOMER_LOGO = "images/customer_logo.jpg";

define("PS", PATH_SEPARATOR);
ini_set('include_path',ini_get('include_path'). PS .OVMS_INCLUDE_PATH . PS . OVMS_LOCAL_PEAR . PS . OVMS_VENDOR_PATH);

?>
