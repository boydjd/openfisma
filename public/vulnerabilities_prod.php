<?PHP
// no-cache ? forces caches to submit the request to the origin server for validation before releasing a cached copy, every time. This is useful to assure that authentication is respected.
// must-revalidate ? tells caches that they must obey any freshness information you give them about a representation. By specifying this header, you’re telling the cache that you want it to strictly follow your rules.
header("Cache-Control: no-cache, must-revalidate");

// required for all pages, after user login is verified function displayloginfor checks all user security functions, gets the users first/last name and customer log as well as loads ovms.ini.php
require_once("config.php");
require_once("dblink.php");
require_once("finding.class.php");
require_once("findingDBManager.php");

// session_start() creates a session or resumes the current one based on the current session id that's being passed via a request, such as GET, POST, or a cookie.
// If you want to use a named session, you must call session_name() before calling session_start().
session_start();

// creates a new user object from the user class
$user = new User($db);

// validates that the user is logged in properly, if not redirects to the login page.
verify_login($user, $smarty);

$view_right	= $user->checkRightByFunction("vulnerability", "read");
$edit_right = $user->checkRightByFunction("vulnerability", "update");
$add_right  = $user->checkRightByFunction("vulnerability", "create");
$del_right  = $user->checkRightByFunction("vulnerability", "delete");

// let's template know how to display the page
$smarty->assign('view_right', $view_right);
$smarty->assign('edit_right', $edit_right);
$smarty->assign('add_right', $add_right);
$smarty->assign('del_right', $del_right);
/**************User Rigth*****************/

//	echo "view_right : ". $view_right . " edit_right : ". $edit_right. " add_right : ". $add_right . " del_right : ". $del_right ;


/**************Main Area*****************/
//if($view_right || $del_right || $edit_right) 
if($add_right) 
{

	//Add new vulnerability
	if ($_POST[submit] == 'Create new vulnerability') 
		$current_vuln_id = Add_New_Vul();
	else
		$current_vuln_id = $_POST[current_vuln_id];	

	if ( $current_vuln_id == '' )
		header("Location: vulnerabilities.php");		
	else
		$smarty->assign('current_vuln_id', $current_vuln_id);



	
	
	//add products	
	if ($_POST[submit] == 'Add products') 
	{
		$vuln_id = $_POST['current_vuln_id'] ;
		$p_id = $_POST['product_id'];
		if ($p_id != NULL)
		{
			foreach ($p_id as $s) 
			{
				$product_sql = "REPLACE INTO " . TN_VULN_PRODUCTS . " VALUES( $vuln_id  , 'MAN' , $s)";   //echo "$s<br />";
				$product_result  = mysql_query($product_sql) or die("Query failed: " . mysql_error());
			}
		}	
	}

	if ($_POST['remove_product'] )
	{
		$vuln_id = $_POST['current_vuln_id'] ;
		
		$p_id = $_POST['remove_product'];
		//print_r($p_id);
		$remove_sql = "DELETE  FROM " . TN_VULN_PRODUCTS . " WHERE prod_id=$p_id AND vuln_seq=$vuln_id";   //echo "$s<br />";
		$remove_result  = mysql_query($remove_sql) or die("Query failed: " . mysql_error());
	}

	
	//search products
	$para = ' LIMIT 20';
	if (($_POST["p_keyword"] !='' )	&& ($_POST[submit] == 'Search') )
		$para = " WHERE prod_meta  LIKE '%". $_POST[p_keyword]. "%' ";	 
	else if ($_POST[submit] == 'All Products') 
		$para = "";	 
	$smarty->assign('para', $_POST["p_keyword"]);		

	$p_list = Get_Product_List($para);	
	$smarty->assign('p_list', $p_list);
	
	$vp_list = Get_Vuln_Prod_List($current_vuln_id);		
	if ($vp_list != 0 )
		$smarty->assign('vp_list', $vp_list);

	
}
	$smarty->display('vulnerabilities_prod.tpl');


function Get_Product_List($para) //$para
{
	$sql = "SELECT  prod_vendor, prod_name, prod_version, prod_id FROM " . TN_PRODUCTS . "  $para ";
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

function Get_Vuln_Prod_List($vid) //$para
{
	if ($vid != '')	
	{
		$sql = "SELECT  p.prod_vendor, p.prod_name, p.prod_version, p.prod_id FROM " . TN_PRODUCTS . " as p, " . TN_VULN_PRODUCTS . " AS v WHERE v.vuln_seq = $vid AND v.prod_id = p.prod_id";
	
	
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
	else
		return 0;
}


function Add_New_Vul() //$para
{
	$current_date = date("Y-m-d");   
	
	$sql = "INSERT INTO " . TN_VULNERABILITIES . "(   
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
							)  VALUES (					
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
		$lastest_vul_sql = "SELECT MAX(vuln_seq) FROM " . TN_VULNERABILITIES ;
		$lastest_vul_result = mysql_query($lastest_vul_sql) or die("Query failed: " . mysql_error());
		$lastest_vul_value = mysql_fetch_row($lastest_vul_result);
		$lastest_vul_value[0];

		return $lastest_vul_value[0];
	}
	else
	{		
		return 0 ;
	}
}

?>
