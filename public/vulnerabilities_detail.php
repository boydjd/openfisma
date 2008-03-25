<?PHP

// required for all pages, after user login is verified function displayloginfor checks all user security functions, gets the users first/last name and customer log as well as loads ovms.ini.php
require_once("config.php");
require_once("dblink.php");
require_once("finding.class.php");
require_once("findingDBManager.php");

// set the page name
$smarty->assign("pageName","Vulnerablity Detail");

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

if($view_right || $del_right || $edit_right) 
{

	$search_para = $_POST['vn'];
	
	$v_table = Get_Vul_Detail($search_para);	
	$smarty->assign('v_table', $v_table);

	$v_product = Get_Product_Detail($search_para);	
	$smarty->assign('v_product', $v_product);
		
}
	$smarty->display('vulnerabilities_detail.tpl');


//select p.prod_vendor, p.prod_name, p.prod_version from PRODUCTS as p, VULN_PRODUCTS as vp, VULNERABILITIES as v where v.vuln_seq=1 and v.vuln_seq=vp.vuln_seq and vp.prod_id=p.prod_id
function Get_Product_Detail($para) 
{
	$sql = "SELECT p.prod_vendor, p.prod_name, p.prod_version FROM " . TN_PRODUCTS . " AS p, " . TN_VULN_PRODUCTS . " AS vp, " . TN_VULNERABILITIES . " AS v WHERE v.vuln_seq=$para AND v.vuln_seq=vp.vuln_seq AND vp.prod_id=p.prod_id";
	$result  = mysql_query($sql) or die("Query failed: " . mysql_error());
	$data = null;

	if($result) 
	{		
			while($row = mysql_fetch_array($result)) 
			{
				$data[] = array('prod_name'=>$row[1], 
								'prod_vendor'=>$row[0], 
								'prod_version'=>$row[2]);
		 	}
	}

/*	if($result) 
	{		
			$row = mysql_fetch_row($result) ;
			{
				$data = array('prod_name'=>$row[1], 
   							  'prod_vendor'=>$row[0], 
							  'prod_version'=>$row[2]);
				
		 	}
	}
*/	//print_r($data) ;
	return $data;
}

function Get_Vul_Detail($para) 
{
	$sql = "SELECT * FROM " . TN_VULNERABILITIES . " WHERE vuln_seq = $para";
	$result  = mysql_query($sql) or die("Query failed: " . mysql_error());
	$data = null;

	if($result) 
	{
			$row = mysql_fetch_assoc($result)  ;
		
			if ($row['vuln_loss_availability'] == 1)
				$vuln_loss_availability = "checked" ;		
			if ($row['vuln_loss_confidentiality'] == 1)
				$vuln_loss_confidentiality = "checked" ;
			if ($row['vuln_loss_integrity'] == 1)
				$vuln_loss_integrity = "checked" ;
			if ($row['vuln_loss_security_admin'] == 1)
				$vuln_loss_security_admin = "checked" ;		
			if ($row['vuln_loss_security_user'] == 1)
				$vuln_loss_security_user = "checked" ;
			if ($row['vuln_loss_security_other'] == 1)
				$vuln_loss_security_other = "checked" ;
				
			if ($row['vuln_type_access'] == 1)
				$vuln_type_access = "checked" ;
			if ($row['vuln_type_input'] == 1)
				$vuln_type_input = "checked" ;
			if ($row['vuln_type_input_bound'] == 1)
				$vuln_type_input_bound = "checked" ;
			if ($row['vuln_type_input_buffer'] == 1)
				$vuln_type_input_buffer = "checked" ;	
			if ($row['vuln_type_design'] == 1)
				$vuln_type_design = "checked" ;
			if ($row['vuln_type_exception'] == 1)
				$vuln_type_exception = "checked" ;
			if ($row['vuln_type_environment'] == 1)
				$vuln_type_environment = "checked" ;
			if ($row['vuln_type_config'] == 1)
				$vuln_type_config = "checked" ;
			if ($row['vuln_type_race'] == 1)
				$vuln_type_race = "checked" ;
			if ($row['vuln_type_other'] == 1)
				$vuln_type_other = "checked" ;
				
			if ($row['vuln_range_local'] == 1)
				$vuln_range_local = "checked" ;
			if ($row['vuln_range_remote'] == 1)
				$vuln_range_remote = "checked" ;
			if ($row['vuln_range_user'] == 1)
				$vuln_range_user = "checked" ;
				
				
			$data = array('vuln_seq'=>$row['vuln_seq'], 
							'vuln_type'=>$row['vuln_type'], 

							'vuln_severity'=>$row['vuln_severity'], 
	
							'vuln_desc_primary'=>$row['vuln_desc_primary'], 
							'vuln_desc_secondary'=>$row['vuln_desc_secondary'], 
							
							'vuln_loss_availability'=>isset($vuln_loss_availability)?$vuln_loss_availability:'',
							'vuln_loss_confidentiality'=>isset($vuln_loss_confidentiality)?$vuln_loss_confidentiality:'', 														
							'vuln_loss_integrity'=>isset($vuln_loss_integrity)?$vuln_loss_integrity:'', 							
							'vuln_loss_security_admin'=>isset($vuln_loss_security_admin)?$vuln_loss_security_admin:'', 														
							'vuln_loss_security_user'=>isset($vuln_loss_security_user)?$vuln_loss_security_user:'', 							
							'vuln_loss_security_other'=>isset($vuln_loss_security_other)?$vuln_loss_security_other:'', 														
							
							'vuln_type_access'=>isset($vuln_type_access)?$vuln_type_access:'', 							
							'vuln_type_input'=>isset($vuln_type_input)?$vuln_type_input:'', 														
							'vuln_type_input_bound'=>isset($vuln_type_input_bound)?$vuln_type_input_bound:'', 							
							'vuln_type_input_buffer'=>isset($vuln_type_input_buffer)?$vuln_type_input_buffer:'', 														
							'vuln_type_design'=>isset($vuln_type_design)?$vuln_type_design:'', 							
							'vuln_type_exception'=>isset($vuln_type_exception)?$vuln_type_exception:'', 														
							'vuln_type_environment'=>isset($vuln_type_environment)?$vuln_type_environment:'', 	
							'vuln_type_config'=>isset($vuln_type_config)?$vuln_type_config:'', 
							'vuln_type_race'=>isset($vuln_type_race)?$vuln_type_race:'', 
							'vuln_type_other'=>isset($vuln_type_other)?$vuln_type_other:'', 
							
							'vuln_range_local'=>isset($vuln_range_local)?$vuln_range_local:'', 
							'vuln_range_remote'=>isset($vuln_range_remote)?$vuln_range_remote:'', 
							'vuln_range_user'=>isset($vuln_range_user)?$vuln_range_user:'' );
		
	}

	return $data;
}
?>
