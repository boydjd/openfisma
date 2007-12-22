<?php
//whole define
define("_INST_WHOLE_1",'Next');
define("_INST_WHOLE_2","Back");
define("_INST_WHOLE_3","OK!");
define("_INST_WHOLE_4","Failed!");
define("_INST_WHOLE_5","Success!");
define("_INST_WHOLE_6","Reload");

//database info  It define all the information of database
define("_INST_DB_L1","Database Hostname");
define("_INST_DB_L2","Database Service Port");
define("_INST_DB_L3","Database Username");
define("_INST_DB_L4","Database Password");
define("_INST_DB_L5","Database Name");
define("_INST_DB_L6","Create User for new Database");
define("_INST_DB_L7","Create New User Password");
define("_INST_DB_L8","Confirm Password");
define("_INST_DB_L9","Please enter %s");
define("_INST_DB_L10","Database");
define("_INST_DB_L11","Choose the database to be used");
define("_INST_DB_L12","Input password mismatch!");
define("_INST_DB_L13","Installation physical path");

define("_INSTALL_L66","Hosting database");
define("_INSTALL_L67","Hostname of the database server. If you are unsure, 'localhost' works in most cases.");
define("_INSTALL_L65","Your database user account on the host");
define("_INSTALL_L64","The name of database on the host. The installer will attempt to create the database if not exist");
define("_INSTALL_L68","Password for your database user account");


//Page 1 init
define("_INST_INIT_L0","Welcome to the Install Wizard for OpenFISMA");
define("_INST_INIT_L1","OpenFISMA Introduction");

//Page 2 setting check
define("_INST_SET_L1","Setting Check");
define("_INST_SET_L2","Currently PHP version is %s</p>");
define("_INST_SET_L3","<pre>
           Installation of PHP 5 or higher is outside the scope of this installation tutorial. 
           Please refer to 
           <a href=\"http://www.php.net\">PHP site </a>for more information
</pre>");
define("_INST_SET_L4","Current Smarty version is %s, Smarty installed path is:<br />&nbsp;&nbsp;");
define("_INST_SET_L6"," Smarty not available or Smarty version is %s, required version is 2.6.18</p>");

define("_INST_SET_L8"," <pre>
          To install the Smarty
          a: Download and install Smarty(version 2.6.18)<a href=\"http://smarty.php.net/do_download.php?download_file=Smarty-2.6.18.tar.gz\"> please refer to </a>
          b: Copy the lib into vendor directory under the OpenFISMA root
          </pre> Now your smarty version is");
define("_INST_SET_L9","Back to check if Smarty have been installed.");
define("_INST_SET_L10","Smarty version is %s,require version is %s</p>");
define("_INST_SET_L11","Class OLE exists!");
define("_INST_SET_L12","Class spreadsheet exists!");
define("_INST_SET_L13","Class spreadsheet not exists!");
define("_INST_SET_L14","Class OLE not exists!");



//Page 3 dirwritable&&file
define("_INST_DW_L1","Checking file and directory permissions..");
define("_INST_DW_L2","Directory %s is writable.");
define("_INST_DW_L3","File %s is writable.");
define("_INST_DW_L4","File %s is NOT writable.");
define("_INST_DW_L5","Directory %s is NOT writable.");
define("_INST_DW_L6","Directory %s is writable.");
define("_INST_DW_L7","No errors detected.");
define("_INST_DW_L8","In order for the modules included in the package to work correctly, the following files must be writeable by the server. Please change the permission setting for these files. (i.e. 'chmod 666 file_name' and 'chmod 777 dir_name' on a UNIX/LINUX server, or check the properties of the file and make sure the read-only flag is not set on a Windows server)");
define("_INST_DW_L9","Please click Next to Continue");
define("_INST_DW_L10","Use existing Database");

//Page 4 add Database information
define("_INST_CI_L1","General settings");
define("_INST_CI_L2","Verify database connection");

//Page 6 write to configuration
define("_INST_WC_L1","Save Settings");
define("_INST_WC_L2","Saving configuration data..");
define("_INST_WC_L3","Constant %s written to %s.");
define("_INST_WC_L4","Configuration data has been saved successfully to mainfile.php.");
define("_INST_WC_L5","Check Database");
define("_INST_WC_L6","File %s overwritten by %s.");
define("_INST_WC_L7","Could not write to file %s.");
define("_INST_WC_L8","Please check the file permission and try again.");
define("_INST_WC_L9","Failed writing constant %s.");

//Page confirm database
define("_INST_CMD_L1","Confirm Database");
define("_INST_CMD_L2","If the above setting is correct, press the button below to continue.");
define("_INST_CMD_L3","re-add data");
define("_INST_CMD_L4","ready to initial database");

//Page 5 check Database
define("_INST_CD_L1","Connection Test Configuration");
define("_INST_CD_L2","Confirm database settings");
define("_INST_CD_L3","Password do not match ");
define("_INST_CD_L4","Please confirm the following submitted data:");

//page to initial database
define("_INST_ID_L1","Initial Database");
define("_INST_ID_L2",'Create database %s');
//define("_INST_ID_L3",'RESEVED');
define("_INST_ID_L4",'Create account %s and grant all privilege of %s to it');
define("_INST_ID_L5",'Create tables.');
define("_INST_ID_L6",'Connect to ');
define("_INST_ID_L7",'User ');
define("_INST_ID_L8",'already exists. Now set its password.');

/*Magic Quote
define("_INST_MQ_L1","check magic quote");
define("_INST_MQ_L2","Copy setting file Fail!';");
define("_INST_MQ_L3",": magic_quotes_runtime =");
define("_INST_MQ_L4",": magic_quotes_gpc =");
define("_INST_MQ_L5","Magic Quote Setting");
define("_INST_MQ_L6","configure magic quote setting");
define("_INST_MQ_L7","Current setting");
define("_INST_MQ_L8","Copy setting file");
*/

//Install complete
define("_INST_OK_L1","install_complete");
define("_INST_OK_L2","Now,please directly go to index page!");

define('_INSTALL_CHARSET','ISO-8859-1');

//function testconndatabase
define('_INST_TEST_CONN_1','Database connection test successes. </p>');
define('_INST_TEST_CONN_2','Database connection test fails. </p>');


