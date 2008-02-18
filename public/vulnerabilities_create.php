<?PHP
// no-cache — forces caches to submit the request to the origin server for validation before releasing a cached copy, every time. This is useful to assure that authentication is respected.
// must-revalidate — tells caches that they must obey any freshness information you give them about a representation. By specifying this header, you’re telling the cache that you want it to strictly follow your rules.
header("Cache-Control: no-cache, must-revalidate");

/*
** The addslashes() in dblink.php messes with the product_id[]
** brackets, so capture this right away. For now. Mar. 31 2006
*/
$p_id = isset($_POST['product_id'])?$_POST['product_id']:NULL;

require_once("config.php");
require_once("smarty.inc.php");
require_once("dblink.php");
require_once("finding.class.php");
require_once("findingDBManager.php");
require_once("user.class.php");
require_once("page_utils.php");

// set the screen name used for security functions
$screen_name = "vulnerability";

// set the page name
$smarty->assign("pageName","New Vulnerablities");

// session_start() creates a session or resumes the current one based on the current session id that's being passed via a request, such as GET, POST, or a cookie.
// If you want to use a named session, you must call session_name() before calling session_start().
session_start();

// creates a new user object from the user class
$user = new User($db);

// validates that the user is logged in properly, if not redirects to the login page.
verify_login($user, $smarty);

$view_right	= $user->checkRightByFunction($screen_name, "view");
$edit_right = $user->checkRightByFunction($screen_name, "edit");
$add_right  = $user->checkRightByFunction($screen_name, "add");
$del_right  = $user->checkRightByFunction($screen_name, "delete");

// let's template know how to display the page
$smarty->assign('view_right', $view_right);
$smarty->assign('edit_right', $edit_right);
$smarty->assign('add_right', $add_right);
$smarty->assign('del_right', $del_right);
/**************User Right*****************/

//	echo "view_right : ". $view_right . " edit_right : ". $edit_right. " add_right : ". $add_right . " del_right : ". $del_right ;

/**************Main Area*****************/
if( $add_right )
{
 	Keep_All_Paras();


	$smarty->assign('now', get_page_datetime());

 	if (isset($_POST['submit_val']) && ($_POST['submit_val'] == 'Add Products'))
	{
/*
		$p_id = $_POST['product_id'];
** This is being retrieved prior to dblink.php
*/
		if ($p_id != NULL)
		{
			foreach ($p_id as $s)
			{
				$_SESSION['selected_product'][$s] = $s ;
			}
		}
	} else if (isset($_POST['submit_val']) && ($_POST['submit_val'] == 'Create New Vulnerability'))
	{
		//echo "<br>p_lists <br>" ;
		if ($_SESSION['temp_prod_lists'] == NULL )
		{
			//$smarty->assign('create_success', '<p align=center><font color=red>Create New Vulnerability failed! You have to select at least one effected product!</font></p>'  );
			$smarty->assign('create_success', 'Unable to create new vulnerability. Please select at least one affected product.'  );
		}
		else
		{
			Create_New_Vulnerability($_SESSION['temp_prod_lists']);
			unset($_SESSION['temp_prod_lists']);
		}
	}  else if (isset($_POST['remove_product'])) {
		//echo "<br>del product: " . $_POST[''remove_product''] ."<br> " ;
		$temp_pid = $_POST['remove_product'] ;
		//print_r($_POST) ;
		$_SESSION['selected_product'][$temp_pid] = NULL ;
    }


	//global $prod_lists ;

	if (isset($_SESSION['selected_product']))
	{
		$_SESSION['selected_product'] = array_unique($_SESSION['selected_product']);
		//echo "<br>Remove duplicate varible from ".TN_session <br>" ;
		//print_r($_SESSION['selected_product']) ;

		$final_array = null ;
		foreach ($_SESSION['selected_product'] as $my_value)
		{
			if ( $my_value != NULL )
		  		$final_array[''] = $my_value ;
		}

		if ($final_array != NULL )
			$prod_lists = Get_Selected_Product($final_array) ;
	}


	$_SESSION['temp_prod_lists'] = isset($prod_lists)?$prod_lists:NULL;
	if ( isset($_POST['submit_val']) && ($_POST['submit_val'] == 'Create New Vulnerability')&& ($_SESSION['temp_prod_lists'] != NULL ))
	{
		unset($_SESSION['temp_prod_lists']);
		unset($_SESSION['selected_product']);
		//$smarty->assign('create_success', '<p align=center><font color=red>Successfully created new vulnerability</font></p>'  );
		$smarty->assign('create_success', 'Successfully created new vulnerability'  );
	}

	$smarty->assign('selected_product', $_SESSION['temp_prod_lists']  );





	//$para = ' limit 5';
	if (isset($_POST["p_keyword"])	&& ($_POST['submit_val'] == 'Search') )
	{
		$para = " where prod_meta  LIKE '%". $_POST[p_keyword]. "%' ";
	}

	$p_page = isset($_POST['p_page'])?$_POST['p_page']:NULL;
	if (( $p_page == NULL ) || ($_POST['submit_val'] == 'Search') )
		$p_page = 1 ;

    if (isset($_POST['submit_val'])) {
    	if ( $_POST['submit_val'] == 'Next Page')
    		$p_page ++ ;
    	else if ( $_POST['submit_val'] == 'Prev Page')
    		$p_page -- ;
    }

	if (isset($_POST["p_keyword"]))
		$k_para = " where prod_meta  LIKE '%". $_POST['p_keyword']. "%'  ";

	$smarty->assign('p_page', $p_page);

	$smarty->assign('p_keyword', isset($_POST["p_keyword"])?$_POST["p_keyword"]:'');

	$p_list = Get_Product_List(isset($k_para)?$k_para:NULL, $p_page);

	$p_amount = Get_Product_Page_Amount(isset($k_para)?$k_para:NULL);

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



	$smarty->assign('para', $_POST["p_keyword"]);

	$smarty->assign("vuln_desc_primary", trim($vuln_desc_primary));
	$smarty->assign("vuln_desc_secondary", trim($vuln_desc_secondary));
	$smarty->assign("vuln_loss_confidentiality_checked", $vuln_loss_confidentiality_checked);
	$smarty->assign("vuln_loss_security_admin_checked", $vuln_loss_security_admin_checked);
	$smarty->assign("vuln_loss_availability_checked", $vuln_loss_availability_checked);
	$smarty->assign("vuln_loss_security_user_checked", $vuln_loss_security_user_checked);
	$smarty->assign("vuln_loss_integrity_checked", $vuln_loss_integrity_checked);
	$smarty->assign("vuln_loss_security_other_checked", $vuln_loss_security_other_checked);
	$smarty->assign("vuln_range_local_checked", $vuln_range_local_checked);
	$smarty->assign("vuln_range_remote_checked", $vuln_range_remote_checked);
	$smarty->assign("vuln_range_user_checked", $vuln_range_user_checked);
	$smarty->assign("vuln_type_access_checked", $vuln_type_access_checked);
	$smarty->assign("vuln_type_input_buffer_checked", $vuln_type_input_buffer_checked);
	$smarty->assign("vuln_type_exception_checked", $vuln_type_exception_checked);
	$smarty->assign("vuln_type_other_checked", $vuln_type_other_checked);
	$smarty->assign("vuln_type_input_checked", $vuln_type_input_checked);
	$smarty->assign("vuln_type_race_checked", $vuln_type_race_checked);
	$smarty->assign("vuln_type_environment_checked", $vuln_type_environment_checked);
	$smarty->assign("vuln_type_input_bound_checked", $vuln_type_input_bound_checked);
	$smarty->assign("vuln_type_design_checked", $vuln_type_design_checked);
	$smarty->assign("vuln_type_config_checked", $vuln_type_config_checked);
	$smarty->assign("vuln_severity", $vuln_severity);
	$smarty->assign("p_keyword", $p_keyword);
	$smarty->assign("p_page", $p_page);


}

$smarty->display('vulnerabilities_create.tpl');

function Create_New_Vulnerability($p_lists) //$para
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
							$_POST['vuln_desc_primary'] . "' ,  '" .
							$_POST['vuln_desc_secondary'] . "' ,  '" .
							$current_date . "' ,  '" .
							$current_date . "' ,  '" .
							$current_date . "' ,   '" .
							$_POST['vuln_severity'] . "' ,  '" .
							$_POST['vuln_loss_availability'] . "' ,  '" .
							$_POST['vuln_loss_confidentiality'] . "' ,  '" .
							$_POST['vuln_loss_integrity']  . "' ,  '" .
							$_POST['vuln_loss_security_admin'] . "' ,  '" .
							$_POST['vuln_loss_security_user'] . "' ,  '" .
							$_POST['vuln_loss_security_other'] . "' ,  '" .
							$_POST['vuln_type_access'] . "' ,  '" .
							$_POST['vuln_type_input'] . "' ,  '" .
							$_POST['vuln_type_input_bound'] . "' ,  '" .
							$_POST['vuln_type_input_buffer'] . "' ,  '" .
							$_POST['vuln_type_design'] . "' ,  '" .
							$_POST['vuln_type_exception'] . "' ,  '" .
							$_POST['vuln_type_environment'] . "' ,  '" .
							$_POST['vuln_type_config'] . "' ,  '" .
							$_POST['vuln_type_race']  . "' ,  '" .
							$_POST['vuln_type_other'] . "' ,  '" .
							$_POST['vuln_range_local'] . "' ,  '" .
							$_POST['vuln_range_remote'] . "' ,  '" .
							$_POST['vuln_range_user'] . "'   ) ";


	$result  = mysql_query($sql) or die("Query failed: " . mysql_error());

	if($result)
	{
		//$correct_msg = "<p align=center><font color=red>You have created a new Vulnerabaility successfully.</font></p>" ;

		// add product into the table
		$latest_vul_sql = "select max(vuln_seq) from " . TN_VULNERABILITIES ;
		$latest_vul_result = mysql_query($latest_vul_sql) or die("Query failed: " . mysql_error());
		$latest_vul_value = mysql_fetch_row($latest_vul_result);
		$latest_vul_ID = $latest_vul_value[0];
		//return $latest_vul_value[0];

		// add effected products
		//global $prod_lists ;
		//print_r($p_lists);
		if ( $p_lists != NULL )
		{
			//echo "<br> add prodcuts<br>";
			foreach ($p_lists as $s)
			{
				//print_r($s);
				$temp_product_id = $s[prod_id] ;
				//echo "temp_product_id : $temp_product_id <br>" ;
				$product_sql = "replace into VULN_PRODUCTS values( $latest_vul_ID  , 'MAN' , $temp_product_id)";   //echo "$s<br />";
				$product_result  = mysql_query($product_sql) or die("Query failed: " . mysql_error());
			}



		}
	}




}











function Get_Selected_Product($pid_array) //$para
{
	$data = null;

	foreach ($pid_array as $my_value)
	{
		$sql = "select  prod_vendor, prod_name, prod_version, prod_id from " . TN_PRODUCTS . "  where prod_id=$my_value ";
		$result  = mysql_query($sql) or die("Query failed: " . mysql_error());


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
	}
	//print_r($data);
	return $data;
}






function Keep_All_Paras() //$para
{
    error_reporting(~E_NOTICE);
    
	global $vuln_desc_primary ;
	$vuln_desc_primary = $_POST['vuln_desc_primary'] ;

	global $vuln_desc_secondary ;
	$vuln_desc_secondary = $_POST['vuln_desc_secondary'] ;

	global $vuln_loss_confidentiality ;
	$vuln_loss_confidentiality = $_POST['vuln_loss_confidentiality'] ;
	if ( $vuln_loss_confidentiality )
	{
		global $vuln_loss_confidentiality_checked ;
		$vuln_loss_confidentiality_checked = 'checked' ;
	}


	global $vuln_loss_security_admin ;
	$vuln_loss_security_admin = $_POST['vuln_loss_security_admin'] ;
	if ( $vuln_loss_security_admin )
	{
		global $vuln_loss_security_admin_checked ;
		$vuln_loss_security_admin_checked = 'checked' ;
	}



	global $vuln_loss_availability ;
	$vuln_loss_availability = $_POST['vuln_loss_availability'] ;
	if ( $vuln_loss_availability )
	{
		global $vuln_loss_availability_checked ;
		$vuln_loss_availability_checked = 'checked' ;
	}


	global $vuln_loss_security_user ;
	$vuln_loss_security_user = $_POST['vuln_loss_security_user'] ;
	if ( $vuln_loss_security_user )
	{
		global $vuln_loss_security_user_checked ;
		$vuln_loss_security_user_checked = 'checked' ;
	}


	global $vuln_loss_integrity ;
	$vuln_loss_integrity = $_POST['vuln_loss_integrity'] ;
	if ( $vuln_loss_integrity )
	{
		global $vuln_loss_integrity_checked ;
		$vuln_loss_integrity_checked = 'checked' ;
	}


	global $vuln_loss_security_other ;
	$vuln_loss_security_other = $_POST['vuln_loss_security_other'] ;
	if ( $vuln_loss_security_other )
	{
		global $vuln_loss_security_other_checked ;
		$vuln_loss_security_other_checked = 'checked' ;
	}

	global $vuln_range_local ;
	$vuln_range_local = $_POST['vuln_range_local'] ;
	if ( $vuln_range_local )
	{
		global $vuln_range_local_checked ;
		$vuln_range_local_checked = 'checked' ;
	}


	global $vuln_range_remote ;
	$vuln_range_remote = $_POST['vuln_range_remote'] ;
	if ( $vuln_range_remote )
	{
		global $vuln_range_remote_checked ;
		$vuln_range_remote_checked = 'checked' ;
	}


	global $vuln_range_user ;
	$vuln_range_user = $_POST['vuln_range_user'] ;
	if ( $vuln_range_user )
	{
		global $vuln_range_user_checked ;
		$vuln_range_user_checked = 'checked' ;
	}


	global $vuln_type_access ;
	$vuln_type_access = $_POST['vuln_type_access'] ;
	if ( $vuln_type_access )
	{
		global $vuln_type_access_checked ;
		$vuln_type_access_checked = 'checked' ;
	}


	global $vuln_type_input_buffer ;
	$vuln_type_input_buffer = $_POST['vuln_type_input_buffer'] ;
	if ( $vuln_type_input_buffer )
	{
		global $vuln_type_input_buffer_checked ;
		$vuln_type_input_buffer_checked = 'checked' ;
	}


	global $vuln_type_exception ;
	$vuln_type_exception = $_POST['vuln_type_exception'] ;
	if ( $vuln_type_exception )
	{
		global $vuln_type_exception_checked ;
		$vuln_type_exception_checked = 'checked' ;
	}


	global $vuln_type_other ;
	$vuln_type_other = $_POST['vuln_type_other'] ;
	if ( $vuln_type_other )
	{
		global $vuln_type_other_checked ;
		$vuln_type_other_checked = 'checked' ;
	}


	global $vuln_type_input ;
	$vuln_type_input = $_POST['vuln_type_input'] ;
	if ( $vuln_type_input )
	{
		global $vuln_type_input_checked ;
		$vuln_type_input_checked = 'checked' ;
	}



	global $vuln_type_race ;
	$vuln_type_race = $_POST['vuln_type_race'] ;
	if ( $vuln_type_race )
	{
		global $vuln_type_race_checked ;
		$vuln_type_race_checked = 'checked' ;
	}

	global $vuln_type_environment ;
	$vuln_type_environment = $_POST['vuln_type_environment'] ;
	if ( $vuln_type_environment )
	{
		global $vuln_type_environment_checked ;
		$vuln_type_environment_checked = 'checked' ;
	}


	global $vuln_type_input_bound ;
	$vuln_type_input_bound = $_POST['vuln_type_input_bound'] ;
	if ( $vuln_type_input_bound )
	{
		global $vuln_type_input_bound_checked ;
		$vuln_type_input_bound_checked = 'checked' ;
	}


	global $vuln_type_design ;
	$vuln_type_design = $_POST['vuln_type_design'] ;
	if ( $vuln_type_design )
	{
		global $vuln_type_design_checked ;
		$vuln_type_design_checked = 'checked' ;
	}


	global $vuln_type_config ;
	$vuln_type_config = $_POST['vuln_type_config'] ;
	if ( $vuln_type_config )
	{
		global $vuln_type_config_checked ;
		$vuln_type_config_checked = 'checked' ;
	}


	global $vuln_severity ;
	$vuln_severity = $_POST['vuln_severity'] ;
	global $p_keyword ;
	$p_keyword = $_POST['p_keyword'] ;

	global $p_page;
	 $p_page = $_POST['p_page'] ;

}














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
		$latest_vul_sql = "select max(vuln_seq) from " . TN_VULNERABILITIES;
		$latest_vul_result = mysql_query($latest_vul_sql) or die("Query failed: " . mysql_error());
		$latest_vul_value = mysql_fetch_row($latest_vul_result);
		$latest_vul_value[0];

		return $latest_vul_value[0];
	}
	else
	{
		//	$correct_msg = "<p align=center><font color=red>Something is wrong.</font></p>" ;
		return 0 ;
	}
}

function Get_Product_List($para, $p_n) //$para
{
	$from_page = ($p_n - 1) * 20 ;

	$sql = "select  prod_vendor, prod_name, prod_version, prod_id from " . TN_PRODUCTS . " $para  limit $from_page , 20 ";
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


?>
