<?PHP
// no-cache — forces caches to submit the request to the origin server for validation before releasing a cached copy, every time. This is useful to assure that authentication is respected.
// must-revalidate — tells caches that they must obey any freshness information you give them about a representation. By specifying this header, you’re telling the cache that you want it to strictly follow your rules.
header("Cache-Control: no-cache, must-revalidate");

// required for all pages, after user login is verified function displayloginfor checks all user security functions, gets the users first/last name and customer log as well as loads ovms.ini.php
require_once("config.php");
// required for all pages, sets smarty directory locations for cache, templates, etc.
require_once("smarty.inc.php");
require_once("dblink.php");
require_once("finding.class.php");
require_once("findingDBManager.php");
// User class which is required by all pages which need to validate authentication and interact with variables of a user (Functions: login, getloginstatus, getusername, getuserid, getpassword, checkactive, etc)
require_once("user.class.php");
// Functions required by all front-end pages gathered in one place for ease of maintenance. (verify_login, sets global page title, insufficient priveleges error, and get_page_datetime)
require_once("page_utils.php");

// set the page name
$smarty->assign("pageName","Edit Vulnerability");

// session_start() creates a session or resumes the current one based on the current session id that's being passed via a request, such as GET, POST, or a cookie.
// If you want to use a named session, you must call session_name() before calling session_start().
session_start();

// creates a new user object from the user class
$user = new User($db);

// validates that the user is logged in properly, if not redirects to the login page.
verify_login($user, $smarty);

$view_right	= $user->checkRightByFunction("vulnerability", "view");
$edit_right = $user->checkRightByFunction("vulnerability", "edit");
$add_right  = $user->checkRightByFunction("vulnerability", "add");
$del_right  = $user->checkRightByFunction("vulnerability", "delete");

// let's template know how to display the page
$smarty->assign('view_right', $view_right);
$smarty->assign('edit_right', $edit_right);
$smarty->assign('add_right', $add_right);
$smarty->assign('del_right', $del_right);
/**************User Right*****************/

//	echo "view_right : ". $view_right . " edit_right : ". $edit_right. " add_right : ". $add_right . " del_right : ". $del_right ;


/**************Main Area*****************/
if($view_right || $del_right || $edit_right) 
{

	if ($_POST[submit] == 'Update Vulnerability') 
	{
		//echo "1" ;
		$update_msg = update_Vul($_POST['vn'] ) ; 
		$smarty->assign('update_msg', $update_msg);
	}
	//add products	
	else if ($_POST[submit] == 'Add products') 
	{
		//echo "2" ;
		$vuln_id = $_POST['vn'] ;
		$p_id = $_POST['product_id'];
		if ($p_id != NULL)
		{
			foreach ($p_id as $s) 
			{
				$product_sql = "replace into VULN_PRODUCTS values( $vuln_id  , 'MAN' , $s)";   //echo "$s<br />";
				$product_result  = mysql_query($product_sql) or die("Query failed: " . mysql_error());
			}
		}	
	}
//	else if ($_POST['remove_product'] )
	else if ( $_POST['remove_product'] )
	{
		$vuln_id = $_POST['vn'] ;
		
		$p_id = $_POST['remove_product'];
		
		$remove_sql = "delete  from " . TN_VULN_PRODUCTS . " where prod_id=$p_id and vuln_seq=$vuln_id ";   //echo "$s<br />";
		$remove_result  = mysql_query($remove_sql) or die("Query failed: " . mysql_error());
	}
	
	$smarty->assign('now', get_page_datetime());

	$search_para = $_POST[vn];
	
	$v_table = Get_Vul_Detail($search_para);	
	$smarty->assign('v_table', $v_table);

	$v_product = Get_Product_Detail($search_para);	
	$smarty->assign('v_product', $v_product);
	


	//$k_para =  ' limit 20';

	$p_page = $_POST[p_page] ;
	if (( $p_page == NULL ) || ($_POST[submit] == 'Search') )
		$p_page = 1 ;

	if ( $_POST[submit] == 'Next Page')
		$p_page ++ ;	
	else if ( $_POST[submit] == 'Prev Page')
		$p_page -- ;	

	
	if ($_POST["p_keyword"] !='' )	
		$k_para = " where prod_meta  LIKE '%". $_POST[p_keyword]. "%'  ";	 

	$smarty->assign('p_page', $p_page);
		

	
	
	$smarty->assign('p_keyword', $_POST["p_keyword"]);		

	$p_list = Get_Product_List($k_para, $p_page);
	
	$p_amount = Get_Product_Page_Amount($k_para);
	
	if ( $p_page == 1 )
	{
		$smarty->assign('prev_page_disabled', 'disabled');
		//$_SESSION['prev_page_disabled'] = 'disabled';
	}
	
	if ( $p_page == $p_amount )
	{
		$smarty->assign('next_page_disabled', 'disabled');
		//$_SESSION['next_page_disabled'] = 'disabled';
	}
		
	$smarty->assign('p_list', $p_list);
	$smarty->assign('p_amount', $p_amount);

	$vp_list = Get_Vuln_Prod_List($current_vuln_id);		
	if ($vp_list != 0 )
		$smarty->assign('vp_list', $vp_list);

	
	
//	$smarty->assign("pass_search_para", stripslashes($_POST[pass_search_para] ));		
//	$smarty->assign("pass_page_no", stripslashes($_POST[pass_page_no]));		

}

$smarty->display('vulnerabilities_edit.tpl');


function Get_Product_List($para, $p_n) //$para
{
	$from_page = ($p_n - 1) * 20 ;

	$sql = "select  prod_vendor, prod_name, prod_version, prod_id from " . TN_PRODUCTS . "  $para  limit $from_page , 20 ";
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

function Get_Product_Page_Amount($para) //$para
{
	$sql = "select prod_id from " . TN_PRODUCTS . " $para ";
	$result  = mysql_query($sql) or die("Query failed: " . mysql_error());
	$no_rows = mysql_num_rows($result) ;
	
	$total_pages = ceil( $no_rows / 20 );

	return $total_pages;
}


function Get_Product_Detail($para) 
{
	$sql = "select p.prod_vendor, p.prod_name, p.prod_version, p.prod_id from " . TN_PRODUCTS . " as p, VULN_PRODUCTS as vp, VULNERABILITIES as v where v.vuln_seq=$para and v.vuln_seq=vp.vuln_seq and vp.prod_id=p.prod_id";
	$result  = mysql_query($sql) or die("Query failed: " . mysql_error());
	$data = null;

	if($result) 
	{		
			while($row = mysql_fetch_array($result)) 
			{
				$data[] = array('prod_name'=>$row[1], 
								'prod_vendor'=>$row[0], 
								'prod_id'=>$row[3], 
								'prod_version'=>$row[2]);
		 	}
	}

	return $data;
}







function Get_Vul_Detail($para) 
{
	$sql = "select * from " . TN_VULNERABILITIES . " where vuln_seq = $para";
	$result  = mysql_query($sql) or die("Query failed: " . mysql_error());
	$data = null;

	if($result) 
	{
			$row = mysql_fetch_assoc($result)  ;
		
			if ($row[vuln_loss_availability] == 1)
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
							'vuln_severity' =>  $row['vuln_severity'], 
							
							'vuln_desc_primary'=>$row['vuln_desc_primary'], 
							'vuln_desc_secondary'=>$row['vuln_desc_secondary'], 
							
							'vuln_loss_availability'=>$vuln_loss_availability,
							'vuln_loss_confidentiality'=>$vuln_loss_confidentiality, 														
							'vuln_loss_integrity'=>$vuln_loss_integrity, 							
							'vuln_loss_security_admin'=>$vuln_loss_security_admin, 														
							'vuln_loss_security_user'=>$vuln_loss_security_user, 							
							'vuln_loss_security_other'=>$vuln_loss_security_other, 														
							
							'vuln_type_access'=>$vuln_type_access, 							
							'vuln_type_input'=>$vuln_type_input, 														
							'vuln_type_input_bound'=>$vuln_type_input_bound, 							
							'vuln_type_input_buffer'=>$vuln_type_input_buffer, 														
							'vuln_type_design'=>$vuln_type_design, 							
							'vuln_type_exception'=>$vuln_type_exception, 														
							'vuln_type_environment'=>$vuln_type_environment, 	
							'vuln_type_config'=>$vuln_type_config, 
							'vuln_type_race'=>$vuln_type_race, 
							'vuln_type_other'=>$vuln_type_other, 
							
							'vuln_range_local'=>$vuln_range_local, 
							'vuln_range_remote'=>$vuln_range_remote, 
							'vuln_range_user'=>$vuln_range_user );
		
	}

	return $data;
}




function Get_Vuln_Prod_List($vid) //$para
{
	if ($vid != '')	
	{
		$sql = "select  p.prod_vendor, p.prod_name, p.prod_version, p.prod_id from " . TN_PRODUCTS . " as p, VULN_PRODUCTS as v where v.vuln_seq = $vid and v.prod_id = p.prod_id";
	
	
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


function update_Vul($vid) //$para
{
 	$sql = "update VULNERABILITIES set    
							vuln_desc_primary = '". $_POST[vuln_desc_primary] . "',  
							vuln_desc_secondary = '". $_POST[vuln_desc_secondary]  . "',  
							vuln_date_modified = '". $current_date . "',
							vuln_date_published = '". $current_date . "',
							vuln_severity = '". $_POST[vuln_severity] . "',
							vuln_loss_availability = '". $_POST[vuln_loss_availability]  . "',
							vuln_loss_confidentiality = '". $_POST[vuln_loss_confidentiality] . "',
							vuln_loss_integrity = '". $_POST[vuln_loss_integrity] . "',
							vuln_loss_security_admin = '". $_POST[vuln_loss_security_admin] . "',
							vuln_loss_security_user = '". $_POST[vuln_loss_security_user] . "',
							vuln_loss_security_other = '". $_POST[vuln_loss_security_other]  . "',
							vuln_type_access = '". $_POST[vuln_type_access]  . "',
							vuln_type_input = '". $_POST[vuln_type_input] . "',
							vuln_type_input_bound = '". $_POST[vuln_type_input_bound] . "',
							vuln_type_input_buffer = '". $_POST[vuln_type_input_buffer]  . "',
							vuln_type_design = '". $_POST[vuln_type_design]  . "',
							vuln_type_exception = '". $_POST[vuln_type_exception] . "',
							vuln_type_environment = '". $_POST[vuln_type_environment] . "',
							vuln_type_config = '". $_POST[vuln_type_config]  . "',
							vuln_type_race = '". $_POST[vuln_type_race]  . "',
							vuln_type_other = '". $_POST[vuln_type_other]  . "',
							vuln_range_local = '". $_POST[vuln_range_local] . "',
							vuln_range_remote = '". $_POST[vuln_range_remote]  . "',
							vuln_range_user	= '". $_POST[vuln_range_user] . "'	
							where vuln_seq = $vid	" ;		

	
	$result  = mysql_query($sql) or die("Query failed: " . mysql_error());
	$data = null;

	if($result) 
	{		
		return "<div align=center><font color=red> Updated vulnerability successfully!</font></div>" ;
	}
	else
	{		
		return "<div align=center><font color=red>Updated vulnerability failed!</font></div>" ;
	}
}

?>
