<?
/*
** This is used as a single point of configuration data for scripts
** - both PHP (dblink, finding_upload) and Perl (inject_utils).
*/

// This determines whether errors should be printed to the screen as part of 
// the output or if they should be hidden from the user.
// On - Display Errors on the web page
// Off - Suppress Errors but still log them 
ini_set('display_errors', 'Off');

// The error_reporting() function sets the error_reporting directive at runtime. 
// PHP has many levels of errors, using this function sets that level for the 
// duration (runtime) of your script. 
error_reporting(E_ALL);

define("_S", DIRECTORY_SEPARATOR);

if (_S == '/'){ // unix, linux  
    define("_INC_S", ":");
}
elseif (_S == '\\') { // window$   
    define("_INC_S", ";");
}

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
    define('OVMS_DB_USER', 'openfisma');

    // Database Password
    // Password for your database user account
    define('OVMS_DB_PASS', 'openfisma');

    // Database Name
    // The name of database on the host. The installer will attempt to create the database if not exist
    define('OVMS_DB_NAME', 'ovms_test');

    //this pass_c was used to connect database by new user
    define('OVMS_DB_PASS_C', 'openfisma');

    //this name_c was used to connect database by new user
    define('OVMS_DB_NAME_C', 'openfisma');

    if(!defined('OVMS_ROOT_PATH')){
        define('OVMS_ROOT_PATH', '/opt/openfisma/trunk');
    }

define("OVMS_WEB_ROOT", OVMS_ROOT_PATH._S."public");
define("OVMS_WEB_TEMP", OVMS_WEB_ROOT._S."temp");
define("VENDER_TOOL_PATH", OVMS_ROOT_PATH._S."vendor");
define("PDF_FONT_FOLDER", VENDER_TOOL_PATH._S."pdf"._S."fonts");
define("OVMS_INJECT_PATH", OVMS_ROOT_PATH._S."inject");
define("OVMS_INCLUDE_PATH", OVMS_ROOT_PATH._S."include");
define("OVMS_PEAR_PATH", VENDER_TOOL_PATH._S."Pear");
define("OVMS_TEMP", ini_get('upload_tmp_dir'));

$OVMS_ROOT = OVMS_ROOT_PATH;
$DB_HOST = OVMS_DB_HOST;
$DB_USER= OVMS_DB_USER;
$DB_PASS= OVMS_DB_PASS;
$DB= OVMS_DB_NAME;

$CUSTOMER_URL  = "https://ovms.ed.gov/index.php";
$CUSTOMER_LOGO = "images/customer_logo.png";
$LOGIN_WARNING = "This is a United States Government Computer system operated and maintained by the U.S. Department of Education, Federal Student Aid which encourages its use by authorized staff, auditors, and contractors. Activity on this system is subject to monitoring in the course of systems administration and to protect the system from unauthorized use. Users are further advised that they have no expectation of privacy while using this system or in any material on this system. Unauthorized use of this system is a violation of Federal Law and will be punished with fines or imprisonment (P.L. 99-474) Anyone using this system expressly consents to such monitoring and acknowledges that unauthorized use may be reported to the proper authorities.";

ini_set('include_path',ini_get('include_path')._INC_S.OVMS_INCLUDE_PATH._INC_S.OVMS_PEAR_PATH);

//All this below was added from .../conf/ovms.ini

define("PAGE_SIZE",'5');
define("PAGE_INTERVALS",'5');

define("SESSION_TIMEOUT", '300');
define("SESSION_PATH",'/');
define("SESSION_DOMAIN",'localhost.tld');
define("SESSION_EXPIRATION",'86400');
define("SESSION_SECURE_ONLY",'1');

define("CIPHER_HASH",' SHA1');

define("BUFFER_OUTPUT", '1');

//Above was added by david from .../conf/ovms.ini
?>
