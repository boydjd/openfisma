<?PHP
// no-cache ? forces caches to submit the request to the origin server for validation before releasing a cached copy, every time. This is useful to assure that authentication is respected.
// must-revalidate ? tells caches that they must obey any freshness information you give them about a representation. By specifying this header, you�re telling the cache that you want it to strictly follow your rules.
header("Cache-Control: no-cache, must-revalidate");

// required for all pages, after user login is verified function displayloginfor checks all user security functions, gets the users first/last name and customer log as well as loads ovms.ini.php
require_once("config.php");
require_once("dblink.php");
require_once("finding.class.php");
require_once("findingDBManager.php");
require_once("upload_utils.php");

// set the page name
$smarty->assign('pageName', 'Finding Upload');

// session_start() creates a session or resumes the current one based on the current session id that's being passed via a request, such as GET, POST, or a cookie.
// If you want to use a named session, you must call session_name() before calling session_start().
session_start();

// creates a new user object from the user class
$user = new User($db);

// validates that the user is logged in properly, if not redirects to the login page.
verify_login($user, $smarty);

// get user right for this screen
$upload_right = $user->checkRightByFunction("finding", "upload");

// assign user right to smarty template
$smarty->assign('upload_right', $upload_right);

// Execute an external program and display the output
// string system  ( string $command  [, int &$return_var  ] )
// check to see if the system has perl installed and is in current path
$rcode = system('perl -e "print 0"');
if ($rcode != 0) {
	$smarty->assign('err_msg', "System could not locate PERL in the server's path. Please install PERL and try again.");
	$smarty->display('finding_upload_status.tpl');
	return ;
}

/*
** If this is called for submission, act on the upload file.
** Otherwise display the submission form.
*/

$FORM_FILE_NAME = 'upload_file';

if (isset($_POST['submitted'])) {

	/*
	** If error in file upload
	*/

	if (get_upload_status($FORM_FILE_NAME) != UPLOAD_ERR_OK) {

		/*
		** Report error, return
		*/

		$upload_error_msg = get_upload_error_message($FORM_FILE_NAME);
		$smarty->assign('err_msg', 'File upload error: ' . $upload_error_msg);
		$smarty->display('finding_upload_status.tpl');

		return;

	} // if upload error

	/*
	** Act on uploaded file
	*/

	$temp_filename = get_upload_temp_filename($FORM_FILE_NAME);
	$temp_basename = basename($temp_filename);

	/*
	** Copy file to ovms root for injection
	*/

	if(!isset($OVMS_ROOT)) {

		/*
		** $OVMS_ROOT needs to be set to the uploads/ parent folder in ovms.ini.php
		*/

		$smarty->assign('err_msg', 'Configuration error: OVMS_ROOT not set');
		$smarty->display('finding_upload_status.tpl');

		return;

	} // if OVMS_ROOT

	putenv("OVMS_ROOT=$OVMS_ROOT");
	$DEST_SUBFOLDER = OVMS_TEMP;

	// grab the file and move to the upload direcoty
	$working_file = $DEST_SUBFOLDER.DS.'ovms'.$temp_basename;
	$move_status  = move_upload_file($FORM_FILE_NAME, $working_file);

	if (!$move_status) {

		$smarty->assign('err_msg', 'File upload error: unable to move uploaded file to ' . $DEST_SUBFOLDER);
		$smarty->display('finding_upload_status.tpl');

		return;
	}

	# convert the file from dos to unix formatting
//	$cmd = "dos2unix $working_file";
//	exec($cmd);
    dos2unix($working_file, $working_file);

//print "$cmd<br>";


	/*
	** Get system, source and network DB IDs from form
	*/
	$system_id  = $_POST['system'];
	$source_id  = $_POST['source'];
	$network_id = $_POST['network'];
	$plugin_nm  = $_POST['plugin'];

	/*
	** Get code module version of plugin name.
	** This can be database-driven if we add a column to the
	** PLUGINS table that maps the lowercase module name.
	*/
	$module_for = array(
		'BLSCR'        => 'blscr',
		'NVD Products' => 'product',
		'NVD List'     => 'nvd',
		'Nessus'       => 'nessus',
		'AppDetective' => 'appdetective',
		'ShadowScan'   => 'shadowscan',
		'ManualList'   => 'finding',
		'Inventory'    => 'inventory',
		);

	// test the plugin name
	if (!array_key_exists($plugin_nm, $module_for)) {

		$smarty->assign('err_msg', "Unrecognized plugin type '$plugin_nm'");
		$smarty->display('finding_upload_status.tpl');

		return;

	} // if no plugin recognized

	// assign the plugin name
	$plugin_module = $module_for[$plugin_nm];

	/*
	** Make sure this file is good for the plugin.
	*/

	if(!is_valid_data($working_file, $plugin_module)) {

		$smarty->assign('err_msg', "Unrecognized file input for plugin '$plugin_nm'");
		$smarty->display('finding_upload_status.tpl');

		return;
	}


	/*
	** Prepend upload attributes line to input.
	** This will be ignored by translator plugin but passed along to
	** the injector.
	*/

	$upload_attrs      = "upload<>$source_id<>$system_id<>$network_id\n";

	$prepend_file      = "$working_file"."-prepend";
	$consolidated_file = "$working_file"."-cons";
	$translated_file   = "$working_file"."-trans";


	/*
	** Set up the injector command.
	** Write the upload attributes line to a temp file,
	** cat that temp file and the uploaded file through the translator,
	** pipe those results through the injector,
	** clean up the intermediate files
	*/

	// Break out piped operation into smaller chunks so
	// any processing errors can be tracked more effectively

	#
	# CREATE THE PREPEND FILE WITH UPLOAD INFORMATION
	#

//	$cmd = "echo '$upload_attrs' > $prepend_file";
//print "$cmd<br>";
//	$return_str = system($cmd, $return_code);
    $return_code = file_put_contents($prepend_file, $upload_attrs);

	if(!$return_code) {

		$smarty->assign('err_msg', "Server error: prepend operation, code '$return_code' - please retry upload");
		$smarty->display('finding_upload_status.tpl');

		return;

	}


	#
	# PREPEND THE UPLOAD INFORMATION
	#

//	$cmd = "cat $prepend_file $working_file > $consolidated_file";
//print "$cmd<br>";
//	$return_str = system($cmd, $return_code);
    $return_code = file_put_contents($consolidated_file, file_get_contents($prepend_file) . file_get_contents($working_file));

	if(!$return_code) {

		$smarty->assign('err_msg', "Server error: concatenation operation, code '$return_code' - please retry upload");
		$smarty->display('finding_upload_status.tpl');

		return;

	}


	#
	# TRANSLATE THE FINDINGS
	#

//	$cmd = "rm $working_file";
//print "$cmd<br>";
//	system($cmd, $return_code);
    @unlink($working_file);

	$cmd = "perl ".OVMS_INJECT_PATH."/plugins/$plugin_module/$plugin_module.pl < $consolidated_file > $translated_file";
//print "$cmd<br>";
	$return_str = system($cmd, $return_code);

	if($return_code != 0) {

		$smarty->assign('err_msg', "Server error: translation operation, code '$return_code' - please verify format");
		$smarty->display('finding_upload_status.tpl');

		return;

	}


	#
	# INJECT THE FINDINGS
	#

	$cmd = "perl $OVMS_ROOT/inject/inject.pl < $translated_file";
//print "$cmd<br>";
	$return_str = system($cmd, $return_code);

	if ($return_code != 0) {

		$smarty->assign('err_msg', "Server error: injection operation, code '$return_code' - please retry upload");
		$smarty->display('finding_upload_status.tpl');

		return;

	}

//	$cmd = "rm $consolidated_file; rm $prepend_file";
//print "$cmd<br>";
//	$return_str = system($cmd, $return_code);
    @unlink($consolidated_file);
    @unlink($prepend_file);

	$smarty->assign('status_msg', 'Injection complete.');
	$smarty->display('finding_upload_status.tpl');

} // if submitted

// no submission, display the upload form
else {

	/*
	** Load plugin list
	*/


	$sql = 'SELECT plugin_id, plugin_name, plugin_nickname FROM ' . TN_PLUGINS . ' ORDER BY plugin_nickname asc';
	$plugins = get_list($db, $sql);
	$smarty->assign('plugins', $plugins);

	/*
	** Load sources list
	*/

	$sql = 'SELECT source_id, source_name, source_nickname FROM ' . TN_FINDING_SOURCES . ' ORDER BY source_nickname asc';
	$sources = get_list($db, $sql);
	$smarty->assign('finding_sources', $sources);

	/*
	** Load system list
	*/

	$sql = 'SELECT system_id, system_name, system_nickname FROM ' . TN_SYSTEMS . ' ORDER BY system_nickname asc';
	$systems = get_list($db, $sql);
	$smarty->assign('systems', $systems);

	/*
	** Load network list
	*/

	$sql = 'SELECT network_id, network_name, network_nickname FROM ' . TN_NETWORKS . ' ORDER BY network_nickname asc';
	$networks = get_list($db, $sql);
	$smarty->assign('networks', $networks);

	/*
	** Display the template
	*/

	$smarty->display('finding_upload.tpl');

} // else


/*
** Execute sql statement to get list of results for caller
*/
function get_list($dbConn, $sql) {

	$result  = $dbConn->sql_query($sql) or die("Query failed: " .$sql."<br>". $dbConn->sql_error());
	return $dbConn->sql_fetchrowset($result);

} // get_list()

/*
** Quickly check input file - is the correct plugin being requested?
*/
function is_valid_data($data_file_name, $plugin_module) {

	// false until proven true
	$status = false;
	$result = '';
    $regexp = '';
	// 
	switch ($plugin_module) {

	case 'blscr':

		// Check for existence of Management, Operational or Technical records
//		$result = shell_exec("grep -e '^Management\|^Operational\|^Technical' $data_file_name");
        $regexp = "/^Management\|^Operational\|^Technical/";
		break;

	case 'nvd':

		// Check for nvd_xml_version declaration
//		$result = shell_exec("grep -e 'nvd_xml_version' $data_file_name");
        $regexp = "/nvd_xml_version/";
		break;
 
	case 'nessus':

		// Check for nessus stylesheet declaration
//		$result = shell_exec("grep -e 'xml-stylesheet.*nessus.xsl' $data_file_name");
        $regexp = "/xml-stylesheet.*nessus.xsl/";
		break;

	case 'appdetective':

		// Check for root_header declaration
//		$result = shell_exec("grep -e 'root_header' $data_file_name");
        $regexp = "/root_header/";
		break;

	case 'shadowscan':

		// Check for ShadowSecurityScannerXML declaration
//		$result = shell_exec("grep -e 'ShadowSecurityScannerXML' $data_file_name");
        $regexp = "/ShadowSecurityScannerXML/";
		break;

	case 'finding':

		//
//		$result = shell_exec("grep -e 'MANUAL FINDINGS SPREADSHEET' $data_file_name");
        $regexp = "/MANUAL FINDINGS SPREADSHEET/";
		break;

	case 'inventory':

		//
//		$result = shell_exec("grep -e 'Inventory Upload Spreadsheet\t' $data_file_name");
        $regexp = "/Inventory Upload Spreadsheet\t/";
		break;

	case 'product':

		//
//		$result = "true";
		$result = 1;
		break;

	} // switch plugin_module

    $result = preg_match($regexp, file_get_contents($data_file_name));
	// return the results based on length of the result string
	if ($result > 0) { return (true); } else { return (false); }

} // is_valid_data()



?>
