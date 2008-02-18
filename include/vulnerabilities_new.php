<?PHP
// no-cache — forces caches to submit the request to the origin server for validation before releasing a cached copy, every time. This is useful to assure that authentication is respected.
// must-revalidate — tells caches that they must obey any freshness information you give them about a representation. By specifying this header, you’re telling the cache that you want it to strictly follow your rules.
header("Cache-Control: no-cache, must-revalidate"); 

require_once("config.php");
require_once("smarty.inc.php");
require_once("dblink.php");
require_once("finding.class.php");
require_once("findingDBManager.php");
// User class which is required by all pages which need to validate authentication and interact with variables of a user (Functions: login, getloginstatus, getusername, getuserid, getpassword, checkactive, etc)
require_once("user.class.php");
// Functions required by all front-end pages gathered in one place for ease of maintenance. (verify_login, sets global page title, insufficient priveleges error, and get_page_datetime)
require_once("page_utils.php");

// set the page name
$smarty->assign('pageName', 'New Vulnerability');

// session_start() creates a session or resumes the current one based on the current session id that's being passed via a request, such as GET, POST, or a cookie.
// If you want to use a named session, you must call session_name() before calling session_start().
session_start();

// creates a new user object from the user class
$user = new User($db);

// validates that the user is logged in properly, if not redirects to the login page.
verify_login($user, $smarty);

$role_id =  $user.role_id;

$smarty->assign("username", $user->getUsername());

$view_right	= $user->checkRightByFunction("vulnerability", "view");
$edit_right = $user->checkRightByFunction("vulnerability", "edit");
$add_right  = $user->checkRightByFunction("vulnerability", "add");
$del_right  = $user->checkRightByFunction("vulnerability", "delete");

// let's template know how to display the page
$smarty->assign('view_right', $view_right);
$smarty->assign('edit_right', $edit_right);
$smarty->assign('add_right', $add_right);
$smarty->assign('del_right', $del_right);

if($add_right) 
{
	$smarty->assign('now', get_page_datetime());

	$para = ' limit 5';
	if (($_POST["p_keyword"] !='' )	&& ($_POST[submit] == 'Search') )
	{
		$para = " where prod_meta  LIKE '%". $_POST[p_keyword]. "%' ";	 
	}
	else if ($_POST[submit] == 'All Products') 
	{
		$para = "";	 
	}

	if ($_POST[submit] == 'Submit') 
	{
		$return_results = Add_New_Vul() ;
		
		if ( $return_results == 0 )
			$smarty->assign('return_results', "Create New Vulnerability Failed");	
		else
			header("Location: vulnerabilities_detail.php?vn=$return_results");		
	}
	
	$p_list = Get_Product_List($para);	

	$smarty->assign('p_list', $p_list);
	$smarty->assign('para', $_POST["p_keyword"]);	
}
	$smarty->display('vulnerabilities_new.tpl');


function Add_New_Vul() //$para
{
	$current_date = date("Y-m-d");   
	
	$sql = "insert into VULNERABILITIES(   
							vuln_type,
							vuln_desc_primary , 
							vuln_desc_secondary ,
							vuln_date_discovered ,
							vuln_date_modified ,
							vuln_date_published ,
							vuln_severity ,
							vuln_loss_availability ,
							vuln_loss_confidentiality ,
							vuln_loss_integrity ,
							vuln_loss_security_admin ,
							vuln_loss_security_user ,
							vuln_loss_security_other ,
							vuln_type_access ,
							vuln_type_input ,
							vuln_type_input_bound ,
							vuln_type_input_buffer ,
							vuln_type_design ,
							vuln_type_exception ,
							vuln_type_environment ,
							vuln_type_config ,
							vuln_type_race ,
							vuln_type_other ,
							vuln_range_local ,
							vuln_range_remote ,
							vuln_range_user		
							)  values (					
							'MAN' ,  '".
							$_POST[vuln_desc_primary] . "' ,  '" . 
							$_POST[vuln_desc_secondary] . "' ,  '" . 
							$current_date . "' ,  '" . 
							$current_date . "' ,  '" . 
							$current_date . "' ,   '" . 
							$_POST[vuln_severity] . "' ,  '" .  
							$_POST[vuln_loss_availability] . "' ,  '" .  
							$_POST[vuln_loss_confidentiality] . "' ,  '" . 
							$_POST[vuln_loss_integrity]  . "' ,  '" . 
							$_POST[vuln_loss_security_admin] . "' ,  '" . 
							$_POST[vuln_loss_security_user] . "' ,  '" . 
							$_POST[vuln_loss_security_other] . "' ,  '" . 
							$_POST[vuln_type_access] . "' ,  '" . 
							$_POST[vuln_type_input] . "' ,  '" .  
							$_POST[vuln_type_input_bound] . "' ,  '" . 
							$_POST[vuln_type_input_buffer] . "' ,  '" . 
							$_POST[vuln_type_design] . "' ,  '" . 
							$_POST[vuln_type_exception] . "' ,  '" . 
							$_POST[vuln_type_environment] . "' ,  '" .  
							$_POST[vuln_type_config] . "' ,  '" . 
							$_POST[vuln_type_race]  . "' ,  '" . 
							$_POST[vuln_type_other] . "' ,  '" . 
							$_POST[vuln_range_local] . "' ,  '" . 
							$_POST[vuln_range_remote] . "' ,  '" . 
							$_POST[vuln_range_user] . "'   ) ";
	
	
	$result  = mysql_query($sql) or die("Query failed: " . mysql_error());
	$data = null;

	if($result) 
	{		
		//$correct_msg = "<p align=center><font color=red>You have created a new Vulnerabaility successfully.</font></p>" ;

		// add product into the table
		$lastest_vul_sql = "select max(vuln_seq) from " . TN_VULNERABILITIES ;
		$lastest_vul_result = mysql_query($lastest_vul_sql) or die("Query failed: " . mysql_error());
		$lastest_vul_value = mysql_fetch_row($lastest_vul_result);
		$lastest_vul_value[0];

		return $lastest_vul_value[0];
	}
	else
	{		
		//	$correct_msg = "<p align=center><font color=red>Something is wrong.</font></p>" ;
		return 0 ;
	}
}

function Get_Product_List($para) //$para
{
	$sql = "select  prod_vendor, prod_name, prod_version, prod_id from " . TN_PRODUCTS . " $para ";
	$result  = mysql_query($sql) or die("Query failed: " . mysql_error());
	$data = null;

	if($result) 
	{		
			while($row = mysql_fetch_array($result)) 
			{
				$data[] = array('prod_name'=>$row[1], 
								'prod_vendor'=>$row[0], 
								'prod_version'=>$row[2],
								'prod_id'=>$row[3]
								);
		 	}
	}

	return $data;
}

?>
